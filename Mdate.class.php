<?php
/*------------------------------------------------------------*/
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
  * Mdate - date utilities
  *
  * integer dates are in the form 20110929
  *
  * @package M
  */
/*------------------------------------------------------------*/
class Mdate {
	/*------------------------------*/
	/**
	 * compose a date from its three components
	 *
	 * @param int
	 * @param int
	 * @param int
	 * @return int
	 */
	public static function compose($year, $month, $day) {
		return($year * 10000 + $month * 100 + $day);
	}
	/*------------------------------*/
	/**
	  * separate a date to its components
	  *
	  * @param int|string the date
	  * @return array array with 3 components in this order: year, month, day
	  */
	public static function separate($date) {
		$dt = self::undash($date);
		$d = $dt % 100;
		$m = ( (int)($dt/100) ) % 100;
		$y = (int)($dt / 10000);
		return(array($y, $m, $d));
	}
	/*------------------------------*/
	/**
	 * add 1 or more days to a date
	 *
	 *	@param int|string
	 *	@param int
	 *	@return int
	 */
	public static function addDays($date, $days = 1) {
		$ut = self::unixTime($date);
		$added = $ut + $days * 24 * 60 * 60;
		$ret = self::fromUnixTime($added);
		return($ret);
	}
	/*---------------*/
	/**
	 * an alias to addDays
	 */
	public static function add($date, $days = 1) {
		return(self::addDays($date, $days));
	}
	/*--------------------*/
	/**
	 * subtract 1 or more days from a date
	 *
	 *	@param int|string
	 *	@param int
	 *	@return int
	 */
	public static function subtractDays($date, $days = 1) {
		return(self::addDays($date, $days * -1));
	}
	/*--------------------*/
	/**
	 * alias to subtractDays
	 */
	 public static function subtract($date, $days = 1) {
	 	return(self::subtractDays($date, $days));
	 }
	/*--------------------*/
	/**
	 * add 1 or more weeks to a date
	 *
	 *	@param int|string
	 *	@param int
	 *	@return int
	 */
	public static function addWeeks($date, $weeks = 1) {
		return(self::addDays($date, $weeks * 7));
	}
	/*--------------------*/
	/**
	 * subtract 1 or more weeks from a date
	 *
	 *	@param int|string
	 *	@param int
	 *	@return int
	 */
	public static function subtractWeeks($date, $weeks = 1) {
		return(self::addWeeks($date, $weeks * -1));
	}
	/*--------------------*/
	/**
	 * add 1 or more years to a date
	 *
	 *	@param int|string
	 *	@param int
	 *	@return int
	 */
	public static function addYears($date, $years = 1) {
		list($y, $m, $d) = self::separate($date);
		$y += $years;
		return(self::compose($y, $m, $d));
	}
	/*--------------------*/
	/**
	 * subtract 1 or more years from a date
	 *
	 *	@param int|string
	 *	@param int
	 *	@return int
	 */
	public static function subtractYears($date, $years = 1) {
		return(self::addYears($date, $years * -1));
	}
	/*--------------------*/
	private static function addMonth($date) {
		list($y, $m, $d) = self::separate($date);
		if ( $m < 12 )
			$m++;
		else {
			$y++;
			$m = 1;
		}
		return(self::compose($y, $m, $d));
	}
	/*--------------------*/
	private static function subtractMonth($date) {
		list($y, $m, $d) = self::separate($date);
		if ( $m > 1 )
			$m--;
		else {
			$y--;
			$m = 12;
		}
		return(self::compose($y, $m, $d));
	}
	/*--------------------*/
	/**
	 * subtract 1 or more months from a date
	 *
	 *	@param int|string
	 *	@param int
	 *	@return int
	 */
	public static function subtractMonths($date, $months = 1) {
		for($i=0;$i<$months;$i++)
			$date = self::subtractMonth($date);
		return($date);
	}
	/*--------------------*/
	/**
	 * add 1 or more months to a date
	 *
	 *	@param int|string
	 *	@param int
	 *	@return int
	 */
	public static function addMonths($date, $months = 1) {
		if ( $months < 0 )
			return(subtractMonths($date, -$months));
		for($i=0;$i<$months;$i++)
			$date = self::addMonth($date);
		return($date);
	}
	/*------------------------------*/
	/**
	 * remove any dashes from a database formatted date
	 * '2009-09-29' -> 20090929
	 * 20090929 -> 20090929
	 *
	 * @param string|int
	 * @return int
	 */
	public static function undash($str) {
		if ( ! $str || is_int($str) )
			return($str);
		return((int)str_replace('-', '', $str));
	}
	/*------------------------------*/
	/**
	 * format a date separated by dashes for the database
	 *
	 * @param int|string
	 * @return string
	 */
	public static function dash($date) {
		list($y, $m, $d) = self::separate($date);
		return(sprintf("%04d-%02d-%02d",$y,$m,$d));
	}

