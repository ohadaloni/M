<?php
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
  * The is no bootstrap and dispatcher separate from Mcontroller
  * This is quite diffrent than most PHP MVC frameworks
  * 
  *	Using M mostly means:
  *	 setting database access as described in Mconfig.php<br />
  *	 'requiring' this file, extending Mcontroller and calling control() from the extended class.<br />
  */
/*------------------------------------------------------------*/
require_once("Logger.class.php");
require_once("Mmemcache.class.php");
require_once("Perf.class.php");
require_once("Mmodel.class.php");
require_once("Mview.class.php");
require_once("Mcontroller.class.php");
require_once("Msession.class.php");
require_once("Mlogin.class.php");
require_once("Mfile.class.php");
require_once("Mdate.class.php");
require_once("Mcal.class.php");
require_once("Mtime.class.php");
require_once("Mutils.class.php");
require_once("Ll.class.php");
require_once("MlineGraphs.class.php");
require_once("MpieCharts.class.php");
require_once("MgeoBubbles.class.php");
require_once("Ngrams.class.php");
require_once("Mrecaptcha.class.php");
require_once("MmailJet.class.php");
require_once("Mcurl.class.php");
require_once("msu.php");
/*------------------------------------------------------------*/
/*------------------------------------------------------------*/
