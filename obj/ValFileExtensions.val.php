<?

class ValFileExtensions extends Validator {
	var $isrequired = true;

	// Server side validation
	function validate ($value, $args) {

		// Make sure the value is an array of file info
		if (! is_array($value)) {
			return false;
		}

		// Make sure we have a filename to work with
		if (! isset($value['name'])) {
			return false;
		}

		// Make sure there is a file extension to block file named
		// one of supported file extensions with no actual extension
		if (strpos($value['name'], '.') === false) {
			return false;
		}

		// Get the file extension (last dotted segment)
		$filenameParts = explode('.', $value['name']);
		$ext = strtolower($filenameParts[count($filenameParts) - 1]);

		// Make sure uploaded file extension is in the list of accepted extensions
		return in_array($ext, $args['acceptExts']);
	}

	// Client side validation
	function getJSValidator () {
		return <<<END
			function (name, label, value, args) {

				// If no acceptExts are supplied then the validator is not properly initialized
				if (typeof(args['acceptExts']) !== 'object') {
					return 'This validator was not supplied with a list of accepted file extensions';
				}

				var failMessage = label + ' must have one of the following file extensions:';
				// For each of the accepted file extensions...
				for (extIndex in args['acceptExts']) {
					var checkExt = args['acceptExts'][extIndex];

					// filter out Array object methods that come back with this iteration
					if (typeof(checkExt) === 'function') {
						continue;
					}

					failMessage += ' .' + checkExt;
				}


				// Make sure we have some '.' file extension somewhere past the first character...
				var valueExtPos = value.lastIndexOf('.');
				if (valueExtPos <= 0) {
					return failMessage;
				}

				// Get the file extension
				var valueExt = value.substring(valueExtPos + 1);

				var extIndex;

				if (args['acceptExts'].indexOf(valueExt) !== -1) {
					return true;
				}

				// We didn't find our value's extension in the list of accepted extensions :(
				return failMessage;
			}
END;
	}
}

