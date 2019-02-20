<?php
require_once('/usr/local/apache2/htdocs/libraries/tcpdf/tcpdf.php');
include 'report_down_lan.php';
date_default_timezone_set('Asia/Shanghai');
$options=getopt('n:f:d:l:t:p:s:e:');
$post=$options['n'];//指定从数据库里查找top几?
$path=$options['f'];//指定pdf保存的路径
 // n top几     f  pdf保存的路径pdf文件名    d 导出的报表 日期 是月报 周报 日报 还是自定义的    l 语言  
 //  t 导出的是单个服务器策略还是全部   p  写入服务器策略    s开始时间  e 结束时间   
$schedule = $options['d'];
$report_lan = $options['l'];

//$server_type = $options['st'];
$server_type = $options['t'];
//$server_policy = $options['sp'];
$server_policy = $options['p'];

$start_time = isset($options['s']) ? $options['s'] : '';
//$start_time = isset($argv[16]) ? $argv[16] : '';
$end_time = isset($options['e']) ? $options['e'] : '';
//$end_time = isset($argv[18]) ? $argv[18] : '';
if ($report_lan == 'chinese') {
    $report_msg = $pdf_lan[0];
}else {
    $report_msg = $pdf_lan[1];
}

//指定pdf文件名
//$fileName = ($options['f'] == '') ? 'report' :$options['f'];
if(!$options['n']==''){
    $post=$options['n'];
}else{
	$post=10;
}

if(!$options['f']==''){
    $path=$options['f'];
}else{  
    $path='../../cachedata/report.pdf';
}
include_once "Mysql.class.php";
include_once 'phplot.php';
include_once 'phplot_data_table.php';
$picpath = "/usr/local/apache2/htdocs/attachements/";

$dbconfig = array (
    'dbhost' => 'localhost',
    'dbname' => 'topwaf_logdb',
    'dbuser' => 'topwaflogger',
    //'dbuser' => 'root',
    'dbpassword' => 'loggerxxoo**',
    //'dbpassword' => 'topsec*talent',
    'charset' => 'utf8',
    'pconnect'=> false
);
$db = new mysql($dbconfig);

$dbconfig_config = array (
    'dbhost' => 'localhost',
    'dbname' => 'topwaf_config_db',
    'dbuser' => 'topwafconfig',
    'dbpassword' => 'configxxoo**',
    'charset' => 'utf8',
    'pconnect'=> false
);
$db_config = new mysql($dbconfig_config);

$time_event='';
$time_ip='';
$time_id='';

function &get_config($file = 'config', $replace = array()) {
    static $_config;
    if (isset($_config[$file])) {
        return $_config[$file];
    }
    $file_path = '/usr/local/apache2/htdocs/config/' . $file . '.php';
    if (!file_exists($file_path)) {
        exit('The configuration file does not exist.');
    }
    require_once $file_path;
    $config = $$file;
    if (!isset($config) OR ! is_array($config)) {
        exit('Your config file does not appear to be formatted correctly.');
    }
    if (count($replace) > 0) {
        foreach ($replace as $key => $val) {
            if (isset($config[$key])) {
                $config[$key] = $val;
            }
        }
    }
    return $_config[$file] = & $config;
}

function config_item($item, $file = 'config') {
    static $_config_item = array();
    if (!isset($_config_item[$item])) {
        $config = & get_config($file);
        if (!isset($config[$item])) {
            return FALSE;
        }
        $_config_item[$item] = $config[$item];
    }
    return $_config_item[$item];
}

//生成每三十天.每时,每周的图
function inarray($str, $eventType)
{
    for ($i = 0; $i < count($eventType); $i++) {
        if ($str == $eventType[$i]) {
            return true;
        };
    };
};
function inarray_ip($str, $eventType)
{
    for ($i = 0; $i < count($eventType); $i++) {
        if ($str == $eventType[$i]) {
            return true;
        };
    };
};
function inarray_ruleid($str, $eventType)
{
    for ($i = 0; $i < count($eventType); $i++) {
        if ($str == $eventType[$i]) {
            return true;
        };
    };
};


function draw_pie($data, $title, $picname, $settings = NULL)
{ //
    $plot = new PHPlot(900, 550);
    $plot->SetIsInline(TRUE);
    $plot->SetOutputFile($picname);
    $plot->SetFileFormat('png');
    $plot->SetDefaultTTFont('/usr/share/fonts/simsun.ttf');
    $plot->SetFont('title', NULL, 14);
    $plot->SetTitle($title);
    $plot->SetTitleColor('#0F3A82');
    $plot->SetDataValues($data);
    $plot->SetBackgroundColor('#EAF5F5');
    //$plot->SetImageBorderType('plain');
    $plot->SetShading(0);
    
    $plot->SetFont('generic', NULL, 14);
   
    $plot->SetDataType('text-data-single');
    $plot->SetPlotType('pie');
    $plot->SetFont('legend', NULL, 10);  
    foreach ($data as $row) { //图例
        if ($settings)
            $plot->SetLegend($row[0]);
        else
            $plot->SetLegend(implode(': ', $row));
    }
    if ($settings){
        $plot->SetFont('legend', NULL, 12);  
        $plot->SetCallback('draw_graph', 'draw_data_table', $settings); //表格
    }
    else {
        $plot->SetMarginsPixels(0,0,50,0);
        // $plot->SetPlotAreaPixels(40, 20, 600, 580);
        // $plot->SetLegendPosition(1, 0, 'image', 1, 0, -50, 50);
    }
    $plot->SetPlotAreaPixels(NULL,100,NULL,450);
    $plot->SetLegendStyle('left','left');
    $plot->SetDrawPieBorders(false);
    $plot->SetLegendPosition(1,0,'plot',1,0,-30,-30);
    $plot->SetImageBorderType('solid');
    $plot->SetImageBorderColor('#6BB1B1');
    $plot->DrawGraph();
}

function draw_bar($data, $title, $picname, $ytitle)
{
    $plot = new PHPlot(1800, 900);
    $plot->SetIsInline(TRUE);
    $plot->SetOutputFile($picname);
    $plot->SetFileFormat('png');
    $plot->SetDefaultTTFont('/usr/share/fonts/simsun.ttf');
    $plot->SetFont('title', NULL, 28);
    $plot->SetTitle($title);
    $plot->SetTitleColor('#0F3A82');
    $plot->SetDataValues($data);
    $plot->TuneXAutoRange(1);
    $plot->SetBackgroundColor('#EAF5F5');
    $plot->SetDataType('text-data-yx');
    $plot->SetPlotType('bars');
     $plot->SetFont('x_label', NULL, 18);
    $plot->SetFont('y_label', NULL, 18);
    $plot->SetPlotAreaPixels(NULL,50,1750,850);



    # Define the data range. PHPlot can do this automatically, but not as well.
    //$plot->SetPlotAreaWorld(0, 0, $x_values, 100);
    $plot->SetDataColors('#f0d290');
    $plot->SetDataBorderColors('#e9bb5c');
    # Select an overall image background color and another color under the plot:
    // $plot->SetBackgroundColor('#BDBEBD');
   // $plot->SetDrawPlotAreaBackground(True);
    $plot->SetGridColor('#C8C8C8');
    $plot->SetPlotBgColor('#ffffff');
    //去掉X轴标签
    $plot->SetXTickPos('none');

// $plot->SetImageBorderType('raised');
// $plot->SetImageBorderColor(array(209,208,222));
// $plot->SetImageBorderWidth(3);
    //设置背景图片
    // $plot->SetImageBorderColor(array(105, 105, 115));
    // $plot->SetPlotAreaBgImage('./bg_bar.png',tile);
    # Draw lines on all 4 sides of the plot:
    #设置是否显示边框
    $plot->SetPlotBorderType('left');
    # Define the data range. PHPlot can do this automatically, but not as well.
    //$plot->SetPlotAreaWorld(0, 0, $x_values, 100);
    $plot->SetXTickLabelPos('none');
    $plot->SetXDataLabelPos('plotin');
    $plot->SetYTickLabelPos('none');
    $plot->SetYTickPos('none');
    $plot->SetShading(0);
    $plot->SetGridColor('#000000');
    $plot->SetImageBorderType('solid');
    $plot->SetImageBorderColor('#6BB1B1');
    $plot->DrawGraph();
}

function draw_line($data, $title, $picname)
{
    prune_labels($data, 15);
    $plot = new PHPlot(1400, 800);
    $plot->SetIsInline(TRUE);
    $plot->SetOutputFile($picname);
    $plot->SetFileFormat('jpg');
    $plot->SetDefaultTTFont('/usr/share/fonts/simsun.ttf');
    $plot->SetFont('title', NULL, 22);
    $plot->SetTitle($title);
    $plot->SetTitleColor('#0F3A82');
    $plot->SetDataValues($data);

    $plot->SetDataType('data-data');

    $plot->SetPlotType('linepoints');
    $plot->SetFont('x_label', NULL, 14);
    $plot->SetFont('y_label', NULL, 14);
    $plot->SetXLabelType('time', '%Y-%m-%d %H:%M');
    $plot->SetXLabelAngle(30);
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetXTickLabelPos('none');
    $plot->SetXTickPos('none');
    $plot->SetMarginsPixels(80, 80, 50, 105);



    $plot->SetBackgroundColor('#EAF5F5');
     $plot->SetGridColor('#000000');
     $plot->SetImageBorderType('solid');
     $plot->SetImageBorderColor('#6BB1B1');

    //$plot->SetYTitle("攻击次数");
    $plot->SetYLabelType('data');
    $plot->SetPrecisionY(0);
    $plot->SetDrawXGrid(false);
    $plot->SetDrawYGrid(True);
    $plot->SetLineWidth(2);
    $plot->DrawGraph();
}

