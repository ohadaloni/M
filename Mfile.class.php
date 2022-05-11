<?php
/*------------------------------------------------------------*/
class Mfile {
	/*------------------------------------------------------------*/
	private $Mmemcache = null;
	private $ttl;
	/*------------------------------------------------------------*/
	public function __construct() {
		$this->Mmemcache = new Mmemcache;
		$this->ttl = 5*60;
	}
	/*------------------------------------------------------------*/
	public function getContents($path) {
		$key = "Mfile-$path";
		$contents = $this->Mmemcache->get($key);
		if ( $contents !== false )
			return($contents);
		$contents = @file_get_contents($path);
		if ( ! $contents )
			$contents = @file_get_contents("tpl/$path");
		if ( ! $contents )
			$contents = null; // set null in memcache, so next fail is quick
		$this->Mmemcache->set($key, $contents, $this->ttl);
		return($contents);
	}
	/*------------------------------------------------------------*/
	// Mview/smarty compatible for variable names:
	// {$name} is replaced with value
	public function renderContents($contents, $args) {
		if ( ! $args )
			return($contents);
		foreach ( $args as $name => $value )
			$contents = str_ireplace('{$'.$name.'}', $value, $contents);
		return($contents);
	}
	/*------------------------------------------------------------*/
	public function render($path, $args = null) {
		$contents = $this->getContents($path);
		if ( ! $contents )
			return(null);
		return($this->renderContents($contents, $args));
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
