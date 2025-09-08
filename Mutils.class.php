<?php
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
  * Mutils - M utilities
  *
  * @package M
  */
/*------------------------------------------------------------*/
class Mutils {
	/*------------------------------*/
	/**
	 * like file() but without the newlines at the end of each line
	 *
	 * @param path to file
	 * @return array
	 */
	public static function Mfile($f) {
		if ( ! ( $c = @file_get_contents($f) ) )
			return(null);
		$c = str_replace("\r\n", "\n", $c);
		$ret = explode("\n", $c);
		array_pop($ret);
		return($ret);
	}
	/*------------------------------*/
	/**
	 * format a money value
	 *
	 * @param float
	 * @param string
	 * @return string
	 */
	public static function moneyFmt($v, $currencyPrefix = '$') {
		if ( ! $v )
			return("");
		if ( $v < 0.1 )
			$fmt = number_format($v, 4);
		else
			$fmt = number_format($v, 2);
		return("$currencyPrefix$fmt");
	}
	/*------------------------------------------------------------*/
	/**
	 *
	 * escape quotes for javascript
	 *
	 * @param string
	 * @return string
	 */
	public static function jsStr($str)
	{
		// if they are already escaped
		$ret = str_replace("\\'", "'", $str);
		$ret = str_replace("'", "\\'", $ret);

		$ret = str_replace("\r\n", "\n", $ret);
		$ret = str_replace("\\n", "\n", $ret);
		$ret = str_replace("\n", "\\n", $ret);

		return($ret);
	}
	/*------------------------------*/
	/**
	 * execute javascript by echoing it wrapped in html and flushing output buffers for immeadiate execution
	 *
	 * @param string
	 */
	public static function js($s) {
		echo "<script type=\"text/javascript\"> $s </script>\n";
		flush();
		ob_flush();
	}
	/*------------------------------*/
	/**
	 * set title of page using javascript
	 *
	 * @param string
	 */
	public static function jsTitle($s) {
		$s = self::jsStr($s);
		self::js("document.title = '$s'; ");
	}
	/*------------------------------*/
	/**
	 * set status line using javascript
	 *
	 * @param string
	 */
	public static function jsStatus($s) {
		$s = self::jsStr($s);
		self::js("window.status = '$s'; ");
	}
	/*------------------------------------------------------------*/
	/**
	 * like in_array but return the key
	 */
	public static function arrayIndex($a, $v) {
		foreach ( $a as $key => $value )
			if ( $value == $v ) 
				return($key);
		return(null);
	}
	/*------------------------------------------------------------*/
	/**
	 * select one column from an array,
	 * like From Mmodel->getRows() to Mmodel-> getStrings()
	 */
	public static function arrayColumn($rows, $fname) {
		$ret = array();
		foreach ( $rows as $row )
			$ret[] = $row[$fname];
		return($ret);
	}
	/*------------------------------------------------------------*/
	/*
	 * total the array or one column of the array
	 */
	public static function arraySum($a, $fname = null) {
		if ( $fname != null )
			$a = self::arrayColumn($a, $fname);
		$ret = 0;
		foreach ( $a as $n )
			$ret += (double)$n;
		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function selectList($rows, $valName, $keyName = "id") {
		$ret = array();
		foreach ( $rows as $row )
			$ret[$row[$keyName]] = $row[$valName];
		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function download($fileName, $content) {
		$filesize = strlen($content);
		header("Content-type: text/plain");
		header("Content-Disposition: attachment; filename=$fileName");
		header("Content-Length: $filesize");
		echo $content;
	}
	/*------------------------------------------------------------*/
	/*
	 * name of uploaded file info
	 * return an array with information about an uploaded file
	 * if $varName is not present then the file uploade input variable nam is assumed to be "file"
	 */
	public static function uploadedFileInfo($varName = "file") {
		if ( ! isset($_REQUEST[$varName]) && ! isset($_FILES[$varName]) ) {
			Mview::error("$varName not set in _REQUEST nor _FILES");
			Mview::print_r($_FILES, "_FILES", __FILE__, __LINE__);
			Mview::print_r($_REQUEST, "_REQUEST", __FILE__, __LINE__);
			return(null);
		}

		if ( isset($_REQUEST[$varName]) )
			$fileInfo = $_REQUEST[$varName];
		else
			$fileInfo = $_FILES[$varName];

		if ( $fileInfo['error'] || $fileInfo['name'] == "" ) {
			Mview::error("Error loading file");
			Mview::print_r($fileInfo, "fileInfo", __FILE__, __LINE__);
			return(null);
		}

		$ret = array(
			'name' => $fileInfo['name'],
			'file' => $fileInfo['tmp_name'],
			'size' => $fileInfo['size'],
			'type' => $fileInfo['type'],
		);

		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function isUploadedFile($varName = "file") {
		return( isset($_REQUEST[$varName]) || isset($_FILES[$varName]) );
	}
	/*------------------------------------------------------------*/
	/*
	 * content of uploaded file
	 * if $varName is not present then the file uploade input variable nam is assumed to be "file"
	 */
	public static function uploadedFileContent($varName = "file") {
		if ( ! isset($_REQUEST[$varName]) && ! isset($_FILES[$varName]) ) {
			Mview::error("$varName not set in _REQUEST nor _FILES");
			Mview::print_r($_FILES, "_FILES", __FILE__, __LINE__);
			Mview::print_r($_REQUEST, "_REQUEST", __FILE__, __LINE__);
			return(null);
		}

		if ( isset($_REQUEST[$varName]) )
			$fileInfo = $_REQUEST[$varName];
		else
			$fileInfo = $_FILES[$varName];

		if ( $fileInfo['error'] || $fileInfo['name'] == "" ) {
			Mview::error("Error loading file");
			Mview::print_r($fileInfo, "fileInfo", __FILE__, __LINE__);
			return(null);
		}

		$name = $fileInfo['name'];
		$file = $fileInfo['tmp_name'];
		$size = $fileInfo['size'];
		$type = $fileInfo['type'];

		$ret = file_get_contents($file);

		$strlen = strlen($ret);
		if ( $strlen != $size )
			Mview::error("size reported: $size, got: $strlen");

		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function extractGetValue($name, $url) {
		$start = strstr($url, "&$name=");
		if ( ! $start )
			$start = strstr($url, "?$name=");
		if ( ! $start )
			return(null);
		$parts = explode('&', $start);
		$nv = explode('=', $parts[0]);
		if ( count($nv) != 2 )
			return(null);
		return($nv[1]);
	}
	/*------------------------------------------------------------*/
	public static function isupper($s) {
		return(strtoupper($s) == $s);
	}
	/*------------------------------------------------------------*/
	public static function islower($s) {
		return(strtolower($s) == $s);
	}
	/*------------------------------------------------------------*/
	public static function arrayIsUntitled($a) {
		$i=0;
		foreach($a as $key => $value )
			if ( $i == $key )
				$i++;
			else
				return(false);
		return(true);
	}
	/*------------------------------------------------------------*/
	public static function xml2array($xml) {
		$obj = simplexml_load_string($xml);
		$json = json_encode($obj);
		$array = json_decode($json, true);
		return($array);
	}
	/*------------------------------------------------------------*/
	public static function array2xml($name, $a, $nestLevel = 0, $header = true) {
		if ( $header ) {
			$header = self::$xmlHeader;
			$body = self::array2xml($name, $a, 0, false);
			return("$header$body");
		}
		$tabs = "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
		if ( ! is_array($a) )
			return(substr($tabs, 0, $nestLevel)."<$name>".htmlspecialchars($a)."</$name>\n");
		$nameIsPlural = substr($name, -1) == 's';
		$nameSingular = $nameIsPlural ? substr($name, 0, -1) : $name;
		if ( strlen($name) > 5 && substr($name, -3) == 'ies' )
			$nameSingular = substr($name, 0, -3)."y";
		if ( $nameIsPlural )
			$namePlural = $name;
		else {
			if ( strlen($name) > 5 && substr($name, -1) == 'y' )
				$namePlural = substr($name, 0, -1)."ies";
			else
				$namePlural = $name."s";
		}
		$untitled = self::arrayIsUntitled($a);
		$title = $untitled ? $namePlural : $name;
		$ret = substr($tabs, 0, $nestLevel)."<$title>\n";
		foreach ( $a as $key => $value )
			$ret .= self::array2xml(is_numeric($key) ? $nameSingular : $key, $value, $nestLevel + 1, false);
		$ret .= substr($tabs, 0, $nestLevel)."</$title>\n";
		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function array_column($a, $column) {
		$ret = array();
		foreach ( $a as $row )
			$ret[] = $row[$column];
		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function isAjax() {
		$http_x_requested_with = @$_SERVER['HTTP_X_REQUESTED_WITH'];
		$isAjax =
			$http_x_requested_with &&
			strtolower($http_x_requested_with) == "xmlhttprequest";
		return($isAjax);
	}
	/*------------------------------------------------------------*/
	public static function reIndexBy($a, $by) {
		if ( ! $a )
			return($a);
		if ( ! is_array($a) ) {
			Mview::print_r($a);
			return($a);
		}
		$ret = array();
		foreach ( $a as $row )
			$ret[$row[$by]] = $row;
		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function trace($forceText = false) {
		$isHttp = @$_SERVER['SERVER_ADDR'] != null;
		$stack = debug_backtrace(false);
		$rows = $lines = array();
		foreach ( $stack as $item ) {
			$path = $item['file'];
			$fileName = basename($path);
			$line = $item['line'];
			$class = $item['class'];
			$function = $item['function'];
			if ( $isHttp )
				$rows[] = array(
					'fileName' => $fileName,
					'line' => $line,
					'class' => $class,
					'function' => $function,
					'path' => $path,
				);
			else
				$lines[] = "$class:$function:$line";
		}
		if ( $isHttp && ! $forceText )
			Mview::showRows($rows);
		else
			echo "<pre>".implode("\n", $lines)."\n</pre>\n";
	}
	/*------------------------------------------------------------*/
	public static function parentDir($path) {
		$parts = explode("/", $path);
		array_pop($parts);
		$parentDir = implode("/", $parts);
		return($parentDir);
	}
	/*------------------------------------------------------------*/
	public static function listDir($path, $ext = null) {
		$files = array();
		$dir = opendir($path);
		if ( ! $dir ) {
			Mview::error("$path: Can not open");
			return(null);
		}
		while($file = readdir($dir)) {
			if ( $file == '.' || $file == '..' )
				continue;
			if ( ! $ext ) {
				$files[] = $file;
				continue;
			}
			$fileParts = explode(".", $file);
			$fileExt = end($fileParts);
			if ( $fileExt == $ext )
				$files[] = $file;
		}
		closedir($dir);
		return($files);
	}
	/*------------------------------------------------------------*/
	public static function object2array($obj) {
		if ( is_array($obj) )
			return($obj);
		if ( ! @get_class($obj) )
			return(null);
		$ret = array();
		foreach ( $obj as $n => $v )
			$ret[$n] = $v;
		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function numberFormat($number, $decimals = 2, $sign = null, $withDecimalZeros = null) {
		$str = number_format($number, $decimals);
		if ( ! $withDecimalZeros && $str < 0.00001 && $str > -0.00001 )
			return("");
		if ( $sign === '$' )
			$str = '$'.$str;
		elseif ( $sign === '%' )
			$str = "$str%";
		elseif ( $sign === 'nis' )
			$str = "$str ₪";
		return($str);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private static $env = null;
	/*------------------------------*/
	public static function setenv($nn, $v = null) {
		if ( is_array($nn) ) {
			foreach ( $nn as $n => $v )
				self::$env[$n] = $v;
		} else
			self::$env[$nn] = $v;
	}
	/*------------------------------*/
	public static function getenv($n = null) {
		if ( $n === null )
			return(self::$env);
		return(@self::$env[$n]);
	}
	/*------------------------------------------------------------*/
	// from:
	// http://krasimirtsonev.com/blog/article/php--find-links-in-a-string-and-replace-them-with-actual-html-link-tags
	//
	// if
	//		{$row.story|nl2br|makeLinks}
	// if makeLinks sticks a br in the middle of the link title
	// try
	//		{$row.story|makeLinks|nl2br}
	public static function makeLinks($str, $justAnArrow = null) {
			$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
			$urls = array();
			$urlsToReplace = array();
			if(preg_match_all($reg_exUrl, $str, $urls)) {
					$numOfMatches = count($urls[0]);
					$numOfUrlsToReplace = 0;
					for($i=0; $i<$numOfMatches; $i++) {
							$alreadyAdded = false;
							$numOfUrlsToReplace = count($urlsToReplace);
							for($j=0; $j<$numOfUrlsToReplace; $j++) {
									if($urlsToReplace[$j] == $urls[0][$i]) {
											$alreadyAdded = true;
									}
							}
							if(!$alreadyAdded) {
									array_push($urlsToReplace, $urls[0][$i]);
							}
					}
					$numOfUrlsToReplace = count($urlsToReplace);
					for($i=0; $i<$numOfUrlsToReplace; $i++) {
							if ( $justAnArrow )
								$str = str_replace($urlsToReplace[$i], "<a target=\"_blank\" href=\"".$urlsToReplace[$i]."\">==GoToUrl==</a> ", $str);
							else
								$str = str_replace($urlsToReplace[$i], "<a target=\"_blank\" href=\"".$urlsToReplace[$i]."\">".$urlsToReplace[$i]."</a> ", $str);
					}
					return $str;
			} else {
					return $str;
			}
	}
	/*------------------------------------------------------------*/
	// from https://github.com/kwi-dk/UrlLinker
	// same purpose as makeLinks above
	public static function linkUrls($text)
	{
		$rexScheme    = 'https?://';
		$rexDomain    = '(?:[-a-zA-Z0-9\x7f-\xff]{1,63}\.)+[a-zA-Z\x7f-\xff][-a-zA-Z0-9\x7f-\xff]{1,62}';
		$rexIp        = '(?:[1-9][0-9]{0,2}\.|0\.){3}(?:[1-9][0-9]{0,2}|0)';
		$rexPort      = '(:[0-9]{1,5})?';
		$rexPath      = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
		$rexQuery     = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
		$rexFragment  = '(#[!$-/0-9?:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
		$rexUsername  = '[^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64}';
		$rexPassword  = $rexUsername; // allow the same characters as in the username
		$rexUrl       = "($rexScheme)?(?:($rexUsername)(:$rexPassword)?@)?($rexDomain|$rexIp)($rexPort$rexPath$rexQuery$rexFragment)";
		$rexTrailPunct= "[)'?.!,;:]"; // valid URL characters which are not part of the URL if they appear at the very end
		$rexNonUrl    = "[^-_#$+.!*%'(),;/?:@=&a-zA-Z0-9\x7f-\xff]"; // characters that should never appear in a URL
		$rexUrlLinker = "{\\b$rexUrl(?=$rexTrailPunct*($rexNonUrl|$))}";

		$html = '';

		$position = 0;
		while (preg_match($rexUrlLinker, $text, $match, PREG_OFFSET_CAPTURE, $position))
		{
			list($url, $urlPosition) = $match[0];

			// Add the text leading up to the URL.
			$html .= htmlspecialchars(substr($text, $position, $urlPosition - $position));

			$scheme      = $match[1][0];
			$username    = $match[2][0];
			$password    = $match[3][0];
			$domain      = $match[4][0];
			$afterDomain = $match[5][0]; // everything following the domain
			$port        = $match[6][0];
			$path        = $match[7][0];

			// Do not permit implicit scheme if a password is specified, as
			// this causes too many errors (e.g. "my email:foo@example.org").
			if (!$scheme && $password)
			{
				$html .= htmlspecialchars($username);

				// Continue text parsing at the ':' following the "username".
				$position = $urlPosition + strlen($username);
				continue;
			}

			if (!$scheme && $username && !$password && !$afterDomain)
			{
				// Looks like an email address.
				$completeUrl = "mailto:$url";
				$linkText = $url;
			}
			else
			{
				// Prepend http:// if no scheme is specified
				$completeUrl = $scheme ? $url : "http://$url";
				$linkText = "$domain$port$path";
			}

			$linkHtml = '<a target="_blank" href="' . htmlspecialchars($completeUrl) . '">'
				. htmlspecialchars($linkText)
				. '</a>';

			// Cheap e-mail obfuscation to trick the dumbest mail harvesters.
			$linkHtml = str_replace('@', '&#64;', $linkHtml);

			// Add the hyperlink.
			$html .= $linkHtml;

			// Continue text parsing from after the URL.
			$position = $urlPosition + strlen($url);
		}

		// Add the remainder of the text.
		$html .= htmlspecialchars(substr($text, $position));
		return $html;
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	public static function mime($ext) {
		$mimes = array(
			'txt' => "text/plain",
			'png' => "image/png",
			'jpg' => "image/jpeg",
			'jpeg' => "image/jpeg",
			'pdf' => "application/pdf",
			'doc' => "application/msword",
		);
		$ext = strtolower($ext);
		$mime = @$mimes[$ext];
		return($mime);
	}
	/*------------------------------------------------------------*/
	public static function ext($fileName) {
		$ext = substr(strrchr($fileName, '.'), 1);
		return($ext);
	}
	/*------------------------------------------------------------*/
	public static function terse($str, $numWords = 7) {
		$words = explode(" ", $str);
		$cnt = count($words);
		if ( $cnt <= $numWords )
			return($str);
		$words = array_slice($words, 0, $numWords);
		$str = implode(" ", $words)." ...";
		return($str);
	}
	/*------------------------------------------------------------*/
	public static function urlWorks($url) {
		$mc = new Mcontroller;
		$mkey = "urlWorks-$url";
		$urlWorks = $mc->Mmemcache->get($mkey);
        if ( $urlWorks )
			return($urlWorks == "works");
		$headers = @get_headers($url);
		$ok = strstr(@$headers[0], "200");
		$mc->Mmemcache->set($mkey, $ok ? "works" : "dosnt", 3600);
		return($ok);
	}
	/*------------------------------------------------------------*/
	public static function memcacheTest() {
		$mc = new Mcontroller;
		$randKey = "randKey-".rand(1,100000);
		$randValue = rand(1,100000);
		$ttl = rand(1, 4);
		$setRet = $mc->Mmemcache->set($randKey, $randValue, $ttl);
		if ( ! $setRet ) {
			$mc->Mview->error("memcacheTest: failed to set");
			return;
		}
		$get = $mc->Mmemcache->get($randKey);
		if ( $get == $randValue )
			$mc->Mview->msg("memcacheTest: works");
		else
			$mc->Mview->error("memcacheTest: failed to get after set");
	}
	/*------------------------------*/
	public static function memcacheStats() {
		$mc = new Mcontroller;
		$mc->Mmemcache->connect();
		$memcache = $mc->Mmemcache->memcache();
		$serverStatus = $memcache->getServerStatus("localhost");
		$stats = $memcache->getStats();
		$stats['time'] = date("Y-m-d G:i:s", $stats['time']);
		$upTotalSeconds = $stats['uptime'];
		$upSeconds = $upTotalSeconds % 60;
		$upTotalMinutes = ($upTotalSeconds - $upSeconds)/60;
		$upMinutes = $upTotalMinutes % 60;
		$upTotalHours = ($upTotalMinutes - $upMinutes)/60;
		$upHours = $upTotalHours % 24;
		$upDays = ( $upTotalHours - $upHours ) / 24;
		$upDaysS = $upDays == 1 ? "" : "s";
		$stats['serverStatus'] = $serverStatus;
		$stats['uptimeStr'] = sprintf(" %d day$upDaysS & %d:%02d:%02d h/m/s", $upDays, $upHours, $upMinutes, $upSeconds);
		$mc->Mview->showTpl("Mmemcache/memcacheStats.tpl", array(
			'stats' => $stats,
			'serverStatus' => $serverStatus,
		));
	}
	/*------------------------------------------------------------*/
	public static function embeddings($text) {
		if ( ! defined('TAS_DIR') )
			define('TAS_DIR', "/var/www/vhosts/tas.theora.com");
		require_once(TAS_DIR."/conf/jinaApiKey.php");
		$shortText = substr($text, 0, 30);
		error_log("embeddings: getting for '$shortText...'");
		$mc = new Mcurl();
		$mc->init();
		$apiKey = JINA_API_KEY;
		$mc->setHeaders(array(
			"Authorization: Bearer $apiKey",
		));
		$url = 'https://api.jina.ai/v1/embeddings';
		$body = array(
			'model' => "jina-embeddings-v3",
			'task' => "text-matching",
			'input' => array(
				$text,
			),

		);
		$response = $mc->post($url,  $body);
		$lastHttpCode = $mc->lastHttpCode();
		if ( $lastHttpCode != 200 ) {
			error_log("embeddings: lastHttpCode=$lastHttpCode, response='$response'");
			return(null);
		}
		$embeddings = @$response['data'][0]['embedding'];
		if ( ! $embeddings ) {
			error_log("embeddings: no embeddings for $text");
		}
		return($embeddings);
	}
	/*------------------------------------------------------------*/
	public static function distanceSquared($v1, $v2) {
		$cnt1 = count($v1);
		$cnt2 = count($v2);
		if ($cnt1 != $cnt2 ) {
			error_log("distanceSquared: Invalid Arguments: Vectors must be of the same length: $cnt1 != $cnt2");
			return(1000000.0);
		}

		$distanceSquared = 0.0;
		for ($i = 0; $i < $cnt1 ; $i++) {
			$diff = $v1[$i] - $v2[$i];
			$squared = $diff*$diff;
			$distanceSquared += $squared;
		}
		return($distanceSquared);
	}
	/*------------------------------------------------------------*/
	// keep this at bottom - vim misinterprets the rest of the file
	private static $xmlHeader = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
