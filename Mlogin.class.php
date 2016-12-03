<?php
/*------------------------------------------------------------*/
class Mlogin {
	/*------------------------------------------------------------*/
	public static function login($loginId, $loginName, $loginType) {
		$expires = 2*24*60*60;
		$magic =  self::magic($loginId);
		Mview::setCookie("MloginId", $loginId, $expires);
		Mview::setCookie("MloginName", $loginName, $expires);
		Mview::setCookie("MloginType", $loginType, $expires);
		Mview::setCookie("MloginMagic", $magic, $expires);
	}
	/*------------------------------------------------------------*/
	public static function logout() {
		Mview::setCookie("MloginId", null, -1);
		Mview::setCookie("MloginName", null, -1);
		Mview::setCookie("MloginType", null, -1);
		Mview::setCookie("MloginMagic", null, -1);
		
	}
	/*------------------------------------------------------------*/
	public static function get($var) {
		return(@$_COOKIE[$var]);
	}
	/*------------------------------------------------------------*/
	public static function is() {
		$loginId = self::get("MloginId");
		if ( ! $loginId )
			return(false);
		$cookieMagic = self::get("MloginMagic");
		if ( ! $cookieMagic )
			return(false);
		$magic = self::magic($loginId);
		if ( $magic != $cookieMagic )
			return(false);
		self::stay();
		return(true);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private static function magic($loginId) {
		$agent = @$_SERVER['HTTP_USER_AGENT'];
		$ip = @$_SERVER['REMOTE_ADDRESS'];
		$mybd = "1961-02-15";
		$str = "$agent-$ip-$mybd-$loginId";
		$magic = substr(sha1($str), 10, 20);
		return($magic);
	}
	/*------------------------------------------------------------*/
	private static function stay() {
		$loginId = self::get("MloginId");
		if ( ! $loginId )
			return;
		$loginName = self::get("MloginName");
		$loginType = self::get("MloginType");
		self::login($loginId, $loginName, $loginType);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
