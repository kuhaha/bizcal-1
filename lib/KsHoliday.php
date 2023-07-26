<?php
// declare(strict_types=1);

namespace bizcal;

use bizcal\KsCalendar;

use Exception;
use function array_filter;
use function array_diff;
use function in_array;
use function is_array;
use function sprintf;
use function preg_match;
use function explode;
use function substr;
use function trim;
use function mktime;
use function floor;

class KsHoliday
{
    public $year;
    public $holidays;

    private const HOLIDAY_SINCE = 1948;
    private const SUBSTITUTE_HOLIDAY = '振替休日';
    private const EXTRA_HOLIDAY = '国民の休日';   
    private const DATE_FORMAT ='m-d'; // '01-07' for January 7 
    
    public function __construct(int $year, array $dat_holiday)
    {
        $this->year = $year;
        $this->holidays = $this->parseHolidays($dat_holiday);
    }
    
    /** get holidays of one month or a whole year (default)*/
    public function getHolidays(int $month = 0)
    {
        if ($month == 0) return $this->holidays;
        
        return array_filter($this->holidays, function ($x) use($month) {
                return (int)substr($x, 0, 2) === (int)$month;
            }, ARRAY_FILTER_USE_KEY);
    }
   

    /** query by name, support pattern matching  */
    public function queryByname(string $name)
    {
        return array_filter($this->holidays, function($v) use($name){
            return preg_match("/{$name}/", $v);
        });
    }

    /** query by date, support date format inference */
    public function queryBydate(string $date)
    {
        $date = $this->mkdate($date);
        return array_filter($this->holidays, function($v) use($date){
            return $v === trim($date);
        }, ARRAY_FILTER_USE_KEY);                
    }


    /** Normalize date format while shifting back/forth some days 
     * e.g., mkdate('2-14', 3) => '02-17', mkdate('3-31',2) => '04-02'
    */
    protected function mkdate (string $date, int $days = 0) : string
    {
        if (preg_match('/^[0-9]+-[0-9]+$/', $date)){
            list ($m, $d) = explode('-', $date);
        }elseif (preg_match('/^[0-9]{4,}$/', $date)){
            $m = substr($date, 0, 2);
            $d = substr($date, 2);
        }
        if (!isset($m, $d)) {
            throw new Exception('Invalid date format!');
        }
        $time = mktime(0, 0, 0, (int)$m, (int)$d + $days, $this->year);
        return date(self::DATE_FORMAT, $time);
    }

    /** check if all elements of $keys are defined in $array 
     * e.g. all_defined(['tom','bob'], ['bob'=>23, 'abe'=>35, 'tom'=>56]) => TRUE
    */
    protected  static function all_defined(array $keys, array $array) : bool
    {
        $diff = array_diff($keys, array_keys($array)); 
        return empty($diff); 
    }
    
    /** check if all elements of $keys are defined in $array 
     * e.g. any_defined(['tom','bob'], ['bob'=>23, 'abe'=>35]) => TRUE
    */
    protected  static function any_defined(array $keys, array $array) : bool
    {
        $common = array_intersect($keys, array_keys($array)); 
        return !empty($common); 
    }

    /** check if $a is in range of $range[0] ~ $range[1] 
     * e.g. during(3, [2,4]) => TRUE  
    */
    protected static function during($a, $range) 
    {
        if (sizeof($range) != 2)
            throw new Exception("Illegal arguments! the second argument should be an array of size 2");

        return ($range[0] <= $a and $a <= $range[1]);
    }
    
    /** check if there is exact one day between 2 dates. Return the day if exists, or false otherwise 
     * e.g., sandwiched('03-31', '04-02') => '04-01'
    */
    protected function sandwiched(string $date1, string $date2) : string
    {
        if ( $date2 === $this->mkdate($date1, +2)){
            return $this->mkdate($date1, +1);
        }
        return false;
    }

    /** parse day definition and calculate a definite day 
    */
    private function parseDay($month,  $day) 
    {
        $cal = new KsCalendar($this->year, $month);
        if (is_integer($day))
            return $day;
        if (is_array($day))
            return $cal->w2d($day[1], $day[0]);
        if (in_array($day, ['springEquinox', 'autumnEquinox']))
            return  $this->equinox($day);
        return -1;    
    }

    /** caculate spring and autumn equinox days  
     *  valid for years between 1851 and 2150. return -1 otherwise   
    */
    private function equinox(string $holiday='springEquinox') : int
    {
        $year = $this->year;
        if (!$this->during($year, [1851, 2150])){
            return -1;
        }
        $delta = [20.8431, 23.2488]; // default for [1980, 2099]
        if (self::during($year, [1851, 1899]))
            $delta = [19.8277, 22.2588];
        if (self::during($year, [1900, 1979]))
            $delta = [20.8357, 23.2588];
        if (self::during($year, [2100, 2150]))
            $delta = [21.8510, 24.2488];
        
        if (!in_array($holiday, ['springEquinox', 'autumnEquinox'])){
            throw new Exception("Unknown holiday : " . $holiday);
        }    
        $alpha = ($holiday=='springEquinox') ? $delta[0] : $delta[1];
        return (int)floor($alpha + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    } 

    /** check if $day definition is valid for this year  */
    private function validate(array $day) : bool
    {
        $valid = true;
        if (isset($day['for'])){
            $valid = $valid && self::during($this->year, $day['for']);
        }
        if (isset($day['except'])){
            $valid = $valid && !in_array($this->year, $day['except']);
        }
        if (isset($day['in'])){
            $valid = $valid && in_array($this->year, $day['in']);
        }
        return $valid;
    }

    /** parse holiday definitions and return an array of holidays for this year */
    private function parseHolidays(array $dat_holiday): array
    {
        if ($this->year < self::HOLIDAY_SINCE){
            return [];
        }
        $holidays = [];
        $sp_holiday = null; // a holiday supplemnts coincident holidays
        $year = $this->year;
        foreach ($dat_holiday as $month=>$days){
            foreach ($days as $d){
                if ($this->validate($d)){
                    $hday = $this->parseDay($month, $d['day']);                    
                    if ($hday > 0){
                        $date = (new \DateTime)->setDate($year, $month, $hday);
                        if ($sp_holiday != null ){ // fix the candidate sp_holiday 
                            if ($sp_holiday === $date){
                                $sp_holiday->modify('+1 day');
                            }else{ 
                                $holidays[$sp_holiday->format(self::DATE_FORMAT)] = self:: SUBSTITUTE_HOLIDAY;
                                $sp_holiday = null;
                            }
                        }
                        $holidays[$date->format(self::DATE_FORMAT)] = $d['name'];
                        $cal = new KsCalendar($year, $month);
                        $wday = $cal->d2w($hday);
                        if ($wday === 0) { //  a candiate sp_hodilday 
                            $sp_holiday = (new \DateTime)->setDate($year, $month, $hday +1);
                        }                        
                    }
                }
            }
        }
        ksort($holidays);
        
        $ex_holidays =[]; // check for extra holiday sandwiched by two holidays 
        $prev_date = null;
        foreach (array_keys($holidays) as $date){
            if ($prev_date){
                $sand = $this->sandwiched($prev_date, $date);
                if ($sand){
                    $ex_holidays[$sand] = self::EXTRA_HOLIDAY;
                }
            }
            $prev_date = $date;
        }
        $holidays = array_merge($holidays, $ex_holidays);
        ksort($holidays);      
        return $holidays;
    }
}