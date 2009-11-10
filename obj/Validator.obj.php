<?

abstract class Validator {
	var $name;
	var $label;
	var $onlyserverside = false; //set this if you don't have a JS validator
	var $requiredfields = array();

	/*
	 * spits out javascript required to install a validator in the form
	 * Takes a list of class names
	 */
	static function load_validators ($validators) {

		echo "if (!document.validators) document.validators = {};\n";

		foreach ($validators as $validator) {
			$obj = new $validator();
?>
		//constructor for <?=$validator?>

		document.validators["<?=$validator?>"] =
			function (name, label, args) {
				this.validator = "<?=$validator?>";
				this.onlyserverside = <?= $obj->onlyserverside ? "true" : "false" ?>;
				this.name = name;
				this.label = label;
				this.args = args;
				this.validate = <?= $obj->getJSValidator() ?>;
		};
<?

		}
	}

	/*
	 * works pre-merge
	 */
	static function validate_item ($formdata,$name,$value,$requiredvalues = array()) {
		$validators = $formdata[$name]['validators'];

		foreach ($validators as $validatordata) {
			$validator = $validatordata[0];
			//only validate non empty values (unless its the ValRequired validator)
			if ($validator == "ValRequired" || ((is_array($value) && count($value)) || (!is_array($value) && mb_strlen($value) > 0))) {
				$obj = new $validator();
				$obj->label = $formdata[$name]['label'];
				$obj->name = $name;

				$res = $obj->validate($value, $validatordata,$requiredvalues);

				if ($res !== true)
					return array($validator,$res);
			}
		}
		return true;
	}


	/* abstract */
	function validate($value, $args) {
		return "Unimplemented";
	}

	/* abstract */
	function getJSValidator () {
		return '
			function (name, label, value, args) {
				//...
				return true;
			}
		';
	}

}

class ValRequired extends Validator {
	function validate ($value, $args) {
		if ((is_array($value) && !count($value)) || (!is_array($value) && mb_strlen($value) == 0))
			return "$this->label is required";
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				if (value.length == 0)
					return label + " is required";
				return true;
			}';
	}
}

class ValLength extends Validator {

	function validate ($value, $args) {
		$hasmin = isset($args['min']) && $args['min'] !== false;
		if ($hasmin)
			$min = $args['min']+0;
		$hasmax = isset($args['max']) && $args['max'] !== false;
		if ($hasmax)
			$max = $args['max']+0;

		if ($hasmin && mb_strlen($value) < $min)
			return "$this->label must be at least $min characters long";
		if ($hasmax && mb_strlen($value) > $max)
			return "$this->label cannot be more than $max characters long";

		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				if (args.min && value.length < args.min)
					return label + " must be at least " + args.min + " characters long";
				if (args.max && value.length > args.max)
					return label + " cannot be more than " + args.max + " characters long";
				return true;
			}';
	}
}

class ValNumber extends Validator {

	function validate ($value, $args) {
		$hasmin = isset($args['min']) && $args['min'] !== false;
		if ($hasmin)
			$min = $args['min']+0;
		$hasmax = isset($args['max']) && $args['max'] !== false;
		if ($hasmax)
			$max = $args['max']+0;

		if (!ereg("^-?[0-9]*\.?[0-9]+$",$value))
			return "$this->label must be a number";

		if ($hasmin && $value < $min)
			return "$this->label cannot be less than $min";
		if ($hasmax && $value > $max)
			return "$this->label cannot be greater than $max";

		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				var re = /^-?[0-9]*\.?[0-9]+$/;

				if (!re.test(value))
					return label + " must be a number";

				var f = parseFloat(value);
				if (args.min && f < args.min)
					return label + " cannot be less than " + args.min;
				if (args.max && f > args.max)
					return label + " cannot be greater than " + args.max;

				return true;
			}';
	}
}


class ValNumeric extends Validator {

