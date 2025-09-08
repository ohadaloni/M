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
  * 2. Branch URLs of the form .../className/action to the corresponding class method.<br />
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
	/**
	* @var Mmemcache access the Mmemcache class from this instance
	*/
	public $Mmemcache;
	/**
	* @var startTime starting time of the program
	*/
	private $startTime;
	/*------------------------------------------------------------*/
	public function __construct() {
		global $Mmodel;
		global $Mview;
		
		if ( isset($Mmodel) && $Mmodel != null )
			$this->Mmodel = $Mmodel;
		else
			$this->Mmodel = new Mmodel;

		if ( isset($Mview) && $Mview != null )
			$this->Mview = $Mview;
		else
			$this->Mview = new Mview();

		$this->Mmemcache = new Mmemcache;
		// default startTime, but set it more accuratly if necessary
		$this->startTime = microtime(true);

		if ( ! $this->Mmodel ) {
			$stack = debug_backtrace(false);
			Mview::print_r($stack, "stack", __FILE__, __LINE__);
			$this->quit();
			exit;
		}
	}
	/*------------------------------------------------------------*/
	public function setStartTime($startTime) {
		$this->startTime = $startTime;
	}
	/*------------------------------------------------------------*/
	public function control() {			
		$obj = $this->obj();
		if ( ! $obj ) {
			return(false);
		}
		
		$obj->controller = $this->controller;
		$obj->action = $this->action;
		if ( ! $obj->permit() ) {
			error_log("Mcontroller::control: {$obj->controller}:{$obj->action}: Not Permitted");
			return(false);
		}
		$obj->before();
		$action = $this->action;
		$obj->$action();
		$obj->Mview->runningTime($this->startTime);
		$obj->after();
		return(true);
	}
	/*------------------------------------------------------------*/
	private function obj() {
		$thisClassName = strtolower(get_class($this));
		$pathParts = $this->pathParts();
		$cnt = count($pathParts);
		if ( $cnt == 0 ) {
			$className = $this->controller = get_class($this);
			$action = $this->action = "index";
			/*	error_log("obj: /$className/$action, found (\$this)");	*/
			return($this);
		}
		$className1 = $pathParts[0];
		$action1 = @$pathParts[1];
		if ( ! $action1 )
			$action1 = "index";
		$lastTwo = @array_slice($pathParts, -2);
		$className2 = $lastTwo[0];
		$action2 = @$lastTwo[1];
		if ( ! $action2 )
			$action2 = "index";
		if ( class_exists($className1) ) {
			if ( $thisClassName == $className1 ) {
				$obj = $this;
				if ( is_callable(array($obj, $action1)) ) {			
					$this->controller = $thisClassName;
					$this->action = strtolower($action1);
					/*	error_log("obj: /$className1/$action1, THIS!");	*/
					return($obj);
				}
			}
			$obj = new $className1;
			if ( is_callable(array($obj, $action1)) ) {			
				$this->controller = strtolower($className1);
				$this->action = strtolower($action1);
				/*	error_log("obj: /$className1/$action1, found");	*/
				return($obj);
			}
		}
		$same = $className1 == $className2;
		if ( ! $same && class_exists($className2) ) {
			if ( $thisClassName == $className2 ) {
				$obj = $this;
				if ( is_callable(array($obj, $action1)) ) {			
					$this->controller = $thisClassName;
					$this->action = strtolower($action2);
					/*	error_log("obj: /$className2/$action2, THIS!");	*/
					return($obj);
				}
			}
			$obj = new $className2;
			if ( is_callable(array($obj, $action2)) ) {			
				$this->controller = strtolower($className2);
				$this->action = strtolower($action2);
				/*	error_log("obj: /$className2/$action2, found");	*/
				return($obj);
			}
		}
		$files = Mutils::listDir(".", "php");
		foreach ( $files as $file ) {
			$fileParts = explode(".", $file);
			$baseName = reset($fileParts);
			if(strtolower($className1) == strtolower($baseName) ) {
				require_once($file);
				if ( class_exists($baseName) ) {
					$obj = new $baseName;
					if ( is_callable(array($obj, $action1)) ) {			
						$this->controller = strtolower($className1);
						$this->action = strtolower($action1);
						/*	error_log("obj: /$className1/$action1, loaded");	*/
						return($obj);
					}
				}
			}
			if( ! $same && strtolower($className2) == strtolower($baseName) ) {
				require_once($file);
				if ( class_exists($baseName) ) {
					$obj = new $baseName;
					if ( is_callable(array($obj, $action2)) ) {			
						$this->controller = strtolower($className2);
						$this->action = strtolower($action2);
						/*	error_log("obj: /$className2/$action2, loaded");	*/
						return($obj);
					}
				}
			}
		}
		$error = $same ?
			"Cannot find class/action: $className1/$action1"
			:
			"Cannot find class/action: $className1/$action1, $className2/$action2";
		$this->Mview->error($error);
		return(null);
	}
	/*------------------------------------------------------------*/
	/*
	 * before() & after() always get called by control()
	 * if not overloaded by $obj, they are a no-op
	 */
	protected function before() {}
	protected function after() {}
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
	public function quit() {
		$this->Mview->flushOutput();
		exit;
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
