<?php
/**
 * A library of common utility methods and functions.
 */

class Utils
{
    /**
     * Converts a number into a more currency apealing format. This method makes
     * use of the number_format() function which comes as part of the php
     * standard library.
     *
     * @param float $number
     * @return string
     */
    public static function currency($number)
    {
        return number_format(Utils::round($number,2),2,'.',',');
    }

    public static function deCommalize($number)
    {
        return str_replace(',', '', $number);
    }
    
    /**
     * Converts a string time representation of the format DD/MM/YYY [HH:MI:SS]
     * into a unix timestamp. The conversion is done with the strtotime()
     * function which comes as part of the php standard library.
     *
     * @param string $string The date
     * @param boolean $hasTime When specified, the time components are also added
     * @return int
     */
    public static function stringToTime($string, $hasTime = false)
    {
        if(preg_match("/(\d{2})\/(\d{2})\/(\d{4})(\w\d{2}:\d{2}:\d{2})?/", $string) == 0) return false;
        $dateComponents = explode(" ", $string);

        $decomposeDate = explode("/", $dateComponents[0]);
        $decomposeTime = array();

        if($hasTime === true)
        {
            $decomposeTime = explode(":", $dateComponents[1]);
        }

        return
        strtotime("{$decomposeDate[2]}-{$decomposeDate[1]}-{$decomposeDate[0]}") +
        ($hasTime === true ? ($decomposeTime[0] * 3600 + $decomposeTime[1] * 60 + $decomposeTime[2]) : 0);
    }

    /**
     * Converts a string time representation of the format DD/MM/YYY [HH:MI:SS]
     * into an oracle date format DD-MON-YY.
     *
     * @param string $string The date
     * @param boolean $hasTime When specified, the time components are also added
     * @todo Allow the returning of the time values too.
     * @return string
     */
    public static function stringToDatabaseDate($string, $hasTime = false)
    {
        $timestamp = Utils::stringToTime($string, $hasTime);
        return date("Y-m-d", $timestamp);
    }

    /**
     * Gives the amount in words of a number in terms of currencies. Hence
     * passing a value of 1250.20 into this function would generate something
     * like "One-Thousand, Two-Hundred and Fifty Ghana Cedis, Twenty Pesewas.
     *
     * @param double $number The number to be converted.
     * @todo Make it possible for this method to be called in different currencies.
     */
    public static function getCurrencyString($number)
    {
        $numbers = explode(".", $number);
        $wholePart = $numbers[0];
        //$fractionPart = round($numbers[1]/pow(10,strlen($numbers[1]))*100);
        $fractionPart = round(bcmul(bcdiv($numbers[1], bcpow(10, strlen($numbers[1]))), 100));//$numbers[1]/pow(10,strlen($numbers[1]))*100);
        return ucwords(Utils::convert_number($wholePart) . " Ghana Cedis, " . Utils::convert_number($fractionPart) . " Pesewas");
    }
    
    /**
     * Generates a textual representation of a number by converting the value
     * of the number into words.
     * @param int $number
     * @return string
     */
    public static function convert_number($number)
    {
        /*require_once "Numbers/Words.php";

        $words = new Numbers_Words();
        return $words->toWords($number);*/

        if (($number < 0) || ($number > 9999999999))
        {
            throw new Exception("Number is out of range");
        }

        $Bn = floor($number / 1000000000);  /* Billions (tera) */
        $number -= $Bn * 1000000000;
        $Gn = floor($number / 1000000);  /* Millions (giga) */
        $number -= $Gn * 1000000;
        $kn = floor($number / 1000);     /* Thousands (kilo) */
        $number -= $kn * 1000;
        $Hn = floor($number / 100);      /* Hundreds (hecto) */
        $number -= $Hn * 100;
        $Dn = floor($number / 10);       /* Tens (deca) */
        $n = $number % 10;               /* Ones */

        $res = "";

        if ($Bn)
        {
            $res .= Utils::convert_number($Bn) . " Billion";
        }

        if ($Gn)
        {
            $res .= Utils::convert_number($Gn) . " Million";
        }

        if ($kn)
        {
            $res .= (empty($res) ? "" : " ") .
            Utils::convert_number($kn) . " Thousand";
        }

        if ($Hn)
        {
            $res .= (empty($res) ? "" : " ") .
            Utils::convert_number($Hn) . " Hundred";
        }

        $ones = array("", "One", "Two", "Three", "Four", "Five", "Six",
        "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen",
        "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen",
        "Nineteen");
        $tens = array("", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty",
        "Seventy", "Eighty", "Ninety");

        if ($Dn || $n)
        {
            if (!empty($res))
            {
                $res .= " and ";
            }

            if ($Dn < 2)
            {
                $res .= $ones[$Dn * 10 + $n];
            }
            else
            {
                $res .= $tens[$Dn];
                if ($n)
                {
                    $res .= "-" . $ones[$n];
                }
            }
        }

        if (empty($res))
        {
            $res = "zero";
        }

        return $res;
    }

    public static function isWeekend($date = null)
    {
    	$date = $date === null ? time() : $date;
    	$day = date("N", $date);
    	return $day == 6 || $day == 7;
    }

