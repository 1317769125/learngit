<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/25
 * Time: 17:58
 */

namespace Route\Modules\Cli\Tasks;

use Phalcon\Di;
use Phalcon\Exception;

class GetDataTask extends MainTask
{

    private static $timestamp;

    protected function initialize()
    {
        parent::initialize();
        self::$timestamp = $this->time;
    }

    public function beginAction($date)
    {
        if (!empty($date)) {
            self::$timestamp = strtotime($date[0]);
        }

        $this->pushData();
        while ($info = json_decode($this->redis->spop('flight_' . date('Y_m_d', self::$timestamp)), true)) {
            self::Data($info['forg'], $info['fdst'], date('Ymd', self::$timestamp),$info['fcategory']);
        }
    }

    public function continueAction($date)
    {
        if (!empty($date)) {
            self::$timestamp = strtotime($date[0]);
        }
        while ($info = json_decode($this->redis->spop('flight_' . date('Y_m_d', self::$timestamp)), true)) {
            self::Data($info['forg'], $info['fdst'], date('Ymd', self::$timestamp),$info['fcategory']);
        }
    }

    /**
     * 抓取航班信息存入redis
     */
    public function pushData()
    {
        try{
            if ($this->redis->exists('flight_' . date('Y_m_d', self::$timestamp))) {
                return 'Have been saved';
                exit();
            }
            $date = date('Y-m-d',self::$timestamp);
            $fid = 0;
            while ($data = self::getData($date, $fid)) {
                //添加到redis队列
                foreach ($data as $k => $v) {
                    $array['forg'] = $v['fdst'];
                    $array['fdst'] = $v['forg'];
                    $array['fcategory'] = $v['fcategory'];
                    if (!$this->redis->sismember('flight_'.date('Y_m_d',self::$timestamp), json_encode($array))) {
                        $this->redis->sadd('flight_'.date('Y_m_d',self::$timestamp), json_encode($v));
                    }
                    unset($array);
                }
                $fid += 10000;
            }
        }catch (\Exception $e){
            echo $e->getMessage();
        }
    }

    /**
     * @param $date
     * @param $fid
     * @return array
     */
    protected static function getData($date, $fid)
    {
        $url = 'http://115.182.42.14/search/flightByDate?';
        $array = ['date' => $date, 'fid' => $fid, 'limit' => 10000];
        $str = http_build_query($array);
        $url .= $str;
        $response_obj = \Requests::get($url, array(), ['verify' => false]);
        if ($response_obj->success) {
            $result = json_decode($response_obj->body, true);
            if ($result['code'] == 200) {
                return $result['data'];
            }else{
                return [];
            }
        }
        return [];
    }

    public static function getId($cabin_id =1,$origin='SHA',$destination='PEK',$date='20180725')
    {
        $redis = Di::getDefault()->get('redis');
        $redis_key = $origin . '_' . $destination . '_' . $date . '_' . $cabin_id;
        if (!$redis->exists($redis_key)) {
            $data = [
                'data' => [
                    'cabin_id' => $cabin_id,//1经济舱；2超级经济舱；3商务舱；4头等舱
                    'travelers' => 1,
                    'legs' => [
                        [
                            'origin_code' => $origin,
                            'destination_code' => $destination,
                            'date' => $date
                        ],
                        [
                            'origin_code' => $destination,
                            'destination_code' => $origin,
                            'date' => $date
                        ]
                    ],
                ]
            ];
            $header = self::setHeader(self::getAuthHeader());
            $header = array_merge($header, ['Content-Type' => 'application/json','Referer'=> 'https://www.routehappy.com']);
            $response_obj = \Requests::post('https://www.routehappy.com/api/flight_searches', $header,json_encode($data),['verify'=>false,'timeout'=>60]);
            if ($response_obj->success) {
                $array_ = json_decode($response_obj->body, true);
                $redis->setex($redis_key, 60, $array_['id']);
                return $array_['id'];
            }else{
                throw new Exception($response_obj->body);
            }
        }
        return $redis->get($redis_key);
    }

    public function ttAction()
    {
        self::Data();
    }

