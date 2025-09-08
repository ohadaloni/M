<?php
/*------------------------------------------------------------*/
/**
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
  * 
  * McsvLoader is a facility to load a CSV file into a mysql table.
  *
  * A new table name is always sensibly created from the file name.<br />
  * Label names are analysed to create column names.<br />
  * Data is analysed to determine column datatypes, most date formats are well understood.<br />
  *
  * @package M
  */
/*------------------------------------------------------------*/
class McsvLoader extends Mcontroller {
	/*------------------------------------------------------------*/
	private $tableName = null;
	private $columns = null;
	/*------------------------------------------------------------*/
	/**
	  * use the uploaded csv file to create a table in the database<br />
	  * in the upload form:<br />
	  *		the field 'file' is the uploaded file field. (input type="file" name="file")<br />
	  *		the file name is used to create the table name<br />
	  *		the header row titles are used to create column names<br />
	  *		an id auto_increment column is prepended regardless, with the name 'id'	<br />
	  */
	public function load($maxRows = null, $makeNewTableNameIfNeeded = false) {
		ini_set('max_execution_time', 900);
		ini_set("memory_limit","30M");
	
		if ( @$_REQUEST['tableName'] )
			$this->tableName = $_REQUEST['tableName'];
		$fileInfo = Mutils::uploadedFileInfo();
		if ( ! $fileInfo )
			return(null);
		$file = $fileInfo['file'];
		$fileName = $fileInfo['name'];
		$ext = substr($fileName, -3, 3);
		if ( $ext != 'csv' ) {
			Mview::error("$fileName: Must have extention .csv to proceed. (Is this a csv file?).");
			return(null);
		}
		if ( ! $this->tableName )
			$this->tableName = $this->makeTableName($fileInfo['name'], $makeNewTableNameIfNeeded);
		$tableName = $this->tableName;

		if ( ($fp = fopen($file, "r")) == false ) {
			Mview::error("Can not open $file");
			return(null);
		}
		$headerLine = fgetcsv($fp);
		if ( ! $headerLine ) {
			Mview::error("$file: No Header Line");
			return(null);
		}
		$sampleRows = array();
		for ($i=0; $i < 200 && ($row = fgetcsv($fp));$i++ )
			$sampleRows[] = $row;
		@fclose($fp);
		$this->setColumns($headerLine, $sampleRows);
		if ( @$_REQUEST['dropTableIfExists'] ) {
			$dropTable = $_REQUEST['dropTableIfExists'];
			$this->Mmodel->sql("drop table if exists $dropTable");
		}
		if ( $this->Mmodel->isTable($tableName) && @$_REQUEST['dropTableIfExists'] !== $tableName ) {
			Mview::msg("$tableName - Already Exists. Drop first, or set input dropTableIfExists=$tableName to drop it automatically.");
			return(null);
		}
		if ( ! $this->createTable() ) {
			return(null);
		}
		if ( ($fp = fopen($file, "r")) == false ) {
			Mview::error("Can not open $file");
			return(null);
		}
		$headerLine = fgetcsv($fp);
		$loaded = 0;
		for ($lineNo=1; $row = fgetcsv($fp);$lineNo++ ) {
			if ( $maxRows != null && $loaded > $maxRows ) {
				$this->Mview->msg("Limit reached.");
				break;
			}
			if ( $this->loadLine($row, $lineNo) )
				$loaded++;
		}
		@fclose($fp);
		$rowNum = $this->Mmodel->rowNum($tableName);
		if ( $rowNum > 0 )
			return($tableName);
		Mview::error("$tableName - Nothing Loaded");
		return(null);
	}
	/*------------------------------------------------------------*/
	private function createTable() {
		$tableName = $this->tableName;
		if ( $this->Mmodel->isTable($tableName) )
			$this->Mmodel->sql("drop table $tableName");
		$createSql = "create table $tableName ( id int auto_increment";
		foreach ( $this->columns as $key => $column ) {
			$colName = $column['colName'];
			if ( ! $colName )
				continue;
			$typeEtc = $column['typeEtc'];
			$createSql .= ", $colName $typeEtc";
		}
		$createSql .= ", primary key (id ) )";
		$ret = $this->Mmodel->sql($createSql);
		return($ret !== null);
	}
	/*------------------------------------------------------------*/
	private function loadLine($row) {
		$data = array();
		foreach ( $this->columns as $key => $column ) {
			$colName = $column['colName'];
			if ( ! $colName )
				continue;
			$value = $row[$key];
			if ( $value === "" )
				$data[$colName] = null;
			elseif ( $column['converter'] ) {
				$converter = $column['converter'];
				$data[$colName] = $this->$converter($value);
			} else
				$data[$colName] = $value;
		}
		$id = $this->Mmodel->dbInsert($this->tableName, $data);
		return($id);
	}
	/*------------------------------------------------------------*/
	private function excelName($n) {
		if ( $n < 0 || $n > (26*26) )
			return("Mcsv$n");
		if ( $n < 26 )
			return(chr(ord('A') + $n));
		$secondN = $n % 26;
		$firstN = ($n - $secondN) / 26 - 1;
		$firstChar = chr(ord('A') + $firstN);
		$secondChar = chr(ord('A') + $secondN);
		$ret = $firstChar.$secondChar;
		return($ret);
	}
	/*------------------------------------------------------------*/
	private function dateFrom($value, $separator, $order = "mdy") {
		$parts = explode($separator, $value);
		if ( count($parts) != 3 )
			return(null);
		list($a, $b, $c) = $parts;
		switch ( $order ) {
			case 'ymd' :
					$y = $a;
					$m = $b;
					$d = $c;
				break;
			case 'dmy' :
					$d = $a;
					$m = $b;
					$y = $c;
				break;
			
			case 'mdy' :
			default:
					$m = $a;
					$d = $b;
					$y = $c;
		}
		if ( $d < 1 || $d > 31 )
			return(null);
		if ( $m < 1 || $m > 12 )
			return(null);
		if ( $y < 1 || $y > 3000 )
			return(null);
		if ( $y < 25 )
			$y += 2000;
		elseif ( $y < 100 )
			$y += 1900;
		return(sprintf("%04d-%02d-%02d", $y, $m, $d));
	}
	/*--------------------------------------------------*/
	private function dateFromUs($value) {
		return($this->dateFrom($value, '/', "mdy"));
	}
	/*------------------------------*/
	private function dateFromIl($value) {
		return($this->dateFrom($value, '/', "dmy"));
	}
	/*------------------------------*/
	private function dateFromEuro($value) {
		return($this->dateFrom($value, '.', "dmy"));
	}
	/*------------------------------*/
	private function dateFromStd($value) {
		return($this->dateFrom($value, '-', "ymd"));
	}
	/*----------------------------------------*/
	private function isUsDate($value) {
		return( $value === "" || $this->dateFrom($value, '/', "mdy") != null);
	}
	/*------------------------------*/
	private function isIlDate($value) {
		return( $value === "" || $this->dateFrom($value, '/', "dmy") != null);
	}
	/*------------------------------*/
	private function isEuroDate($value) {
		return( $value === "" || $this->dateFrom($value, '.', "dmy") != null);
	}
	/*------------------------------*/
	private function isStdDate($value) {
		return( $value === "" || $this->dateFrom($value, '-', "ymd") != null);
	}
	/*----------------------------------------*/
	private function areUsDates($values) {
		foreach ( $values as $value )
			if ( ! $this->isUsDate($value) )
				return(false);
		return(true);
	}
	/*------------------------------*/
	private function areIlDates($values) {
		foreach ( $values as $value )
			if ( ! $this->isIlDate($value) )
				return(false);
		return(true);
	}
	/*------------------------------*/
	private function areEuroDates($values) {
		foreach ( $values as $value )
			if ( ! $this->isEuroDate($value) )
				return(false);
		return(true);
	}
	/*------------------------------*/
	private function areStdDates($values) {
		foreach ( $values as $value )
			if ( ! $this->isStdDate($value) )
				return(false);
		return(true);
	}
	/*------------------------------------------------------------*/
	private function columnDD($title, $position, $sampleValues) {
		$defaultName = $this->excelName($position);
		$columnName = $this->canonizeColumnName($title, $defaultName);
		if ( $this->areUsDates($sampleValues) )
			$ret = array(
				'colName' => $columnName,
				'typeEtc' => "date",
				'converter' => "dateFromUs",
			);
		else if ( $this->areIlDates($sampleValues) )
			$ret = array(
				'colName' => $columnName,
				'typeEtc' => "date",
				'converter' => "dateFromIl",
			);
		else if ( $this->areEuroDates($sampleValues) )
			$ret = array(
				'colName' => $columnName,
				'typeEtc' => "date",
				'converter' => "dateFromEuro",
			);
		else if ( $this->areStdDates($sampleValues) )
			$ret = array(
				'colName' => $columnName,
				'typeEtc' => "date",
				'converter' => 'dateFromStd',
			);
		else
			$ret = array(
				'colName' => $columnName,
				'typeEtc' => "varchar(40)",
				'converter' => null,
			);
		return($ret);
	}
	/*------------------------------*/
	private function setColumns($headerLine, $sampleRows) {
		$this->columns = array();
		foreach ( $headerLine as $key => $heading )
			$this->columns[] = $this->columnDD($heading, $key, Mutils::arrayColumn($sampleRows, $key));
	}
	/*------------------------------------------------------------*/
	private function canonizeName($name) {
		$canon = "";
		$strlen = strlen($name);
		for($i=0;$i<$strlen;$i++) {
			$c = $name[$i];
			if ( $canon == "" && ctype_digit($c) )
				continue;
			if ( ctype_alnum($c) || $c === '_' )
				$canon .= $c;
		}
		return($canon);
	}
	/*------------------------------------------------------------*/
	private function isColumn($name) {
		if ( $name == 'id' )
			return(true);
		foreach ($this->columns as $column)
			if ( $column['colName'] == $name )
				return(true);
		return(false);
	}
	/*------------------------------*/
	private function canonizeColumnName($name, $defaultName = null) {
		$canon = $this->canonizeName($name);
		if ( ! $canon )
			$canon = $defaultName;
		if ( ! $canon )
			$canon = "A";
		$colName = $canon;
		for($i=2;$this->isColumn($colName);$i++)
			$colName = "$canon$i";
		return($colName);
	}
	/*------------------------------------------------------------*/
	private function makeTableName($fileName, $makeNewTableNameIfNeeded) {
		$len = strlen($fileName);
		$root = substr($fileName, 0, $len - 4);
		$candidateName = $this->canonizeName($root);
		if ( ! $candidateName )
			$candidateName = 'McsvLoaderTable';

		if ( ! $makeNewTableNameIfNeeded )
			return($candidateName);
		$name = $candidateName;
		for($i=2;$this->Mmodel->isTable($name);$i++)
			$name = "$candidateName$i";
		return($name);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
