<?php
/*------------------------------------------------------------*/
class Perf {
	/*------------------------------------------------------------*/
	private static $labels = array();
	/*------------------------------------------------------------*/
	public static function start($label) {
		$t = self::time();
		if ( ! isset(self::$labels[$label]) ) {
			self::$labels[$label] = array(
				'times' => 0,
				'elapsed' => 0.0,
				'started' => null,
			);
		}
		self::$labels[$label]['started'] = $t;
		self::$labels[$label]['times']++;
	}
	/*------------------------------------------------------------*/
	public static function pause($label) {
		$t = self::time();
		$started = @self::$labels[$label]['started'];
		self::$labels[$label]['started'] = null;
		if ( ! $started ) {
			echo "No starting point for '$label'<br />\n";
			return;
		}
		$elapsed = $t - $started;
		self::$labels[$label]['elapsed'] += $elapsed;
	}
	/*------------------------------------------------------------*/
	public static function stop($label) {
		self::pause($label);
		$stat = self::pull($label);
		self::reset($label);
		return($stat);
	}
	/*------------------------------------------------------------*/
	public static function restart($label) {
		self::reset($label);
		self::start($label);
	}
	/*------------------------------------------------------------*/
	public static function pull($label) {
		$stat = self::stats($label);
		self::reset($label);
		return($stat);
	}
	/*------------------------------------------------------------*/
	public static function reset($label = null) {
		if ( $label )
			unset(self::$labels[$label]);
		else
			self::$labels = array();
	}
	/*------------------------------------------------------------*/
	public static function stats($only = null) {
		$ret = array();
		foreach ( self::$labels as $label => $data ) {
			$elapsed = $data['elapsed'];
			if ( $data['started'] ) {
				$t = self::time();
				$elapsedLately = $t - $data['started'];
				$elapsed += $elapsedLately;
				$elapsed .= "...";
			}
			$ret[] = array(
				'label' => $label,
				'seconds' => round($elapsed, 10),
				'times' => $data['times'],
			);
		}
		if ( $only ) {
			foreach ( $ret as $stat )
				if ( $stat['label'] == $only )
					return($stat);
				return(null);
		}
		/*	Mview::print_r($ret, "ret", basename(__FILE__), __LINE__, null, false);	*/
		usort($ret, array('Perf', 'cmpSeconds'));
		return($ret);
	}
	/*------------------------------*/
	private static function cmpSeconds($a, $b) {
		$val = $b['seconds'] - $a['seconds'];
		$ret = $val > 0 ? 1 : ( $val < 0 ? -1 : 0 );
		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function time() {
		list($usec, $sec) = explode(" ", microtime());
		return((double)$usec + (double)$sec);
	}
	/*------------------------------------------------------------*/
	public static function space() {
		$bytes = memory_get_usage();
		$sbytes = memory_get_usage(true);
		$use = $bytes;
		$M = 1024*1024;
		$MB = $use / $M;
		$round = round($MB, 3);
		return($round);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
