<?php
namespace Route\Modules\Cli\Tasks;

use Phalcon\Di;
use Phalcon\Exception;

class MainTask extends \Phalcon\Cli\Task
{
    public function mainAction($time)
    {
        echo "Congratulations! You are now flying with Phalcon CLI!";
    }

    protected $time;

    protected function initialize()
    {
        $this->time = time();
    }

    private static $_header = [
        "Accept-Encoding"=> "gzip, deflate, br",
        "Accept-Language"=> "zh-CN,zh;q=0.9",
        "User-Agent"=> "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36",
        "Accept"=> "application/json",
        "Connection"=> "keep-alive"
    ];

    /**
     * @return array
     */
    protected static function getAuthHeader()
    {
        if ($auth_token = self::getAuthor()) {
            return ['Authorization'=> 'Bearer ' . $auth_token];
        }
    }

    /**
     * 合并头信息
     * @param $array
     * @return array
     */
    protected static function setHeader ($array)
    {
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        $ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        $ip = ['CLIENT-IP' => $ip, 'X-FORWARDED-FOR' => $ip];
        return array_merge(self::$_header, $array,$ip);
    }

    /**
     * 获取权限
     * @return mixed
     */
    public static function getAuthor()
    {
        if ($auth = Di::getDefault()->get('redis')->get('json_auth')) {
            return $auth;
        }
        $header = self::setHeader(['Origin'=> 'https://www.routehappy.com', 'Content-Length'=> 0]);
        $response_obj = \Requests::post('https://www.routehappy.com/api/sessions', $header, array(),['verify'=>false,'timeout'=>60]);
        if ($response_obj->success) {
            $array = json_decode($response_obj->body, true);
            Di::getDefault()->get('redis')->setex('json_auth', 180, $array['auth_token']);
            return $array['auth_token'];
        }else{
            throw new Exception($response_obj->body);
            exit();
        }
    }


    public static function addLog($string,$type=\Phalcon\Logger::NOTICE)
    {
        $logger = Di::getDefault()->get('logger');
        $logger->log($string,$type);
    }
}
