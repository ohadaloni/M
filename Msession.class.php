<?php
/*------------------------------------------------------------*/
class Msession {
	/*------------------------------------------------------------*/
	private static $sessionIdCookieName;
	private static $sessionId;
	private static $session;
	private static $mcKey;
	private static $expires; // both memcache session & cookie, if session is not active
	private static $Mmemcache;
	private static $version = "2";
	/*------------------------------------------------------------*/
	private static function init() {
		if ( ! @$_SERVER['SERVER_ADDR'] ) 
			return(false);
		$version = self::$version;
		self::$sessionIdCookieName = "MsessionId-V$version";
		self::$expires = 2*60*60;
		if ( ! self::$Mmemcache )
			self::$Mmemcache = new Mmemcache;
		if ( ! self::$sessionId )
			self::$sessionId = @$_COOKIE[self::$sessionIdCookieName];
		if ( ! self::$sessionId ) {
			self::$sessionId = rand(1, 1000000);
			self::setMsessionCookie();
		}
		if ( ! self::$mcKey )
			self::$mcKey = self::mcKey(self::$sessionId);
		if ( ! self::$session )
			self::$session = self::$Mmemcache->get(self::$mcKey);
		if ( ! self::$session )
			self::$session = array();
		return(true);
	}
	/*------------------------------------------------------------*/
	public static function unsetVar($n = null) {
		if ( ! self::init() )
			return;
		if ( $n )
			unset(self::$session[$n]);
		else
			self::$session = array();
		self::$Mmemcache->set(self::$mcKey, self::$session, self::$expires);
	}
	/*------------------------------------------------------------*/
	public static function set($n, $v) {
		static $num=0;
		$num++;
		if ( $num > 100 ) {
			echo "MSESSION: num=$num $n=$v\n";
			Mutils::trace();
			Mview::flushOutput();
			exit;
		}

		if ( ! self::init() )
			return;
		self::$session[$n] = $v;
		self::setMsessionCookie();
		self::$Mmemcache->set(self::$mcKey, self::$session, self::$expires);
	}
	/*------------------------------------------------------------*/
	public static function get($n = null) {
		if ( ! self::init() )
			return(null);
		if ( $n === null )
			return(self::$session);
		return(@self::$session[$n]);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private static function setMsessionCookie() {
		Mview::setCookie(self::$sessionIdCookieName, self::$sessionId, self::$expires);
	}
	/*------------------------------------------------------------*/
	private static function mcKey($sessionId) {
		$version = self::$version;
		$mcKey = "Msession:V$version-$sessionId";
		return($mcKey);
	}
	/*------------------------------------------------------------*/
	public function encode($a) {
		$json = json_encode($a);
		$encoded = base64_encode($json);
		return($encoded);
	}
	/*------------------------------*/
	public function decode($encoded) {
		$json = base64_decode($encoded);
		$decoded = json_decode($json, true);
		return($decoded);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
