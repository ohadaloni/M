<?php
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
  * Mtime - time as integers
  *
  * for example 1330 means 1:30 pm
  *
  * @package M
  */
class Mtime {
	/*------------------------------*/
	/**
	 * the time right now (on the server)
	 *
	 * @return int
	 */
	public static function now() {
		return((int)date("Gi"));
	}
	/*------------------------------*/
	/**
	 *
	 * the time right now in a given timezone can be used to localize an application
	 * independant of the location of the server
	 *
	 * @param string timezone (see date_default_timezone_set())
	 * @return int
	 */
	public static function nowInTZ($timeZone = null, $colonsNseconds = false) {
		if ( ! $timeZone )
			$timeZone = "UTC";
		$tz = date_default_timezone_get();
		date_default_timezone_set($timeZone);
		if ( $colonsNseconds )
			$ret = date("G:i:s");
		else 
			$ret = (int)date("Gi");
		date_default_timezone_set($tz);
		return($ret);
	}
	/*------------------------------*/
	/**
	 * scan or convert to this integer format
	 *
	 * @param string
	 * @return int
	 *
	 * '13:22:11' -> 1322 (13:22)
	 * '13:22' -> 1322 (13:22)
	 * '1322' -> 1322 (13:22)
	 * '13' -> 1300 (13:00)
	 * '24' -> 24 (12:24 am)
	 * '0:13' -> 13 (12:13 am)
	 */
	public static function scan($s) {
		$hms = explode(":", $s);
		$cnt = count($hms);
		if ( $cnt == 3 ) {
			list($h, $m, $s) = $hms;
			$ret = (int)$h*100+(int)$m;
		} elseif ( $cnt == 2 ) {
			list($h, $m) = $hms;
			$ret = (int)$h*100+(int)$m;
		} elseif ( $s < 24 ) {
			$ret = $s * 100;
		} else {
			$ret = (int)$s;
		}
		return($ret);
	}
	/*------------------------------*/
	/**
	 * number of minutes since midnight
	 *
	 * minute(324) = 3*60 + 24 = 204
	 *
	 * @param integer|string
	 * @return int
	 */
	public static function minutes($t) {
		$i = self::scan($t);
		$m = $i % 100;
		$h = ($i - $m)/100;
		return($h * 60 + $m);
	}
	/*------------------------------*/
	/**
	 * number of minutes bewteen two time values
	 *
	 * @param integer|string the later time in the day
	 * @param integer|string the earlier
	 * @return int
	 */

	public static function minuteDiff($t1, $t2) {
		return(self::minutes($t1) - self::minutes($t2));
	}
	/*------------------------------*/
	/**
	 * number of hours between two time values
	 *
	 * @param integer|string the later time in the day
	 * @param integer|string the earlier
	 * @return int
	 */
	 
	public static function hourDiff($t1, $t2) {
		return((int)(self::minuteDiff($t1, $t2)/60));
	}
	/*------------------------------*/
	/**
	 * format a time value as usual
	 *
	 * @param integer|string
	 * @return string
	 *
	 * 1322 -> '13:22'
	 * 7 -> '0:07'
	 */
	 
	public static function fmt($t) {
		$i = self::scan($t);
		$m = $i % 100;
		$h = ($i - $m)/100;
		return(sprintf("%d:%02d", $h, $m));
	}
	/*------------------------------*/
}
/*------------------------------------------------------------*/