function severity ($rule_severity){
	global $report_msg;
     if($rule_severity == '0'){
         $rule_severity = $report_msg['high'];
     }
     if($rule_severity == '1'){
         $rule_severity = $report_msg['middle'];
     }
      if($rule_severity == '2'){
         $rule_severity = $report_msg['low'];
     }
     return $rule_severity;
}

// 数据对应的时间减少1天或者1小时
 function time_subtract ($data,$flag,$timename){
     foreach ($data as $kk => $vv) {
        if($flag){
          $data[$kk][$timename] = strtotime($data[$kk][$timename]) - 3600 * 24;
        }else{
          $data[$kk][$timename] = strtotime($data[$kk][$timename]) - 3600;
        }
          $data[$kk][$timename] = date('Y-m-d H:i:s', $data[$kk][$timename]);       
      } 
      return $data;
    } 
function init_get_inter(){
	if(!function_exists('waf_geo_init')){
		return "waf_geo_init".$report_msg['interface_noexist'];
    }	
	if (waf_geo_init(IPDB) != TRUE) {
		return "waf_geo_init".$report_msg['initial_fail'];
	}
	return 1;
}
function put_data_to_table($table){
	if($report_lan == 'english')
	{
		$language = 0;
	}else{
		$language = 1;
	}
	return waf_geo_fill_table($table, $language);
}

function get_where_by_policy($schedule,$server_type,$server_policy,$start_time,$end_time)
{
	if($schedule == 'none' && $server_type == 'all')
	{
        $start_time = date('Y-m-d H:i:s', strtotime($start_time));
        $end_time = date('Y-m-d H:i:s', strtotime($end_time)+3600*24);
		$where = ' where sttime >= \''.$start_time.'\' and sttime <= \''.$end_time.'\' ';
		$where1 = '';
	}else if($schedule == 'none' && $server_type == 'single'){
        $start_time = date('Y-m-d H:i:s', strtotime($start_time));
        $end_time = date('Y-m-d H:i:s', strtotime($end_time)+3600*24);
		$where = ' where server=\''.$server_policy.'\' and sttime >= \''.$start_time.'\' and sttime <= \''.$end_time.'\' ';
		$where1 = '';
	}else if($schedule == 'weekly' && $server_type == 'single'){
		$where = ' where server = \''.$server_policy.'\' ';
		$where1 = ' and server = \''.$server_policy.'\' ';
	}else if(($schedule == 'daily' || $schedule == 'monthly') && $server_type == 'single'){
		$where = ' where server = \''.$server_policy.'\' ';
		$where1 = '';
	}else{
		$where = '';
		$where1 = '';
	}
	
	return array($where,$where1);
}
$arr_where = get_where_by_policy($schedule,$server_type,$server_policy,$start_time,$end_time);

$where = $arr_where[0];
$where1 = $arr_where[1];

//生成攻击时间趋势
$bHaveImgLine = false;
$trend_line_args = array(
		'daily'=>array('tbname'=>'event_times_1day','num'=>12,'tick'=>2*60*60,'dateformat'=>'Y-m-d H:i','picformat'=>'%Y-%m-%d %H:%i'),
		'weekly'=>array('tbname'=>'event_times_1month','num'=>7,'tick'=>24*60*60,'dateformat'=>'Y-m-d','picformat'=>'%Y-%m-%d'),
		'monthly'=>array('tbname'=>'event_times_1month','num'=>30,'tick'=>24*60*60,'dateformat'=>'Y-m-d','picformat'=>'%Y-%m-%d'),
		'none'=>array('tbname'=>'event_times_1month','num'=>30,'tick'=>24*60*60,'dateformat'=>'Y-m-d','picformat'=>'%Y-%m-%d'),
		'hour'=>array('tbname'=>'event_times_1hour','num'=>6,'tick'=>10*60,'dateformat'=>'H:i')
);

if($schedule != 'none')
{
	$sql = "select max(sttime) as maxtime from ".$trend_line_args[$schedule]['tbname'].$where ;
	$ret = $db->select($sql);
	foreach($ret as $k=>$v){
		$tMax = $v['maxtime'];
	}		
}	
if($schedule == 'none')
{
    $none_time_tmp = strtotime($end_time) - strtotime($start_time);
    if($none_time_tmp > 24*3600){
        $none_table_name = "event_times_1month";
    }else if($none_time_tmp > 6*3600){
        $none_table_name = "event_times_1day";
    }else if($none_time_tmp > 3*3600){
        $none_table_name = "event_times_6hours";
    }else if($none_time_tmp > 3600){
        $none_table_name = "event_times_3hours";
    }else{
        $none_table_name = "event_times_1hour";
    }
	$sql = "select sttime,sum(count) as csum from ".$none_table_name . $where."group by sttime order by csum desc";      
}else{
	$sql = "select sttime,sum(count) as csum from ".$trend_line_args[$schedule]['tbname'].$where." group by sttime order by csum desc";      
}
$retData = $db->select($sql);
//$retData = time_subtract($retData,true,'sttime');
$return = array();
if(count($retData)>0) {
	$bHaveImgLine = true;
	if($schedule=='monthly'){
		$tCurr = date('Y-m-d', strtotime($tMax));
		$tCurrPreMonth = date("Y-m-d",strtotime("-1months",strtotime($tMax)));
		$days=ceil((strtotime($tCurr)-strtotime($tCurrPreMonth))/3600/24) ;
		$trend_line_args['monthly']['num'] = $days;
	}
	
	if($schedule == 'daily' || $schedule == 'weekly' || $schedule == 'monthly')
	{
		$trend_arr = $trend_line_args[$schedule];
	}else{
		$trend_arr['num'] = 16;
		$days = 15;
		$trend_arr['tick'] = (strtotime($end_time)-strtotime($start_time))/$trend_arr['num'];
		$trend_arr['dateformat'] = ($trend_arr['tick'] < 86400) ? 'Y-m-d H:i' : 'Y-m-d';
		$trend_arr['picformat'] = ($trend_arr['tick'] < 86400) ? '%Y-%m-%d %H:%M' : '%Y-%m-%d';
	}
	for($i=0; $i<=$trend_arr['num']; $i++){
		if($schedule == 'none')
		{
			if($trend_arr['tick'] < 0)
			{
				break;
			}
			$return['count'][$i] = 0;
			$time = strtotime($end_time)-$i*$trend_arr['tick'];
			$lastTime = strtotime($end_time)-($i+1)*$trend_arr['tick'];
			$return['time'][$i] = $time;
			foreach ($retData as $k=>$v) {
				if(strtotime($v['sttime']) < $time && strtotime($v['sttime']) >= $lastTime)
				{
					$return['count'][$i] += $v['csum'];
				}
			}
		}else{
			$time = strtotime($tMax)-$i*$trend_arr['tick'];
			$retDate = date('Y-m-d H:i:s', $time);
			$return['time'][$i] = $time;
			$find=0;
			foreach ($retData as $k=>$v) {
				if($v['sttime'] == $retDate){
					$return['count'][$i] = $v['csum'];
					$find=1;
					break;
				}
			}
			if($find==0) {
				$return['count'][$i]='0';
			}            
		}
		$data_line[$i] = array($return['time'][$i],$days-$i,$return['count'][$i]);
	}
	draw_line($data_line,$report_msg['attack_num'], $picpath."exportpage_line.jpg",$trend_arr['picformat']);        
}

//生成威胁等级统计图
if($schedule=='daily') {
    $sql = "select severity, sum(count) as sumCount from severity_times_1day ".$where." group by severity;";
} else if($schedule=='weekly') {
    $sql = "select max(sttime) as maxtime from severity_times_1month ".$where;
    $ret = $db->select($sql);
    $tMax = (isset($ret[0]) && isset($ret[0]['maxtime'])) ? $ret[0]['maxtime'] : time();
    $t = strtotime($tMax)-(7 * 24 * 60 * 60);
    $data = date('Y-m-d H:i:s', $t);
    $sql = "select severity,sum(count) as sumCount from severity_times_1month where sttime>='".$data."' ".$where1." group by severity;";
} else if($schedule=='monthly') {
    $sql = "select severity, sum(count) as sumCount from severity_times_1month ".$where." group by severity;";
}else if($schedule=='none'){
	$sql = 'select severity, sum(count) as sumCount from severity_times_1month '.$where.' group by severity;';
} else {
    $sql = "select severity, sum(count) as sumCount from severity_times_1hour group by severity;";
}
$data_severity = $db->select($sql);

$numSeverity = count($data_severity);
$bHaveImgSeverity = false;
if($numSeverity>0) {
    $bHaveImgSeverity = true;
    $severityType = array();
    $severityCount = array();
    for($i=0;$i<$numSeverity;$i++){
        if(!inarray($data_severity[$i]['severity'],$severityType)){
            array_push($severityType,$data_severity[$i]['severity']);
            array_push($severityCount,$data_severity[$i]['sumCount']);  
            $data_s[$i] = array($data_severity[$i]['severity'],$data_severity[$i]['sumCount']);           
        }
    }
    $settings = array(
	    'headers' => array($report_msg['level'], $report_msg['total']),
	    'data' => $data_s,
		);
    draw_pie($data_s,$report_msg['severity_level_distributed'],$picpath."exportpage_severity.jpg",$settings);//严重级别统计图      
}

