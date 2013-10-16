<?php

/**
 * obj/Formatters.obj.php - A testable library of formatter methods
 *
 * DIRECT EXTERNAL DEPENDENCIES
 * inc/formatters.inc.php
 * messagedata/en/targetedmessage.php
 *
 * @author skelly@schoolmessenger.com
 * @package libraries
 */

/**
 * A testable library of formatter methods
 *
 *
 * CONTRACT / CONVENTIONS
 * For the individual fmt_field_*() methods, the first argument should be the
 * value that we want to format, and the second the data array that the value
 * came from; this allows the formatter to access other fields in the data as
 * needed to provide addition context about how to format this value. Beware
 * that dependency on data in this way requires that all callers conform to the
 * same structure for data when calling such a field formatter.
 *
 * For formatters that conditionally perform work, if none of the conditions
 * are satisfied, the original, value should be returned, or some variant of
 * it in the format that the formatter is expected to return (e.g. if an array
 * is supplied but a string is expected, the formatter should have a way to
 * convert the array to a useful string even if it is unable to perform any
 * transformations on the individual array values).
 */
class Formatters {

	public function __construct() {
	}

	/**
	 * Format supplied data into a line of CSV text
	 *
	 * If the $data provided has 4 keys (either numeric or hashed), the
	 * $keys array may have up to 4 elements, each with a unique key from
	 * $data - they may not be any duplicates, and all they keys in $keys
	 * must be one of the keys from the $data. The ONLY data that will make
	 * it into the final CSV output line will be the nodes with keys in
	 * $keys. This lets us provide surplus data to the formatter and only
	 * show what we want in the output.
	 * 
	 * All specified formatters must be methods in this class, even if they
	 * are wrappers to external/global functions.
	 *
	 * @param array $data Associative array (1D) of data values for the CSV
	 *   line, one value per key
	 * @param array $keys Array of keys (1D); a subset of the available keys
	 *   in $data up to the complete set
	 * @param array $formatters Associative array (1D) of formatter method
	 *   names to use mapped to data keys (optional)
	 *
	 * @return string A line of CSV text with a subset of data specified by
	 *   the keys and formatted by the formatters, without trailing \r or \n
	 */
	public function fmt_csv_line($data, $keys, $formatters = array()) {
		$formatted = array();

		// For each data key in keys (must be a subset of data's keys)
		foreach ($keys as $key) {

			// Add either the supplied data value for this key, or a formatted
			// version of it if a formatter is spefcified for this key
			$formatted[] = (isset($formatters[$key])) ? $this->$formatters[$key]($data[$key], $data) : $data[$key];
		}

		return(array_to_csv($formatted));
	}

	/**
	 * Format a message delivery result field which maybe multipurposed for
	 * phone or email
	 *
	 * The data array provides context in this case where data[type] lets us
	 * know if it was an email result or a phone result and thus we can
	 * format differently depending on which it was.
	 *
	 * @param string $value The result value that we want to format
	 * @param array Array (1D) of data that this value was plucked from
	 *
	 * @return string a formatted version of the supplied value
	 */
	public function fmt_field_phone_or_email_result($value, $data) {

		// We can't do anything if we don't know the type
		if (! isset($data['type'])) return($value);

		// Translate some of the raw values into something human readable
		switch ($data['type']) {
			case 'email':
				// Translate value as an email result
				return($this->fmt_field_email_result($value));

			case 'phone':
				// Translate value as a phone result
				return($this->fmt_field_phone_result($value));
		}

		return($value);
	}

	/**
	 * Format a phone delivery result field
	 *
	 * @param string the raw result for a phone message delivery attempt
	 * @param array Array of data that value came from;
	 *
	 * @return string Readable text representation of the result code
	 */
	public function fmt_field_phone_result($value, $data = array()) {
		return(fmt_result(array($value), 0));
	}

	/**
	 * Format an email delivery result field
	 *
	 * @param string the raw result for an email message delivery attempt
	 *
	 * @return string Readable text representation of the result code
	 */
	public function fmt_field_email_result($value, $data = array()) {
		return(fmt_email_result(array($value), 0));
	}

	/**
	 * Format a classroom messaaging messagekey into a full description
	 *
	 * @param string the short messagekey descriptor stored in the database
	 * @param array Array of data that value came from;
	 *
	 * @return string One of the full message desciptions that matches this
	 *   messagekey, or an empty string if there isn't a match
	 */
	public function fmt_field_messagekey($value, $data = array()) {
		global $messagedatacache;
		return(isset($messagedatacache['en'][$value]) ? $messagedatacache['en'][$value] : '');
	}
}
