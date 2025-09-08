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
require_once(TAS_DIR."/conf/mailJetCredentials.php"); // define('MAILJET_USER', ...
/*------------------------------------------------------------*/
class MmailJet {
	/*------------------------------*/
	public static function mail($to, $subject, $message, &$httpCode) {
		$mCurl = new Mcurl;
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
		$mCurl->init();
		$mCurl->setOpts(array(
			CURLOPT_USERPWD => MAILJET_USER,
		));
		$httpCode = $mCurl->postHttpCode($url, $body);
		return($httpCode);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
