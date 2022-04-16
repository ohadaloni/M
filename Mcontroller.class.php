<?php
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
 * Mmodel and Mview are accessible from Mcontroller and any class
 * that extends Mcontroller
 */
require_once("Mmodel.class.php");
require_once("Mview.class.php");
/*------------------------------------------------------------*/
/**
  * 
  * Mcontroller has several control functions:
  * 1. Allow control to flow only if appropriate permission conditions are met.<br />
  * 2. Branch URLs of the form className=...&action=... to the corresponding class method.<br />
  * 3. Serve as a superclass to be extended with seamless inheritance from Mmoel and Mview.<br />
  *
  * @package M
  */
/*------------------------------------------------------------*/
class Mcontroller {
	/**
	* @var controller name of the current controller
	*/
	protected $controller;
	/**
	* @var action name of the current action
	*/
	protected $action;
	/**
	* @var Mmodel access the Mmodel class from this instance
	*/
	public $Mmodel;
	/**
	* @var Mview access the Mview class from this instance
	*/
	public $Mview;
	/*------------------------------------------------------------*/
	/**
	 * Mcontroller is typically extended with no arguments
	 * or created with no arguments.
	 *
	 * @param Mmodel force use of this instance 
	 * @param Mview force use of this instance 
	 *
	 */
	function __construct($Mm = null, $Mv = null) {
		global $Mmodel;
		global $Mview;
		
		if ( $Mm !== null )
			$this->Mmodel = $Mm;
		elseif ( isset($Mmodel) && $Mmodel != null )
			$this->Mmodel = $Mmodel;
		else
			$this->Mmodel = new Mmodel();

		if ( $Mv !== null )
			$this->Mview = $Mv;
		elseif ( isset($Mview) && $Mview != null )
			$this->Mview = $Mview;
		else
			$this->Mview = new Mview();

		if ( ! $this->Mmodel ) {
			$stack = debug_backtrace(false);
			Mview::print_r($stack, "stack", __FILE__, __LINE__);
			$this->quit();
			exit;
		}
	}
	/*------------------------------------------------------------*/
	public function control($className = null, $action = null, $args = null) {			
		$requestArgs = array();
		if ( is_string($args) ) {
			$vars = explode('&', $args);
			foreach ( $vars as $var ) {
				$nv = explode('=', $var);
				if ( count($nv) != 2 ) {
					$this->Mview->error("$var ???");
					return(false);
				}
				list($n, $v) = $nv;
				$requestArgs[$n] = $v;
			}
		} else if ( $args ) {
			foreach ( $args as $key => $arg )
				$requestArgs[$key] = $arg;
		}				
		
		$obj = $this->obj($className);
		if ( ! $obj ) {
			return(false);
		}
		
		$action = $this->action($action);
		if ( $action == null )
			$action = "index";
		
		
		if ( ! is_callable(array($obj, $action)) ) {			
			$className = get_class($obj);			
			$this->Mview->error("Method '$action' not callable in class '$className'");
			return(false);
		}
		$className = get_class($obj);

		$this->controller = strtolower($className);
		$this->action = strtolower($action);
		$obj->controller = strtolower($className);
		$obj->action = strtolower($action);
		Mutils::setenv("controller", $this->controller);
		Mutils::setenv("action", $this->action);
		$savedRequestArgs = $this->setRequestArgs($requestArgs);
		if ( ! $obj->permit() ) {
			error_log("Mcontroller: {$obj->controller}:{$obj->action}: Not Permitted");
			return(false);
		}
		$obj->before();
		$obj->$action();
		$obj->after();
		$this->revertRequestArgs($requestArgs, $savedRequestArgs);
		return(true);
	}
	/*------------------------------*/
	private function setRequestArgs($requestArgs) {
		$savedRequestArgs = array();
		foreach ( $requestArgs as $key => $arg ) {
			if ( array_key_exists($key, $_REQUEST) )
				$savedRequestArgs[$key] = $_REQUEST[$key];
			$_REQUEST[$key] = $arg;
		}
		return($savedRequestArgs);
	}
	/*------------------------------*/
	private function revertRequestArgs($requestArgs, $savedRequestArgs) {
		foreach ( $requestArgs as $key => $arg )
			unset($_REQUEST[$key]);
		foreach ( $savedRequestArgs as $key => $arg )
			$_REQUEST[$key] = $arg;
	}

	/*------------------------------*/
	protected function before() {}
	protected function after() {}
	/*------------------------------------------------------------*/
	private function obj($className) {
		if ( ($className = $this->className($className)) == null )
			return($this);
		if ( class_exists($className) ) {
				$obj = new $className;
				return($obj);
		}
		$files = Mutils::listDir(".", "php");
		foreach ( $files as $file ) {
			$fileParts = explode(".", $file);
			$baseName = reset($fileParts);
			if(strtolower($className) != strtolower($baseName) )
				continue;
			require_once($file);
			if ( class_exists($baseName) ) {
				$obj = new $baseName;
				return($obj);
			}
			$this->Mview->error("class $baseName not found in $file");
			return(false);
		}
		$this->Mview->error("cannot find class for '$className'");
		return(null);
	}
	/*------------------------------------------------------------*/
	public function redirect($url = null) {
		if ( $url && substr($url, 0, 4) == "http" ) {
			header("Location: $url");
			exit;
		}
		if ( ! $url ) {
			$url = $this->controller;
			if ( $this->action )
				$url .= "/".$this->action;
		}
		if ( $url == "/" )
			$url = "";
		$serverName = $_SERVER['SERVER_NAME'];
		$isHttps = @$_SERVER['HTTPS'] == "on";
		$s = $isHttps ? "s" : "";
		$url = trim($url, "/");
		$uri = "http$s://$serverName/$url";
		header("Location: $uri");
		exit;
	}
	/*------------------------------------------------------------*/
	/**
	 * decide whether to allow the execution of a method by the user
	 *
	 * permit() is by default suitable for public access.
	 * It returns true thus allowing everything.<br />
	 * In secure and controlled systems, permit() is overridden by the extending class
	 * to check login credentials
	 * 
	 * @return bool
	 */
	 protected function permit() {
	 	return(true);
	 }
	/*------------------------------------------------------------*/
	/**
	 * when a URL specifies a controller without an action
	 * the method index() is called.
	 *
	 */

