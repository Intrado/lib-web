<?php

/**
 * Dependency Injector Library Class
 *
 * A simple dependency container which supports the accumulation of a collection of dependencies.
 * Each new instance begins with an empty collection. Supporting methods are provided to add items
 * to the collection and retrieve them by name. This it logically equivalent to using an associative
 * array to collect named dependencies except that we standardize the interface such that it may be
 * centrally altered in the future.
 */
class Injector {

	/**
	 * Dependencies collection
	 */
	protected $dependencies;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->dependencies = array();
	}

	/**
	 * Set a named dependency
	 *
	 * @param $name String name of the dependency to set
	 * @param $dependency Object instance of the dependency to set
	 */
	public function setDependency($name, $dependency) {
		$this->dependencies[$name] = $dependency;
	}

	/**
	 * Get a named dependency
	 *
	 * @param $name String name of the dependency to get
	 *
	 * @return Object instance of the named depenendency if it is set, else null
	 */
	public function getDependency($name) {
		return $this->hasDependency($name) ? $this->dependencies[$name] : null;
	}

	/**
	 * Check whether we have the named dependency
	 *
	 * @param $name String name of the dependency to get
	 *
	 * @return boolean true if we have the named dependency set, else false
	 */
	public function hasDependency($name) {
		return isset($this->dependencies[$name]);
	}
}