	function validate ($value, $args) {
		$hasmin = isset($args['min']) && $args['min'] !== false;
		if ($hasmin)
			$min = $args['min']+0;
		$hasmax = isset($args['max']) && $args['max'] !== false;
		if ($hasmax)
			$max = $args['max']+0;

		if (!ereg("^[0-9]*$",$value))
			return "$this->label must be numeric";

		if ($hasmin && mb_strlen($value) < $min)
			return "$this->label must be at least $min digits long";
		if ($hasmax && mb_strlen($value) > $max)
			return "$this->label cannot be more than $max digits long";

		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				var re = /^[0-9]*$/;

				if (!re.test(value))
					return label + " must be numeric";

				if (args.min && value.length < args.min)
					return label + " must be at least " + args.min + " digits long";
				if (args.max && value.length > args.max)
					return label + " cannot be more than " + args.max + " digits long";

				return true;
			}';
	}
}

/**
 * Requires inc/utils.inc.php validEmail() and checkEmailDomain()
 * @var String $args['domain'] If set, used to validate the address matches this domain
 * @var String $args['subdomain'] If set (default false), used to allow subdomains
 * @var String $args['field'] If set, only continue to validate if $requiredvalues[$args['field']] == $args['requirefieldvalue']; assumes $args['requirefieldvalue'] is also set.
 * @var String $args['requirefieldvalue'] If set, only continue to validate if $requiredvalues[$args['field']] == $args['requirefieldvalue']; assumes $args['field'] is also set.
 */
class ValEmail extends Validator {

	function validate ($value, $args, $requiredvalues) {
		if (!empty($args['requirefieldvalue']) && $requiredvalues[$args['field']] != $args['requirefieldvalue'])
			return true;

		if (!validEmail($value))
			return "$this->label is not a valid email format";

		if (isset($args['domain'])) {
			$subdomain = false;
			if (isset($args['subdomain']))
				$subdomain = $args['subdomain'];
			$result = checkEmailDomain($value, $args['domain'], $subdomain);
			if ($result)
				return true;
			return "$this->label must use domain ".$args['domain'];
		}
		return true;
	}

	function getJSValidator () {
		$addr_spec = addslashes(getEmailRegExp());

		return
			'function (name, label, value, args, requiredvalues) {
				if (args["requirefieldvalue"] && requiredvalues[args["field"]] != args["requirefieldvalue"])
					return true;

				var emailregexp = "^' . $addr_spec . '$";
				var reg = new RegExp(emailregexp);
				var r = reg.exec(value);
				// r is null, or [0] is match, [1] is username, [2] is @, [3] is domain
				if (r == null)
					return label + " is an invalid email format";

				if (args.domain) {
					var matched = false;
					var lowdomainvalue = r[3].toLowerCase();
					var domains = args.domain.toLowerCase().split(";");
					for (i=0; i<domains.length; i++) {
						var domain = domains[i];
						if (domain != lowdomainvalue) {
							if (args.subdomain && lowdomainvalue.endsWith("."+domain))
								matched = true;
						} else {
							matched = true;
						}
					}
					if (matched)
						return true;
					return label + " must use domain " + args.domain;
				}
				return true;
			}';
	}
}

//
// requires inc/utils.inc.php checkemails()
//
class ValEmailList extends Validator {

	function validate ($value, $args) {

		if ($bademaillist = checkemails($value)) {
			$errmsg = "$this->label has invalid emails.  Each email must be separated by a semi-colon. Invalid emails found are: ";
			foreach ($bademaillist as $bademail) {
				$errmsg .= $bademail . ";";
			}
			$errmsg = substr($errmsg, 0, strlen($errmsg)-1);
			return $errmsg;
		}

		return true;
	}

	function getJSValidator () {
		$addr_spec = getEmailRegExp();

		return
			'function (name, label, value, args) {
				var isbad = false;
				var badnames = "";
				var emailregexp = "' . addslashes($addr_spec) . '";
				var reg = new RegExp(emailregexp);
				var emailsplit = value.split(";");
				for (i=0; i<emailsplit.length; i++) {
					var e = emailsplit[i];
					if (!reg.test(e)) {
						isbad = true;
						badnames += e + ";";
					}
				}
				if (isbad) {
					badnames = badnames.substr(0,badnames.length-1);
					return label + " has invalid emails.  Each email must be separated by a semi-colon. Invalid emails found are: " + badnames;
				}
				return true;
			}';
	}
}

class ValDomain extends Validator {

	function validate ($value, $args) {
		$domainregexp = getDomainRegExp();

		if (!preg_match("!^$domainregexp$!", $value))
			return "$this->label is not a valid domain format";

		return true;
	}