	/*------------------------------*/
	/**
	 * the unixTime of a date (seconds since the unix epoc 1/1/1970)
	 *
	 * @param int|string
	 * @return int
	 */
	 
	public static function unixTime($date) {
		list($y, $m, $d) = self::separate($date);
		return(mktime(12, 0, 0, $m, $d, $y));
	}
	/*------------------------------*/
	/**
	 * convert unixTime to date
	 *
	 * @param int
	 * @return int
	 */
	public static function fromUnixTime($unixTime) {
		$arr = getdate($unixTime);
		return(self::compose($arr['year'], $arr['mon'], $arr['mday']));
	}
	/*------------------------------------------------------------*/
	/**
	 * number of days beween two dates
	 *
	 * @param int|string the later date (if the return value is to be positive)
	 * @param int|string the earlier date
	 * @return int
	 */
	public static function diff($d1, $d2) {
		$t1 = self::unixTime($d1);
		$t2 = self::unixTime($d2);
		$diffSeconds = $t1 - $t2;
		$diffDays = (int)($diffSeconds / (24 * 60 * 60));
		return($diffDays);
	}
	/*------------------------------------------------------------*/
	/**
	 * format a date
	 *
	 * 
	 * keep compatibility with the jquery datepicker plugin scanning and formatting standard
	 * unless formatting with the weekday.
	 *
	 * @param int|string
	 * @param bool
	 * @return string
	 */
	public static function fmt($date, $withWeekDay = false) {
		if ( ! $date || $date == '0000-00-00' )
			return('');
		$unixTime = self::unixTime($date);
		if ( $withWeekDay )
			$fmt = "D, M d Y"; // not scannable by datepicker, only use for display
		else
			$fmt = "n/j/Y";
		return(date($fmt, $unixTime));
	}
	/*------------------------------------------------------------*/
	/**
	 * try to figure out what date is denoted by $str
	 *
	 * in addition to strtotime():
	 * 1. a unix timestamp is accepted
	 * 2. 19831107 and 20111107 are accepted
	 * 3. "t" is today's date
	 * 4. 7 means the 7'th of the current month
	 * 5. 7/11 and "7 11" means November 7'th of this year
	 * 6. "7 11 2011" is 20111107
	 * 7. "7 11 1983" is 19831107
	 * 8. "7 11 11" is 20111107
	 * 9. "7 11 83" is 19831107
	 *
	 * @param string some description of a date
	 * @return int the implied date or null
	 */
	public static function scan($str) {
		$thisYear = date("Y");
		$thisMonth = date("m");
		if ( $str == "t" )
			return(self::today());
		if ( is_numeric($str) ) {
			$i = (int)$str;
			if ( $i > 1900 * 10000 && $i < 2100 * 10000 )
				return($i);
			if ( $i > 2100 * 10000 )
				return(self::fromUnixTime($i));
		}
		$spaced = str_replace("/", " ", $str);
		$parts = explode(' ', $spaced);
		$cnt = count($parts);
		if ( $cnt == 1 && is_numeric($parts[0]) ) {
			$d = $parts[0];
			if ( $d < 1 || $d > 31 )
				return(null);
			return($thisYear * 10000 + $thisMonth * 100 + $d);
		}
		if ( $cnt == 2 && is_numeric($parts[0]) && is_numeric($parts[1]) ) {
			$m = $parts[0];
			$d = $parts[1];
			if ( $d < 1 || $d > 31 )
				return(null);
			if ( $m < 1 || $m > 12 )
				return(null);
			return($thisYear * 10000 + $m * 100 + $d);
		}
		if ( $cnt == 3 && is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2]) ) {
			$m = $parts[0];
			$d = $parts[1];
			$y = $parts[2];
			if ( $d < 1 || $d > 31 )
				return(null);
			if ( $m < 1 || $m > 12 )
				return(null);
			if ( $y < 25 )
				$y += 2000;
			elseif ( $y < 100 )
				$y += 1900;
			return($y * 10000 + $m * 100 + $d);
		}
		// if this is a formatted date, ignore the weekday prefix
		$parts = explode(' ', $str);
		if ( count($parts) == 4 ) {
			$unixTime = strtotime(substr($str, 4));
			if ( $unixTime && $unixTime != -1 )
				return(self::fromUnixTime($unixTime));
		}
		$unixTime = strtotime($str);
		if ( $unixTime && $unixTime != -1 )
			return(self::fromUnixTime($unixTime));
		return(null);
	}
	/*----------------------------------------*/
	/**
	 * @param int
	 * @return boolean
	 */
	public static function isLeap($year) {
		return( $year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0 );
	}
	/*------------------------------*/
	/**
	  * how many days are in a month
	  *
	  * @param int
	  * @param int if not given then monthLength(2) always returns 28.
	  * @return int
	  */
	public static function monthLength($m, $y = null) {
		static $monthlen = array( 0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		$m = (int)$m;
		if ( $y == null )
			return($monthlen[$m]);
		return($monthlen[$m] + ( ( $m == 2 && self::isLeap($y)) ? 1 : 0 ));
	}
	/*----------------------------------------*/
	/**
	  * List of short month names
	  *
	  * @return array of 12 strings @returned[9] == "Sep"
	  */
	public static function monthList() {
		return(array(
			1 => "Jan",
			2 => "Feb",
			3 => "Mar",
			4 => "Apr",
			5 => "May",
			6 => "Jun",
			7 => "Jul",
			8 => "Aug",
			9 => "Sep",
			10 => "Oct",
			11 => "Nov",
			12 => "Dec",
		));
	}
	/*----------*/
	public static function monthNumber($monthStr) {
		$monthStr = substr($monthStr, 0, 3);
		$monthList = self::monthList();
		foreach ( $monthList as $monthNumber => $monthStrCandidate )
			if ( stristr($monthStr, $monthStrCandidate) )
				return($monthNumber);
		return(null);
	}
	/*----------*/
	/**
	  * Short month name - monthStr(9) == "Sep"
	  *
	  * @param int
	  * @return string
	  */
	public static function monthStr($m) {
		$monthList = self::monthList();
		return(@$monthList[$m]);
	}
	/*----------------------------------------*/
	/**
	  * List of long month names
	  *
	  * @return array of 12 strings @returned[9] == "September"
	  */
	public static function monthLlist() {
		return(array(
			1 => "January",
			2 => "February",
			3 => "March",
			4 => "April",
			5 => "May",
			6 => "June",
			7 => "July",
			8 => "August",
			9 => "September",
			10 => "October",
			11 => "November",
			12 => "December",
		));
	}
	/*----------*/
	/**
	  * Long month name - monthLname(9) == "September"
	  *
	  * @param int
	  * @return string
	  */
	public static function monthLname($m) {
		$monthLlist = self::monthLlist();
		return(isset($monthLlist[(int)$m]) ? $monthLlist[(int)$m] : $m);
	}
	/*----------------------------------------*/
	public static function dayOfWeek($date) {
		$date = self::dash($date);
		$time = strtotime($date);
		$dayOfWeek = date("l", $time);
		return($dayOfWeek);
	}
	/*----------------------------------------*/
	/**
	  * List of short week day names
	  *
	  * @return array of 7 strings @returned[0] == "Sun"
	  */
	public static function weekDayList() {
		return(array(
			0 => "Sun",
			1 => "Mon",
			2 => "Tue",
			3 => "Wed",
			4 => "Thu",
			5 => "Fri",
			6 => "Sat",
		));
	}
	/*----------*/
	public static function weekDayNumber($str) {
		$weekDayList = self::weekDayList();
		$numbers = array_flip($weekDayList);
		$number = @$numbers[$str];
		if ( $number )
			return($number);
		$weekDayLlist = self::weekDayLlist();
		$numbers = array_flip($weekDayLlist);
		$number = @$numbers[$str];
		return($number);
	}
	/*----------*/
	/**
	  * Short week day name - weekDayStr(0) == "Sun"
	  *
	  * @param int
	  * @return string
	  */
	public static function weekDayStr($wday) {
		$weekDayList = self::weekDayList();
		return(@$weekDayList[$wday]);
	}
	/*----------------------------------------*/
	/**
	  * List of long week day names
	  *
	  * @return array of 7 strings @returned[0] == "Sunday"
	  */
	public static function weekDayLlist() {
		return(array(
			0 => "Sunday",
			1 => "Monday",
			2 => "Tuesday",
			3 => "Wednesday",
			4 => "Thursday",
			5 => "Friday",
			6 => "Saturday",
		));
	}
	/*----------*/
	/**
	  * Long week day name - weekDayLname(0) == "Sunday"
	  *
	  * @param int
	  * @return string
	  */
	public static function weekDayLname($wday) {
		$weekDayLlist = self::weekDayLlist();
		return(@$weekDayLlist[$wday]);
	}
	/*----------------------------------------*/
	/**
	  * the php date as returned from getdate()
	  *
	  * @param int|string as in 20090929 or "2009-09-29"
	  * @return array 
	  */
	public static function phpDate($date) {
		return(getdate(self::unixTime($date)));
	}
	/*----------------------------------------*/
	/**
	  * the week day of a date 
	  *
	  * @param int|string $date
	  * @return int 0 if date is on a Sunday, 6 if Saturday
	  */
	public static function wday($date) {
		$a = self::phpDate($date);
		return($a['wday']);
	}
	/*----------------------------------------*/
	/**
	  * today's date
	  *
	  * @return int
	  */
	public static function today() {
		return((int)date("Ymd"));
	}
	/*------------------------------*/
	/**
	  * today's date in a given timezone
	  *
	  * @param string time zone (see date_default_timezone_set())
	  * @return int
	  */
	public static function todayInTZ($timeZone = null) {
		if ( ! $timeZone )
			$timeZone = "UTC";
		$tz = date_default_timezone_get();
		date_default_timezone_set($timeZone);
		$ret = (int)date("Ymd");
		date_default_timezone_set($tz);
		return($ret);
	}
	/*------------------------------*/
	/**
	 * list dates between two dates
	 *
	 * @param int|string = $returned[0]
	 * @param int|string the last entry in the returned array
	 * @return array
	 */
	public static function dateList($starting, $ending) {
		$starting = self::undash($starting);
		$ending = self::undash($ending);
		$ret = array();
		for ( $date = $starting ; $date <= $ending ; $date = self::addDays($date, 1) )
			$ret[] = $date;
		return($ret);
	}
	/*----------------------------------------*/
	public static function datetimeScan($str) {
		if ( ! $str )
			return("");
			if ( $str == 'now()' )
				return($str);
		if ( preg_match("/[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] *[0-9]*:[0-9]*.*/", $str) )
			return($str);
		$dt = explode(' ', $str);
		if ( is_numeric($dt[0]) && $dt[0] > 19000101 && $dt[0] < 1410400000 )
			$dt[0] = self::dash($dt[0]);
		if ( preg_match("/[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]/", $dt[0]) )
			list($y, $m, $d) = explode('-', $dt[0]);
		else
			list($m, $d, $y) = explode('/', $dt[0]);
		$day = sprintf("%s-%s-%s", $y, $m, $d);
		if ( @$dt[1] ) {
			list($h, $mm) = explode(':', $dt[1]);
			$time = sprintf("%s:%s", $h, $mm);
			$value = "$day $time";
		} else {
			$value = $day;
		}
		return($value);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
