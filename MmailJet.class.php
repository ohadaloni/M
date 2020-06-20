<?php
/*------------------------------------------------------------*/
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
if ( ! defined('TAS_DIR') )
	define('TAS_DIR', "/var/www/vhosts/tas.theora.com");
require_once(TAS_DIR."/conf/mailJetCredentials.php");
/*------------------------------------------------------------*/
class MmailJet {
	/*------------------------------*/
	public static function mail($to, $subject, $message, &$httpCode) {
		$curl = curl_init();
		if ( ! $curl )
			return(null);
		$headers = array(
			"Content-Type: application/json",
		);
		$toName = explode('@', $to);
		$toName = $toName[0];
		$toName = ucfirst($toName);
		$url = "https://api.mailjet.com/v3.1/send";
		$body = array(
			'Messages' => array(
				array(
					'From' => array(
						'Email' => "ohad@theora.com",
						'Name' => "Ohad",
					),
					'To' => array(
						array(
							'Email' => $to,
							'Name' => $toName,
						),
					),
					'Subject' => $subject,
					'HTMLPart' => $message,
				),
			),
		);
		$json = json_encode($body);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERPWD, MAILJET_USER);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_ENCODING, "utf-8");
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		$curlResponse = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return($httpCode);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
