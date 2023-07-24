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
$year  = (int)isset($_GET['y']) ? $_GET['y'] : 2023;
$month = (int)isset($_GET['m']) ? $_GET['m'] :8;

$schedule = [];
try {
    $schedule = Yaml::parseFile(DAT_DIR . "/schedule/cal{$year}.yaml");
}catch (Exception $e){} 

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

echo printMonth($year, $month,$holiday, $schedule);

function printMonth($year, $month, $holiday, $schedule)
{
    $cal = new KsCalendar($year, $month);
    $hdays = $holiday->getHolidays($month);
    $sdays = [];
     if (isset($schedule[$year][$month])){
        foreach ($schedule[$year][$month] as $d=>$info){
        if (isset($info['tag'])){
            if (in_array($info['tag'], ['workday','lecture','extra'])){
                $sdays[$d] = ['class'=>'workday', 'name'=>$info['name']]; 
            }
            if (in_array($info['tag'], ['holiday','vacation'])){
                $sdays[$d] = ['class'=>'vacation', 'name'=>$info['name']]; 
            }

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
            $class .= $sdays[$d]['class']; 
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

    $daynames = [];
    foreach ($hdays as $md=>$name){
        $d = (int)substr($md,-2);
        $daynames[$d] =[ 'class'=>'holiday', 'name'=>$name]; 
    }
    foreach ($sdays as $d=>$info){
        $daynames[$d] =[ 'class'=>$info['class'], 'name'=>$info['name'] ]; 
    }
    ksort($daynames);
    foreach ($daynames as $d=>$info){ 
        $out .= '<span class="' . $info['class'] . '">' . $d . ': ' . $info['name'] . '</span><br>'; 
    }
    return $out;
}
?>

</div>
</body>
</html>