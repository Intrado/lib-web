<?php

/**
 * Helper class for checking out external dependencies
 * (Singleton Pattern)
 *
 * ref: http://www.phptherightway.com/pages/Design-Patterns.html
 * Also check out SingletonBase: http://stackoverflow.com/questions/3237562/implement-a-php-singleton-static-class-properties-or-static-method-variables
 */
class Dependencies {

	/**
	 * Our singleton instance
	 */
	private static $instance;

	/**
	 *
	 */
	public static function getInstance($grapiClient = null) {

		// If we don't already have an instance then we need to instantiate
		if (is_null(static::$instance)) {

			// If our dependencies have not been injected then we can't instantiate
			if (is_null($grapiClient)) {
				error_log('Dependencies::getInstance() - First attempt did not inject dependencies');
				return null;
			}

			// Instantiate
			static::$instance = new static($grapiClient);
		}
		return static::$instance;
	}

	/**
	 * Constructor - protected so that only new() can get at it
	 */
	protected function __construct ($grapiClient) {
		$this->grapiClient = $grapiClient;
		$this->dependencies = array();
		$this->dependencyNames = array('globalregistry');
	}

	/**
	 * Prevent cloning singleton
	 */
	private function __clone () { }

	/**
	 * Prevent deserializing singleton
	 */
	private function __wakeup () { }

	/**
	 *
	 */
	protected $dependencyNames;

	/**
	 *
	 */
	protected $dependencies;

	/**
	 *
	 */
	private $grapiClient;

	/**
	 * Get the state of a known dependency by name
	 *
	 * @return boolean true if the named dependency is thought to be up and available, else false
	 */
	public function getState($dependencyName) {
		return isset($this->dependencies[$dependencyName]) ? $this->dependencies[$dependencyName] : false;
	}

	/**
	 * Get ourselves initialized
	 */
	private function init() {
		foreach ($this->dependencyNames as $dependencyName) {
			$this->poll($dependencyName);
		}
	}

	/**
	 * Poll for the state for a named dependency
	 *
	 * Since PHP doesn't have any multi-threaded/async support, we will be waiting for whatever
	 * dependency is requested to respond. Reasonable timeouts are important here, or several
	 * dependencies timing out will cause a given page load to appear to take a while to fail.
	 */
	private function poll($dependencyName) {
		$state = false;
		switch ($dependencyName) {
			case 'globalregistry':
				$state = $this->grapiClient->getStatus();
				break;

			default:
				error_log("Dependencies.poll('{$dependencyName}') -> unrecognized dependency name!");
				break;
		}
		$this->dependencies[$dependencyName] = $state;
	}
}
