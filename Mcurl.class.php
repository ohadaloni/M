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
	public function __construct() {
		$this->curl = curl_init();
		if ( ! $this->curl )
			error_log("Mcurl::__construct: cannot curl_init()");
	}
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
	public function post($url, $input) {
		$this->go($url, $input);
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
	private function go($url, $input = null) {
		if ( ! $this->curl ) { // reusing this instance
			$this->responseDecoded =
				$this->httpCode =
					$this->headers =
						$this->opts =
							null;
			$this->curl = curl_init();
			if ( ! $this->curl ) {
				error_log("Mcurl::go: cannot curl_init()");
				return(null);
			}
		}

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($this->curl, CURLOPT_ENCODING, "utf-8");
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, 7);
		if ( $this->opts ) {
			foreach ( $this->opts as $key => $value )
				curl_setopt($this->curl, $key, $value);
		}

		if ( $input ) {
			$json = json_encode($input);
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $json);
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
