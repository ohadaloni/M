<?php
/*------------------------------------------------------------*/
class Ngrams extends Mcontroller {
	/*------------------------------------------------------------*/
	public function closest($str, $strings) {
		$terms = array();
		foreach( $strings as $string )
			$terms[] = array(
				'str' => $string,
				'distance' => $this->strDistance($str, $string),
			);
		usort($terms, array($this, 'cmpDistance'));
		$closest = $terms[0];
		$ret = $closest['distance'] < 1 ? $terms[0]['str'] : null;
		return($ret);
	}
	/*------------------------------------------------------------*/
	public function c14n($searchTerm) {
		$c14n = $searchTerm;
		$c14n = str_replace("-", " ", $c14n);
		$c14n = str_replace("'", " ", $c14n);
		$c14n = str_replace("\"", " ", $c14n);
		$c14n = preg_replace("/[^A-Za-z0-9 ]*/", "", $c14n);
		$c14n = str_replace("\r\n", " ", $c14n);
		$c14n = preg_replace("/[ \n\t]+/", " ", $c14n);
		$c14n = strtolower($c14n);
		$c14n = $this->unCommon($c14n);
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
	private function unCommon($str) {
		if ( ! $this->Mmodel->isTable("ignoreWords") )
			return($str);
		$words = $this->Mmodel->getStrings("select word from ignoreWords order by 1", 12*60*60);
		if ( ! $words )
			return($str);
		$str = trim($str);
		$str = " $str ";
		foreach ( $words as $word )
			$str = str_replace(" $word ", " ", $str);
		$str = trim($str);
		return($ret);
	}
	/*------------------------------------------------------------*/
	public function vector($s, $n = 4) {
		$ngrams = array();
		$s = " ".trim($s)." ";
		$slen = strlen($s);
		for($i=0;$i<=$slen-$n;$i++) {
			$ngram = substr($s, $i, $n);
			if ( ! isset($ngrams[$ngram]) )
				$ngrams[$ngram] = 0;
			$ngrams[$ngram]++;
		}
		ksort($ngrams);
		return($ngrams);
	}
	/*------------------------------------------------------------*/
	public function strDistance($s1, $s2, $n = 4) {
		/*	static $cnt = 0;	*/
		/*	$cnt++;	*/
		/*	error_log("strDistance:$cnt: $s1/$s2");	*/
		/*	return(1);	*/
		$c14nS1 = $this->c14n($s1);
		$c14nS2 = $this->c14n($s2);
		if ( $s1 == $s2 )
			return(0);
		$s1Ngrams = $this->vector($c14nS1, $n);
		$s2Ngrams = $this->vector($c14nS1, $n);
		$cartesianProduct = $this->vectorMultiply($s1Ngrams, $s2Ngrams);
		if ( ! $cartesianProduct )
			return(1);
		$strDistance = 1.0 / $cartesianProduct ;
		return($strDistance);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	public function vectorMultiply($ngrams1, $ngrams2) {
		$ret = 0;
		foreach ( $ngrams1 as $s1 => $cnt1 ) {
			foreach ( $ngrams2 as $s2 => $cnt ) {
				if ( $s1 == $s2 )
					$ret += $cnt1*$cnt;
			}
		}
		return($ret);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private function cmpDistance($c1, $c2) {
		$ret = $c2['distance'] - $c1['distance'];
		return($ret > 0 ? 1 : ( $ret < 0 ? -1 : 0 ));
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
