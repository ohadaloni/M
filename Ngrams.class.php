<?php
/*------------------------------------------------------------*/
class Ngrams extends Mcontroller {
	/*------------------------------------------------------------*/
	public static function closest($str, $strings) {
		$terms = array();
		foreach( $strings as $string )
			$terms[] = array(
				'str' => $string,
				'distance' => self::strDistance($str, $string),
			);
		usort($terms, array($this, 'cmpDistance'));
		$closest = $terms[0];
		$ret = $closest['distance'] < 1 ? $terms[0]['str'] : null;
		return($ret);
	}
	/*------------------------------------------------------------*/
	public static function c14n($searchTerm) {
		$c14n = $searchTerm;
		$c14n = str_replace("-", " ", $c14n);
		$c14n = str_replace("'", " ", $c14n);
		$c14n = str_replace("\"", " ", $c14n);
		$c14n = preg_replace("/[^A-Za-z0-9 ]*/", "", $c14n);
		$c14n = str_replace("\r\n", " ", $c14n);
		$c14n = preg_replace("/[ \n\t]+/", " ", $c14n);
		$c14n = strtolower($c14n);
		$c14n = self::unCommon($c14n);
		$c14n = str_replace("    ", " ", $c14n);
		$c14n = str_replace("    ", " ", $c14n);
		$c14n = str_replace("   ", " ", $c14n);
		$c14n = str_replace("   ", " ", $c14n);
		$c14n = str_replace("  ", " ", $c14n);
		$c14n = str_replace("  ", " ", $c14n);
		if ( ! $c14n )
			return(null);

		$c14n = explode(" ", $c14n);
		$c14n = array_unique($c14n);
		$c14n = implode(" ", $c14n);
		$c14n = trim($c14n);
		$c14n = " $c14n ";
		return($c14n);
	}
	/*------------------------------*/
	private static function unCommon($str) {
		$Mmodel = new Mmodel;
		if ( ! $Mmodel->isTable("ignoreWords") )
			return($str);
		$sql = "select word from ignoreWords";
		$words = $Mmodel->getStrings($sql);
		if ( ! $words )
			return($str);
		$str = trim($str);
		$str = " $str ";
		foreach ( $words as $word ) {
			$str = str_replace(" $word ", " ", $str);
			/*	echo "word=$word, str=$str<br />\n";	*/
		}
		$str = trim($str);
		return($str);
	}
	/*------------------------------------------------------------*/
	public static function vector($s, $n = 4) {
		static $cache = array();
		if ( @$cache[$s] )
			return($cache[$s]);
		$ngrams = array();
		$s = self::c14n($s);
		$s = " ".trim($s)." ";
		$slen = strlen($s);
		for($i=0;$i<=$slen-$n;$i++) {
			$ngram = substr($s, $i, $n);
			if ( ! isset($ngrams[$ngram]) )
				$ngrams[$ngram] = 0;
			$ngrams[$ngram]++;
		}
		ksort($ngrams);
		$cache[$s] = $ngrams;
		return($ngrams);
	}
	/*------------------------------------------------------------*/
	public static function strDistance($s1, $s2, $n = 4) {
		if ( $s1 == $s2 )
			return(0);
		$s1Ngrams = self::vector($s1, $n);
		$s2Ngrams = self::vector($s2, $n);
		return(self::vectorDistance($s1Ngrams, $s2Ngrams));
	}
	/*------------------------------------------------------------*/
	public static function vectorDistance($v1, $v2) {
		$cartesianProduct = self::vectorMultiply($v1, $v2);
		if ( ! $cartesianProduct )
			return(1.0);
		$vectorDistance = 1.0 / $cartesianProduct;
		return($vectorDistance);
	}
	/*------------------------------------------------------------*/
	public static function vectorMultiply($ngrams1, $ngrams2) {
		$ret = 0;
		foreach ( $ngrams1 as $s1 => $cnt1 )
			if ( isset($ngrams2[$s1]) )
					$ret += $cnt1*$ngrams2[$s1];
		return($ret);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private static function cmpDistance($c1, $c2) {
		$ret = $c2['distance'] - $c1['distance'];
		return($ret > 0 ? 1 : ( $ret < 0 ? -1 : 0 ));
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
