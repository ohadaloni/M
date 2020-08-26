<?php
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
 *
 */
require_once("Mcontroller.class.php");
require_once("Mdate.class.php");
require_once("Mtime.class.php");
/*------------------------------------------------------------*/
/**
  * A production quality, feature rich, database table scaffold, browser, editor, and superclass.
  *
  * Simple to use editting facilites<br />
  * production presentable GUI<br >
  * built-in jquery autocomplete<br />
  * built-in jquery date input
  *
  * can be used out-of-the-box for production back-end Admin tasks.
  * 
  * extend this as a per-table controller for more specific tasks
  * without loosing or re-developing the powerful built-in features.
  *
  * @package M
  */
/*------------------------------------------------------------*/
class Mtable extends Mcontroller {
	private $databaseName = null;
	private $tableName = null;
	private $ai = null;
	private $orderBy = null;
	public $currentRow = null;
	private $limit = null;
	/*------------------------------------------------------------*/
	/**
	 * construct an instance of a table browser and super class
	 *
	 * @param string which table is handeled by this instance and/or sublass
	 * @param string the default column by which to sort displayed rows
	 * @param int how many rows to display at the most by default
	 */
	public function __construct($tableName = null, $orderBy = null, $limit = null) {
		parent::__construct();
		if ( $tableName )
			$this->tableName = $tableName;
		else
			$this->tableName = $this->tableName();
		if ( ! $this->tableName )
			return;
		if ( isset($_REQUEST['databaseName']) ) {
			$this->databaseName = $_REQUEST['databaseName'];
		} else {
			$this->databaseName = M_DBNAME;
		}
		if ( $this->databaseName != M_DBNAME )
			$this->Mmodel->useDB($this->databaseName);

		if ( ! $this->tableName )
			return;
		$this->createTableIfNotExists();

		$this->ai = $this->Mmodel->autoIncrement($this->tableName);
		if ( ! $this->ai ) {
			Mview::error(__FILE__.":". __LINE__.": ".get_class().":".__FUNCTION__.": {$this->tableName}: no auto_incrment column");
			return;
		}
		if ( $orderBy )
			$this->orderBy = $orderBy;
		else
			$this->orderBy = $this->ai . " desc";
		if ( $limit === null )
			$limit = 1000;
		$this->limit = "limit $limit";
	}
	/*------------------------------*/
	public function getTableName() {
		return($this->tableName);
	}
	/*------------------------------*/
	private function tableName() {
		if ( isset($_REQUEST['tableName']) )
			return($_REQUEST['tableName']);
		if ( $this->Mmodel->isTable($this->controller) )
			return($this->controller);
		return($this->makeTableName($this->controller));
	}
	/*------------------------------------------------------------*/
	public function makeTableName($name) {
		$tables = $this->Mmodel->tables();
		foreach ( $tables as $tableName )
			if ( strcasecmp($tableName, $name) == 0 )
				return($tableName);
		return(null);
	}
	/*------------------------------------------------------------*/
	private function lcfirst($str) {
		if ( function_exists("lcfirst") )
			return(lcfirst($str));
		$first = substr($str, 0, 1);
		$rest = substr($str, 1);
		$lcfirst = strtolower($first);
		return("$lcfirst$rest");
	}
	/*------------------------------------------------------------*/
	private function createTableIfNotExists() {
		if ( ! $this->tableName )
			return;
		if ( $this->Mmodel->isTable($this->tableName) )
			return;
		$tableName = $this->tableName;
		/*
		 * if the table does not exists
		 * attempt to create it
		 */
		$crFile = "sql/$tableName.crtable.sql";
		if ( ! file_exists($crFile) ) {
			$this->Mview->error("No table $tableName, no file $crFile");
			return;
		}
		if ( ($crSql = file_get_contents($crFile)) == false ) {
			$this->Mview->error("Can not read $crFile");
			return;
		}
		$this->Mview->msg("Creating table $tableName from $crFile");
		$this->Mmodel->_sql($crSql);
		/*
		 * if there is a separate data file...
		 */
		$dataFile = "sql/$tableName.data.sql";
		if ( ! file_exists($dataFile) ) {
			/*	$this->Mview->msg("Data file $dataFile not found. Table $tableName left empty");	*/
			return;
		}
		if ( ($datSqls = Mutils::Mfile($dataFile)) == false ) {
			$this->Mview->error("Can not read $dataFile");
			return;
		}
		$this->Mview->msg("Loading $tableName from $dataFile");
		foreach ( $datSqls as $line )
			$this->Mmodel->_sql($line);
		
	}
	/*------------------------------------------------------------*/
	/**
	 * the default action is called when no action is specified and
	 * after an operation that was not overridden completes
	 *
	 * if not extended, this will show the current view of the table with all its controls.
	 * for more complex applications this might be overridden to show things like
	 * a private menu of method controls only and/or table statistics
	 */
	public function index() {
		$tableName = $this->tableName;
		if ( ($tpl = $this->tpl($tableName, "main")) != null )
			$this->Mview->showTpl($tpl);
		else
			$this->showTable();
	}
	/*------------------------------------------------------------*/
	/**
	 * present for browsing a list of tables in the database
	 *
	 * listTables() can be used as a supermenu to browse all tables in a database
	 *
	 * @param array all tables in the database are listed for browsing unless a subset is specified
	 */
	public function listTables($tableList = null) {
		$tableList = $this->Mmodel->tables();
		$tables = array();
		foreach ( $tableList as $tableName ) {
			$table['name'] = $tableName;
			$className = ucwords($tableName);
			$classFile = "$className.class.php";
			if ( class_exists($className) || file_exists($classFile) )
				$table['className'] = $className;
			else
				$table['className'] = "Mtable";
				
			$table['rows'] = $this->Mmodel->rowNum($tableName);
			$table['hasId'] = ( $this->Mmodel->autoIncrement($tableName) != null );
			$tables[] = $table;
		}
		$this->Mview->showTpl("Mtables.tpl", array(
			'tables' => $tables,
		));
	}
	/*------------------------------------------------------------*/
	private function getClass() {
		$className = ucwords($this->tableName);
		if ( class_exists($className) || file_exists("$className.class.php") )
			return($className);
		else
			return("Mtable");
	}
	/*------------------------------------------------------------*/
	/**
	 * a form for capturing data for a new row
	 *
	 * a template with the name $tableName.new.tpl can be used to override the default
	 * without having to override this method
	 * default field values can be passed by array argument, or via URL (in database format)
	 */
	public function newForm($defaults = null) {
		//$this->menu();
		$tableName = $this->tableName;
		$fields = $this->Mmodel->fields($this->tableName);
		$row = array();
		foreach ( $fields as $field ) {
			$fname = $field['name'];
			if ( isset($defaults[$fname]) )
				$row[$fname] = $defaults[$fname];
			elseif ( isset($_REQUEST[$fname]) )
				$row[$fname] = $_REQUEST[$fname];
		}
		$args = array(
			'databaseName' => $this->databaseName,
			'className' => $this->getClass(),
			'tableName' => $tableName,
			'fields' => $fields,
			'submitLabel' => "New $tableName record",
			'submitAction' => "dbInsert",
			'row' => $row,
		);
		$tpl = $this->tpl($tableName, "new");
		$this->Mview->showTpl($tpl, $args);
	}
	/*------------------------------------------------------------*/
	/**
	 * the default browsing menu
	 * you can override this function entirely or just place
	 * a template called $tableName.menu.tpl
	 * the menu template is passed $tableName and $className separately:
	 * tableName could be somthing like 'authors'
	 * class name might be 'Mtable'
	 * but will be 'Authors' if called from this class, extending from Mtable
	 */
	private $menuSeen = false;
	public function menu() {
		if ( $this->menuSeen )
			return;
		$this->menuSeen = true;

		$tableName = $this->tableName;
		$tpl = $this->tpl($tableName, "menu");
		$this->Mview->showTpl($tpl, array(
			'databaseName' => $this->databaseName,
			'className' => $this->getClass(),
			'tableName' => $tableName,
		));
	}
	/*------------------------------*/
	/**
	 * show a link to the control method of this table
	 *
	 * a template with the name $tableName.link.tpl is used in place of the default if it exists
	 */
	public function showLink() {
		$tableName = $this->tableName;
		$tpl = $this->tpl($tableName, "link");
		$this->Mview->showTpl($tpl, array(
			'databaseName' => $this->databaseName,
			'className' => $this->getClass(),
			'tableName' => $tableName,
		));
	}
	/*------------------------------------------------------------*/
	/**
	 * the main table view 
	 *
	 * @param the rows to be shown
	 *
	 * by default, as with small tables, all rows are shown
	 * after a row is inserted or updated, it is highlighted in this view
	 *
	 * if $arg is a string, it is treated as a complete single sql statement to select the desired rows from the database
	 *
	 * if $arg is an array, it is treated as a set of rows, as if returned from Mmodel->getRows,
	 * but may have been 'massaged' for some special use
	 *
	 * the template $tableName.table.tpl will be used in place of the default to display the results
	 * and 'rows' denotes the table rows, while 'currentRow' denotes the id of the row last inserted or updated.
	 */
	/*------------------------------*/
	public function showTableHtml($arg = null) {
		$tableName = $this->tableName;
		$orderBy = $this->orderBy;
		$columns = $this->Mmodel->columns($this->tableName);
		if ( is_string($arg) )
			$rows = $this->Mmodel->getRows($arg);
		elseif ( is_array($arg) )
			$rows = $arg ;
		else {
			$limit = $this->limit;
			$sql = "select * from $tableName order by $orderBy $limit";
			$rows = $this->Mmodel->getRows($sql);
		}

		if ( ! $rows ) 
			return(null);

		$tpl = $this->tpl($tableName, "table");

		$html = $this->Mview->render($tpl, array(
			'databaseName' => $this->databaseName,
			'className' => $this->getClass(),
			'tableName' => $tableName,
			'columns' => $columns,
			'rows' => $rows,
			'currentRow' => $this->currentRow,
			));
		return($html);
	}
	/*------------------------------*/
	public function showTable($arg = null) {
		$this->menu();

		$html = $this->showTableHtml($arg);
		if ( ! $html ) 
			return;

		$this->Mview->pushOutput($html);
	}
	/*------------------------------------------------------------*/
	/*
	 * scan the data usually in _REQUEST
	 * converting time and date fields to db ready values
	 */
	private function scan($request) {
		$ok = true;
		$tname = $this->tableName;
		$columns = $this->Mmodel->columns($tname);
		$ret = array();
		foreach ( $columns as $fname ) {
			if ( $fname == $this->ai || ! isset($request[$fname]) || ! isset($request[$fname]) )
				continue;
			$str = $request[$fname];
			$dataType = $this->Mmodel->dataType($tname, $fname);
			if ( $dataType == 'double' || $dataType == 'float' || strncmp($dataType, 'int(', 4) == 0 )
				$str = trim(str_replace(',', '', $str));
			if ( $dataType == 'date' ) {
				$scannedDate = Mdate::scan($str);
				if ( ! $scannedDate ) {
					$this->Mview->error("$str: Bad date");
					$ok = false;
				} else
					$value = Mdate::dash($scannedDate);
			} elseif ( $dataType == 'datetime' ) {
				$value = Mdate::datetimeScan($str);
			} elseif ( $dataType == 'time' ) {
				$scannedTime = Mtime::scan($str);
				if ( ! $scannedTime || $scannedTime < 0 || $scannedTime > 2359 ) {
					$this->Mview->error("$str: Bad time");
					$ok = false;
				} else
					$value = Mtime::fmt($scannedTime);
			} else
				$value = $str;

			$ret[$fname] = $value;
		}
		if ( $ok ) 
			return($ret);

		$this->Mview->error("Scan Failed");
		return(null);
	}
	/*------------------------------------------------------------*/
	/**
	 * duplicate a row interactively
	 *
	 * duplicated presents an existing row in a form so that data can be modified
	 * and inserted as a new row into the table
	 *
	 * @param int the id of the row to be duplicated, this is usually passed as &id= in the url
	 */
	public function duplicate($id = null) {
		$tableName = $this->tableName;
		if ( ! $id )
			$id =  $_REQUEST[$this->ai];
		$fields = $this->Mmodel->fields($this->tableName);
		$row = $this->Mmodel->getRow("select * from $tableName where id = $id");
		$tpl = $this->tpl($tableName, "new");
		$this->Mview->showTpl($tpl, array(
			'databaseName' => $this->databaseName,
			'className' => $this->getClass(),
			'tableName' => $tableName,
			'fields' => $fields,
			'submitLabel' => "New $tableName record",
			'submitAction' => "dbInsert",
			'row' => $row,
		));
	}
	/*------------------------------*/
	/**
	 * present a row for editing
	 *
	 */
	public function edit($id = null) {
		$tableName = $this->tableName;
		$ai = $this->ai;
		if ( ! $id )
			$id =  $_REQUEST[$ai];
		$fields = $this->Mmodel->fields($this->tableName);
		$row = $this->Mmodel->getRow("select * from $tableName where $ai = $id");
		$tpl = $this->tpl($tableName, "edit");
		$this->Mview->showTpl($tpl, array(
			'databaseName' => $this->databaseName,
			'className' => $this->getClass(),
			'tableName' => $tableName,
			'fields' => $fields,
			'row' => $row,
			'submitLabel' => "Update",
			'submitAction' => "dbUpdate",
		));
	}
	/*------------------------------*/
	/**
	 * update the table with new data for a row previosly presented by edit()
	 */
	public function dbUpdate($silent = false) {
		if ( ($data = $this->scan($_REQUEST)) == null ) {
			if ( ! $silent )
				$this->showTable();
			return(0);
		}
		$id = $_REQUEST[$this->ai];
		$this->setCurrentRow($id);
		$ret = $this->Mmodel->dbUpdate($this->tableName, $id, $data);
		$this->redirect("/".$this->controller);
	}
	/*------------------------------------------------------------*/
	/**
	 * insert a new to the table
	 *
	 * the data for the new row is taken from _REQUEST and was created from newForm() or duplicate()
	 */
	public function dbInsert($silent = false) {
		if ( ($data = $this->scan($_REQUEST)) == null ) {
			$this->showTable();
			return;
		}
		$id = $this->Mmodel->dbInsert($this->tableName, $data);
		if ( ! $silent ) {
			$this->setCurrentRow($id);
			$this->showTable();
		}
	}
	/*------------------------------------------------------------*/
	/**
	 * delete the row from the table denoted by $_REQUEST[nameOfAutoIncrementColumn]
	 */
	public function dbDelete($silent = false) {
		$this->Mmodel->dbDelete($this->tableName, $_REQUEST[$this->ai]);
		if ( ! $silent )
			$this->showTable();
	}
	/*------------------------------------------------------------*/
	/**
	 * put insert statements with content of table in sql/$tableName.data.sql
	 *
	 * @param string the table name if this instance doesn't konw it already
	 */
	public function dump($tableName = null) {
		if ( $tableName == null )
			$tableName = $this->tableName;
		$str = $this->Mmodel->tableDump($tableName);
		file_put_contents("sql/$tableName.data.sql", $str);
	}
	/*------------------------------------------------------------*/
	/**
	 * set the hilighted row for showTable()
	 */
	public function setCurrentRow($id) {
		$this->currentRow = $id;
		$this->Mview->assign("currentRow", $id);
	}
	/*------------------------------------------------------------*/
	public function getCurrentRow() {
		return($this->currentRow);
	}
	/*------------------------------------------------------------*/
	private function tpl($tableName, $which) {
		$candidates = array(
			"Mtable/$tableName/$tableName.$which.tpl",
			"Mtable/$tableName.$which.tpl",
			"$tableName.$which.tpl",
			"Mtable.$which.tpl",
		);
		foreach ( $candidates as $candidate )
			if ( file_exists("tpl/$candidate") )
				return($candidate);
		if ( defined('M_DIR') ) {
			$mdir = M_DIR;
			foreach ( $candidates as $candidate )
				if ( file_exists("$mdir/tpl/$candidate") )
					return($candidate);
		}
		return(null);
	}
	/*------------------------------------------------------------*/
	public function searchForm($defaults = null) {
		//$this->menu();
		$tableName = $this->tableName;
		$fields = $this->Mmodel->fields($this->tableName);
		$row = array();
		foreach ( $fields as $field ) {
			$fname = $field['name'];
			if ( isset($defaults[$fname]) )
				$row[$fname] = $defaults[$fname];
			elseif ( isset($_REQUEST[$fname]) )
				$row[$fname] = $_REQUEST[$fname];
		}
		$args = array(
			'databaseName' => $this->databaseName,
			'className' => $this->getClass(),
			'tableName' => $tableName,
			'fields' => $fields,
			'submitLabel' => "Search $tableName",
			'submitAction' => "search",
			'row' => $row,
		);
		$tpl = $this->tpl($tableName, "search");
		$this->Mview->showTpl($tpl, $args);
	}
	/*------------------------------------------------------------*/
	public function search() {
		$this->searchForm($_REQUEST);
		$tableName = $this->tableName;
		$orderBy = $this->orderBy;
		$fields = $this->Mmodel->fields($this->tableName);
		$conds = array();
		foreach ( $fields as $field ) {
			$fname = $field['name'];
			$searchValue = @$_REQUEST[$fname];
			if ( ! $searchValue )
				continue;
			switch ( $field['typeGroup'] ) {
				case 'date' :
					$date = substr($searchValue, 0, 10);
					$conds[] = "left($fname, 10) = '$date'";
					break;
				case 'text':
					$conds[] = "$fname like '%$searchValue%'";
					break;
				default:
					$conds[] = "$fname = '$searchValue'";
			}
		}
		if ( ! $conds )
			return;
		$cond = implode(" and ", $conds);
		$sql = "select * from $tableName where $cond order by $orderBy";
		$html = $this->showTableHtml($sql);
		if ( ! $html ) {
			$this->Mview->msg("Nothing found for <<<$sql>>>");
			return;
		}
		$this->Mview->pushOutput($html);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