	public function index() {
		$className = get_class($this);
		$this->Mview->error("$className: method index() not defined");
		return(null);
	}
	/*------------------------------*/
	public function defaultAction() { $this->index(); }
	public function main() { $this->index(); }
	/*------------------------------------------------------------*/
	/**
	 * show an array on screen - for developing and debugging
	 *
	 * @param array
	 */
	public function showArray($a) {
		$this->Mview->showTpl("mShowArray.tpl", array(
				'a' => $a,
			));
		
	}
	/*------------------------------------------------------------*/
	public function pathParts() {
		if ( isset($_REQUEST['PATH_INFO']) )
			$path = $_REQUEST['PATH_INFO'];
		elseif ( isset($_SERVER['PATH_INFO']) )
			$path = $_SERVER['PATH_INFO'];
		else
			return(null);

		// ignore leading, trailing and duplicate slashes
		$pathParts = array();
		$parts = explode("/", $path);
		foreach ( $parts as $part )
			if ( $part != "" )
				$pathParts[] = $part;

		return($pathParts);
	}
	/*------------------------------------------------------------*/
	protected static $debugLevel = 0;
	/*------------------------------*/
	public static function debugLevel($level = null) {
		$currentLevel = self::$debugLevel;
		$requestLevel = @$_REQUEST['debugLevel'];
		$envLevel = Mutils::getenv("debugLevel");
		$newLevel = 0;
		if ( $level != null )
			$newLevel = $level;
		if ( $currentLevel > $newLevel )
			$newLevel = $currentLevel;
		if ( $requestLevel > $newLevel )
			$newLevel = $requestLevel;
		if ( $envLevel > $newLevel )
			$newLevel = $envLevel;
		if ( self::$debugLevel != $newLevel )
			Mutils::setenv("debugLevel", $newLevel);
		self::$debugLevel = $newLevel;
		return($newLevel);
	}
	/*------------------------------*/
	public function debug($file, $lineNo, $tag, $msg = null, $debugLevelAtLeast = 1) {
		$debugLevel = $this->debugLevel();
		if ( self::$debugLevel < $debugLevelAtLeast )
			return;
		$datetime = date("Y-m-d G:i:s");
		$fileName = basename($file);

		$text = "$datetime: $fileName:$lineNo:$tag";
		if ( $msg )
			$text .= ": $msg";
		$isHttp = @$_SERVER['SERVER_ADDR'] != null;
		if ( $isHttp )
			$text = htmlspecialchars($text)."<br />";
		echo "$text\n";
	}
	/*------------------------------------------------------------*/
	public function space($tag) {
		$me = get_class()."::".__FUNCTION__."()";
		$space = Perf::space();
		Mview::msg("$tag: $space space");
		return($space);
	}
	/*------------------------------------------------------------*/
	public function exportToExcel($rows, $fileName = null) {
		if ( ! $rows || count($rows) == 0 ) {
			$this->Mview->msg("No Rows");
			return;
		}
			
		$headings = array_keys($rows[0]);
		$content = $this->Mview->render("excel.tpl", array(
			'headings' => $headings,
			'rows' => $rows,
		));
		if ( ! $fileName && isset($_REQUEST['fileName']) )
			$fileName = $_REQUEST['fileName'];
		if ( ! $fileName )
			$fileName = "M2excel-".date("Ymd");
		$filesize = strlen($content);
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=$fileName.xls");
		header("Content-Length: $filesize");
		echo $content;
	}
	/*------------------------------------------------------------*/
	public function showRows($rows, $showCount = false, $exportFileName = null) {
		if ( ! $rows || ! is_array($rows) || count($rows) == 0 ) {
			$this->Mview->msg("No Rows");
			return;
		}
		$headings = array_keys($rows[0]);
		$this->Mview->showTpl("mShowRows.tpl", array(
				'showCount' => $showCount,
				'columns' => $headings,
				'rows' => $rows,
				'exportFileName' => $exportFileName,
			));
	}
	/*------------------------------------------------------------*/
	public function quit() {
		$this->Mview->flushOutput();
		exit;
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private function className($className = null) {
		if ( $className )
			return($className);
		if ( isset($_POST['className']) && $_POST['className'] != '' )
			return($_POST['className']);
		if ( isset($_GET['className']) && $_GET['className'] != '' )
			return($_GET['className']);
		$pathParts = $this->pathParts();
		$pr = print_r($pathParts, true);
		return(isset($pathParts[0]) ? $pathParts[0] : null);
	}
	/*------------------------------------------------------------*/
	private function action($action = null) {
		if ( $action )
			return($action);
		if ( isset($_POST['action']) && $_POST['action'] != '' )
			return($_POST['action']);
		if ( isset($_GET['action']) && $_GET['action'] != '' )
			return($_GET['action']);
		$pathParts = $this->pathParts();
		if ( isset($pathParts[1]) )
			return($pathParts[1]);
		return(null);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
