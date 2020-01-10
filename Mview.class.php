<?php
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
  * requires Smarty software
  */
if ( ! class_exists("Smarty") )
	require_once("Smarty-2.6.9/libs/Smarty.class.php");
/*------------------------------------------------------------*/
require_once("msu.php");
require_once("Mdate.class.php");
/*------------------------------------------------------------*/
/**
  *  Mview - View class
  *
  * Mview is an extension of the class Smarty (smarty.php.net)
  *
  * @package M
  * @author Ohad Aloni
  */
class Mview extends Smarty {
	/*------------------------------------------------------------*/
	// messages and error require no construction with new()
	/*------------------------------------------------------------*/
	/**
	 * @var bool
	 */
	private $templateDirPath = array();
	/*------------------------------------------------------------*/
	function __construct() {
		if ( ! defined('SMARTY_TPL_DIR') )
			define('SMARTY_TPL_DIR', 'tpl');

		if ( ! defined('SMARTY_RUN_DIR') )
			define('SMARTY_RUN_DIR', 'smarty');

		$this->use_sub_dirs = false;
		$smartyTplDir = SMARTY_TPL_DIR ;
		$smartyRunDir = SMARTY_RUN_DIR ;
		$this->template_dir = $smartyTplDir;
		$this->prependTemplateDir($smartyTplDir);
		if ( defined('M_DIR') )
			$this->appendTemplateDir(M_DIR."/tpl");
		$this->compile_dir = "$smartyRunDir/compile/";
		$this->config_dir = "$smartyRunDir/config/";
		$this->cache_dir = "$smartyRunDir/cache/";

		$this->registerFunction('msuShowTpl');
		$this->registerFunction('msuVarDump');
		$this->registerFunction('showMsg');
		$this->registerFunction('showError');
		$this->registerFunction('msuSetTitle');
		$this->registerFunction('msuSetStatus');

		$this->registerModifier('msuImplode');
		$this->registerModifier('msuMoneyFmt');
		$this->registerModifier('msuFloatFmt');
		$this->registerModifier('msuDateFmt');
		$this->registerModifier('msuDateTimePickerFmt');
		$this->registerModifier('msuIntFmt');
		$this->registerModifier('msuTimeFmt');
		$this->registerModifier('htmlspecialchars');
		$this->registerModifier('undash', 'Mdate');

		$this->assign(array(
			"yesNo" => array(
				0 => 'No',
				1 => 'Yes',
			),
		));
	}
	/*------------------------------------------------------------*/
	private function registerClass($method, $class) {
		if ( function_exists($method) )
			return(true);

		if ( $class ) {
			if ( ! class_exists($class) ) {
				require_once("$class.class.php");
				if ( ! class_exists($class) ) {
					self::error("Mview::registerClass: class $class not found");
					return(null);
				}
			}
			if ( method_exists($class, $method) )
				return($class);
			else {
				self::error("Mview::registerClass: method $method not found in $class");
				return(null);
			}
		}

		if ( method_exists("Mutils", $method) )
			return("Mutils");
		self::error("Mview::registerClass: method $method not found");
		return(null);
	}
	/*------------------------------*/
	/**
	 * register a smarty plugin function
	 *
	 * @param string can be any function or method in Mview or Mutils or the passed class
	 * @param string
	 */
	private function registerFunction($method, $class = null) {
		$callable = array($this, $method);
		if ( is_callable($callable) ) {
			$this->register_function($method, $callable);
			return;
		}
		$class = $this->registerClass($method, $class);
		if ( ! $class )
			return;
		if ( $class === true )
			$this->register_function($method, $method, false);
		else
			$this->register_function($method, array($class, $method,));
	}
	/*------------------------------------------------------------*/
	/**
	 * register a smarty plugin filter (modifier)
	 *
	 * @param string can be any function or method in Mview or Mutils or the passed class
	 * @param string
	 */
	public function registerModifier($method, $class = null) {
		$class = $this->registerClass($method, $class);
		if ( ! $class )
			return;
		if ( $class === true )
			$this->register_modifier($method, $method);
		else
			$this->register_modifier($method, array($class, $method,));
	}
	/*------------------------------------------------------------*/
	/**
	 * prepend a template folder to the template search path
	 *
	 * for use with {msuShowTpl file=... arg1=...} - 
	 *
	 * when using include in templates, only the first $smarty->template_dir is recognized
	 *
	 */
	public function prependTemplateDir($dir) {
		if ( ! in_array($dir, $this->templateDirPath) )
			array_unshift($this->templateDirPath, $dir);
	}
	/*------------------------------*/
	/**
	 * append a template folder to the template search path
	 *
	 * for use with {msuShowTpl file=... arg1=...} - 
	 *
	 * when using include in templates, only the first $smarty->template_dir is recognized
	 *
	 */
	public function appendTemplateDir($dir) {
		if ( ! in_array($dir, $this->templateDirPath) )
			$this->templateDirPath[] = $dir;
	}
	/*------------------------------------------------------------*/
	public function templateDirPath() {
		$templateDirPath = implode(":", $this->templateDirPath);
		return($templateDirPath);
	}
	/*------------------------------------------------------------*/
	private function _render($tpl, $errorLogger = null) {
		if ( ! is_writable($this->compile_dir) ) {
			$pwd = trim(`pwd`);
			$error = "Smarty Compile Dir $pwd/{$this->compile_dir} not writable";
			$this->error($error);
			if ( $errorLogger )
				$errorLogger->log($error);
			return(false);
		}
		if ( is_readable($tpl) )
			return($this->fetch($tpl));
		$cwd = getcwd();
		foreach ( $this->templateDirPath as $dir ) {
			if ( is_readable("$cwd/$dir/$tpl") )
				return($this->fetch("$cwd/$dir/$tpl"));
			if ( is_readable("$dir/$tpl") )
				return($this->fetch("$dir/$tpl"));
		}
		if ( isset($this->template_dir) ) {
			$td = $this->template_dir;
			if ( is_readable("$td/$tpl") )
				return($this->fetch($tpl));
		}
		$templateDirPath = $this->templateDirPath();
		$error = "Mview: $tpl not found in $templateDirPath";
		$this->error($error);
		if ( $errorLogger )
			$errorLogger->log($error);
		return(null);
	}
	/*------------------------------------------------------------*/
	public function renderText($text, $args = array(), $errorLogger = null) {
		$args['evalText'] = $text;
		$rendered = $this->render("eval.tpl", $args, $errorLogger);
		return($rendered);
	}
	/*------------------------------------------------------------*/
	/**
	 * return a rendered template
	 */
	public function render($tpl, $args = null, $errorLogger = null) {
		if ( is_array($args) ) {
			$this->assign($args);
			$this->assign(array('tplArgs' => $args));
		}
		$rendered = $this->_render($tpl, $errorLogger);
		if ( is_array($args) ) {
			$keys = array_keys($args);
			/*	$this->clear_assign($keys);	*/
			/*	$this->clear_assign('tplArgs');	*/
		}
		return($rendered);
	}
	/*------------------------------------------------------------*/
	private static $holdOutput = false;
	private static $outputBuffer = "";
	/*------------------------------*/
	public static function holdOutput() {
		self::$holdOutput = true;
	}
	/*------------------------------*/
	public static function flushOutput() {
		// msgbuf had better been output by now by app, not usable after this;
		$Msession = new Msession;
		$msgBuf = $Msession->get('msgBuf');
		if ( $msgBuf )
			$Msession->set('msgBuf', array()); // sets cookie - must be bfore next...
		if ( self::$outputBuffer ) {
			echo self::$outputBuffer;
			self::$outputBuffer = "";
		}
		flush();
		@ob_flush(); // may have been turned off by ob_implicit_flush()
	}
	/*------------------------------*/
	public static function pushOutput($htmlText) {
		if ( self::$holdOutput )
			self::$outputBuffer .= $htmlText;
		else
			echo $htmlText;
	}
	/*------------------------------*/
	/**
	 * show a template
	 *
	 * @param string file name
	 * @param array list of named arguments
	 * @param fetch only return the rendered template and do not display if true
	 *
	 */
	public function showTpl($tpl = null, $args = null, $fetch = false) {
		if ( $tpl == null )
			$tpl = @$_REQUEST['tpl'];
		$fetched = $this->render($tpl, $args);
		if ( ! $fetch )
			self::pushOutput($fetched);
		return($fetched);
	}
	/*------------------------------*/
	public function permit() {
		 // allow automatic urls /Mview/showTpl?tpl=
		return(true);
	}
	/*------------------------------------------------------------*/
	public static function showRows($rows, $exportFileName = null) {
		global $Mview; // need an instance anyway

		if ( ! $rows || ! is_array($rows) || count($rows) == 0 ) {
			self::msg("No Rows");
			return;
		}
		$columns = array_keys($rows[0]);

		if ( ! $Mview )
			$Mview = new Mview;
		$Mview->showTpl("mShowRows.tpl", array(
				'columns' => $columns,
				'rows' => $rows,
				'exportFileName' => $exportFileName,
			));
	}
	/*------------------------------------------------------------*/
	private static $msgBuf = array();
	private static $isHold = false;
	/*------------------------------*/
	/**
	 * buffered messages
	 */
	public function messages() {
		return(self::$msgBuf);
	}
	/*------------------------------*/
	/**
	 * buffer requested messages and errors
	 * do not show anything at least until a further notice by flushMsgs()
	 */
	public function holdMsgs() {
		self::$isHold = true;
	}
	/*------------------------------*/
	/**
	 * flush messages and errors previously held due to a call to holdMsgs() and stop buffering
	 */
	public function flushMsgs() {
		self::$isHold = false ;

		foreach ( self::$msgBuf as $msg )
			self::message($msg['msg'], $msg['iserror']);
		self::$msgBuf = array();
	}
	/*------------------------------*/
	private static function message($msg, $iserror, $url = null) {
		$me = get_class()."::".__FUNCTION__."()";
		$isHtml = isset($_SERVER['REMOTE_ADDR']);
		if ( $isHtml ) {
			$type = $iserror ? "alert-danger" : "alert-info" ;
			$msg = htmlspecialchars($msg);
			
			$tokens = explode("\n", $msg);
			
			$before = "";
			for($i=0;$i<count($tokens);$i++){
				if(trim($tokens[$i])!= "")
					break;
				if(trim($tokens[$i])== "")
					$before.="<br/>";
			}
			$tokens = array_reverse($tokens);
			$after = "";
			for($i=0;$i<count($tokens);$i++){
				if(trim($tokens[$i])!= "")
					break;
				if(trim($tokens[$i]) == "")
					$after.="<br/>";
			}			
			$msg = trim($msg,"\n");
			$msg = nl2br($msg);
			if ($url){
				$msg = "<a target=\"_blank\" href=\"$url\">$msg</a>";
			}
			$text = "<div class='alert $type'><strong>$msg</strong></div>" ;
			$text = $before.$text.$after;
		}
		else {
			$pfx = $iserror ? "ERROR: " : "" ;
			$text =  "$pfx$msg\n" ;
		}
		if ( $isHtml ) {
			$Msession = new Msession;
			$sessionMsgBuf = $Msession->get('msgBuf');
			if ( ! $sessionMsgBuf )
				$sessionMsgBuf = array();
			$numMessages = count($sessionMsgBuf);
			if ( $numMessages >= 7 ) {
				$lastText = $sessionMsgBuf[$numMessages-1];
				if ( $lastText != '...' ) {
					$sessionMsgBuf[] = '...';
					$Msession->set('msgBuf', $sessionMsgBuf);
				}
			} else {
				$sessionMsgBuf[] = $text;
				$Msession->set('msgBuf', $sessionMsgBuf);
			}
		}
		self::pushOutput($text);
	}
	/*------------------------------*/
	/**
	 * show a msg (or hold it - see holdMsgs())
	 *
	 * @param string
	 */
	public static function msg($msg, $iserror = false, $url = null) {
		if ( self::$isHold )
			self::$msgBuf[] = array('msg' => $msg, 'iserror' => $iserror, 'url' => $url, );
		else
			self::message($msg, $iserror, $url);
	}
	/*------------------------------*/
	/**
	 * show an error (or hold it - see holdMsgs())
	 *
	 * @param string
	 */
	public static function error($msg) {
		error_log($msg);
		self::msg($msg, true);
	}
	/*------------------------------*/
	public static function urlMsg($msg, $url) {
		self::msg($msg, false, $url);
	}
	/*------------------------------------------------------------*/
	/**
	 * show a message from a template
	 *
	 * {showMsg msg="..."}
	 */
	public function showMsg($a) { self::msg($a['msg']); }
	/*------------------------------*/
	/**
	 * show an error from a template
	 *
	 * {showError msg="..."}
	 */
	public function showError($a) { self::error($a['msg']); }
	/*------------------------------------------------------------*/
	/**
	 * a fancier version of print_r helps debugging by showing a title, file and line number
	 * in a more legible display
	 *
	 *
	 * e.g<br />
	 * Mview::print_r($_REQUEST, "_REQUEST", __FILE__, __LINE__);
	 *
	 * @param mixed
	 * @param string
	 * @param string
	 * @param int
	 * 
	 */
	public static function print_r($var, $varName = null, $file = null, $line = null, $return = false, $logError = false) {
		$print_r = print_r($var, true);
		if ( $file ) {
			$fileParts = explode("/", trim($file, "/"));
			$fileName = $fileParts[count($fileParts)-1];
		}
		if ( $logError ) {
			$str = str_replace("\n", " --> ", $print_r);
			$str = "$fileName:$line: $varName: [[[ $print_r ]]]";
			error_log($str);
			return;
		}
		$isHtml = isset($_SERVER['REMOTE_ADDR']);
		$ret = "";
		if ( $isHtml )
			$ret .= "\n<table border=\"0\"><tr><td align=\"left\"><pre>\n";
		if ( $file ) {
			$ret .= "$varName ($fileName: $line)\n--------------------------------------------------------------\n";
		}
		$ret .= $print_r;
		$ret .= "\n";
		if ( $isHtml )
			$ret .= "\n</pre></td></tr></table>\n";
		if ( ! $return )
			self::pushOutput($ret);
		return($ret);
	}
	/*------------------------------------------------------------*/
	/**
	 *
	 * escape quotes for javascript
	 *
	 * @param string
	 * @return string
	 */
	public function jsStr($str) {
		// escape with \ but
		// if they are already escaped ...
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
	public function js($s) {
		echo "<script type=\"text/javascript\"> $s </script>\n" ;
		flush();
		ob_flush();
	}
	/*------------------------------*/
	/**
	 * set title of page using javascript
	 *
	 * @param string
	 */
	public function jsTitle($s) {
		$title = $this->jsStr($s);
		$this->js("document.title = '$title' ; ");
		
	}
	/*------------------------------------------------------------*/
	public static function setCookie($name, $value, $expires = null) {
		if ( $expires < 0 ) {
			unset($_COOKIE[$name]);
			@setcookie($name, null, time(0) - 3, "/");
			return;
		}
		$defaultExpires = 10*365*24*60*60;
		if ( $expires  == null )
			$expires = $defaultExpires;
		if ( $expires  <= $defaultExpires )
			$expires += time();
		if ( @setcookie($name, $value, $expires, "/") ) {
			$_COOKIE[$name] = $value;
		} else {
			/*	self::error("Cannot set cookie - output already started");	*/
			/*	Mutils::trace();	*/
			/*	self::flush();	*/
			/*	exit;	*/
		}
	}
	/*------------------------------------------------------------*/
	/**
	 * include a template
	 * {msuShowTpl file="abc.tpl" a=... b=... c=...}
	 */
	public function msuShowTpl($a) {
		$tpl = $a['file'];
		$b = $a ;
		$b['tplArgs'] = $a;
		$rendered = $this->showTpl($tpl, $b, true);
		// call from a smarty template -
		// skip output buffering or output will be reversed.
		echo $rendered;
	}
	/*------------------------------------------------------------*/
}

/*------------------------------------------------------------*/
