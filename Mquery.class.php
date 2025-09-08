<?php
/*------------------------------------------------------------*/
/**
  * 
  * Mquery
  *
  * View SQL query results and export to excel
  *
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
  * Mquery is a general purpose facility to query the database
  * and display and export information with ease.
  *
  * The data display can be exported to Excel with a single click.
  *
  * With an empty query, a list of tables is shown.<br />
  * with a partial name of a table, a search is performed to locate the matching tables <br />
  * with a single table name, a query is formulated, some data is presented,
  * and the query can be further manipulated with ease.<br />
  * With the intention of making this interface useful in production environments,
  * and safe for for non-programming personnel
  * queries that are not read-only are not executed, and a message is issued instead.<br />
  * In tandem with McsvLoader, this can be a useful ad-hoc analysis tool for Excel data
  *
  * @package M
  */
/*------------------------------------------------------------*/
class Mquery extends Mcontroller {
	/*------------------------------------------------------------*/
	/**
	 * the main and default action is to show the Mquery form
	 */
	public function index() {
		$this->form();
	}
	/*------------------------------------------------------------*/
	/**
	 * show the Mquery form
	 */
	public function form($sql = null) {
		$this->Mview->showTpl("Mquery.tpl", array('sql' => $sql,));
	}
	/*------------------------------------------------------------*/
	/**
	 * execute the result from the form
	 */
	public function go() {
		ini_set('max_execution_time', 900);
		ini_set("memory_limit","30M");
		$dbName = M_DBNAME;
		$tableName = null;
		$str = trim($_REQUEST['sql']);
		$str = str_replace("\\", "", $str);
		if ( $str == "" )
			$sql = "select TABLE_NAME, TABLE_ROWS from information_schema.tables where TABLE_SCHEMA = '$dbName' order by TABLE_NAME";
		elseif ( strstr($str, ' ') )
			$sql = $str;
		elseif ( $this->Mmodel->isTable($str) ) {
			$tableName = $str;
			$sql = $this->Mmodel->sampleSql($str);
		} else {
			$low = strtolower($str);
			$sql = "select TABLE_NAME, TABLE_ROWS from information_schema.tables where TABLE_SCHEMA = '$dbName' and lower(TABLE_NAME) like '%$low%'";
		}

		$this->form($sql);

		$isReadOnly = true;
		if ( strncasecmp($sql, "show ", 5) != 0 && strncasecmp($sql, "select ", 7) != 0 )
			$isReadOnly = false;
		foreach ( array('insert', 'update', 'delete',) as $cmd ) {
			if ( strncmp($sql, $cmd, strlen($cmd)) == 0 || strstr($sql, " $cmd ") )
				$isReadOnly = false;
		}
		if ( ! $isReadOnly ) {
			$filePath = __FILE__;
			$parts = explode('/', $filePath);
			$fileName = $parts[count($parts)-1];
			Mview::error($fileName.":". __LINE__.": Queries that are not read-only are not supported.");
			return;
		}
		$rows = $this->Mmodel->getRows($sql);
		$this->Mview->showRows($rows);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
