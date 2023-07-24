<?php
declare(strict_types=1);

namespace bizcal;

use Exception;
use function array_unique;
use function is_scalar;
use function ceil;
use function range;
use function date;

class KsCalendar
{
    public int $year;// @var year
    public int $month;// @var month
    public int $lastday;// @var lastday of the month, days of the month
    public int $n_weeks;// @var number of weeks
    public int $firstwday;// @var weekday of the first day 
    public int $lastwday;// @var weekday of the last day
    
    public const PREFER_TO_WDAY = 1;  // find the n'th weekday    
    public const PREFER_TO_WEEK = 2;  // in n'th week find the weekday  
    private const JP_WEEKDAY = ["日", "月", "火", "水", "木", "金", "土"];
    private const EN_WEEKDAY = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

    public function __construct(int $year, int $month)
    {
        $time = mktime(0, 0, 0, $month, 1, $year);
        $this->year = (int)date('Y', $time); 
        $this->month = (int)date('m', $time);         
        $this->lastday = (int)date('t', $time);
        $this->firstwday = (int)date('w', $time);
        $this->lastwday = $this->d2w($this->lastday);
        $this->n_weeks = (int)ceil(($this->firstwday + $this->lastday) / 7.0 ); 
    }

    /** select() : select days of specified weekdays */
    public function select(array|int $week, array|int $wday=[], int $prefer=1): array
    {    
        $days = [];
        if (is_scalar($week)) $week = [$week];
        if (is_scalar($week)) $wday = [$wday];
        $wday =  empty($wday) ? range(0, 6) : array_unique($wday);
        foreach ($wday as $wd){
            foreach ($week as $n ){
                if ($prefer == self::PREFER_TO_WEEK){
                    $n = ($wd < $this->firstwday) ? $n - 1 : $n;
                }
                if ($n < 1)  continue;
                $day = $this->w2d($wd, $n);
                if ( $this->is_valid($day) ) $days[] = $day;
            }
        };
        return $days;
    } 

    /** w2d() : transform the n'th weekday to a day number */
    public function w2d(int $wday, int $n = 1) : int
    {   
        $n = ($wday >= $this->firstwday) ?  $n - 1 : $n;
        return $n * 7 + $wday - $this->firstwday + 1;
    }

    /** d2w(): transform a day number to weekday */
    public function d2w(int $day, string $name='') : int | string
    {
        $w = ($this->firstwday + $day -1) % 7;
        if ($name === 'JP')
            return self::JP_WEEKDAY[$w];
        if ($name === 'EN')
            return self::EN_WEEKDAY[$w];
        return $w;
    }

    /** is_valid(): check the validality of a day */
    public function is_valid(int  $d, string $flag='DAY') : bool
    {
        if ($flag==='DAY')
            return (1 <= $d and $d <= $this->lastday);
        if ($flag==='WEEK')
            return (1 <= $d and $d <= $this->n_weeks);
        if ($flag==='WDAY')
            return (0 <= $d and $d <= 6);
        if ($flag==='MONTH')
            return (1 <= $d and $d <= 12);
        
        return false;
    }


    public function getWeekdays($name="JP")
    {
        if ($name==='EN'){
            return self::EN_WEEKDAY;            
        }
        return self::JP_WEEKDAY;
    }

    public function getDays()
    {
        $days = [];
        foreach (range(1, $this->lastday) as $d){
            $w = $this->d2w($d);
            $class = ($w==0 or $w==6) ? strtolower(self::EN_WEEKDAY[$w]) : '';
            $days[$d] = ['wday'=>$w, 'class'=>$class];
        }
        return $days;
    }

    public function __toString() : string
    {
        $out = "Sun Mon Tue Wed Thu Fri Sat\n";
        for ($i = 0; $i < $this->firstwday; $i++){
            $out .= "    "; // 4 spaces
        }
        for ($i = 0; $i < $this->lastday; $i++){
            $out .= sprintf("% 2d  ", $i + 1);
            if (($i + $this->firstwday + 1) % 7 == 0){
                $out .= "\n";
            }
        }
        return trim($out); 
    }
}