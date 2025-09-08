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
	private $response;
	private $responseDecoded;
	private $headers;
	private $opts;
	/*------------------------------------------------------------*/
	// get - return the response - json decoded
	// the httpCode can be gotten later, like in curl
	public function get($url, $dontDecode = false) {
		$this->go($url);
		if ( $dontDecode )
			return($this->response);
		if ( $this->responseDecoded )
			return($this->responseDecoded);
		else
			return($this->response);
	}
	/*------------------------------------------------------------*/
	public function getImage($url) {
		$this->go($url, null, null, true);
		return($this->response);
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
			CURLOPT_NOBODY => true,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_TIMEOUT => 3,
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
	/*------------------------------------------------------------*/
	// use init() separately if setting opts or headers
	private function go($url, $input = null, $dontEncode = false, $dontDecode = false) {
		if ( ! $this->curl && ! $this->init()) {
			error_log("Mcurl::go: init failed");
			return;
		}

		$opts = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_ENCODING => "utf-8",
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 7,
			CURLOPT_TIMEOUT => 30,
		);
		foreach ( $opts as $key => $value ) {
			if ( ! isset($this->opts[$key]) ) {
				curl_setopt($this->curl, $key, $value);
			}
		}
		// overide the above defaults
		if ( $this->opts ) {
			foreach ( $this->opts as $key => $value ) {
				curl_setopt($this->curl, $key, $value);
			}
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
		$this->response  = $this->responseDecoded = null;
		if ( $curlResponse ) {
			$this->response = $curlResponse;
			if ( ! $dontDecode ) {
				$this->responseDecoded = @json_decode($curlResponse, true);
				if ( ! $this->responseDecoded )
					$this->responseDecoded = $this->jsonDecodeRows($curlResponse);
			}
		}
		$this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		curl_close($this->curl);
		$this->curl = null;
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	// Sat Aug 12 13:45:53 IDT 2023
	// this will only work if the json is an array of rows.
	// probably php5 json_decode has a bug and returns null,
	// so this comepnsates for db table like data
	private function jsonDecodeRows($json) {
		if ( ! $json ) {
			error_log("jsonDecodeRows: no json");
			return(null);
		}
		$json = trim($json);;
		$json = trim($json, "[]");
		$json = trim($json, "{}");
		$rowStrings = explode("},{", $json);
		$rows = array();
		foreach ( $rowStrings as $rowString ) {
			$nameValueStrings = explode(",", $rowString);
			$row = array();
			foreach ( $nameValueStrings as $nameValueString ) {
				$nv = explode(":", $nameValueString, 2);
				$name = trim($nv[0], '"');
				$value = trim($nv[1], '"');
				$row[$name] = $value;
			}
			$rows[] = $row;
		}
		return($rows);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
