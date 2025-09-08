<?php
/*------------------------------------------------------------*/
class Mmemcache {
	/*------------------------------------------------------------*/
	private $memcache = null;
	private $version = 20;
	private $logFile;
	private $host;
	private $port;
	private $isConnected;
	/*------------------------------------------------------------*/
	public function __construct($host = null, $port = 11211) {
		$topdir = dirname(dirname(__FILE__));
		$today = date("Y-m-d");
		$this->logFile = "$topdir/logs/Mmemcache/$today.log";
		if ( $host ) {
			$this->host = $host;
		} else {
			if ( defined('M_MEMCACHE_HOST') )
				$this->host = M_MEMCACHE_HOST;
			else
				$this->host = "localhost";
		}
		$this->port = $port;

		if ( class_exists("Memcache") ) {
				$this->memcache = new Memcache;
		}
	}
	/*------------------------------------------------------------*/
	public function connect() {
		if ( ! $this->is() )
			return(false);
		if ( $this->isConnected )
			return(true);

		$before = microtime(true);
		try {
			$this->memcache->pconnect($this->host, $this->port);
			$this->memcache->setCompressThreshold(10000, 0.3);
			$after = microtime(true);
			$this->isConnected = true;
			$this->timedLog("pconnectTime", "", $after - $before);
			/*	error_log(basename(__FILE__).":".__LINE__.": connected");	*/
			return(true);
		} catch (Exception $e) {
			$printR = print_r($e, true);
			error_log("Mmemcache::connect: $printR");
			return(false);
		}
	}
	/*------------------------------------------------------------*/
	public function memcache() {
		return($this->memcache);
	}
	/*------------------------------------------------------------*/
	public function is() {
		return($this->memcache !== null);
	}
	/*------------------------------------------------------------*/
	public function get($key) {
		$before = microtime(true);
		if ( ! $this->connect() ) {
			$this->error("get: memcache not connected");
			return(false);
		}
		$vkey = $this->versionKey($key);
		$get = @$this->memcache->get($vkey);
		if ( $get === false ) {
			return(false);
		}
		if ( ! isset($get['value']) ) {
			return(null); // if the value is null, isset() returns false
		}
		$get = $this->unpack($get);
		$after = microtime(true);
		$this->timedLog("get", $key, $after - $before);
		return($get);
	}
	/*------------------------------*/
	public function set($key, $value, $ttl = null) {
		$before = microtime(true);
		$host = $this->host;
		if ( ! $this->connect() ) {
			$this->error("set: $host: no memcache");
			return(false);
		}
		if ( $ttl === null )
			$ttl = 15*60;
		$versionKey = $this->versionKey($key);
		$value = $this->pack($value);
		$setOK = $this->memcache->set($versionKey, $value, 0, $ttl);
		$after = microtime(true);
		$this->timedLog("set", $key, $after - $before);
		if ( ! $setOK )
			$this->error("set: $host: set of $key failed");
		return($setOK);
	}
	/*------------------------------------------------------------*/
	public function rawGet($key) {
		$before = microtime(true);
		if ( ! $this->connect() )
			return(false);
		$after = microtime(true);
		$this->timedLog("rawGet", $key, $after - $before);
		return($this->memcache->get($key));
	}
	/*------------------------------------------------------------*/
	public function rawSet($key, $value, $ttl) {
		$before = microtime(true);
		if ( ! $this->connect() )
			return(false);
		$after = microtime(true);
		$this->timedLog("rawSet", $key, $after - $before);
		return($this->memcache->set($key, $value, 0, $ttl));
	}
	/*------------------------------------------------------------*/
	public function increment($key, $ttl = 0) {
		$before = microtime(true);
		if ( ! $this->connect() )
			return(false);
		if ( $this->memcache->increment($key) !== false ) {
			$after = microtime(true);
			$this->timedLog("increment", $key, $after - $before);
			return(true);
		}
		return($this->memcache->set($key, 1, 0, $ttl));
	}
	/*------------------------------------------------------------*/
	public function incrementBy($key, $by, $ttl = 0) {
		$before = microtime(true);
		if ( ! $this->connect() )
			return(false);
		if ( $this->memcache->increment($key, $by) !== false ) {
			$after = microtime(true);
			$this->timedLog("incrementBy", $key, $after - $before);
			return(true);
		}
		return($this->memcache->set($key, $by, 0, $ttl));
	}
	/*------------------------------------------------------------*/
	public function msgQadd($qname, $msg) {
		$before = microtime(true);
		if ( ! $this->connect() ) {
			$this->error("Mmemcache::msgQadd: no memcache");
			return(false);
		}
		$firstIdKey = $this->msgQfirstIdKey($qname);
		$lastIdKey = $this->msgQlastIdKey($qname);
		$lastId = $this->memcache->get($lastIdKey);
		if ( ! $lastId ) {
			$this->log("Mmemcache::msgQadd: starting Q $qname");
			$this->memcache->set($firstIdKey, 1, 0, 0);
			$this->memcache->set($lastIdKey, 0, 0, 0);
		}
		$lastId = $this->memcache->increment($lastIdKey);
		if ( $lastId === false ) {
			$this->error("Mmemcache::msgQadd: cannot increment $lastIdKey");
			return(false);
		}
		$bundle = array(
			'id' => $lastId,
			'queued' => time(),
			'msg' => $msg,
		);
		$idKey = $this->msgQidKey($qname, $lastId);
		$ok = $this->memcache->set($idKey, $bundle, 0, 0);
		if ( $ok === false ) {
			$this->error("Mmemcache::msgQadd: set of $idKey failed for $lastId");
		}
		$after = microtime(true);
		$this->timedLog("msgQadd", $qname, $after - $before);
		return($ok);
	}
	/*------------------------------*/
	public function msgQlength($qname) {
		if ( ! $this->connect() )
			return(null);
		$firstIdKey = $this->msgQfirstIdKey($qname);
		$lastIdKey = $this->msgQlastIdKey($qname);
		$firstId = $this->memcache->get($firstIdKey);
		if ( ! $firstId )
			return(0);
		$lastId = $this->memcache->get($lastIdKey);
		return($lastId - $firstId + 1);
	}
	/*------------------------------*/
	// read only the entire queue
	// with bundle info
	// sampleSize:
	//	null - entire queue
	//	 -5 - last 5
	//	 5 - first 5
	public function msgQ($qname, $sampleSize = null) {
		if ( ! $this->connect() )
			return(null);
		$firstIdKey = $this->msgQfirstIdKey($qname);
		$lastIdKey = $this->msgQlastIdKey($qname);
		$firstId = $this->memcache->get($firstIdKey);
		if ( ! $firstId )
			return(null);
		$lastId = $this->memcache->get($lastIdKey);
		$msgQ = array();
		if ( $sampleSize === null || abs($sampleSize) > ($lastId - $firstId) )
			for ( $id = $firstId ; $id <= $lastId ; $id++ )
				$msgQ[] = $this->memcache->get($this->msgQIdKey($qname, $id));
		elseif ( $sampleSize < 0 )
			for ( $id = $lastId ; $id > $lastId + $sampleSize ; $id-- )
				$msgQ[] = $this->memcache->get($this->msgQIdKey($qname, $id));
		else
			for ( $id = $firstId ; $id < $firstId + $sampleSize ; $id++ )
				$msgQ[] = $this->memcache->get($this->msgQIdKey($qname, $id));
		return($msgQ);
	}
	/*------------------------------*/
	// null on empty, false on error
	public function msgQnext($qname, $delaySeconds = 0) {
		$before = microtime(true);
		if ( ! $this->connect() ) {
			$this->error("Mmemcache::msgQnext: no memcache");
			return(false);
		}
		$firstIdKey = $this->msgQfirstIdKey($qname);
		$firstId = $this->memcache->get($firstIdKey);
		if ( ! $firstId )
			return(null);
		$lastIdKey = $this->msgQLastIdKey($qname);
		$lastId = $this->memcache->get($lastIdKey);
		// the q is empty
		if ( $firstId > $lastId )
			return(null);
		// at least this item is in the queue
		$idKey = $this->msgQidKey($qname, $firstId);

		// rarely, but it happens, once in 100-5000 ids
		// rest a bit, and try again
		$tries = array(
			'1st' => 2000,
			'2nd' => 5000,
			'3rd' => 10000,
			'4th' => null,
		);
		foreach ( $tries as $tryName => $usleepIfFail ) {
			$bundle = $this->memcache->get($idKey);
			if ( $bundle !== false )
				break;
			if ( $usleepIfFail ) {
				$this->error("Mmemcache::msgQnext: $qname, id=$firstId, get($idKey) - $tryName try failed: Trying again in $usleepIfFail microseconds...");
				usleep($usleepIfFail);
			} else {
				$this->error("Mmemcache::msgQnext: $qname, id=$firstId, get($idKey) - $tryName try failed: Giving up");
				break; // redundant
			}
		}
		if ( $delaySeconds ) {
			$now = time();
			$msgTime = $bundle['queued'];
			$since = $now - $msgTime;
			if ( $since < $delaySeconds )
				return(null);
		}
		$this->memcache->delete($idKey);
		if ( $this->memcache->increment($firstIdKey) == false ) {
			$this->error("Mmemcache::msgQnext: Could not increment $firstIdKey");
		}
		if ( $bundle === false ) {
			$this->error("Mmemcache::msgQnext: GET Failed");
			return(false);
		}
		if ( @$bundle['id'] != $firstId ) {
			$json = json_encode($bundle);
			$this->error("Mmemcache::msgQnext: id Mismatch: $qname, id=$firstId, key=$idKey, bundle=$json");
			return(false);
		}
		$after = microtime(true);
		$this->timedLog("msgQnext", $qname, $after - $before);
		return($bundle['msg']);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private function versionKey($key) {
		$version = $this->version;
		$versionKey = "$version:$key";
		$versionKey = sha1($versionKey);
		return($versionKey);
	}
	/*------------------------------*/
	// memcache fails to store short strings ?
	// but not small arrays
	// pack all values into arrays
	// this did not work for a null value
	// its important to store the empty results
	// of sql queries, or else the db will be consulted again.
	// so adding to pack array to make it have some content
	/*--------------------*/
	private function pack($value) {
		return(array(
			'dummyContent' => 'The quick brown fox jumps over a lazy dog',
			'value' => $value,
		));
	}
	/*--------------------*/
	private function unpack($value) {
		return($value['value']);
	}
	/*------------------------------*/
	private function msgQidKey($qname, $id) {
		$msgQidKey = $this->versionKey("$qname:$id");
		return($msgQidKey);
	}
	/*------------------------------*/
	private function msgQfirstIdKey($qname) {
		$msgQfirstIdKey = $this->versionKey("$qname:msgQfirstIdKey");
		return($msgQfirstIdKey);
	}
	/*------------------------------*/
	private function msgQlastIdKey($qname) {
		$msgQLastIdKey = $this->versionKey("$qname:msgQLastIdKey");
		return($msgQLastIdKey);
	}
	/*------------------------------------------------------------*/
	private function error($msg) {
		$this->log("ERROR: $msg", 1);
	}
	/*------------------------------*/
	private function timedLog($name, $value, $seconds) {
		$time = sprintf("%.05lf", $seconds * 1000);
		$time = sprintf("%9s", $time);
		$timeStr = "$time milliseconds";
		$msg = sprintf("%-14s %-24s %s", $name, $timeStr, $value);
		$sampleRate = $this->sampleRate($seconds);
		$this->log($msg, $sampleRate);
	}
	/*------------------------------*/
	private function sampleRate($seconds) {
		$msecs = $seconds * 1000;
		$sampleRates = array(
			50 => 1, // greater than 50 millseconds: always.
			25 => 5,
			10 => 500,
			7 => 1000,
			5 => 10 * 1000,
			2 => 50 * 1000,
			1 => 100 * 1000,
			0 => 1000 * 1000,
		);
		foreach ( $sampleRates as $milliseconds => $sampleRate ) {
			if ( $msecs > $milliseconds )
				return($sampleRate);
		}
		return(1000 * 1000); // not reached
	}
	/*------------------------------*/
	private function log($msg, $sampleRate = 1) {
		if ( rand(1, $sampleRate) != 1 )
			return;
		$now = date("Y-m-d H:i:s");
		$str = sprintf("%18s 1/%-8d %-14s %s\n", $now, $sampleRate, $this->host, $msg);
		@chmod($this->logFile, 0777); // may be owned by apache or me
		@file_put_contents($this->logFile, $str, FILE_APPEND);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
