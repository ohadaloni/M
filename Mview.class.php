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
  *	Mview - View class
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
		$smartyTplDir = SMARTY_TPL_DIR;
		$smartyRunDir = SMARTY_RUN_DIR;
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
		Msession::init();
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
		$msgBuf = Msession::get('msgBuf');
		if ( $msgBuf )
			Msession::set('msgBuf', array()); // sets cookie - must be before next...
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
	public static function showRows($rows) {
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
			));
	}
	/*------------------------------------------------------------*/
	public static function tell($msg, $options = null) {
		$defaultOptions = array(
			'isError' => false,
			'silent' => false,
			'url' => null,
			'urlNewWindow' => false,
			'rememberForNextPage' => false,
		);
		if ( $options ) {
			foreach ( $defaultOptions as $key => $value )
				if ( ! array_key_exists($key, $options) )
					$options[$key] = $value;
		} else {
			$options = $defaultOptions;
		}
		$isHtml = isset($_SERVER['REMOTE_ADDR']);
		if ( $options['isError'] ) {
			if ( $isHtml )
				$cssClass = "alert-danger";
			else
				$msg = "ERROR: $msg";
		} else {
			if ( $isHtml )
				$cssClass = "alert-info";
		}
		if ( $isHtml ) {
			$msg = nl2br($msg);
			if ( $options['url'] ) {
				$url = $options['url'];
				if ( $options['urlNewWindow'] )
					$msg = "<a target=\"message\" href=\"$url\">$msg</a>";
				else
					$msg = "<a href=\"$url\">$msg</a>";
			}
			$text = "<div class=\"alert $cssClass\"><strong>$msg</strong></div>";
		} else {
			$text = $msg;
		}
		if ( $isHtml && $options['rememberForNextPage'] )
			self::rememberForNextPage($text);
		if ( ! $options['silent'] )
			self::pushOutput($text);
	}
	/*------------------------------*/
	private static function rememberForNextPage($text) {
		$sessionMsgBuf = Msession::get('msgBuf');
		if ( ! $sessionMsgBuf )
			$sessionMsgBuf = array();
		$numMessages = count($sessionMsgBuf);
		if ( $numMessages >= 7 ) {
			$lastText = $sessionMsgBuf[$numMessages-1];
			if ( $lastText != '...' ) {
				$sessionMsgBuf[] = '...';
				Msession::set('msgBuf', $sessionMsgBuf);
			}
		} else {
			$sessionMsgBuf[] = $text;
			Msession::set('msgBuf', $sessionMsgBuf);
		}
	}
	/*------------------------------*/
	public static function msg($msg) {
		self::tell($msg);
	}
	/*------------------------------*/
	public static function runningTime($startTime) {
		if ( defined("NO_RUNNING_TIME") )
			return;
		$endTime = microtime(true);
		$time = $endTime - $startTime;
		$millis = $time * 1000;
		$millis = round($millis, 3);
		self::br(2);
		self::tell("Running Time: $millis milliseconds.");
	}
	/*------------------------------*/
	public function showMsgs() {
		$msgs = Msession::get('msgBuf');
		self::showTpl("msgs.tpl", array(
			'msgs' => $msgs,
		));
	}
	/*------------------------------*/
	public static function hr($howMany = 1) {
		for($i=0;$i<$howMany;$i++)
			self::pushOutput('<hr style="height:3px; color:blue;" />'."\n");
	}
	/*------------------------------*/
	public static function br($howMany = 1) {
		for($i=0;$i<$howMany;$i++)
			self::pushOutput("<br />\n");
	}
	/*------------------------------*/
	public static function msgLater($msg) {
		self::tell($msg, array(
			'rememberForNextPage' => true,
		));
	}
	/*------------------------------*/
	public static function error($msg) {
		error_log($msg);
		self::tell($msg, array(
			'isError' => true,
			'rememberForNextPage' => true,
		));
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
		echo "<script type=\"text/javascript\"> $s </script>\n";
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
		$this->js("document.title = '$title'; ");
		
	}
	/*------------------------------------------------------------*/
	public static function setCookie($name, $value, $expires = null) {
		if ( $expires < 0 ) {
			unset($_COOKIE[$name]);
			@setcookie($name, null, time(0) - 3, "/");
			return;
		}
		$defaultExpires = 10*365*24*60*60;
		if ( $expires == null )
			$expires = $defaultExpires;
		if ( $expires <= $defaultExpires )
			$expires += time();
		if ( @setcookie($name, $value, $expires, "/") ) {
			$_COOKIE[$name] = $value;
		} else {
			error_log("Mview::setCookie: Cannot set cookie '$name'");
		}
	}
	/*------------------------------------------------------------*/
	/**
	 * include a template
	 * {msuShowTpl file="abc.tpl" a=... b=... c=...}
	 */
	public function msuShowTpl($a) {
		$tpl = $a['file'];
		$b = $a;
		$b['tplArgs'] = $a;
		$rendered = $this->showTpl($tpl, $b, true);
		// call from a smarty template -
		// skip output buffering or output will be reversed.
		echo $rendered;
	}
	/*------------------------------------------------------------*/
}

/*------------------------------------------------------------*/