	function getJSValidator () {
		$domainregexp = addslashes(getDomainRegExp());

		return
			'function (name, label, value, args) {
				var domainregexp = "^' . $domainregexp . '$";
				var reg = new RegExp(domainregexp);
				if (!reg.test(value))
					return label + " is not a valid domain format";

				return true;
			}';
	}
}

class ValDomainList extends Validator {

	function validate ($value, $args) {
		$errmsg = validateDomainList($value);
		if ($errmsg !== true)
			return "$this->label has invalid domains.  ".$errmsg;
		return true;
	}

	function getJSValidator () {
		$domainregexp = addslashes(getDomainRegExp());

		return
			'function (name, label, value, args) {
				var domainregexp = "^' . $domainregexp . '$";
				var reg = new RegExp(domainregexp);
				var isbad = false;
				var badnames = "";
				var domainsplit = value.split(";");
				for (i=0; i<domainsplit.length; i++) {
					var d = domainsplit[i];
					if (!reg.test(d)) {
						isbad = true;
						badnames += d + ";";
					}
				}
				if (isbad) {
					badnames = badnames.substr(0,badnames.length-1);
					return label + " has invalid domains.  Each domain must be separated by a semi-colon.  Invalid domains found are: " + badnames;
				}
				return true;
			}';
	}
}

/**
 * Requires obj/Phone.obj.php validate()
 * @var String $args['field'] If set, only continue to validate if $requiredvalues[$args['field']] == $args['requirefieldvalue']; assumes $args['requirefieldvalue'] is also set.
 * @var String $args['requirefieldvalue'] If set, only continue to validate if $requiredvalues[$args['field']] == $args['requirefieldvalue']; assumes $args['field'] is also set.
 */
class ValPhone extends Validator {
	function validate ($value, $args, $requiredvalues) {
		if (!empty($args['requirefieldvalue']) && $requiredvalues[$args['field']] != $args['requirefieldvalue'])
			return true;

		if ($err = Phone::validate($value)) {
			$errmsg = "$this->label is invalid.  ";
			foreach ($err as $e) {
				$errmsg .= $e . " ";
			}
			return $errmsg;
		}

		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args, requiredvalues) {
				if (args["requirefieldvalue"] && requiredvalues[args["field"]] != args["requirefieldvalue"])
					return true;

				var phone = value.replace(/[^0-9]/g,"");
				if (phone.length == 10) {
					var areacode = phone.substring(0, 3);
					var prefix = phone.substring(3, 6);

					// based on North American Numbering Plan
					// read more at en.wikipedia.org/wiki/List_of_NANP_area_codes

					if ((phone.charAt(0) == "0" || phone.charAt(0) == "1") || // areacode cannot start with 0 or 1
						(phone.charAt(3) == "0" || phone.charAt(3) == "1") || // prefix cannot start with 0 or 1
						(phone.charAt(1) == "1" && phone.charAt(2) == "1") || // areacode cannot be N11
						(phone.charAt(4) == "1" && phone.charAt(5) == "1") || // prefix cannot be N11
						("555" == areacode) || // areacode cannot be 555
						("555" == prefix)    // prefix cannot be 555
						) {
						// check special case N11 prefix with toll-free area codes
						// en.wikipedia.org/wiki/Toll-free_telephone_number
						if ((phone.charAt(4) == "1" && phone.charAt(5) == "1") && (
							("800" == areacode) ||
							("888" == areacode) ||
							("877" == areacode) ||
							("866" == areacode) ||
							("855" == areacode) ||
							("844" == areacode) ||
							("833" == areacode) ||
							("822" == areacode) ||
							("880" == areacode) ||
							("881" == areacode) ||
							("882" == areacode) ||
							("883" == areacode) ||
							("884" == areacode) ||
							("885" == areacode) ||
							("886" == areacode) ||
							("887" == areacode) ||
							("888" == areacode) ||
							("889" == areacode)
							)) {
							return true; // OK special case
						}

						return label + " seems to be invalid.";
					}
					return true;
				} else {
					return label + " is invalid. The phone number must be exactly 10 digits long (including area code).\nYou do not need to include a 1 for long distance.";
				}
			}';
	}
}