    public static function Data($origin='SHA',$destination='PEK',$date='20180730',$fcategory='4')
    {
        $header = self::setHeader(self::getAuthHeader());
        $db = Di::getDefault()->get('db');
        for ($j = 1; $j < 5; $j++) {
            $id = self::getId($j,$origin,$destination,$date);
            for ($i = 0; $i < 2; $i++) {
                $array_legs = $array_segments = $segments = $data = [];
                $url = "https://www.routehappy.com/api/flight_searches/{$id}/leg_lists/{$i}/legs";
                $response_obj = \Requests::get($url, $header, ['verify' => false]);
                if ($response_obj->success) {
                    $array_legs = json_decode($response_obj->body, true);
                    if (!count($array_legs)) {
                        Di::getDefault()->get('redis')->lpush('flight_' . date('Y_m_d_',self::$timestamp) . 'no_result', $origin.'_'.$destination.'_'.$date.'_'.$i.'_'.$j );
                        continue;
                    }
                    //把segments 中的id 拿出来作为键，重构数组
                    foreach ($array_legs as $k => $v) {
                        $keys = array_column($v['segments'], 'id');
                        unset($v['id']);
                        unset($v['segments']);
                        unset($v['speed_id']);
                        unset($v['upa_ids']);
                        unset($v['duration']);
                        $data += array_fill_keys($keys, $v);
                    }
                    $array_legs = $data;
                }else{
                    self::addLog($response_obj->body);
                }

                $url = "https://www.routehappy.com/api/flight_searches/{$id}/leg_lists/{$i}/segments";
                $response_obj = \Requests::get($url, $header, ['verify' => false]);
                if ($response_obj->success) {
                    $segments = json_decode($response_obj->body, true);
                    if (!count($segments)) {
                        Di::getDefault()->get('redis')->lpush('flight_' . date('Y_m_d_',self::$timestamp) . 'no_result', $origin.'_'.$destination.'_'.$date.'_'.$i.'_'.$j );
                        continue;
                    }
                    //重构数组
                    foreach ($segments as $key => $value) {
                        $array_segments[$value['id']]['dep'] = $value['dep'];
                        $array_segments[$value['id']]['arr'] = $value['arr'];
                        $array_segments[$value['id']]['duration'] = $value['duration'];
                        $array_segments[$value['id']]['arr_day_delta'] = $value['arr_day_delta'];
                        $array_segments[$value['id']]['codeshare_disclosure'] = $value['codeshare_disclosure'];
                        $array_segments[$value['id']]['cabin_code'] = $value['cabin_code'];
                        $array_segments[$value['id']]['cabin_name'] = $value['cabin_name'];
                        $array_segments[$value['id']]['dep_time'] = substr($value['dep_time'], -8);
                        $array_segments[$value['id']]['arr_time'] = substr($value['arr_time'], -8);
                        $array_segments[$value['id']]['date'] = strstr($value['dep_time'],'T',true);
                        $array_segments[$value['id']]['fno'] = $value['carrier'] . $value['flt_no'];
                    }
                    unset($segments);
                }else{
                    self::addLog($response_obj->body);
                }
                //查询各种参数概要信息
                $seats = self::getSummary($id,$i,'seat');
                $entertainments = self::getSummary($id,$i,'entertainment');
                $powers = self::getSummary($id,$i,'power');
                $fresh_foods = self::getSummary($id,$i,'fresh_food');
                $layouts = self::getSummary($id,$i,'layout');
                $wifis = self::getSummary($id,$i,'wifi');
                $aircrafts = self::getSummary($id,$i,'aircraft');

                foreach ($array_legs as $k => $v) {
                    if (isset($array_segments[$k])) {
                        $data = $array_segments[$k];
                        $data['score'] = $v['score'];
                        $data['seat'] = $seats[$v['seat_summary_id']];
                        $data['entertainment'] = $entertainments[$v['entertainment_summary_id']];
                        $data['power'] = $powers[$v['power_summary_id']];
                        $data['fresh_food'] = $fresh_foods[$v['fresh_food_summary_id']];
                        $data['layout'] = $layouts[$v['layout_summary_id']];
                        $data['wifi'] = $wifis[$v['wifi_summary_id']];
                        $data['aircraft'] = $aircrafts[$v['aircraft_summary_id']];
                        $data['fcategory'] = $fcategory;
                        $db->insertAsDict('flight_happy',$data);
                    }else{
                        print_r($array_segments);
                        print_r($array_legs[$k]);
                        echo $url;
                    }
                }

                unset($segments);
                unset($array_segments);
                unset($data);
            }
        }
    }

    protected static function getSummary($id,$i,$type)
    {
        $data = $array = [];
        $url = "https://www.routehappy.com/api/flight_searches/{$id}/leg_lists/{$i}/{$type}_summaries";
        $header = self::setHeader(self::getAuthHeader());
        $response_obj = \Requests::get($url, $header, ['verify' => false]);
        if ($response_obj->success) {
            $array = json_decode($response_obj->body, true);
            foreach ($array as $k => $value) {
                if (in_array($type, ['layout', 'seat', 'aircraft'])) {
                    $data[$value['id']] = $value['display_text'];
                }else{
                    $data[$value['id']] = intval($value['exists'] == 'yes') ?: 0;
                }
            }
            unset($array);
            return $data;
        }else{
            self::addLog($response_obj->body);
        }
    }

}