//生成ddos攻击流量图片
$bHaveImgDdosFlow = false;
$numTotal =  $db->select("select count(*) as count from ads_flow;");
$numAtack =  $db->select("select count(*) as count from ads_attack;");
if($numTotal[0]['count']>0 && $numAtack[0]['count']>0) {
    $bHaveImgDdosFlow=true;
    $sqlTotal = "select sum(total_bytes) as totalBytes  from ads_flow;";
    $sqlAttack = "select sum(attack_bytes) as attackBytes from ads_attack;";
    $data_total = $db->select($sqlTotal);
    $data_attack = $db->select($sqlAttack);
    $flowType = array();
    $flowCount = array();
    array_push($flowType,$report_msg['normal_flow']);
    array_push($flowType,$report_msg['attack_flow']);
    array_push($flowCount,$data_total[0]['totalBytes']);
    array_push($flowCount,$data_attack[0]['attackBytes']);
    $data_f = array(array($report_msg['normal_flow'],$data_total[0]['totalBytes']),array($report_msg['attack_flow'],$data_attack[0]['attackBytes']));
    $settings = array(
	    'headers' => array($report_msg['attack_type'], $report_msg['total']),//
	    'data' => $data_f,	    
		);
    draw_pie($data_f,"",$picpath."exportpage_flow.jpg",$settings);//DDOS攻击流量统计图 
}
//生成ddos攻击类型图片
$sql = "select attack_type, count(*) as typeCount from ads_attack group by attack_type order by typeCount desc;";
$data_ddos = $db->select($sql);
$numDdos= count($data_ddos);
$attackType = array();
$attackCount = array();
$bHaveImgDdosType = false;
if($numDdos>0) {
    $bHaveImgDdosType = true;
    for($i=0;$i<$numDdos;$i++){
        if(!inarray($data_ddos[$i]['attack_type'],$attackType)){
            array_push($attackType,$data_ddos[$i]['attack_type']);
            array_push($attackCount,$data_ddos[$i]['typeCount']); 
            $data_ddos[$i] = array($data_ddos[$i]['attack_type'],$data_ddos[$i]['typeCount']);     
        }
    }
    $settings = array(
	    'headers' => array($report_msg['attack_type'], $report_msg['total']),//
	    'data' => $data_ddos,
		);
    draw_pie($data_ddos,"",$picpath."exportpage_ddos.jpg",$settings);// 攻击类型分布图   
}

// 生成图片:攻击事件 攻击源IP 规则ID
$picture_array=array("exportpage_eventtype_","exportpage_ip_","exportpage_ruleid_","exportpage_url_");
$time_array=array('daily'=>array('event'=>'event_times_1day','ip'=>'ip_times_1day','ruleid'=>'ruleid_times_1day','url'=>'url_times_1day'),//day
				  'weekly'=>array('event'=>'event_times_1month','ip'=>'ip_times_1month','ruleid'=>'ruleid_times_1month','url'=>'url_times_1month'),//week
				  'monthly'=>array('event'=>'event_times_1month','ip'=>'ip_times_1month','ruleid'=>'ruleid_times_1month','url'=>'url_times_1month')//month
);//每个事件都有三张图,每天,每周,每月,
$bNoData= array();
$data = array();
$where_event_ip_rule = array();
$ruleIdNamePair = array();
array_push($where_event_ip_rule,array('where'=>$where,'where1'=>$where1));
	
$server_policy_arr = explode(',',$server_policy);
foreach($server_policy_arr as $v)
{
	$arr_where = get_where_by_policy($schedule,'single',$v,$start_time,$end_time);
	$where = $arr_where[0];
	$where1 = $arr_where[1];
	array_push($where_event_ip_rule,array('where'=>$where,'where1'=>$where1));
}

