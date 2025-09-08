<?php
/*------------------------------------------------------------*/
class LlNode {
	public $value;
	public $next;
	public $prev;
	/*------------------------------------------------------------*/
	public function __construct($value, $prev = null, $next = null) {
		$this->value = $value;
		$this->prev = $prev;
		$this->next = $next;
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
class Ll {
	/*------------------------------------------------------------*/
	public $start, $end;
	private $length = 0;
	/*------------------------------------------------------------*/
	public function push($value) {
		if ( ! $this->start ) {
			$this->start = $this->end = new LlNode($value);
			$this->length++;
			return(true);
		}
		$node = new LlNode($value, $this->end, null);
		
		$this->end->next = $node;
		$this->end = $node;
		$this->length++;
		return(true);
	}
	/*------------------------------------------------------------*/
	public function pop() {
		if ( ! $this->end )
			return(null);
		$end = $this->end;
		if ( $end->prev ) {
			$end->prev->next = null;
			$this->end = $end->prev;
		} else {
			$this->start = $this->end = null;
		}
		$this->length--;
		return($end->value);
	}
	/*------------------------------------------------------------*/
	public function peek($end = false) {
		if ( ! $this->start )
			return(null);
		if ( $end )
			return($this->end->value);
		else
			return($this->start->value);
	}
	/*------------------------------------------------------------*/
	public function shift() {
		if ( ! $this->start )
			return(null);
		$start = $this->start;
		if ( $start->next ) {
			$start->next->prev = null;
			$this->start = $start->next;
		} else {
			$this->start = $this->end = null;
		}
		$this->length--;
		return($start->value);
	}
	/*------------------------------------------------------------*/
	public function unshift($value) {
		if ( ! $this->start ) {
			$this->start = $this->end = new LlNode($value);
			$this->length++;
			return(true);
		}
		$node = new LlNode($value, $this->start, null);
		
		$this->start->prev = $node;
		$this->start = $node;
		$this->length++;
		return(true);
	}
	/*------------------------------------------------------------*/
	public function replaceNode($node, $newValue) {
		$newNode = new LlNode($newValue, $node->prev, $node->next);
		if ( $newNode->prev )
			$newNode->prev->next = $newNode;
		if ( $newNode->next )
			$newNode->next->prev = $newNode;
	}
	/*------------------------------------------------------------*/
	public function find($value, $cmpFunc = null) {
		for($node=$this->start; $node; $node = $node->next) {
			if ( $cmpFunc )
				$found = call_user_func($cmpFunc, $value, $node->value);
			else
				$found = $node->value == $value;
			if ( $found )
				return($node);
		}
		return(null);
	}
	/*------------------------------------------------------------*/
	public function remove($node) {
		if ( ! $node->prev ) {
			// its the first
			// maybe also the last
			$this->start = $node->next;
			if ( $this->start )
				$this->start->prev = null;
			return;
		}
		if ( ! $node->next ) {
			// its the last
			// but not the first
			$this->end = $node->prev;
			$this->end->next = null;
			return;
		}
		// somewhere in the middle
		$prev = $node->prev;
		$next = $node->next;
		$prev->next = $next;
		$next->prev = $prev;
	}
	/*------------------------------------------------------------*/
	public function _traverse($visitFunc) {
		for($node=$this->start; $node; $node = $node->next)
			call_user_func($visitFunc, $node);
	}
	/*------------------------------------------------------------*/
	public function traverse($visitFunc) {
		for($node=$this->start; $node; $node = $node->next)
			call_user_func($visitFunc, $node->value);
	}
	/*------------------------------------------------------------*/
	public function toString($inReverse = false, $valueToStringFunc = null) {
		$values = array();
		if ( $inReverse )
			for($node=$this->end; $node; $node = $node->prev)
				$values[] = $this->valueToString($node->value);
		else
			for($node=$this->start; $node; $node = $node->next)
				$values[] = $this->valueToString($node->value, $valueToStringFunc);
		$str = implode(",", $values);
		return($str);
	}
	/*------------------------------*/
	private function valueToString($value, $valueToStringFunc = null) {
		if ( ! $valueToStringFunc )
			return("$value");
		return(call_user_func($valueToStringFunc, $value));
	}
	/*------------------------------------------------------------*/
	public function length() {
		return($this->length);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
