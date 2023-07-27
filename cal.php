<?php
namespace bizcal;

require "vendor/autoload.php";

use \Symfony\Component\Yaml\Yaml;
use \bizcal\KsCalendar;
use \bizcal\KsHoliday;
use \bizcal\KsDateTime;
use Exception;

class MyCalendar{

    const DAT_DIR = 'dat';

    function parseSchedule($data)
    {
        $days = [];
        foreach ($data as $d=>$info){
            if (isset($info['tag'])){
                if (in_array($info['tag'], ['workday','lecture','extra','exam'])){
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

    public function getMonth($year, $month, $holidays, $schedule)
    {
        $cal = new KsCalendar($year, $month);
        $names = $cal->getWeekdays();
        $days = $cal->getDays(); 
        $sdays = [];
        if (isset($schedule[$year][$month])){
            $sdays = $this->parseSchedule($schedule[$year][$month]);
        }
        $table =  $row = "";
        for ($w = 0; $w < 7; $w++){
            $class = $w==0 ? 'wday sun' : ($w==6 ? 'wday sat' : 'wday');
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
        if ($cal->lastwday < 6){
            $row .= '<td colspan="' .(6-$cal->lastwday). '"><br></td>';
        }
        $table .=  "<tr>{$row}</tr>\n";
        if ($cal->n_weeks < 6) {
            $table .= "<td colspan=7><br></td>\n";
        }
        $table = "<table class='table'>\n{$table}</table>\n";
    
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
        $days = [];
        foreach ($daynames as $d){
            if (isset($sdaynames[$d])){
                $info = $sdaynames[$d];
                $days[] = '<span class="' . $info['class'] . '">' . $d . ': ' . $info['name'] . "</span>"; 
            } 
            if (isset($hdaynames[$d])){
                $info = $hdaynames[$d];
                $days[] = '<span class="' . $info['class'] . '">' . $d . ': ' . $info['name'] . "</span>"; 
            }
        }
        return ['table'=>$table, 'days'=>$days];
    }
}
?>