$ipNamePair = array();
$urlNamePair = array();
$attack_return = array();
foreach($where_event_ip_rule as $k3 => $v3){
	if($server_type == 'single' && $k3 == 1)
	{
		break;
	}
	if($schedule == 'weekly'){
		$data1 = array();
		foreach($time_array['weekly'] as $kk => $vv){
			$sql = "select max(sttime) as maxtime from ".$vv.$v3['where'];
			$ret = $db->select($sql);
			$tMax = $ret[0]['maxtime'];
			$t = (strtotime($tMax)-(7 * 24 * 60 * 60)) > 0 ? (strtotime($tMax)-(7 * 24 * 60 * 60)) : time();
			array_push($data1,date('Y-m-d H:i:s', $t));
		}
		$sql = "select event_type,sum(count) as csum from event_times_1month where sttime>='".$data1[0]."'".$v3['where1']." group by event_type order by csum desc limit ".$post;
		$sql_ip = "select ip,sum(count) as csum,".(($report_lan == 'chinese')? 'country as cou,province as pro,city as cit ' : 'country_en as cou,province_en as pro,city_en as cit ')." from ip_times_1month where sttime>='".$data1[1]."'".$v3['where1']." group by ip order by csum desc limit ".$post;
		$sql_ruleid = "select ruleid,sum(count) as csum from ruleid_times_1month where sttime>='".$data1[2]."'".$v3['where1']." group by ruleid order by csum desc limit ".$post;
        $sql_url = "select url,sum(count) as csum,server from url_times_1month where sttime>='".$data1[3]."'".$v3['where1']." group by url order by csum desc limit ".$post;
        //select url,sum(count) as csum,server from url_times_1month group by url order by csum desc limit 10;
		$ip_times_table_name = 'ip_times_1month';
	}else if($schedule == 'none'){
		$sql = "select event_type,sum(count) as csum from event_times_1month ".$v3['where']." group by event_type order by csum desc limit ".$post;
		$sql_ip = "select ip,sum(count) as csum,".(($report_lan == 'chinese')? 'country as cou,province as pro,city as cit ' : 'country_en as cou,province_en as pro,city_en as cit ')." from ip_times_1month ".$v3['where']." group by ip order by csum desc limit ".$post;
		$sql_ruleid = "select ruleid,sum(count) as csum from ruleid_times_1month ".$v3['where']." group by ruleid order by csum desc limit ".$post;
        $sql_url = "select url,sum(count) as csum,server from url_times_1month ".$v3['where']." group by url order by csum desc limit ".$post;
		$ip_times_table_name = 'ip_times_1month';
	}else{
		$sql = "select event_type,sum(count) as csum from ".$time_array[$schedule]['event'].$v3['where']." group by event_type order by csum desc";
		$sql_ip = "select ip,sum(count) as csum,".(($report_lan == 'chinese')? 'country as cou,province as pro,city as cit ' : 'country_en as cou,province_en as pro,city_en as cit ')." from ".$time_array[$schedule]['ip'].$v3['where']." group by ip order by csum desc limit ".$post;
		$sql_ruleid= "select ruleid,sum(count) as csum from ".$time_array[$schedule]['ruleid'].$v3['where']." group by ruleid order by csum desc limit ".$post;
        $sql_url= "select url,sum(count) as csum,server from ".$time_array[$schedule]['url'].$v3['where']." group by url order by csum desc limit ".$post;
		$ip_times_table_name = $time_array[$schedule]['ip'];
	}

    // url数据
    $data_url = $db->select($sql_url);
    $num_url = count($data_url);
    //$url_ratio_arr = array();
    $url_sum = 0;
    if($num_url==0){
       // $bNoData[$k3][0] = true;
        $urlTable = array();
    }else{
        foreach ($data_url as $uk => $uv) {
            $url_sum = $url_sum + $uv['csum'];
        }
        for($i=0;$i< $num_url;$i++){ 
            // 计算url比例
            $urlatio = floor($data_url[$i]['csum']/$url_sum*1000)/10;
            $data_url[$i] = array($data_url[$i]['url'],$data_url[$i]['csum'],$urlatio); 
        };
        $urlTable = $data_url;
       // draw_pie($data_url,$report_msg['attack_type_dis'],$picpath.'exportpage_url_'.$k3.$schedule."_pie.jpg");                     
    }
    array_push($urlNamePair,$urlTable);


	//event数据
	$data=$db->select($sql);
	//$num1= count($data);
	$eventType=array();
	$eventCount=array();
	$attack_ret = $db_config->select("select * from attack_table where type<=13");
	$return = array();
	$index = 0;
	foreach($attack_ret as $m => $n){
		$find=-1;
		foreach ($data as $k=>$v) {
			if($v['event_type'] == $n['type_string']){
				//$ret[$k]['event_type'] = $n['cn_name'];
				$return[$index]['event_type'] = ($report_lan == 'english')?$n['name']:$n['cn_name'];
				$return[$index]['csum'] = $data[$k]['csum'];
				$index++;
				$find=1;
				break;
			}
		}
		if($find==-1) {
			$return[$index]['event_type'] = ($report_lan == 'chinese')?$n['cn_name']:$n['name'];
			$return[$index]['csum'] = 0;
			$index++;
		}
	}
	foreach ($return as $key=>$value){
		$id[$key] = $value['event_type'];
		$price[$key] = $value['csum'];
	}
	array_multisort($price,SORT_NUMERIC,SORT_DESC,$id,SORT_STRING,SORT_ASC,$return);
	if($k3 == 0)
	{
		$attack_return = $return;
	}
	$num1= count($data);
	if($num1==0){
		$bNoData[$k3][0] = true;
	}else{
		for($i=0;$i< $num1;$i++){
			if(!inarray($return[$i]['event_type'],$eventType)){
				array_push($eventType,$return[$i]['event_type']);
				array_push($eventCount,$return[$i]['csum']);
				$data_event[$i] = array($return[$i]['event_type'],$return[$i]['csum']); 
			}
		};
        
        for($i=0; $i < count($data_event) ; $i++){ 
             if($data_event[$i][1] == 0){
                array_splice($data_event,$i,1);
                $i--;
                //unset($data_event[$i]);
             }
        }
        if(!$data_event){
             $data_event[0] = array('all_data','0');
             $bNoData[$k3][0] = true;
        }

		$re = draw_bar(array_reverse($data_event),$report_msg['attack_type_count'],$picpath.'exportpage_eventtype_'.$k3.$schedule."_bar.jpg",$report_msg['attack_type']);
		draw_pie($data_event,$report_msg['attack_type_dis'],$picpath.'exportpage_eventtype_'.$k3.$schedule."_pie.jpg");       				
	}
	
	//ip数据
	$re = init_get_inter();
	if($re !== 1)
    {
        echo '{"success":false,"info":"'.$re.'"}';
        return;
    }
    $report_language = ($report_lan == "english") ?  0 : 1;
    $re = waf_geo_fill_table($ip_times_table_name,$report_language);
    if($re !== 0)
    {
        echo '{"success":false,"info":"'.$re.'"}';
        return;
    }

    waf_geo_shutdown();
	$data_ip=$db->select($sql_ip);

	$num1_ip= count($data_ip);
	$eventType_ip=array();
	$eventCount_ip=array();
	//print_r($num1_ip);
	if($num1_ip==0){
		$bNoData[$k3][1] = true;
        $ipTable = array();
	}else{
		$ipTable = $data_ip;
		for($i=0;$i<$num1_ip;$i++){
			if(!inarray($data_ip[$i]['ip'],$eventType_ip)){
				array_push($eventType_ip,$data_ip[$i]['ip']);
				array_push($eventCount_ip,$data_ip[$i]['csum']);
				$data_ip[$i] = array($data_ip[$i]['ip'],$data_ip[$i]['csum']); 
			}
		};
		draw_bar(array_reverse($data_ip),$report_msg['arrack_count'],$picpath.'exportpage_ip_'.$k3.$schedule."_bar.jpg",$report_msg['att_source']);
		if($k3 == 0)
		{
			draw_pie($data_ip,$report_msg['arrack_dis'],$picpath.'exportpage_ip_'.$k3.$schedule."_pie.jpg");
		}
	}
    array_push($ipNamePair,$ipTable);
	
	//ruleid数据
	$data_ruleid=$db->select($sql_ruleid);
	$num1_ruleid= count($data_ruleid);
	$eventType_ruleid=array();
	$eventCount_ruleid=array();
	if($num1_ruleid==0){
		$bNoData[$k3][2] = true;
        $ruleidTable = array();
	}else{        		
		$ruleidTable = $data_ruleid;
		for($i=0;$i<$num1_ruleid;$i++){
			if(!inarray($data_ruleid[$i]['ruleid'],$eventType_ruleid)){
				array_push($eventType_ruleid,$data_ruleid[$i]['ruleid']);
				array_push($eventCount_ruleid,$data_ruleid[$i]['csum']);
				$data_ruleid[$i] = array($data_ruleid[$i]['ruleid'],$data_ruleid[$i]['csum']); 
			}
		};
		draw_bar(array_reverse($data_ruleid),$report_msg['idhits'],$picpath.'exportpage_ruleid_'.$k3.$schedule."_bar.jpg",$report_msg['rule_id']);
		draw_pie($data_ruleid,$report_msg['iddis'],$picpath.'exportpage_ruleid_'.$k3.$schedule."_pie.jpg");				
		
		for($i=0; $i<count($ruleidTable);$i++) {
			$ruleid = $ruleidTable[$i]['ruleid'];
			if ($ruleid>=800 && $ruleid<=1000) {
				$ruleidTable[$i]['cnName'] = $report_msg['user_rule'];
			} else {
				$sql = "select * from rule_table where id=".$ruleid;
				$ret = $db_config->select($sql);
				if(count($ret) > 0)
				{
					$ruleidTable[$i]['cnName'] = ($report_lan =='chinese')?$ret[0]['cn_name']:$ret[0]['name'];
				}else{
					/*$param['security-policy'] = $v3['server_policy1'];
					$rspString=getResponse( 'waf proto-compliance-policy','show', $param, 3);
					if(is_array($rspString)){
						$list_arr = getAssign($rspString, 0);
						foreach($list_arr['rows'] as $kr=>$vr){
							if ($ruleid==$vr['tid']) {
								$ruleidTable[$i]['cnName'] = $vr['name'];
								break;
							}
						}
					}*/
				}
			}
		}
		
	}
    array_push($ruleIdNamePair,$ruleidTable);
}
$pdf_title = ($report_lan == 'chinese') ? '天融信WEB应用防火墙' : 'Topsec Web Application Firewall';
$pdf_title_des = ($report_lan == 'chinese') ? '报表名称：WAF报表     20' : 'Report Name: WAF Report     20'; 
$report_content = ($report_lan == 'chinese') ? '报表内容' : 'Report Content';
$atteck_statistic = ($report_lan == 'chinese') ? '攻击威胁统计' : 'Attack Threaten Statistic';
$welcome_des = ($report_lan == 'chinese') ? '欢迎使用WEB应用防火墙系统<br><br>本报表用于帮助用户快速了解当前网络的风险状况，及近期的风险趋势。通过各种对各种攻击的来源、目标、攻击方式等进行统计分析，使用户能够掌握用户业务系统的脆弱点及风险所在，并提供相应的解决办法。' : 'Welcome to Web Application Security Protection System,this report include Attack Evrnts,Souces IPs and Rules Hit Number.';
$safe_proposal = ($report_lan == 'chinese') ? '安全建议：如您的站点只对本地（本省或者国内）用户提供服务，建议
其他外地（外省或者国外）地址加入IP地址黑名单或者限制攻击源的方式来禁止这些IP的访问。' : 'Safe Proposal:If your site server for local or home users only,you\'d better add others IP to blacklist or forbid others IP to access by limiting attack resource'; 
class MYPDF extends TCPDF {
    //Page header
    public function Header() {
		global $pdf_title,$pdf_title_des;
        //Print page number
        if($this->PageNo()==1){
            // // Logo
            $image_file = K_PATH_IMAGES.'bkground.png';
			$this->Image($image_file, 0, 0, 210, 15, '', '', '', false, 300, '', false, false, 0);
            // Set font
            $this->SetFont('droidsansfallback', 'B', 13);
          
            $this->SetTextColor(240,240,240);
            $this->setCellPaddings(15, 10, 5, 0);//设置表内内容距离边框的距离。分别左、上、右、下。
            $this->setCellMargins(0, 1, 0, 0);//
            $this->Cell(180, 15, $pdf_title, 0, true, 'L', 0, '', 0, true, 'M', 'M');
            $this->SetFont('droidsansfallback', 'B', 8);
            $this->setCellPaddings(13, 10, 5, 2);//设置表内内容距离边框的距离。分别左、上、右、下。
            $time = date("y-m-d  H:i:s",time());
            $this->Cell(210, 15, $pdf_title_des.$time.""  ,0, true, 'R', 0, '', 0, true, 'B', 'B');
        }
    }

     public function Footer() {
        // Position at 15 mm from bottom
        global $end_page;
        if($end_page){
              $this->SetY(-15);
              $image_file = K_PATH_IMAGES.'footer.png';
              $this->Image($image_file, 0, 287, 210, 10, '', '', '', false, 300, '', false, false, 0);
        }
      
    }

}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle($title);
$pdf->setPrintHeader(true); //设置打印页眉
$pdf->setPrintFooter(true); //设置打印页脚

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(1, 15, 1);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
// set bottomMargin
$pdf->SetAutoPageBreak(TRUE, 15);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('droidsansfallback', '', 10);
// add a page
$pdf->AddPage();
$pdf->Bookmark($report_content, 0, 0, '', 'B', array(81,94,120));
$pdf->Bookmark($atteck_statistic, 1, 0, '', '', array(81,94,120));
/* NOTE:
 * *********************************************************
 * You can load external XHTML using :
 *
 * $html = file_get_contents('/path/to/your/file.html');
 *
 * External CSS files will be automatically loaded.
 * Sometimes you need to fix the path of the external CSS.
 * *********************************************************
 */
$post = "TOP_" . $post;
$data[0] = $report_msg['threatreport'];
$data[1] = $report_msg['customized'];
$data[2] = $reporter;
if ($schedule == 'daily') {
    $data[3] = $report_msg['daysrep'];
} else if ($schedule == 'weekly') {
    $data[3] = $report_msg['weeksrep'];
} else if ($schedule == 'monthly') {
    $data[3] = $report_msg['monthrep'];
} else {
    $data[3] = $report_msg['hoursrep'];
}
$data[4] = $post;
$data[5] = $post;
$data[6] = $post;
$data[7] = $construct_time;

$time_end = date("y-m-d  H:i:s",time());

