<?php
/*------------------------------------------------------------*/
/**
  * Mmodel - mysql convenience utilities
  *
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
 * Mmodel may be used independently,
 * It might use Mview to display some error messages
 */
require_once("Mview.class.php");
/*------------------------------------------------------------*/
/**
  * Mmodel - mysql convenience utilities
  *
  * @package M
  * @author Ohad Aloni
  */
class Mmodel {
	/*------------------------------------------------------------*/
	private $isConnected = false;
	/*------------------------------------------------------------*/
	private $dbHost = null;
	private $dbUser = null;
	private $dbPasswd = null;
	private $dbName = null;
	/*------------------------------*/
	private $dbHandle = null;
	private $lastSql = null;
	private $lastError = null;
	private $lastInsertId = null;
	/*------------------------------*/
	private $Mmemcache = null;
	/*------------------------------------------------------------*/
	public function __construct() {
		if ( defined('M_HOST') )
			$this->dbHost = M_HOST;
		else
			$this->dbHost = 'localhost';

		if ( defined('M_DBNAME') )
			$this->dbName = M_DBNAME;

		if ( defined('M_USER') )
			$this->dbUser = M_USER;

		if ( defined('M_PASSWORD') )
			$this->dbPasswd = M_PASSWORD;

		if ( ! $this->dbUser || $this->dbPasswd === null ) {
			Mview::error("Must define M_USER M_PASSWORD");
			return;
		}
		if ( ! $this->selectHost($this->dbHost, $this->dbUser, $this->dbPasswd, $this->dbName) )
			return;
		$this->isConnected = true;
	}
	/*------------------------------*/
	public function isConnected() {
		return($this->isConnected);
	}
	/*------------------------------------------------------------*/
	public function selectHost($dbHost, $dbUser, $dbPasswd, $dbName) {
		$this->dbHost = $dbHost;
		$this->dbUser = $dbUser;
		$this->dbPasswd = $dbPasswd;
		$this->dbName = $dbName;
		$this->dbHandle = @mysqli_connect($dbHost, $dbUser, $dbPasswd);
		if ( ! $this->dbHandle ) {
			$error = "Cannot connect to DB on $dbHost";
			$this->lastError = $error;
			Mview::error($error);
			return(false);
		}
		$res = $this->query("SET NAMES 'utf8'");
		if ( ! $res ) {
			Mview::error("cannot set names to utf8");
			return(false);
		}
		if ( $dbName ) {
			if ( ! $this->selectDb($dbName) ) {
				$error = @mysqli_error($this->dbHandle);
				$this->lastError = $error;
				Mview::error("Unable to select db $dbName: $error");
			}
		}
		return(true);
	}
	/*------------------------------------------------------------*/
	/**
	  * use database
	  */
	public function selectDB($db) {
		$this->dbName = $db;
		$ret = @mysqli_select_db($this->dbHandle, $this->dbName);
		if ( $ret == false ) {
			$error = @mysqli_error($this->dbHandle);
			$this->lastError = $error;
			return(false);
		}
		return(true);
	}
	/*------------------------------*/
	/**
	  * use database
	  */
	public function useDB($db) {
		return($this->selectDB($db));
	}
	/*------------------------------------------------------------*/
	public function insertId() {
		return($this->lastInsertId);
	}
	/*------------------------------------------------------------*/
	/**
	 * make a string usable in quotes of sql statement
	 * @param string
	 * @return string
	 */
	public function str($str) {
		if ( ! $str )
			return($str);
		$ret = $str;
		$ret = str_replace("\\", "\\\\", $ret);
		// if they are already escaped
		$ret = str_replace("\\'", "'", $ret);
		$ret = str_replace("'", "\\'", $ret);
		$ret = str_replace("\r\n", "\n", $ret);
		$ret = str_replace("\n", "\\n", $ret);
		return($ret);
	}
	/*------------------------------------------------------------*/
	public function query($sql) {
		$res = null;
		try {
			$res = @mysqli_query($this->dbHandle, $sql);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			Mview::error($msg);
			$error = @mysqli_error($this->dbHandle);
			$this->lastError = $error;
			if ( $error )
				Mview::error("sql error: $error");
			Mview::error($sql);
			return(null);
		}
		if ( ! $res ) {
			$error = @mysqli_error($this->dbHandle);
			$this->lastError = $error;
			if ( $error )
				Mview::error("sql error: $error");
			Mview::error(substr($sql, 0, 500));
			if ( stristr($error, "MySQL server has gone away") ) {
				Mview::error("EXITTING");
				exit; // servers will auto restart
			}
			return(null);
		}
		return($res);
	}
	/*------------------------------------------------------------*/
	public function _sql($sql, $rememberLastSql = true) {
		$res = $this->query($sql);
		if ( ! $res ) {
			return(null);
		}
		if ( $rememberLastSql )
			$this->lastSql = $sql;
		$affected = @mysqli_affected_rows($this->dbHandle);
		@mysqli_free_result($res);
		return($affected);
	}
	/*------------------------------*/
	public function sql($sql) {
		if ( ! $this->isConnected ) {
			return(null);
		}
		$ret = $this->_sql($sql);
		if ( strstr($sql, 'insert') )
			$this->lastInsertId = @mysqli_insert_id($this->dbHandle);
		if ( $ret > 0 )
			$this->dbLog('', 'sql', 0);

		return($ret);
	}
	/*----------------------------------------*/
	public function getRows($sql, $ttl = null) {
		if ( $ttl !== null && ! $this->Mmemcache )
			$this->Mmemcache = new Mmemcache;
		$memcacheKey = $this->memcacheKey($sql);
		if ( $ttl !== null && ($rows = $this->Mmemcache->get($memcacheKey)) !== false ) {
			return($rows);
		}
		if ( ! $this->isConnected ) {
			return(null);
		}
		$res = $this->query($sql);
		if ( ! $res ) {
			return(null);
		}
		$ret = array();
		while($r = @mysqli_fetch_assoc($res))
			$ret[] = $r;
		@mysqli_free_result($res);
		if ( $ttl !== null ) {
			$set = $this->Mmemcache->set($memcacheKey, $ret, $ttl);
			if ( ! $set )
				error_log("Failed to Mmemcache->set($memcacheKey, ..., $ttl)");
			static $visited = false;
			if ( ! $visited ) {
				$visited = true;
				$get = $this->Mmemcache->get($memcacheKey);
				if ( $get === false ) {
					error_log("Failed to get after Mmemcache->set($memcacheKey, ..., $ttl)");
				}
			}
		}
		return($ret);
	}
	/*----------*/
	private function memcacheKey($sql) {
		$codeVersion = 9;
		$dbHost = $this->dbHost;
		$dbName = $this->dbName;
		$memcacheKey = "$codeVersion-$dbHost-$dbName-$sql";
		return($memcacheKey);
	}
	/*------------------------------------------------------------*/
	public function getRow($sql, $ttl = null) {
		$rows = $this->getRows($sql, $ttl);
		if ( count($rows) == 0 )
			return(null);
		return($rows[0]);
	}
	/*------------------------------*/
	/**
	 * get a row from a table by its id
	 *
	 * @param string the table from which the data is fetched
	 * @param int the id value
	 * @param string the name of the id field if it is not 'id'
	 * @return array associative array of data of the row
	 */
	public function getById($tableName, $id, $ttl = null, $idName = "id") {
		return($this->getRow("select * from $tableName where $idName = '$id'", $ttl));
	}
	/*----------------------------------------*/
	/**
	 * get a column of data from the database
	 *
	 * @param string the query
	 * @return array an array with the data
	 */
	public function getStrings($sql, $ttl = null) {
		$rows = $this->getRows($sql, $ttl);
		if ( $rows === null )
			return(null);
		$ret = array();
		foreach ( $rows as $row )
			$ret[] = array_shift($row); // take the value of the first (and only) column, ignoring the index field name
		return($ret);
	}
	/*----------------------------------------*/
	/**
	 * get an item (single row, single column) from the database
	 *
	 * @param string the query
	 * @return string the item data
	 */
	public function getString($sql, $ttl = null) {
		if ( ! $this->isConnected ) {
			return(null);
		}
		$strings = $this->getStrings($sql, $ttl);
		if ( $strings )
			return($strings[0]);
		else
			return(null);
	}
	/*----------------------------------------*/
	/**
	 * get an int item from the database
	 *
	 * @param string the query
	 * @return int the returned number
	 */
	public function getInt($sql, $ttl = null) {
		if ( ! $this->isConnected ) {
			return(null);
		}
		if ( ($ret = $this->getString($sql, $ttl)) === null )
			return(null);

		return((int)$ret);
	}
	/*------------------------------------------------------------*/
	/**
	 * name of the auto_increment column in table
	 *
	 * @param string the name of the table
	 * @return string name of auto_increment column
	 */
	public function autoIncrement($tableName) {
		if ( ! $this->Mmemcache )
			$this->Mmemcache = new Mmemcache;
		static $cache = array();

		if ( isset($cache[$tableName]) )
			return($cache[$tableName]);

		$memcacheKey = $this->memcacheKey("autoIncrement-$tableName");
		if ( ($cache[$tableName] = $this->Mmemcache->get($memcacheKey)) != null )
			return($cache[$tableName]);

		$fields = $this->fields($tableName);
		if ( ! $fields ) {
			$cache[$tableName] = null;
			return($cache[$tableName]);
		}
		foreach ( $fields as $field ) {
			if ( $field['isAutoInc'] ) {
				$cache[$tableName] = $field['name'];
				break;
			}
		}
		if ( ! isset($cache[$tableName]) )
			$cache[$tableName] = null;
		if ( $cache[$tableName] )
			$this->Mmemcache->set($memcacheKey, $cache[$tableName], 600);
		return($cache[$tableName]);
	}
	/*------------------------------------------------------------*/
	public function typeGroup($ftype) {
		if ( strncmp($ftype, 'int', 3) == 0 )
			return("int");
		if ( $ftype == 'text' )
			return("text");
		if ( strncmp($ftype, 'varchar', 7) == 0 )
			return("text");
		if ( in_array($ftype, array('date', 'datetime', 'timestamp')) )
			return("date");
		return($ftype);
	}
	/*------------------------------------------------------------*/
	/**
	 * schema information for a table:
	 *
	 * @param string the name of the table
	 * @return array two dimensional array. For each column in the table: name, type, isNull, isPrimary, default, isAutoInc
	 */
	public function fields($tableName) {
		static $cache = array();

		if ( isset($cache[$tableName]) )
			return($cache[$tableName]);

		$columnRows = $this->getRows("show columns from $tableName", 30);
		if ( ! $columnRows ) {
			Mview::error("No columns for $tableName");
			$cache[$tableName] = null;
			return($cache[$tableName]);
		}
		$fields = array();
		foreach ( $columnRows as $col )
			$fields[] = array(
				'name' => $col['Field'],
				'type' => $col['Type'],
				'typeGroup' => $this->typeGroup($col['Type']),
				'isNull' => $col['Null'] == 'YES' ? true : false,
				'isPrimary' => $col['Key'] == 'PRI',
				'default' => $col['Default'],
				'isAutoInc' => $col['Extra'] == 'auto_increment',
				'isKey' => $col['Key'] != null,
			);
		$cache[$tableName] = $fields;
		return($cache[$tableName]);
	}
	/*----------------------------------------*/
	/**
	 * schema of one field
	 */
	 public function field($tableName, $fieldName) {
	 	$fields = $this->fields($tableName);
		if ( ! $fields )
			return(null);
		foreach ( $fields as $field )
			if ( $field['name'] == $fieldName )
				return($field);
		return(null);
	 }
	/*----------------------------------------*/
	/**
	 * list fields of a table
	 *
	 * @param string the name of the table
	 * @return array list of field names
	 */
	public function columns($tableName) {
		static $cache = array();

		if ( isset($cache[$tableName]) )
			return($cache[$tableName]);

		$columnRows = $this->getRows("show columns from $tableName", 30);
		if ( ! $columnRows ) {
			Mview::error("No columns for $tableName");
			$cache[$tableName] = null;
			return($cache[$tableName]);
		}
		$cols = array();
		foreach ( $columnRows as $col )
			$cols[] = $col['Field'];
		$cache[$tableName] = $cols;
		return($cache[$tableName]);
	}
	/*----------------------------------------*/
	/**
	  * does field exist in a table
	  *
	  * @param string the name of the table
	  * @param string the name of the field
	  * @return bool
	  */
	public function isColumn($tableName, $column) {
		$columns = $this->columns($tableName);
		if ( ! $columns )
			return(false);
		return(in_array($column, $columns));
	}
	/*----------------------------------------*/
	/**
	  * number of rows in a table
	  *
	  * @param string the name of the table
	  * @return int the number of rows in the table
	  */
	public function rowNum($t) {
		return($this->getInt("select count(*) from $t"));
	}
	/*----------------------------------------*/
	/**
	  * list of tables in database
	  *
	  * @param string the database name (optional - if not the default database)
	  * @return array list of tables
	  */
	public function tables($db = null) {
		static $cache = null;
		if ( ! $db )
			$db = $this->dbName;

		if ( ! $db )
			return(false);

		if ( isset($cache[$db]) )
			return($cache[$db]);
		$cache[$db] = $this->getStrings("show tables from $db", 24*3600);
		return($cache[$db]);
	}
	/*----------------------------------------*/
	/**
	 * list of databases
	 */
	public function databases() {
		static $cache = null;
		static $excludes = array('performance_schema', 'information_schema', 'mysql', 'test',);
		$dbHost = $this->dbHost;
		if ( isset($cache[$dbHost]) )
			return($cache[$dbHost]);
		$allDatabases = $this->getStrings("show databases");
		$databases = array();
		foreach ( $allDatabases as $db )
			if ( ! in_array($db, $excludes) )
				$databases[] = $db;
		$cache[$dbHost] = $databases;
		return($cache[$dbHost]);
	}
	/*----------------------------------------*/
	/**
	 * does table exist
	 *
	 * @param string the table name
	 * @return bool
	 */
	public function isTable($t) {
		$pair = explode('.', $t);
		if ( count($pair) == 2 ) {
			$dbname = $pair[0];
			$tname = $pair[1];
			$sql = "select count(*) from information_schema.tables where table_schema = '$dbname' and table_name = '$tname'";
			return($this->getInt($sql));
		}
		$tables = $this->tables();
		// with xampp, all table names are lowercase but $t might not be
		return(in_array($t, $tables) || in_array(strtolower($t), $tables));
	}
	/*------------------------------------------------------------*/
	/**
	  * dbInsert() without logging (see dbLog())
	  *
	  * @param string table to insert the data to
	  * @param array associative array with data. Fields not matching columns of the table are silently ignored. 
	  * @return int auto-increment id of the new row
	  */
	 
