<?php
namespace bizcal;

require "vendor/autoload.php";

use \Symfony\Component\Yaml\Yaml;
use \bizcal\KsCalendar;
use \bizcal\KsHoliday;
use \bizcal\KsDateTime;
use Exception;

define ('DAT_DIR', 'dat');

// header('Content-Type: text/plain; charset=UTF-8');
$year  = isset($_GET['y']) ? (int)$_GET['y'] : 2023;
$month = isset($_GET['m']) ? (int)$_GET['m'] : 8;

$schedule = [];
$ac_year = ($month < 4) ? $year  -1 : $year; 
try {
    $schedule = Yaml::parseFile(DAT_DIR . "/schedule/cal{$ac_year}.yaml");
}catch (Exception $e){} 
// echo json_encode($schedule, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); 

$dat_holiday = Yaml::parseFile(DAT_DIR . "/holiday.yaml");
$holiday = new KsHoliday($year, $dat_holiday);
$national = $holiday->getHolidays($month);
// echo json_encode($national, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); 

$calendar = new KsCalendar($year, $month);

?>
<!DOCTYPE html> 
<html><head>
<meta http-equiv="Content-TYPE" content="text/html; charset=UTF-8">
<link rel="stylesheet" TYPE="text/css" href="css/style.css">
<title></title>
</head>
<body>
<div class="wrapper">
    
<?php
printf("<h2>%d年%d月</h2>\n", $calendar->year, $calendar->month);

echo printMonth($year, $month, $holiday, $schedule);

function printMonth($year, $month, $holiday, $schedule)
{
    $cal = new KsCalendar($year, $month);
    $hdays = $holiday->getHolidays($month);

    $sdays = [];
     if (isset($schedule[$year][$month])){
        foreach ($schedule[$year][$month] as $d=>$info){
            if (isset($info['tag'])){
                if (in_array($info['tag'], ['workday','lecture','extra'])){
                    $sdays[(int)$d] = ['class'=>'workday', 'name'=>$info['name']]; 
                }
                if (in_array($info['tag'], ['holiday','vacation'])){
                    $sdays[(int)$d] = ['class'=>'vacation', 'name'=>$info['name']]; 
                }
            }else{
                $sdays[(int)$d] = ['class'=>'sat', 'name'=>$info['name']];
            }
        }
    }
    $names = $cal->getWeekdays();
    $days = $cal->getDays(); 

    $out =  $row = "";
    for ($w = 0; $w < 7; $w++){
        $class = $w==0 ? 'sun' : ($w==6 ? 'sat' : '');
        $row .=  sprintf ('<td class="%s">%s</td>', $class, $names[$w]);
    }    
    $out .= "<tr>{$row}</tr>\n";
    
    $row = "";
    for ($w=0; $w < $cal->firstwday; $w++){
        $row .= "<td></td>";
    }
    foreach ($days as $d => ['wday'=>$w, 'class'=>$class]){
        $md = sprintf("%02d-%02d", $month, $d);
        if (key_exists($md, $hdays)){
            $class .= ' holiday'; 
        }
        if (isset($sdays[$d])){
            $class .= ' ' . $sdays[$d]['class']; 
        }
        $row .=  sprintf ('<td class="%s">%d</td>', $class, $d);
        if ($w == 6){
            $out .=  "<tr>{$row}</tr>\n";
            $row = "";
        }
    }
    for ($w= $cal->lastwday+1; $w<7; $w++){
        $row .= "<td></td>";
    }
    $out .=  "<tr>{$row}</tr>\n";
    $out = "<table>\n{$out}</table>\n";
 
    $hdaynames = [];
    foreach ($hdays as $md=>$name){
        $d = (int)substr($md,-2);
        $hdaynames[$d] =[ 'class'=>'holiday', 'name'=>$name]; 
    }
    $sdaynames = [];
    foreach ($sdays as $d=>$info){
        $sdaynames[$d] =['class'=>$info['class'], 'name'=>$info['name'] ]; 
    }
    $daynames = [];
    foreach (array_keys($hdaynames) as $d){
        $daynames[] = $d;
    } 
    foreach (array_keys($sdaynames) as $d){
        $daynames[] = $d;
    } 
    $daynames = array_unique($daynames);
    sort($daynames);
    foreach ($daynames as $d){
        if (isset($sdaynames[$d])){
            $info = $sdaynames[$d];
            $out .= '<span class="' . $info['class'] . '">' . $d . ': ' . $info['name'] . "</span><br>\n"; 
        } 
        if (isset($hdaynames[$d])){
            $info = $hdaynames[$d];
            $out .= '<span class="' . $info['class'] . '">' . $d . ': ' . $info['name'] . "</span><br>\n"; 

        } 

    }
    return $out;
}
?>

</div>
</body>
</html>