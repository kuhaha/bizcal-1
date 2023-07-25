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
$year  = isset($_GET['y']) ? (int)$_GET['y'] : 2023;
// $month = isset($_GET['m']) ? (int)$_GET['m'] : 8;
$dat_holiday = Yaml::parseFile(DAT_DIR . "/holiday.yaml");
$holiday = new KsHoliday($year, $dat_holiday);

$cno = 0;
$tb_row = $tl_row = $title ='';
echo '<table>';
foreach (range(3,14) as $m){
    $month = $m % 12 + 1;
    $ac_year = ($month < 4) ? $year + 1 : $year; 
    $schedule = [];
    try {
        $schedule = Yaml::parseFile(DAT_DIR . "/schedule/cal{$year}.yaml");
    }catch (Exception $e){} 
    $national = $holiday->getHolidays($month);
    $calendar = new KsCalendar($year, $month);
    $title .= sprintf('<td class="month" width="200px">%d月</td>',  $month);
    list('table'=>$table, 'days'=>$days) = getMonth($year, $month, $holiday, $schedule);
    $tb_row .= '<td class="cell">' . $table . "</td>\n";
    $tl_row .= '<td class="cell"><hr>' . $days . "</td>\n";
    $cno++;
    if ($cno % 6==0){
        echo '<tr>' . $title . "</tr>\n";
        echo '<tr>' . $tb_row . "</tr>\n";
        echo '<tr>' . $tl_row . "</tr>\n";
        $tb_row = $tl_row = $title ='';     
    }
}
echo '</table>';

function parseSchedule($data)
{
    $days = [];
    foreach ($data as $d=>$info){
        if (isset($info['tag'])){
            if (in_array($info['tag'], ['workday','lecture','extra'])){
                $days[(int)$d] = ['class'=>'workday', 'name'=>$info['name']]; 
            }
            if (in_array($info['tag'], ['holiday','vacation'])){
                $days[(int)$d] = ['class'=>'vacation', 'name'=>$info['name']]; 
            }
        }else{
            $days[(int)$d] = ['class'=>'sat', 'name'=>$info['name']];
        }
    }
    return $days;
}

function getMonth($year, $month, $holiday, $schedule)
{
    $cal = new KsCalendar($year, $month);
    $hdays = $holiday->getHolidays($month);
    $names = $cal->getWeekdays();
    $days = $cal->getDays(); 
    $sdays = [];
    if (isset($schedule[$year][$month])){
        $sdays = parseSchedule($schedule[$year][$month]);
    }
    $table =  $row = "";
    for ($w = 0; $w < 7; $w++){
        $class = $w==0 ? 'sun' : ($w==6 ? 'sat' : '');
        $row .=  sprintf ('<th class="%s">%s</th>', $class, $names[$w]);
    }    
    $table .= "<tr>{$row}</tr>\n";
    
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
        $class .= ' day';
        $row .=  sprintf ('<td class="%s">%d</td>', $class, $d);
        if ($w == 6){
            $table .=  "<tr>{$row}</tr>\n";
            $row = "";
        }
    }
    for ($w= $cal->lastwday+1; $w<7; $w++){
        $row .= "<td></td>";
    }
    $table .=  "<tr>{$row}</tr>\n";
    $table = "<table>\n{$table}</table>\n";
 
    $hdaynames = [];
    $daynames = [];
    foreach ($hdays as $md=>$name){
        $d = (int)substr($md,-2);
        $daynames[] = $d;
        $hdaynames[$d] =['class'=>'holiday', 'name'=>$name]; 
    }
    $sdaynames = [];
    foreach ($sdays as $d=>$info){
        $daynames[] = $d;
        $sdaynames[$d] =['class'=>$info['class'], 'name'=>$info['name'] ]; 
    }
    $daynames = array_unique($daynames);
    sort($daynames);
    $days = "";
    foreach ($daynames as $d){
        if (isset($sdaynames[$d])){
            $info = $sdaynames[$d];
            $days .= '<span class="note ' . $info['class'] . '">' . $d . ': ' . $info['name'] . "</span><br>\n"; 
        } 
        if (isset($hdaynames[$d])){
            $info = $hdaynames[$d];
            $days .= '<span class="note ' . $info['class'] . '">' . $d . ': ' . $info['name'] . "</span><br>\n"; 

        }
    }
    return ['table'=>$table, 'days'=>$days];
}
?>

</div>
</body>
</html>