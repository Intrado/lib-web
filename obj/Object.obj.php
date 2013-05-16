<?php

/**
 * Object base class ala Java
 *
 * The idea here is that application classes derived from this will all share a
 * a common function base which provides a uniform interface for debugging.
 * Please expand as needed/useful; this shouldn't end up being a library of
 * miscellaneous functionality, but rather things that are specific to debug and
 * support of the application and which have broad reaching significance.
 *
 * see: UNITTEST/PHPUnit/application/ObjectTest.php
 */
abstract class Object {

	private $classname = '';

	public function __construct() { }

	/**
	 * The derived class can set the name here in its own constructor with:
	 *
	 * $this->set_classname(__CLASS__);
	 *
	 * By setting the classname in the child's constructor, the other methods
	 * in this parent level class knwo which child it is dealing with.
	 */
	protected function set_classname($name) {
		$this->classname = $name;
	}

	/**
	 * This generic exception thrower allows us to create uniformly formatted
	 * exception messages in the error log which include the class name and
	 * the line number of the class source file that they occurred in. It can
	 * be invoked from any derived class like so:
	 *
	 * $this->except('You have been eaten by a gru.', __LINE__);
	 */
	protected function except($message, $line = 0) {
		$msg = "Exception in {$this->classname}";
		if ($line > 0) $msg .= " on line {$line}";
		$msg .= ": {$message}";
		throw new Exception($msg);
	}
}

?>
