<?php
/*------------------------------------------------------------*/
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
class Mcurl {
	/*------------------------------------------------------------*/
	private $curl;
	private $httpCode;
	private $responseDecoded;
	private $headers;
	private $opts;
	/*------------------------------------------------------------*/
	// get - return the response - json decoded
	// the httpCode can be gotten later, like in curl
	public function get($url) {
		$this->go($url);
		return($this->responseDecoded);
	}
	/*------------------------------------------------------------*/
	// get - but return the httpCode
	public function getHttpCode($url) {
		$this->go($url);
		return($this->httpCode);
	}
	/*------------------------------------------------------------*/
	public function head($url) {
		$this->init();
		$this->setOpts(array(
			CURLOPT_NOBODY =>  true,
		));
		$this->go($url);
		return($this->httpCode);
	}
	/*------------------------------------------------------------*/
	public function lastHttpCode() {
		return($this->httpCode);
	}
	/*------------------------------------------------------------*/
	public function post($url, $input, $dontEncode = false) {
		$this->go($url, $input, $dontEncode);
		return($this->responseDecoded);
	}
	/*------------------------------------------------------------*/
	public function postHttpCode($url, $input) {
		$this->go($url, $input);
		return($this->httpCode);
	}
	/*------------------------------------------------------------*/
	public function setHeaders($headers) {
		$this->headers = $headers;
	}
	/*------------------------------------------------------------*/
	public function setOpts($opts) {
		$this->opts = $opts;
	}
	/*------------------------------------------------------------*/
	// call this before setting up next call
	public function init() {
		$this->responseDecoded =
			$this->httpCode =
				$this->headers =
					$this->opts =
						$this->curl =
							null;
		$this->curl = curl_init();
		if ( ! $this->curl ) {
			error_log("Mcurl::init: cannot curl_init()");
			return(false);
		}
		return(true);
	}
	/*------------------------------------------------------------*/
	// Fri Apr  2 11:34:26 IDT 2021
	// use init() separately if setting opts or headers
	private function go($url, $input = null, $dontEncode = false) {
		if ( ! $this->curl && ! $this->init()) {
			error_log("Mcurl::go: init failed");
			return(null);
		}

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($this->curl, CURLOPT_ENCODING, "utf-8");
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, 7);
		if ( $this->opts ) {
			foreach ( $this->opts as $key => $value )
				curl_setopt($this->curl, $key, $value);
		}

		if ( $input ) {
			if ( ! $dontEncode )
				$input = json_encode($input);
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $input);
		}
		$headers = array(
			"Content-Type: application/json",
		);
		if ( $this->headers )
			$headers = array_merge($headers, $this->headers);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		$curlResponse = curl_exec($this->curl);
		if ( $curlResponse ) {
			$this->responseDecoded = json_decode($curlResponse, true);
		} else {
			$this->responseDecoded = null;
		}
		$this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		curl_close($this->curl);
		$this->curl = null;
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