	public function _dbInsert($tableName, $data, $rememberLastSql = true, $withId = false) {
		if ( ! $this->isConnected ) {
			return(null);
		}

		if ( ($sql = $this->dbInsertSql($tableName, $data, $withId)) == null )
			return(null);

		$affected = $this->_sql($sql, $rememberLastSql);
		if ( $affected != 1 )
			return(null);
		$this->lastInsertId = mysqli_insert_id($this->dbHandle);
		$this->fsck($tableName);
		return($this->lastInsertId);
	}
	/*------------------------------*/
	/**
	  * insert data to database
	  *
	  * @param string table to insert the data to
	  * @param array associative array with data. Fields not matching columns of the table are silently ignored. 
	  * @return int auto-increment id of the new row
	  */
	public function dbInsert($tableName, $data, $withId = false) {
		if ( ($id = $this->_dbInsert($tableName, $data, true, $withId)) == null )
			return(null);
		$this->dbLog($tableName, 'insert', $id);
		return($id);
	}
	/*------------------------------------------------------------*/
	public function bulkInsert($tableName, $rows) {
		$columns = $this->columns($tableName);
		unset($columns[0]); // avoid the id field
		$names = implode(", ", $columns);
		$valuesLists = array();
		foreach ( $rows as $row ) {
			$values = array();
			foreach ( $columns as $column ) {
				if ( isset($row[$column]) ) {
					$value = $row[$column];
					$dbStr = $this->str($value);
					$valueStr = "'$dbStr'";
				} else {
					$valueStr = "null";
				}
				$values[] = $valueStr;
			}
			$valuesString = implode(", ", $values);
			$valuesLists[] = $valuesString;
		}
		$bulkValues = "( ".implode(" ), ( ", $valuesLists)." )";
		$sql = "insert $tableName ( $names ) values $bulkValues";
		$affected = $this->sql($sql);
		$this->fsck($tableName);
		return($affected);
	}
	/*------------------------------------------------------------*/
	/**
	  * update a row of data - raw interface - see also dbUpdate() & dbLog() 
	  *
	  * @param string table name
	  * @param int value of id key identifying the row to be updated
	  * @param array associative array with data. Fields not matching columns of the table are silently ignored. 
	  * @param string the name of the id field if it is not 'id'
	  * @return int -1 on error, 0 if the query had no effect,
	  * 1 if an actual change occured
	  */
	public function _dbUpdate($tableName, $id, $data, $idName = "id") {
		if ( ! $this->isConnected ) {
			return(-1);
		}
		$cols = $this->columns($tableName);
		if ( ! $cols )
			return(-1);
		$this->ammend($data, $cols);
		$origData = $this->getRow("select * from $tableName where $idName = $id");
		$pairs = array();
		foreach ( $data as $fname => $value ) {
			if ( $fname == $idName || ! in_array($fname, $cols) || $this->equiv($origData[$fname], $value) )
				continue;
			if ( $value === null ) {
				$value = null;
				$pairs[] = "$fname = null";
				continue;
			}
			$dataType = $this->dataType($tableName, $fname);
			if ( $dataType == 'timestamp' )
				continue;
			if ( $dataType == 'date' ) {
				if ( $value == '0000-00-00' || $value == null )
					$value = null;
				elseif ( ($value = Mdate::scan($value)) == null )
						continue;
			}
			if ( $dataType == 'datetime' && $value != null && ($value = Mdate::datetimeScan($value)) == null )
					continue;
			if ( strncmp($dataType, "int(", 4) == 0 || $dataType == "double" )
				$value = str_replace(",", "", $value);
			$str = $this->str($value);
			if ( $str === 'now()' )
				$pairs[] = "$fname = $str";
			elseif ( $str === null )
				$pairs[] = "$fname = null";
			else
				$pairs[] = "$fname = '$str'";
		}
		if ( ! $pairs )
			return(0);
		$pairList = implode(", ", $pairs);
		$sql = "update $tableName set $pairList where $idName = $id";
		$affected = $this->_sql($sql);
		$this->fsck($tableName);
		return($affected);
	}
	/*--------------------*/
	/**
	  * update a row of data
	  *
	  * @param string table name
	  * @param int value of id key identifying the row to be updated
	  * @param array associative array with data. Fields not matching columns of the table are silently ignored. 
	  * @param string the name of the id field if it is not 'id'
	  * @return bool true if all is well, (including no-change), false on error
	  */
	public function dbUpdate($tableName, $id, $data, $idName = "id") {
		$affected = $this->_dbUpdate($tableName, $id, $data, $idName);
		if ( $affected > 0 )
			$this->dbLog($tableName, 'update', $id);
		return($affected);
	}
	/*----------------------------------------*/
	/**
	  * delete a row (without logging - see dbLog())
	  *
	  * @param string table name
	  * @param int value of id key identifying the row to be updated
	  * @param string the name of the id field if it is not 'id'
	  * @return int 1 on success, 0 if nothing was deleted, -1 if an error occured
	  */
	public function _dbDelete($tableName, $id, $idName = "id") {
		if ( ! $this->isConnected ) {
			return(-1);
		}
		$sql = "delete from $tableName where $idName = $id";
		$affected = $this->_sql($sql);
		$this->fsck($tableName);
		return($affected);
	}
	/*----------------------------------------*/
	/**
	  * delete a row
	  *
	  * @param string table name
	  * @param int value of id key identifying the row to be updated
	  * @param string the name of the id field if it is not 'id'
	  * @return bool true if all is well, (including no-change), false on error
	  */
	public function dbDelete($tableName, $id, $idName = "id") {
		$affected = $this->_dbDelete($tableName, $id, $idName);
		if ( $affected > 0 )
			$this->dbLog($tableName, 'delete', $id);
		return($affected >= 0);
	}
	/*------------------------------------------------------------*/
	/**
	  * the data type of a column in a table
	  *
	  * @param string the table name
	  * @param string the column name
	  * @return string the data type
	  */
	public function dataType($tableName, $fieldName) {
		static $cache = array();

		if ( isset($cache[$tableName][$fieldName]) )
			return($cache[$tableName][$fieldName]);
		$columnRows = $this->getRows("show columns from $tableName", 2*3600);
		foreach ( $columnRows as $col )
			$cache[$tableName][$col['Field']] = $col['Type'];

		if ( isset($cache[$tableName][$fieldName]) )
			return($cache[$tableName][$fieldName]);
		return(null);
	}
	/*------------------------------------------------------------*/
	private function fsck($tableName) {
		$db = $this->dbName;
		if ( $tableName == 'fsck' ) {
			error_log("fsck: $db:$tableName: table is fsck itself");
			return; // !!!
		}
		if ( ! $this->isTable("fsck") ) {
			error_log("fsck: $db:$tableName: no fsck table");
			return;
		}
		$sql = "select * from fsck where tableName = '$tableName'";
		$fsckRow = $this->getRow($sql);
		$today =  date("Y-m-d");
		if ( $fsckRow ) {
			$lastUpdated = $fsckRow['lastUpdated'];
			$diff = Mdate::diff($today, $lastUpdated);
			if ( $diff < 7 ) {
				error_log("fsck: $db:$tableName: updateed recently");
				return;
			}
		}
		$sql = "select count(*) from $tableName";
		$rows = $this->getInt($sql);
		if ( $fsckRow ) {
			error_log("fsck: $db:$tableName: updating");
			$this->dbUpdate("fsck", $fsckRow['id'], array(
				'lastUpdated' => $today,
				'rows' => $rows,
			));
		} else {
			error_log("fsck: $db:$tableName: new row");
			$this->dbInsert("fsck", array(
				'tableName' => $tableName,
				'lastUpdated' => $today,
				'rows' => $rows,
			));
		}
	}
	/*------------------------------------------------------------*/
	/**
	 * log database activity in a table called queryLog
	 *
	 * Normally only used as 'private',
	 * dbInsert(), dbUpdate(), dbDelete() and sql() attempt
	 * to log activities when successful changes occur,
	 * while _dbInsert(), _dbUpdate(), _dbDelete() and _sql() do not.
	 * 
	 * @param string table name
	 * @param operation performed
	 * @param int id of the affected row
	 * @param string the sql statement performing the operation
	 */
	public function dbLog($tname, $op, $tid, $querySql = null) {
		if ( ! $this->isTable('queryLog') )
			return;
		$excludeTables = array(
			'authLog',
			'errorLog',
			'online',
			'tlog',
			'timeWatch',
			'usageStats',
			'loads',
		);
		if ( in_array($tname, $excludeTables) )
			return;
		$querySql = $querySql ? $querySql : $this->lastSql;
		$querySql = str_replace("\\'", "'", $querySql); // remove added escapes that will be added again by _dbInsert
		foreach ( $excludeTables as $excludeTable ) {
			if ( strstr($querySql, " $excludeTable ") )
				return;
		}
		$row = array(
				'tname' => $tname,
				'op' => $op,
				'tid' => $tid,
				'querySql' => $querySql,
				'loginName' => Mlogin::get('MloginName'),
				'stamp' => date("Y-m-d H:i:s"),
			);
		$this->_dbInsert('queryLog', $row, false);
	}
	/*------------------------------------------------------------*/
	/**
	 * a database ready representation of now()
	 *
	 * @return string
	 */
	public function datetimeNow() {
		$today = Mdate::dash(Mdate::today());
		$now = Mtime::fmt(Mtime::now());
		$ret = "$today $now";
		return($ret);
	}
	/*------------------------------*/
	/**
	 * a database ready representation of now() in a given timezone
	 *
	 * @param string timezone (see date_default_timezone_set())
	 * @return string
	 */
	public function datetimeNowInTZ($tz = null) {
		$todayInTZ = Mdate::dash(Mdate::todayInTZ($tz));
		$nowInTZ = Mtime::nowInTZ($tz, true);
		$ret = "$todayInTZ $nowInTZ";
		return($ret);
	}
	/*------------------------------------------------------------*/
	/**
	 * sql for a sample of rows from table
	 *
	 * @param string name of table
	 */
	public function sampleSql($table) {
		if ( ! $this->isTable($table) )
			return(null);
		$id = $this->autoIncrement($table);
		$fieldList = implode(',', $this->columns($table));

		$rowNum = $this->rowNum($table);
		if ( ! $id ) {
			if ( $rowNum > 500 )
				$limit = "limit 500";
			else
				$limit = "";
			return("select $fieldList from $table $limit");
		}
		if ( $rowNum < 100 )
			$sql = "select $fieldList from $table order by $id";
		elseif ( $rowNum < 1000 )
			$sql = "select $fieldList from $table where $id % 10 = 0 order by $id";
		elseif ( $rowNum < 10000 )
			$sql = "select $fieldList from $table where $id % 100 = 0 order by $id";
		elseif ( $rowNum < 100000 )
			$sql = "select $fieldList from $table where $id % 1000 = 0 order by $id";
		else
			$sql = "select $fieldList from $table order by $id desc limit 100";
		return($sql);
	}
	/*------------------------------------------------------------*/
	public function name($tname, $fname, $id) {
		static $cache = array();
		if ( isset($cache[$tname][$fname][$id]) )
			return($cache[$tname][$fname][$id]);
		if ( ! @$cache[$tname] )
			$cache[$tname] = array();
		if ( ! @$cache[$tname][$fname] ) {
			$cache[$tname][$fname] = array();
			$rows = $this->getRows("select id,$fname from $tname", 30*60);
			foreach ( $rows as $row )
				$cache[$tname][$fname][$row['id']] = $row[$fname];
		}
		return(@$cache[$tname][$fname][$id]);
	}
	/*------------------------------*/
	public function id($tname, $fname, $name, $make = false) {
		static $cache = array();
		if ( isset($cache[$tname][$fname][$name]) )
			return($cache[$tname][$fname][$name]);
		if ( ! @$cache[$tname] )
			$cache[$tname] = array();
		if ( ! @$cache[$tname][$fname] ) {
			$cache[$tname][$fname] = array();
			$rows = $this->getRows("select id,$fname from $tname", 30*60);
			foreach ( $rows as $row )
				$cache[$tname][$fname][$row[$fname]] = $row['id'];
		}
		if ( @$cache[$tname][$fname][$name] )
			return($cache[$tname][$fname][$name]);
		if ( ! $make )
			return(null);
		$id = $this->_dbInsert($tname, array(
			$fname => $name,
		));
		if ( ! $id )
			return(null);
		$cache[$tname][$fname][$name] = $id;
		return($cache[$tname][$fname][$name]);
	}
	/*------------------------------------------------------------*/
	public function lastError() {
		return($this->lastError);
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private function ammend(&$data, $cols, $insert = false) {
		$loginName = Mlogin::get('MloginName');
		if ( $insert && in_array('createdOn', $cols) )
			$data['createdOn'] = date("Y-m-d G:i:s");
		if ( $loginName && $insert && in_array('createdBy', $cols) )
			$data['createdBy'] = $loginName;
		if ( in_array('lastChange', $cols) )
			$data['lastChange'] = date("Y-m-d G:i:s");
		if ( $loginName && in_array('lastChangeBy', $cols) )
			$data['lastChangeBy'] = $loginName;
	}
	/*------------------------------*/
	private function dbInsertSql($tableName, $data, $withId = false) {
		$cols = $this->columns($tableName);
		if ( ! $cols )
			return(null);
		$row = array();
		$idName = $this->autoIncrement($tableName);
		foreach ( $data as $fname => $value ) {
			if ( ! in_array($fname, $cols) )
				continue;
			if ( $fname == $idName && ! $withId )
				continue;
			$dataType = $this->dataType($tableName, $fname);
			if ( $dataType == 'timestamp' )
				continue;
			if ( $dataType == 'date' && ($value = Mdate::scan($value)) == null )
					continue;
			if ( $dataType == 'datetime' && ($value = Mdate::datetimeScan($value)) == null )
					continue;
			if ( strncmp($dataType, "int(", 4) == 0 || $dataType == "double" )
				$value = str_replace(",", "", $value);
			$str = $value;
			if ( $str === null )
				continue;
			$row[$fname] = $str;
		}
		$insertData = array();
		foreach ( $row as $fname => $value )
				$insertData[$fname] = $value;
		$this->ammend($insertData, $cols, true);
		$fieldList = '`'.implode('`,`', array_keys($insertData)).'`';
		$valueList = array();
		$values = array();
		foreach ( $insertData as $value )
			if ( $value === 'now()' )
				$values[] = 'now()';
			else
				$values[] = "'".$this->str($value)."'";
		$valuesList = implode(",", $values);
		$sql = "insert into $tableName ( $fieldList ) values ( $valuesList )";
		return($sql);
	}
	/*------------------------------------------------------------*/
	/*
	 * do fields have equivalent values:
	 * nulls and empty strings, dates in int or dashed format are equivalent
	 * updating of equivalent values is skipped
	 */
	private function equiv($a, $b) {
		if ( $a === $b )
			return(true);
		$alen = strlen($a);
		$blen = strlen($b);
		// dates
		if (
			( $alen == 10 || $alen == 8 )
			&& str_replace('-', '', $a) === str_replace('-', '', $b)
			)
			return(true);
		// text
		if (
			str_replace("\r\n", "\n", $a) === str_replace("\r\n", "\n", $b)
			)
			return(true);
		return(false);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
