<?php
/*------------------------------------------------------------*/
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
class Mcal {
	/*------------------------------*/
	/**
	  * create a monthly calender for the given month
	  *
	  * return a 2 dimensional array of weeks, matching an ordinary visual layout:<br >
	  * the returned array has 4-6 entries.
	  * (4 if its a non-leap February starting on a Sunday).<br />
	  * In each week keys 0-6 (Sunday-Saturday) have the day of month for the week.<br >
	  * Empty entries have a null value, so each week is an array with exactly 7 entries.
	  *
	  * @param int $year
	  * @param int $month
	  * @return array
	  */
	public static function cal($year, $month) {
		$month = (int)$month; // remove leading zeros
		$cal = array();
		$mdays = Mdate::monthLength($month, $year);
		$zeroDay = $year * 10000 + $month * 100;
		$wd = Mdate::wday($zeroDay+1);

		for($day=1,$w=0;$day<=$mdays;$day++) {
			if ( $day != 1 && $wd == 0 )
				$w++;

			$cal[$w][$wd] = $day;

			$wd = ($wd+1) % 7;
		}

		foreach ( $cal as $wkey => $week )
			for($wd=0;$wd<7;$wd++)
				if ( ! isset($week[$wd]) )
					$cal[$wkey][$wd] = null;

		foreach ( $cal as $wkey => $week )
			ksort($cal[$wkey]);

		return($cal);
	}
	/*----------------------------------------*/
}
/*------------------------------------------------------------*/