if ($schedule == 'daily') {
  $time_start = time() - 60*60*24;
  $time_start = date("y-m-d  H:i:s",$time_start);
} else if ($schedule == 'weekly') {
  $time_start = time() - 60*60*24*7;
  $time_start = date("y-m-d  H:i:s",$time_start);
} else if ($schedule == 'monthly') {
  $time_start = time() - 60*60*24*30;
  $time_start = date("y-m-d  H:i:s",$time_start);
} else {
  $time_end = $end_time;
  $time_start = $start_time;
}
// define some HTML content with style
$table0 = <<<EOF
    <div></div>
	<table  style="width:100%;line-height:26px;">
		<tr bgcolor="#81ADCD" >
			<td align="left" style="color:#ffffff;font-size:14px;font-weight:700;">
            &nbsp;{$report_msg['report_overview']}
			</td>
		</tr>
	</table>
	<br>
<margin>
      
<table style="font-size:12px;line-height:26px;text-align:left;text-indent:15px;" border="1" cellpadding="0" cellspacing="0"  nobr="true">
        
        <tr style="background-color:#EAF5F9;color:#222222;text-align:left;" >
            <td style="height:26px;background-color:#DCEBEB;color:#3D5E5E;"  width="327">{$report_msg['reptype']}</td>
            <td  width="327">{$data[1]}</td>
        </tr>
        
        <tr style="background-color:#EAF5F9;color:#222222;text-align:left;" >
            <td style="height:26px;background-color:#DCEBEB;color:#3D5E5E;" width="327">{$report_msg['period']}</td>
            <td  width="327">{$time_start}{$report_msg['time_head_2']}{$time_end}</td>
        </tr>
        <tr style="background-color:#EAF5F9;color:#222222;text-align:left;" >
            <td style="height:26px;background-color:#DCEBEB;color:#3D5E5E;" width="327">{$report_msg['attack']}</td>
            <td  width="327">{$data[4]}</td>
        </tr>
        </table>
</margin>     
        <br>
EOF;
$pdf->MultiCell(0, 5, $table0, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true,$autopadding=true);
// ---------------------------------------------------------
$pdf->setY($pdf->getY()+3);

$time_end = date("y-m-d  H:i:s",time());

if ($schedule == 'daily') {
  $time_start1 = time() - 60*60*24;
  $time_start1 = date("Y-m-d  H:i:s",$time_start1);
  //流量日志周期内记录查询条件
  $traffic_where = ' where tstamp >= \''.$time_start1.'\' ';
  //攻击总数查询条件
  $attack_where = ' where sttime >= \''.$time_start1.'\' ';
  $total_day = 1;
} else if ($schedule == 'weekly') {
  $time_start1 = time() - 60*60*24*7;
  $time_start1 = date("Y-m-d  H:i:s",$time_start1);
  //流量日志周期内记录查询条件
  $traffic_where = ' where tstamp >= \''.$time_start1.'\' ';
  //攻击总数查询条件
  $attack_where = ' where sttime >= \''.$time_start1.'\' ';
  $total_day = 7;
} else if ($schedule == 'monthly') {
  $lastMonthDay = strtotime("-1months",time());
  $time_start1 = date("Y-m-d H:i:s",$lastMonthDay);
  //流量日志周期内记录查询条件
  $traffic_where = ' where tstamp >= \''.$time_start1.'\' ';
  //攻击总数查询条件
  $attack_where = ' where sttime >= \''.$time_start1.'\' ';
  $total_day = (time() - $lastMonthDay)/(24*3600);
}else{
	$time_start1 = $start_time;
    $time_end1 = $end_time;
    $start_time1 = date('Y-m-d H:i:s', strtotime($start_time));
    $end_time1 = date('Y-m-d H:i:s', strtotime($end_time));
    $start_time2 = date('Y-m-d H:i:s', strtotime($start_time)+3600*24);
    $end_time2 = date('Y-m-d H:i:s', strtotime($end_time)+3600*24);
    $start_time = date('Y-m-d H:i:s', strtotime($start_time)-3600*24);
    $end_time = date('Y-m-d H:i:s', strtotime($end_time)-3600*24);

    // $start_time = date('Y-m-d H:i:s', strtotime($start_time));
    //$end_time = date('Y-m-d H:i:s', strtotime($end_time));
	//流量日志周期内记录查询条件
	$traffic_where = ' where tstamp >= \''.$start_time1.'\' and tstamp <= \''.$end_time1.'\' ';
	//攻击总数查询条件
	$attack_where = ' where sttime >= \''.$start_time2.'\' and sttime <= \''.$end_time2.'\' ';
	$start_timeT = (strtotime($start_time) < strtotime("-1months",time())) ? strtotime("-1months",time()) : ((strtotime($start_time) > time()) ? time() : strtotime($start_time));
	$end_timeT = (strtotime($end_time) < strtotime("-1months",time())) ? strtotime("-1months",time()) : ((strtotime($end_time) > time()) ? time() : strtotime($end_time));
	$total_day = ($end_timeT - $start_timeT)/(24*3600);
	if($total_day <= 0)
	{
		$total_day = 1;
	}
}
if($server_type == 'single')
{
	$overall_profile = '';
	$server_policy_val = $server_policy;
}else{
	$overall_profile = $report_msg['overall_profile'].$report_msg['left_bracket'];
	$server_policy_val = $report_msg['all'].$report_msg['right_bracket'];
}

$tablea = <<<EOD
    <table  style="width:100%;line-height:26px;">
        <tr bgcolor="#81ADCD" >
            <td align="left" style="width:100%;font-size:14px;color:#ffffff;font-weight:700;">
            &nbsp;{$report_msg['overall_profile']}
            </td>
        </tr>
    </table>
EOD;

