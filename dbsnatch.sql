--
-- 表的结构 `flight_happy`
--

CREATE TABLE `flight_happy` (
  `id` int(11) NOT NULL,
  `fcategory` tinyint(4) NOT NULL DEFAULT '0' COMMENT '航线类型标识，取已有数据库的值',
  `fno` char(6) NOT NULL COMMENT '航班号，航司二字码+3、4个数字',
  `dep` char(3) NOT NULL COMMENT '出发机场三字码',
  `arr` char(3) NOT NULL COMMENT '目的机场三字码',
  `date` date NOT NULL COMMENT '航班日期',
  `dep_time` time NOT NULL COMMENT '计划起飞时间',
  `arr_time` time NOT NULL COMMENT '计划到达时间',
  `arr_day_delta` tinyint(4) NOT NULL DEFAULT '0' COMMENT '跨几天',
  `cabin_code` char(1) NOT NULL COMMENT '舱等简称',
  `cabin_name` varchar(50) NOT NULL COMMENT '舱等名称',
  `score` decimal(10,1) NOT NULL COMMENT '分值',
  `duration` smallint(6) NOT NULL COMMENT '飞行时长',
  `aircraft` varchar(255) NOT NULL COMMENT '机型',
  `fresh_food` tinyint(4) NOT NULL COMMENT '餐食',
  `layout` varchar(255) NOT NULL COMMENT '座位布局',
  `wifi` tinyint(4) NOT NULL COMMENT '是否有wifi',
  `entertainment` tinyint(4) NOT NULL COMMENT '是否有娱乐设备',
  `power` tinyint(4) NOT NULL COMMENT '是否有电源',
  `seat` varchar(255) NOT NULL COMMENT '座椅',
  `codeshare_disclosure` varchar(255) NOT NULL COMMENT '共享信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=REDUNDANT;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `flight_happy`
--
ALTER TABLE `flight_happy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dep` (`dep`),
  ADD KEY `arr` (`arr`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `flight_happy`
--
ALTER TABLE `flight_happy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

