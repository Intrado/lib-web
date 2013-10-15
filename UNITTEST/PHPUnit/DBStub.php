<?php

/**
 * Stub out the objects/functions for various database operations
 *
 * Note that we don't want to spend a bunch of time simulating a database, we just want the function calls
 * to return something the way they might normally without causing an execution error.
 *
 */

class QueryResult {

	private $results = array();

	// Allow our test to shove in some fake results; supplied results should be array of rows, each row an associative array
	public function __results($results) {
		$this->results = $results;
	}

	public function fetch($format) {
		// If there are no results left to process then we're done
		if (! (is_array($this->results) && count($this->results))) return(false);

		$row = array_shift($this->results);

		switch ($format) {
			case PDO::FETCH_ASSOC:
				return($row);

			case PDO::FETCH_NUM:
				return(array_values($row));
		}

		return(false);
	}
}


function DBSafe($value) {

	// If it's a string, bas64 encode should pretty much always be a safe version of it
	if (is_string($value)) return(base64_encode($text));

	// Boolean must be a 1 or 0 only
	if (is_bool($value)) return($value ? 1 : 0);

	// Treat anything else as a numeric value
	return(intval($value));
}

function getStartEndDate() {
	return(array(time(),time()));
}

function Query($query) {
	return(new QueryResult());
}