    public static function isWorkingDay($date = null)
    {
    	$date = $date === null ? time() : $date;
    	if(Utils::isWeekend($date))
    	{
    		return false;
    	}
    	else
    	{
            $holidaysModel = Model::load("system.holidays");
            $holidaysModel->queryResolve = false;
            $holidays = $holidaysModel->get();
            foreach($holidays as $holiday)
            {
                if($holiday["holiday_date"] == $date) return false;
            }
    	}
    	return true;
    }

    public static function getNextWorkingDay($numberOfDays,$date=null, $previous = false)
    {
        $nextWorkingDay = 0;
        if($date==null) $date = strtotime(date("Y-m-d", Utils::time()));
        $holidaysModel = Model::load("system.holidays");
        $holidaysModel->queryResolve = false;
        $holidays = $holidaysModel->get();
        $daysCounted = 0;
        $factor = $previous === true ? -1 : 1;

        do
        {
            $nextWorkingDay+=$factor;
            $daysCounted++;

            if(date("N", $date+($nextWorkingDay*86400))==6)
            {
                $nextWorkingDay += ($factor == -1 ? -1 : 2);
            }
            else if(date("N",$date+($nextWorkingDay*86400))==7)
            {
                $nextWorkingDay += ($factor == -1 ? -2 : 1);
            }

            foreach($holidays as $holiday)
            {
                if($holiday["holiday_date"] == $date + ($nextWorkingDay*86400))
                {
                    $nextWorkingDay += $factor;
                }
            }
        }
        while($daysCounted < $numberOfDays);
        return $date + ($nextWorkingDay*86400);
    }

    public static function time()
    {
    	global $forcedTime;
    	return $forcedTime != null ? $forcedTime : time();
    }

    public static function round( $value, $precision=0 )
    {
        // If the precision is 0 then default the factor to 1, otherwise
        // use 10^$precision. This effectively shifts the decimal point to the
        // right.
        if ( $precision == 0 ) {
            $precisionFactor = 1;
        }
        else {
            $precisionFactor = pow( 10, $precision );
        }

        // ceil doesn't have any notion of precision, so by multiplying by
        // the right factor and then dividing by the same factor we
        // emulate a precision
        return round( $value * $precisionFactor )/$precisionFactor;
    }
    
    /**
     * 
     */
    public static function sentenceTime($time, $options = null)
    {
        $elapsed = time() - $time;

        if($elapsed < 10)
        {
            $englishDate = 'now';
        }
        elseif($elapsed >= 10 && $elapsed < 60)
        {
            $englishDate = "$elapsed seconds";
        }
        elseif($elapsed >= 60 && $elapsed < 3600)
        {
            $minutes = floor($elapsed / 60);
            $englishDate = "$minutes minutes";
        }
        elseif($elapsed >= 3600 && $elapsed < 86400)
        {
            $hours = floor($elapsed / 3600);
            $englishDate = "$hours hour" . ($hours > 1 ? 's' : '');
        }
        elseif($elapsed >= 86400 && $elapsed < 172800)
        {
            $englishDate = "yesterday";
        }
        elseif($elapsed >= 172800 && $elapsed < 604800)
        {
            $days = floor($elapsed / 86400);
            $englishDate = "$days days";
        }
        elseif($elapsed >= 604800 && $elapsed < 2419200)
        {
            $weeks = floor($elapsed / 604800);
            $englishDate = "$weeks weeks";
        }
        elseif($elapsed >= 2419200 && $elapsed < 31536000)
        {
            $months = floor($elapsed / 2419200);
            $englishDate = "$months months";
        }
        elseif($elapsed >= 31536000)
        {
            $years = floor($elapsed / 31536000);
            $englishDate = "$years years";
        }

        switch($options['elaborate_with'])
        {
            case 'ago':
                if($englishDate != 'now' && $englishDate != 'yesterday')
                {
                    $englishDate .= ' ago';
                }
                break;
        }

        return $englishDate;
    }    
    
    public static function getSubTitle($option, $startDate, $endDate)
    {
        $begin = date("jS F, Y", Utils::stringToTime($startDate));
        $end = date("jS F, Y", Utils::stringToTime($endDate));
        
        switch($option)
        {
            case "EQUALS":
                $return = "on $begin";
                break;
            case "GREATER":
                $return = "after $begin";
                break;
            case "LESS":
                $return = "before $begin";
                break;
            case "BETWEEN":
            default:
                $return = "between $begin and $end";
                break;
        }
        
        return $return;
    }
    
    public static function counter($array)
    {
        foreach($array as $key => $value)
        {
            if(empty($value))
            {
                unset($array[$key]);
            }
        }
        
        return count($array);
    }
    
    public static function ordinal($number) 
    {
        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        
        if ((($number % 100) >= 11) && (($number%100) <= 13)) {
            return $number. 'th';
        } else {
            return $number. $ends[$number % 10];
        }
    }
    public static function specificKeys($array, $pattern) 
    {
        $return = [];
        foreach($array as $key => $value) {
            if(strpos($key, $pattern) !== false) {
                $return[$key] = $value;
            }
        }
        
        return $return;
    }
}