$pdf->MultiCell(0, 5, $tablea, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
// ---------------------------------------------------------
$pdf->setY($pdf->getY());
$pdf->setX(0);
$pdf->SetLeftMargin(8);
$pdf->SetRightMargin(8);

$tableb = <<<EOD
    <br>
    <br>
    <div style="text-align:left;color:#0F3A82;">&nbsp;{$report_msg['business_risk']}</div> 
    <div style="line-height:5px;border-color:#0F4AC2;"></div>
  
EOD;
$pdf->SetLeftMargin(0);
$pdf->MultiCell(0, 5, $tableb, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
// ---------------------------------------------------------
$pdf->setY($pdf->getY());
$pdf->setX(0);
$pdf->SetLeftMargin(10);
$pdf->SetRightMargin(12);

$table = <<<EOD
    <br>
    <br>
  
    <table style="font-size:12px;height:20px;line-height:20px;text-align:center;" border="1"cellpadding="0" cellspacing="0"  nobr="true">
        <tr style="height:26px;line-height:26px;background-color:#DCEBF0;color:#2251A2;text-align:center;">
            <th style="color:#cc3333" width="218">{$report_msg['high_att_num']}</th>
            <th style="color:#eeaa11" width="218">{$report_msg['middle_att_num']}</th>
            <th style="color:#33cc33" width="218">{$report_msg['low_att_num']}</th>
        </tr>  
EOD;
        $data_att_num[0] = $data_s[0] ? $data_s[0][1] : 0;
        $data_att_num[1] = $data_s[1] ? $data_s[1][1] : 0;
        $data_att_num[2] = $data_s[2] ? $data_s[2][1] : 0;
            $table .= <<<EOD
        <tr style="height:26px;line-height:26px;background-color:#EAF5F9;color:#222222;text-align:center;" >           
        <td  width="218">{$data_att_num[0]}</td>
        <td  width="218">{$data_att_num[1]}</td>
        <td  width="218">{$data_att_num[2]}</td>
        </tr>
        </table>
        <br>


EOD;

//显示积分
//周期内流量表访问量
$sql_traffic_count = 'select count(id) as traffic_records from traffic_table'.$traffic_where;
$traffic_log = $db->select($sql_traffic_count);
//统计周期内攻击频率
if(isset($traffic_log[0]) && isset($traffic_log[0]['traffic_records']) && $traffic_log[0]['traffic_records'] > 0)
{
	//流量日志在选择周期时间内有流量：周期内服务器策略（或号暂点）攻击数 /周期内访问量 * 40% +周期内攻击类型数量,每个类型一分 最多10分
	$traffic_records_total = $traffic_log[0]['traffic_records'];
	//周期内服务器策略攻击数
	$attack_records_total = 0;
	$event_scores1 = 0;
	$event_sql = 'select event_type,count from '.$trend_line_args[$schedule]['tbname'].$attack_where;
	$attack_log = $db->select($event_sql);
	foreach($attack_log as $v10)
	{
		$attack_records_total += $v10['count'];
		$event_scores1++;
	}
	$event_scores = ($event_scores1 > 10) ? 10 : $event_scores1;
	$attack_frenqunce = ceil(($attack_records_total/$traffic_records_total) *40 + $event_scores);
	$attack_average = ceil($attack_records_total/$total_day);
	$attack_high = ceil(($data_s[0][1]/$total_day*100)/100);
}else{
	//流量日志在选择周期时间内无流量：周期内攻击数 高严重程度/攻击总数 ×15 + 中严重程度/攻击总数 X 10 + 低严重程度/攻击总数 X 5
	$severity_tabal = ($schedule == 'daily') ? 'severity_times_1day' : 'severity_times_1month';
	$severity_sql = 'select severity,sum(count) as attack_records from '.$severity_tabal.$attack_where.' group by severity';
	$severity_log = $db->select($severity_sql);
	$severity_count = array();
	$attack_count_total = 0;
	foreach($severity_log as $m2)
	{
		$severity_count[$m2['severity']] = $m2['attack_records'];
		$attack_count_total += $m2['attack_records'];
	}
	if(!isset($severity_count['High']))
	{
		$severity_count['High'] = 0;
	}
	if(!isset($severity_count['Low']))
	{
		$severity_count['Low'] = 0;
	}
	if(!isset($severity_count['Medium']))
	{
		$severity_count['Medium'] = 0;
	}
	if($attack_count_total <= 0)
	{
		$attack_count_total = 1;
	}
	$attack_frenqunce = ceil($severity_count['High']/$attack_count_total*15+$severity_count['Medium']/$attack_count_total*10+$severity_count['Low']/$attack_count_total*5);
	$attack_average = ceil(($attack_count_total/$total_day*100)/100);
	$attack_high = ceil(($severity_count['High']/$total_day*100)/100);
}
$score_tmp = 100 - $attack_frenqunce;
$score = ($score_tmp > 97) ? 97 : (($score_tmp < 60) ? 60 : $score_tmp);
$sql = "select id,severity from rule_table group by id order by id desc;";
$data_rule_way = $db_config->select($sql);
$numRuleWay = count($data_rule_way);

$sql_ruleid_score = substr($sql_ruleid,0,strpos($sql_ruleid,'limit'));
$data_ruleid_score=$db->select($sql_ruleid_score);
$num_ruleid_score= count($data_ruleid_score);

 		 for ($k = 0; $k < $num_ruleid_score; $k++)
            {
                $ruleid = $data_ruleid_score[$k]['ruleid'];
                for ($i=0; $i <= $numRuleWay; $i++) { 
                    if($ruleid == $data_rule_way[$i]['id']){
                        if($data_rule_way[$i]['severity'] == 0){
                            $high_num++;
                        }                         
                    }                 
                }
            }

            if($attack_average == 0){
                $score <= 90 ? $score = 90 : $score = $score;
            }else if($attack_average > 0 && $attack_average < 1000){
                if($high_num >0 && $high_num < 10){
                    $score <= 80 ? $score = 80 : $score = $score;
                    $score > 90 ? $score = 89 : $score = $score;
                }else if($high_num >= 10 && $high_num < 20){
                    $score <= 70 ? $score = 70 : $score = $score;
                    $score > 80 ? $score = 79 : $score = $score;
                }else if($high_num >= 20){
                    $score > 70 ? $score = 69 : $score = $score;
                }
            }else if($attack_average >= 1000 && $attack_average < 5000){
                if($high_num >= 0 && $high_num < 20){
                    $score <= 70 ? $score = 70 : $score = $score;
                    $score > 80 ? $score = 79 : $score = $score;
                }else if($high_num >= 20){
                    $score > 70 ? $score = 69 : $score = $score;
                }
            }else if($attack_average > 5000){
                $score > 70 ? $score = 69 : $score = $score;
            }
      


if($score >= '90'){
    $num_color = '#33cc33';
}else if($score >= '80' && $score < '90'){
    $num_color = '#2222ee';
}else if($score >= '70' && $score < '80'){
    $num_color = '#eeaa11';
}else{
    $num_color = '#cc3333';
}

$table .= <<<EOF
   

    <br>
    <div style="text-align:left;text-indent:10px;color:#0F3A82;">{$report_msg['day_att1']}<span style="font-size:16px;color:#111">{$attack_average}</span>{$report_msg['day_att2']}<span style="font-size:16px;color:#111">{$attack_high}</span>{$report_msg['day_att3']}<span style="font-size:20px;color:{$num_color}">{$score}</span>{$report_msg['day_att4']}</div>
EOF;


if($score >= '90'){

$table .=<<<EOF
    <div style="text-indent:-300px;width:100%;">
    <img  style="width:377px;" src="/usr/local/apache2/htdocs/libraries/tcpdf/images/excellent.png">
    </div>
EOF;

}else if($score >= '80' && $score < '90'){
$table .=<<<EOF
     <div style="text-indent:-300px;width:100%;">
     <img width="377" height="37" src="/usr/local/apache2/htdocs/libraries/tcpdf/images/Good.png">
     </div>
EOF;
}else if($score >= '70' && $score < '80'){
$table .=<<<EOF
    <div style="text-indent:-300px;width:100%;">
    <img style="width:377px;" src="/usr/local/apache2/htdocs/libraries/tcpdf/images/General.png">
    </div>
EOF;
}else{
$table .=<<<EOF
    <div style="text-indent:-300px;width:100%;">
    <img style="width:377px;" src="/usr/local/apache2/htdocs/libraries/tcpdf/images/bad.png">
    </div>
EOF;
}



$table .=<<<EOF
<br>
 <div style="text-align:left;text-indent:10px;color:#0F3A82;">{$report_msg['welcome_type']}</div>
<br>

 <table style="font-size:12px;" border="1" cellpadding="0" cellspacing="0"  nobr="true">    
        <tr style="background-color:#EAF5F9;color:#222222;" >
            <td style="height:55px;line-height:55px;background-color:#DCEBEB;color:#33cc33;"  width="50">{$report_msg['excellent']}</td>
            <td style="text-align:left;line-height:27px;" width="607">{$report_msg['score_explain_excellent']}
</td>
        </tr>
        
        <tr style="background-color:#EAF5F9;color:#222222;" >
            <td style="height:55px;line-height:55px;background-color:#DCEBEB;color:#2222ee;" width="50">{$report_msg['good']}</td>
            <td style="text-align:left;line-height:27px;" width="607">{$report_msg['score_explain_good']}</td>
        </tr>
        <tr style="background-color:#EAF5F9;color:#222222;" >
            <td style="height:55px;line-height:55px;background-color:#DCEBEB;color:#eeaa11;" width="50">{$report_msg['general']}</td>
            <td  style="text-align:left;line-height:27px;" width="607">{$report_msg['score_explain_general']}
</td>
        </tr>
        <tr style="background-color:#EAF5F9;color:#222222;" >
            <td style="height:55px;line-height:55px;background-color:#DCEBEB;color:#cc3333;" width="50">{$report_msg['bad']}</td>
            <td  style="text-align:left;line-height:27px;" width="607">{$report_msg['score_explain_bad']}
</td>
        </tr>
        </table>   
 

EOF;


$pdf->SetLeftMargin(0);
$pdf->SetRightMargin(0);
$pdf->MultiCell(0, 5, $table, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
// ---------------------------------------------------------
$pdf->setY($pdf->getY());
$pdf->setX(0);

//攻击时间趋势统计图
if ($bHaveImgLine) {
	$str = "<img src='{$picpath}exportpage_line.jpg'>";
} else {
	$str = ''.$report_msg['attack_time_msg'];
}

if ($bHaveImgLine) {
    $tbl1 .= <<<EOF
		 <div style="text-indent:36px;width:100%;">
          <img width="654" heigh="400"style='text-align:center;' src="/usr/local/apache2/htdocs/attachements/exportpage_line.jpg">
         </div>
		
EOF;
}
if (!$bHaveImgLine) {
    $tbl1 .= <<<EOF
          $str
EOF;
}

$tbl1 .= <<<EOD
	
	<br>

EOD;

if($bHaveImgSeverity) {
$tbl1 .= <<<EOF
		<div style="text-indent:12px;width:100%;">
          <img width="654" heigh="400"style='text-align:center;' src="/usr/local/apache2/htdocs/attachements/exportpage_severity.jpg">
        </div>
EOF;
}else{
$tbl1 .= <<<EOD
		{$report_msg['nodate']}
EOD;
}
$tbl1 .= <<<EOD
<br>
EOD;


$pdf->MultiCell(0, 5, $tbl1, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
$pdf->setY($pdf->getY());
$pdf->setX(0);
// add a page
$pdf->AddPage();
$pdf->setX(0);
$pdf->SetLeftMargin(12);

 for($i=0; $i < count($attack_return) ; $i++){ 
             if($attack_return[$i]['csum'] == 0){
                 array_splice($attack_return,$i,1);
                 $i--;
                //unset($attack_return[$i]);
             }
        }
if($attack_return){

$table1 .= <<<EOF
   
<table style="font-size:11px;height:25px;line-height:25px;text-align:left;" border="1"cellpadding="0" cellspacing="0"  nobr="true">
<tr style="background-color:#DCEBF0;color:#2251A2;text-align:left;">
    <th  width="327">&nbsp;&nbsp;&nbsp;&nbsp;{$report_msg['Threat_attack_type']}</th>
    <th  width="327">&nbsp;&nbsp;&nbsp;&nbsp;{$report_msg['attack_times']}</th> 
</tr>
EOF;


foreach($attack_return as $m3)
{
    $table1 .= <<<EOF
<tr style="background-color:#EAF5F9;color:#222222;text-align:left;" >
EOF;
                $table1 .= <<<EOF
        <td  width="327">&nbsp;&nbsp;&nbsp;&nbsp;{$m3['event_type']}</td>
        <td  width="327">&nbsp;&nbsp;&nbsp;&nbsp;{$m3['csum']}</td>
        </tr>
EOF;
}

$table1 .= <<<EOF
        </table>
        <br>

       
EOF;

$pdf->SetLeftMargin(0);
$pdf->MultiCell(0, 5, $table1, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
$pdf->setY($pdf->getY());
$pdf->AddPage();
$pdf->setX(0);
}

/*严重威胁统计*/

// /*攻击事件类型统计*/
$chart_direction = array($report_msg['eventpic'], $report_msg['sourceips'], $report_msg['rule_id_hitcount'],$report_msg['url_attack']);
$chart_schedule = array('daily'=>'(' . $report_msg['last_day'] . ')', 'weekly'=>'(' . $report_msg['last_week'] . ')', 'monthly'=>'(' . $report_msg['last_month'] . ')','none'=>'('.$start_time.$report_msg['to'].$end_time.')');

$ruleIdLabel = "ID";
$ruleNameLabel =$report_msg['rule'];
$ruleAttackNum = $report_msg['attack_times'];
$attackSourceIp = $report_msg['att_source_ip'];
$attackCount = $report_msg['att_source_count'];
$attackSourceCity = $report_msg['att_source_city'];

foreach($where_event_ip_rule as $k4 =>$v4)
{
    
	if($server_type == 'single' && $k4 != 0){
		break;
	}else if($server_type != 'single' && $k4 != 0)
	{
				$tbl = <<<EOF
				
	<table  style="width:100%;line-height:26px;">
        <tr bgcolor="#91B871" >
            <td align="left" style="width:100%;font-size:14px;color:#ffffff;font-weight:700;">
            {$report_msg['single_server_des']}{$report_msg['left_bracket']}{$report_msg['server_policy_des']}{$server_policy_arr[$k4-1]}{$report_msg['right_bracket']}
            </td>
        </tr>
    </table>
		
EOF;
		$pdf->MultiCell(0, 5, $tbl, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
		$pdf->setY($pdf->getY()+3);
        $pdf->setX(0);
	}
for ($i = 0; $i < count($chart_direction); $i++) {
    $pdf->setX(0);

    //$no = ($server_type != 'single' && $k4 != 0) ? ($i+1) : ($i + 2); //序号
    //for ($j = 0; $j < count($chart_schedule); $j++) {
		$chart_direction_item = $chart_direction[$i];
        if ($bNoData[$k4][$i]) {
            $str = $report_msg['nodate'];
        } else {
            $fpath = '/usr/local/apache2/htdocs/attachements/';
            $pic_bar = $fpath.$picture_array[$i].$k4.$schedule.'_bar.jpg';
            $pic_pie = $fpath.$picture_array[$i].$k4.$schedule.'_pie.jpg';
            $str = "<img style='text-align:center;' src='{$pic_bar}'>";
            //	$str .=  "<br><center><img style='text-align:center;' src='{$pic_pie}'></center>";
		}
        if($server_type == 'all' && $k4 == 0 && $i ==3){
        }else{

        $tbltitle = <<<EOF
	
	<table  style="width:100%;line-height:26px;">
        <tr bgcolor="#81ADCD" >
            <td align="left" style="width:100%;font-size:14px;color:#ffffff;font-weight:700;">
            {$chart_direction_item}
            </td>
        </tr>
    </table>
    <br>
    <br>

EOF;
    $pdf->MultiCell(0, 5, $tbltitle, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
    $pdf->setY($pdf->getY()+3);
    $pdf->setX(0);
    }
       $tbl2 = '';
       if(!$bNoData[$k4][$i] && $i != 3) {
             if($k4 == 0){
                    $textindent1 = '8px';
                }else{
                    $textindent1 = '6px';
                }
            $tbl2 = <<<EOF
                <div style="text-indent:{$textindent1}">
               <img width="654" heigh="500" src="{$pic_bar}">
               </div>
        
    
EOF;
        if((($server_type == 'single' && $i == 0) || ($server_type == 'all' && $k4 == 0) || ($server_type == 'all' && $k4 != 0 && $i == 0)) && $i != 3)
           {
                if($k4 == 0){
                    $textindent = '8px';
                }else{
                    $textindent = '6px';
                }
                $tbl2 .= <<<EOF
            <div style="text-indent:{$textindent}">
           <img width="654" heigh="400" src="{$pic_pie}">
            </div>
EOF;

           }
        }

        if($bNoData[$k4][$i] && $i != 3) {
            $tbl2 .= <<<EOF
	{$report_msg['nodate']}
EOF;
        }
        $tbl2 .=<<<EOF
       
EOF;

$pdf->MultiCell(0, 5, $tbl2, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
$pdf->setY($pdf->getY()+3);
$pdf->setX(0);
if($bNoData[$k4][$i]){

}else{
    if($pdf->getY()>200){
        $pdf->AddPage();
        $pdf->setX(0);
    }
}




// url攻击次数列表显示

if((($server_type == 'all' && $k4 != 0 && $i == 3) || ($server_type == 'single' && $i == 3)) && (!$bNoData[$k4][$i])){
    $pdf->SetLeftMargin(12);
    $urltabl = <<<EOF
    <table style="font-size:12px;height:26px;line-height:26px;text-align:left;" border="1">
        <tr style="background-color:#DCEBF0;color:#2251A2;">
            <th  width="457">&nbsp;&nbsp;{$report_msg['url_site']}</th>
            <th  width="97">&nbsp;&nbsp;{$report_msg['attack_times']}</th>
            <th  width="97">&nbsp;&nbsp;{$report_msg['url_rat']}</th>
        </tr>

EOF;
        foreach($urlNamePair[$k4] as $k6=>$v6)
        {
                $url = $v6[0];
                $urlCount = $v6[1];
                $urlRation = $v6[2];
$urltabl .= <<<EOF
        <tr style="background-color:#EAF5F9;color:#222222;" >
            <td  width="457">&nbsp;&nbsp;$url</td>
            <td  width="97">&nbsp;&nbsp;$urlCount</td>
            <td  width="97">&nbsp;&nbsp;$urlRation%</td>
        </tr>
EOF;
        }
$urltabl .= <<<EOF
    </table>
EOF;
$pdf->SetLeftMargin(0);
$pdf->MultiCell(0, 5, $urltabl, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
$pdf->setY($pdf->getY()+3);
if($k4 != count($where_event_ip_rule) -1 ){
    $pdf->AddPage();
}
$pdf->setX(0);

}



   
        if((($server_type == 'all' && $k4 != 0) || $server_type == 'single') && ($i == 1) && (!$bNoData[$k4][$i])){
$pdf->SetLeftMargin(12);  
            $tbl3 = <<<EOF
           
        
<table style="font-size:12px;height:26px;line-height:26px;text-align:left;" border="1">
<tr style="background-color:#DCEBF0;color:#2251A2;">
    <th  width="217">&nbsp;&nbsp;$attackSourceIp</th>
    <th  width="217">&nbsp;&nbsp;$attackSourceCity</th>
    <th  width="218">&nbsp;&nbsp;$attackCount</th>
</tr>
EOF;
           // for ($k5 = 0; $k5 < count($ipNamePair); $k5++)
           // {
				foreach($ipNamePair[$k4] as $k6=>$v6)
				{
                $attIP = $v6['ip'];
                $attCity = implode('',array_unique(array($v6['cou'],$v6['pro'],$v6['cit'])));
				$attCount = $v6['csum'];
                $tbl3 .= <<<EOF
<tr style="background-color:#EAF5F9;color:#222222;" >
EOF;
                $tbl3 .= <<<EOF
        <td  width="217">&nbsp;&nbsp;$attIP</td>
        <td  width="217">&nbsp;&nbsp;$attCity</td>
        <td  width="218">&nbsp;&nbsp;$attCount</td>
</tr>

EOF;

            }
		$tbl3 .= <<<EOF
        </table>

EOF;

$pdf->SetLeftMargin(0);
$pdf->MultiCell(0, 5, $tbl3, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
$pdf->setY($pdf->getY());
$pdf->SetLeftMargin(8);

$pdf->SetRightMargin(8);
        $tbl4 = <<<EOF
         <div></div>
         <span style="text-align:left;font-size:14px;font-weight:bolder;color:#0F3A82;">{$report_msg['safe_suggest_head']}</span>
         <div style="line-height:19px;border-color:#0F4AC2;"></div>
         <span style="text-align:left;font-size:12px;color:#0F3A82;">{$report_msg['safe_suggest']}</span>
       
         
EOF;
		//}

$pdf->SetLeftMargin(0);
$pdf->MultiCell(0, 5, $tbl4, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
$pdf->setY($pdf->getY());
$pdf->AddPage();
$pdf->SetRightMargin(0);
        }




//if((($server_type == 'all' && $k4 != 0) || $server_type == 'single') && ($i == 2) && (!$bNoData[$k4][$i])){
if($server_type != 'single' && $k4 == 0 && $i == 2){
$rule_proposal = array('chinese'=>'select r.id as id,r.cn_solution as solut,r.cn_description as descr,r.severity as severity,at.cn_name as name from rule_table as r inner join attack_table as at on r.type=at.type and NOT at.type between 18 and 23',
					   'english'=>'select r.id as id,r.solution as solut,r.description as descr,r.severity as severity,at.name as name from rule_table as r inner join attack_table as at on r.type=at.type and NOT at.type between 18 and 23');
$rule_proposal_sql = $rule_proposal[$report_lan];
$rule_result = $db_config->select($rule_proposal_sql);
			$rule_index_rule_id = array();
			foreach($rule_result as $r0)
			{
				$rule_index_rule_id[$r0['id']] = array('solut'=>$r0['solut'],'descr'=>$r0['descr'],'severity'=>severity($r0['severity'],$report_msg),'name'=>$r0['name']);
            }
            
            $tbl5 = <<<EOF
            <table style="font-size:12px;height:26px;line-height:26px;text-align:center;" border="1">
            <tr style="background-color:#DCEBF0;">
                <td style="text-align:left;border:1px solid #86AAAA;background-color:#EAF5F5;line-height:20px;" width="100">    
                    <span style="color:#0F3A82">{$report_msg['id']}:</span>
                </td>
                <td style="text-align:left;border:1px solid #86AAAA;background-color:#EAF5F5;line-height:20px;" width="257">        
                    <span style="color:#0F3A82">{$report_msg['type']}:</span>
                </td>
                <td style="text-align:left;border:1px solid #86AAAA;background-color:#EAF5F5;line-height:20px;" width="297">    
                    <span style="color:#0F3A82">{$report_msg['rule']}:</span>
                </td>
            </tr>
EOF;


	foreach ($ruleIdNamePair[$k4] as $r1)
        {
                $ruleid = $r1['ruleid'];
				$rule_type_name = $rule_index_rule_id[$r1['ruleid']]['name'];
				$rule_severity = $rule_index_rule_id[$r1['ruleid']]['severity'];
				$rule_solution_way = $rule_index_rule_id[$r1['ruleid']]['solut'];
            
				$rule_description = $rule_index_rule_id[$r1['ruleid']]['descr'];
				$cnName = htmlspecialchars($r1['cnName']);
                $hitNum = $r1['csum'];
                $pdf->SetLeftMargin(12);
                $tbl5 .= <<<EOF
<tr style="background-color:#DCEBF0;">
    <td style="text-align:left;border:1px solid #86AAAA;background-color:#EAF5F5;line-height:20px;" width="100">    
        <span>$ruleid</span>
    </td>
    <td style="text-align:left;border:1px solid #86AAAA;background-color:#EAF5F5;line-height:20px;" width="257">    
         <span>$rule_type_name</span> 
    </td>
    <td style="text-align:left;border:1px solid #86AAAA;background-color:#EAF5F5;line-height:20px;" width="297">    
         <span>$cnName</span>
    </td>
</tr>
EOF;

        }   
        $tbl5 .= <<<EOF
        </table>
EOF;
        $pdf->MultiCell(0, 5, $tbl5, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
        $pdf->setY($pdf->getY());
        if($pdf->getY()>300){
            $pdf->AddPage();
        }
        $pdf->SetLeftMargin(0);  
        $pdf->setX(0);    
        if($pdf->getY() > 20){
             $pdf->AddPage();
        }  
        $pdf->setX(0);		
	} 










if((($server_type == 'all' && $k4 != 0) || $server_type == 'single') && ($i == 2) && (!$bNoData[$k4][$i])){
            //}
//ruleid 对应解决办法

$rule_proposal = array('chinese'=>'select r.id as id,r.cn_solution as solut,r.cn_description as descr,r.severity as severity,at.cn_name as name from rule_table as r inner join attack_table as at on r.type=at.type and NOT at.type between 18 and 23',
					   'english'=>'select r.id as id,r.solution as solut,r.description as descr,r.severity as severity,at.name as name from rule_table as r inner join attack_table as at on r.type=at.type and NOT at.type between 18 and 23');
$rule_proposal_sql = $rule_proposal[$report_lan];
$rule_result = $db_config->select($rule_proposal_sql);
			$rule_index_rule_id = array();
			foreach($rule_result as $r0)
			{
				$rule_index_rule_id[$r0['id']] = array('solut'=>$r0['solut'],'descr'=>$r0['descr'],'severity'=>severity($r0['severity'],$report_msg),'name'=>$r0['name']);
			}
           // var_dump($rule_index_rule_id);
			foreach ($ruleIdNamePair[$k4] as $r1)
            {
                $ruleid = $r1['ruleid'];
				$rule_type_name = $rule_index_rule_id[$r1['ruleid']]['name'];
				$rule_severity = $rule_index_rule_id[$r1['ruleid']]['severity'];
				$rule_solution_way = $rule_index_rule_id[$r1['ruleid']]['solut'];
            
				$rule_description = $rule_index_rule_id[$r1['ruleid']]['descr'];
				$cnName = htmlspecialchars($r1['cnName']);
                $hitNum = $r1['csum'];
                $pdf->SetLeftMargin(12);
         
               // var_dump($rule_solution_way);
                $tbl5 = <<<EOF
<table style="font-size:12px;height:26px;line-height:26px;text-align:center;" border="1">
<tr style="background-color:#DCEBF0;">
    <td style="text-align:left;border:1px solid #86AAAA;background-color:#EAF5F5;line-height:20px;" width="654">
       
        <span style="color:#0F3A82">{$report_msg['id']}:</span>
        <span>$ruleid</span>
        <br>
        <span style="color:#0F3A82">{$report_msg['type']}:</span>
         <span>$rule_type_name</span>
         <br>
        <span style="color:#0F3A82">{$report_msg['rule']}:</span>
         <span>$cnName</span>
         <br>
        <span style="color:#0F3A82">{$report_msg['attack_times']}:</span>
         <span>$hitNum</span>
         <br>
        <span style="color:#0F3A82">{$report_msg['severity']}:</span>
         <span style="color:#cc3333">$rule_severity</span>
         <br>
        <table>
            <tr>
                <td style="color:#0F3A82;text-align:left;" width="58">{$report_msg['solve']}:</td>
                <td style="text-align:left;" width="560">$rule_description</td>
            </tr>
        </table>      
        <table>
            <tr>
                <td style="color:#0F3A82;text-align:left;" width="58">{$report_msg['solution']}:</td>
                <td style="text-align:left;" width="560">$rule_solution_way</td>
            </tr>
        </table>
       
    </td>
</tr>
</table>

EOF;
$pdf->MultiCell(0, 5, $tbl5, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true);
        $pdf->setY($pdf->getY());
        if($pdf->getY()>200){
            $pdf->AddPage();
        }
        $pdf->SetLeftMargin(0);  
        $pdf->setX(0);

            }
        
        if($pdf->getY() > 20){
             $pdf->AddPage();
        }
       
        $pdf->setX(0);
		
	} 
	//}
}

if($server_type == 'single' || ($server_type == 'all' && $k4 == 0))
{
/*DDOS攻击类型分布*/
if ($bHaveImgDdosType) {
    $fpath = 'attachements/';
    $str = $fpath.'exportpage_ddos.jpg';
} else {
    $str = ''.$report_msg['ddos_dis_mes'];
}
/* NOTE:
 * *********************************************************
 * You can load external XHTML using :
 *
 * $html = file_get_contents('/path/to/your/file.html');
 *
 * External CSS files will be automatically loaded.
 * Sometimes you need to fix the path of the external CSS.
 * *********************************************************
 */

// define some HTML content with style
$html2 = <<<EOF
	
	<table  style="width:100%;line-height:26px;">
        <tr bgcolor="#81ADCD" >
            <td align="left" style="width:100%;font-size:14px;color:#ffffff;font-weight:700;">
            {$report_msg['ddos_dis']}
            </td>
        </tr>
    </table>
	
	<br>
	
EOF;
if ($bHaveImgDdosType) {
    $html2 .= <<<EOF
          <div style="text-indent:11px;width:100%;">
          <img width="654" heigh="500" style="text-align:center" src="/usr/local/apache2/htdocs/attachements/exportpage_ddos.jpg">
          </div>
EOF;
}
if (!$bHaveImgDdosType) {
    $html2 .= <<<EOF
          <div>
          $str
          </div>
EOF;
}
$html2 .= <<<EOF
	
EOF;
$pdf->MultiCell(0, 5, $html2, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true,$autopadding=true);
$pdf->setY($pdf->getY());
$pdf->AddPage();
$pdf->setX(0);

//ddos 攻击流量比例
if ($bHaveImgDdosFlow) {
    // $str = "<img style='text-align:center;' src='{$picpath}exportpage_flow.jpg'>";
} else {
    $str = ''.$report_msg['ddos_flow_msg'];
}
$html3 = <<<EOF
	
	<table style="width:100%;line-height:26px;">
     <tr bgcolor="#81ADCD" >
         <td align="left" style="width:100%;font-size:14px;color:#ffffff;font-weight:700;">
         {$report_msg['ddos_flow']}
         </td>
     </tr>
    </table>
	
	<br>
	
EOF;
if ($bHaveImgDdosFlow) {
    $html3 .= <<<EOF
          <div style="text-indent:11px;width:100%;">
          <img width="654" heigh="500" style="text-align:center" src="/usr/local/apache2/htdocs/attachements/exportpage_flow.jpg">
          </div>
EOF;
}
if (!$bHaveImgDdosFlow) {
    $html3 .= <<<EOF
          <div>
          $str
          </div>
EOF;
}

$pdf->MultiCell(0, 5, $html3, $border=0, $align='C',$fill=false, $ln=1, $x='', $y='',  $reseth=true, $stretch=0,$ishtml=true,$autopadding=true);

$pdf->setY($pdf->getY());
if(count($where_event_ip_rule) != 2){
    $pdf->AddPage();
}
$pdf->setX(0);
}
}
//$footer =  <<<EOD
// <img  style="width:1000px;" src="/usr/local/apache2/htdocs/libraries/tcpdf/images/footer.png">
//EOD;
//$pdf->SetLeftMargin(0);
//$pdf->SetRightMargin(0);
//$pdf->writeHTML($footer, true, false, false, false, '');
//$pdf->lastPage();
//Close and output PDF document
//$pdf->Output('report_new.pdf', 'I');
global $end_page;
$end_page = $pdf->pageNo();
$outfile = $path;
$pdf->OutPut($outfile,'F');
system("cd /usr/local/apache2/htdocs/attachements && rm *.jpg");
?>