/*
 * must set args[field] to the same as the formdata['requires'] field
 */
class ValFieldConfirmation extends Validator {
	function validate ($value, $args, $requiredvalues) {
		if ($requiredvalues[$args['field']] != $value)
			return "$this->label does not match!";
		return true;
	}
	function getJSValidator () {
		return
			'function (name, label, value, args, requiredvalues) {
				if (requiredvalues[args["field"]] != value)
					return this.label +" does not match!";
				return true;
			}';
	}
}

class ValInArray extends Validator {
	function validate ($value, $args) {
		if (is_array($value)) {
			foreach ($value as $item) {
				if (!in_array($item, $args['values']))
					return "$this->label must be items from the list of available choices.";
			}
		} else if (!in_array($value, $args['values']))
			return "$this->label must be an item from the list of available choices.";
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				var values = args.values;

				if (Object.isArray(value)) {
					//find every value
					for (var i = 0; i < value.length; i++) {
						var item = value[i];
						var found = false;
						for (var j = 0; j < values.length; j++) {
							if (item == values[j])
								found = true;
						}
						if (!found)
							return label + " must be items from the list of available choices.";
					}
				} else {
					var found = false;
					for (var j = 0; j < values.length; j++) {
						if (value == values[j])
							found = true;
					}
					if (!found)
						return label + " must be an item from the list of available choices.";
				}
				return true;
			}';
	}
}

class ValTimeCheck extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args, $requiredvalues) {
		$value = strtotime($value);

		$hasmin = isset($args['min']) && $args['min'] !== false;
		if ($hasmin)
			$min = strtotime($args['min']);
		$hasmax = isset($args['max']) && $args['max'] !== false;
		if ($hasmax)
			$max = strtotime($args['max']);

		if ($value == -1 || $value === false)
			return _L("%$1s is not a valid time format",$this->label);
		if ($hasmin && $value < $min)
			return _L("%$1s cannot be earlier than %$2s", $this->label, $args['min']);
		if ($hasmax && $value > $max)
			return _L("%$1s cannot be later than %$2s", $this->label, $args['min']);

		return true;
	}
}

/**
 * @var Boolean $args["rangedonly"] If true, only allow 'xdays' and 'daterange'
 */
class ValReldate extends Validator {
	var $onlyserverside = true;

	// @param $valueJSON = ['reldate':'', 'xdays':'', 'startdate':'', 'enddate':'']
	function validate ($valueJSON, $args, $requiredvalues) {
		$data = json_decode($valueJSON, true);
		if (!is_array($data) || empty($data))
			return true; // Don't complain if there is no data.

		$errBadOption = _L("You must specify a valid date option");
		if (empty($data['reldate'])) {
			return $errBadOption;
		} else if (!empty($args["rangedonly"]) && !in_array($data['reldate'], array(
			'xdays',
			'daterange'
		))) {
			return $errBadOption;
		} else if (!in_array($data['reldate'], array(
			'today',
			'yesterday',
			'lastweekday',
			'weektodate',
			'monthtodate',
			'xdays',
			'daterange'
		))) {
			return $errBadOption;
		}

		if ($data['reldate'] == 'daterange') {
			if (empty($data['startdate']) || empty($data['enddate']))
				return _L("You must specify both start and end dates");
			if (!strtotime($data['startdate']))
				return _L("The start date is in an invalid format");
			if (!strtotime($data['enddate']))
				return _L("The end date is in an invalid format");
			if (strtotime($data['startdate']) > strtotime($data['enddate']))
				return _L("The start date must be before the end date");
		} else if ($data['reldate'] == 'xdays' && (!isset($data['xdays']) || !is_numeric($data['xdays']))) {
			return _L("You must enter a number for X days");
		}

		return true;
	}
}

class ValRegExp extends Validator {
	function validate ($value, $args) {
		if (!ereg($args["pattern"], $value))
			return _L("Invalid character in ")." ".$this->label;
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				var reg = new RegExp(args["pattern"]);
				if (reg.exec(value) == null)
					return "' . _L("Invalid character in ")  . '" + label ;
				return true;
			}';
	}
}


//alpha
//alphanumeric
//phoneeasycall ??



?>