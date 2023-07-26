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
$dat_holiday = Yaml::parseFile(DAT_DIR . "/holiday.yaml");
$holiday1 = new KsHoliday($year, $dat_holiday);
$holiday2 = new KsHoliday($year+1, $dat_holiday);

$cno = 0;
$_row1 = $_row2 = $_row3 ='';
echo '<table>';
foreach (range(3,14) as $m){
    $month = $m % 12 + 1;
    $n_year = $year;
    $holiday = $holiday1;
    if ($month < 4) {
        $n_year =  $year + 1;
        $holiday = $holiday2;
    }
    $holidays = $holiday->getHolidays($month);
    $calendar = new KsCalendar($n_year, $month);

    $schedule = [];
    try {
        $schedule = Yaml::parseFile(DAT_DIR . "/schedule/cal{$year}.yaml");
    }catch (Exception $e){
        // echo $e->getMessage();
    } 

    $_row1 .= sprintf('<td class="month" width="200px">%d月</td>',  $month);
    list('table'=>$table, 'days'=>$days) = getMonth($n_year, $month, $holidays, $schedule);
    $_row2 .= '<td class="cell">' . $table . "</td>\n";
    $_row3 .= '<td class="holiday-names">' . $days . "</td>\n";
    $cno++;
    if ($cno % 6==0){
        echo '<tr>' . $_row1 . "</tr>\n";
        echo '<tr>' . $_row2 . "</tr>\n";
        echo '<tr>' . $_row3 . "</tr>\n";
        $_row1 = $_row2 = $_row3 ='';     
    }
}
echo '</table>';

function parseSchedule($data)
{
    $days = [];
    foreach ($data as $d=>$info){
        if (isset($info['tag'])){
            if (in_array($info['tag'], ['workday','lecture','extra'])){
                $days[$d] = ['class'=>'workday', 'name'=>$info['name']]; 
            }
            if (in_array($info['tag'], ['holiday','vacation'])){
                $days[$d] = ['class'=>'vacation', 'name'=>$info['name']]; 
            }
        }else{
            $days[(int)$d] = ['class'=>'sat', 'name'=>$info['name']];
        }
    }
    return $days;
}

function getMonth($year, $month, $holidays, $schedule)
{
    $cal = new KsCalendar($year, $month);
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
        if (key_exists($md, $holidays)){
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
    foreach ($holidays as $md=>$name){
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
            $days .= '<span class="' . $info['class'] . '">' . $d . ': ' . $info['name'] . "</span><br>\n"; 
        } 
        if (isset($hdaynames[$d])){
            $info = $hdaynames[$d];
            $days .= '<span class="' . $info['class'] . '">' . $d . ': ' . $info['name'] . "</span><br>\n"; 
        }
    }
    return ['table'=>$table, 'days'=>$days];
}
?>

</div>
</body>
</html>