<?php
/*------------------------------------------------------------*/
/**
  * Mview Smarty Utilities - a class-less namespace for smarty plugins and modifiers
  *
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
 *
 */
require_once("Mmodel.class.php");
require_once("Mview.class.php");
require_once("Mutils.class.php");
/*------------------------------------------------------------*/
/*------------------------------------------------------------*/
/**
 * include a template
 * {msuShowTpl file="abc.tpl" a=... b=... c=...}
 */
function msuShowTpl($a) {
	global $Mview;
	if ( ! isset($Mview) || ! $Mview ) {
		$Mview = new Mview;
	}

	$tpl = $a['file'];
	$b = $a;
	$b['tplArgs'] = $a;
	$Mview->showTpl($tpl, $b);
}
/*------------------------------*/
/**
 * show content of variables
 * {msuVarDump a=... ... ...}
 */
function msuVarDump($a) {
	Mview::print_r($a, "msuVarDump");
}
/*----------------------------------------*/
/**
  * format a date, possibly preceded by a three letter day of the week
  *
  * {$date|msuDateFmt[:1]}
  *
  * @param int date
  * @param bool whether to show day of week
  * @return string
  */
function msuDateFmt($date, $withWeekDay = false) {
	return(Mdate::fmt($date, $withWeekDay));
}
/*------------------------------*/
/**
 * show a number
 *
 * @param int
 * @param bool zeros are invisible by default
 * @return string
 */
function msuIntFmt($n, $showZero = false) {
	return(($n || $showZero) ? "$n" : "" );
}
/*------------------------------*/
/**
 * format a datetime for the datetimepicker jquery plugin
 *
 */
function msuDateTimePickerFmt($datetime) {
	if ( ! $datetime )
		return("");
	$dt = explode(' ', $datetime);
	list($y, $m, $d) = explode('-', $dt[0]);
	list($h, $mm, $s) = explode(':', $dt[1]);
	$day = sprintf("%s/%s/%s", $m, $d, $y);
	$time = sprintf("%s:%s", $h, $mm);
	$ret = "$day $time";
	return($ret);
}
/*------------------------------*/
/**
 * format a time value
 *
 * @param int 1515
 * @return string 15:15
 */
function msuTimeFmt($time) {
	return(Mtime::fmt($time));
}
/*------------------------------*/
/**
 * long name of a month
 *
 * @param int 9
 * @return string September
 */
function msuMonthLname($m) {
	return(Mdate::monthLname($m));
}
/*------------------------------*/
/**
 * short name of a month
 *
 * @param int 9
 * @return string Sep
 */
function msuMonthStr($m) {
	return(Mdate::monthStr($m));
}
/*------------------------------*/
/**
 * escape a string to ready it for javascript use
 *
 * @param string
 * @return string
 */
function msuJsStr($str)
{
	// if they are already escaped
	$ret = str_replace("\\'", "'", $str);
	$ret = str_replace("'", "\\'", $ret);

	/*	$ret = str_replace('\\"', '"', $str);	*/
	/*	$ret = str_replace('"', '\\"', $ret);	*/

	$ret = str_replace("\r\n", "\n", $ret);
	$ret = str_replace("\\n", "\n", $ret);
	$ret = str_replace("\n", "\\n", $ret);

	return($ret);
}
/*------------------------------*/
/**
 * format a floating point value
 *
 * @param string
 * @param int
 * @return string
 */

function msuFloatFmt($f, $precision = 3) {
	if ( $f == 0.00001 )
		return("0.00001"); // special non-zero magic number
	if ( ! $f )
		return("");
	$ret = sprintf("%.{$precision}f", $f);
	$ret = rtrim($ret, "0");
	$ret = rtrim($ret, ".");
	
	return($ret);
}
/*------------------------------*/
/**
 * format a money value - negative numbes appear in red and in parenthsis
 *
 * @param string
 * @param int
 * @return string
 */
function msuMoneyFmt($money, $currencyPrefix = '') {
	if ( $money >= 0 )
		return(Mutils::moneyFmt($money, $currencyPrefix));
	
	$money = -$money;
	$fmt = Mutils::moneyFmt($money, $currencyPrefix);
	return('<font color="red">('.$fmt.')</font>');
}
/*------------------------------------------------------------*/
/*------------------------------------------------------------*/
/**
 * set title of ducument from template body using javascript
 * {msuSetTitle title="..."}
 */
function msuSetTitle($args) {
	$title = $args['title'];
	Mutils::jsTitle($title);
}
/*------------------------------------------------------------*/
/**
 * set status line of ducument using javascript
 * {msuSetStatus status="..."}
 */
function msuSetStatus($args) {
	$status = $args['status'];
	Mutils::jsStatus($status);
}
/*------------------------------------------------------------*/
function msuImplode($arr) {
	if ( ! is_array($arr) )
		return($arr);
	return(implode(", ", $arr));
}
/*------------------------------------------------------------*/
