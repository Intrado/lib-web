<?php

/**
 * Environment Library Class
 *
 * A collection of library functions that help us discover details about and interact with our
 * current running environment.
 *
 * Dependency Injections:
 *   '_SERVER': environment settings ala the $_SERVER superglobal
 */
class Environment {

	/**
	 * Dependency injector
	 */
	protected $injector;

	/**
	 * Constructor
	 *
	 * @param $injector Object instance of lib/Injector class
	 */
	public function __construct(&$injector) {
		$this->injector =& $injector;

		// Verify that we got the needed dependencies injected
		if (! $this->injector->hasDependency('_SERVER')) {
			throw new Exception('Required dependency "_SERVER" was not supplied');
		}
	}

	/**
	 * Determine whether the current request is publicly protected with SSL
	 *
	 * @return boolean true if the request is SSL protected, else false
	 */
	public function isSSL() {
		$_server = $this->injector->getDependency('_SERVER');
		if (isset($_server['HTTPS'])) return true;
		if (isset($_server['HTTP_X_SSL'])) return true;
		if (
			isset($_server['HTTP_X_FORWARDED_PROTO']) &&
			('https' === strtolower($_server['HTTP_X_FORWARDED_PROTO']))
		) {
			return true;
		}
		return false;
	}

	/**
	 * Determine whether the current request has originated via an HTTP service
	 *
	 * @return boolean true if the request is HTTP originated, else false
	 */
	public function isHttp() {
		$_server = $this->injector->getDependency('_SERVER');
		return isset($_server['SERVER_ADDR']);
	}
}

