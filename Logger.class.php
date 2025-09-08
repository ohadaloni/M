<?php
/*------------------------------------------------------------*/
class Logger {
	/*------------------------------------------------------------*/
	private $logFile;
	/*------------------------------------------------------------*/
	public function __construct($logFile = null) {
		if ( $logFile )
			$this->logFile = $logFile;
	}
	/*------------------------------------------------------------*/
	public function setLogFile($logFile) {
		$this->logFile = $logFile;
	}
	/*------------------------------------------------------------*/
	public function rlog($msg, $r = 100, $stamp = true) {
		if ( rand(1, 100 * 1000) > $r * 1000 )
			return;
		if ( $stamp )
			$this->log("$r/100: $msg");
		else
			$this->log($msg, false);
	}
	/*------------------------------*/
	public function log($msg, $stamp = true) {
		if ( $stamp ) {
			$now = date("Y-m-d G:i:s");
			$str = "$now: $msg\n";
		} else {
			$str = "$msg\n";
		}
		if ( $this->logFile ) {
			$logdir = dirname($this->logFile);
			if ( ! file_exists($logdir) )
				@mkdir($logdir);
			file_put_contents($this->logFile, $str, FILE_APPEND);
		}
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
