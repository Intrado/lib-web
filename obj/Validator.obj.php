<?

abstract class Validator {
	var $name;
	var $label;
	var $onlyserverside = false; //set this if you don't have a JS validator
	var $requiredfields = array();

	/* static
	 * spits out javascript required to install a validator in the form 
	 * Takes a list of class names
	 */
	function load_validators ($validators) {
		
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
	
	/* static
	 * works pre-merge 
	 */
	function validate_item ($formdata,$name,$value,$requiredvalues = array()) {
		$validators = $formdata[$name]['validators'];
		
		foreach ($validators as $validatordata) {
			$validator = $validatordata[0];
			//only validate non empty values (unless its the ValRequired validator)
			if ($validator == "ValRequired" || ((is_array($value) && count($value)) || mb_strlen($value) > 0)) {		
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
				///...
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

//
// requires inc/utils.inc.php validEmail()
//
// optional args.domain to validate the address matches this domain
//
class ValEmail extends Validator {
	
	function validate ($value, $args) {
		if (!validEmail($value))
			return "$this->label is not a valid email format";
			
		if (isset($args['domain'])) {
			$emaildomain = substr($value, strpos($value, "@")+1);
			if ($args['domain'] != $emaildomain)
				return "$this->label must use domain ".$args['domain'];
		}
		return true;
	}
	
	function getJSValidator () {
		$addr_spec = getEmailRegExp();
		
		return 
			'function (name, label, value, args) {
				var emailregexp = "' . addslashes($addr_spec) . '";
				var reg = new RegExp(emailregexp);
				if (!reg.test(value))
					return label + " is an invalid email format";' .
							'
				if (args.domain) {
					var emaildomain = value.substr(value.indexOf("@")+1);
					if (emaildomain.toLowerCase() != args.domain.toLowerCase())
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
			$errmsg = "$this->label has invalid emails ";
			foreach ($bademaillist as $bademail) {
				$errmsg .= $bademail . ";";
			}
			return $errmsg;
		}

		return true;
	}
	
	function getJSValidator () {
		$addr_spec = getEmailRegExp();

		return 
			'function (name, label, value, args) {
				var isbad = false;
				var emailregexp = "' . addslashes($addr_spec) . '";
				var reg = new RegExp(emailregexp);
				var emailsplit = value.split(";");
				for (i=0; i<emailsplit.length; i++) {
					var e = emailsplit[i];
					if (!reg.test(e)) {
						isbad = true;
					}
				}
				if (isbad)
					return label + " has invalid emails"; // TODO nice to return the bad email text

				return true;
			}';
	}
}

//
// requires obj/Phone.obj.php validate()
//
class ValPhone extends Validator {
	
	function validate ($value, $args) {
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
			'function (name, label, value, args) {
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
		if (!in_array($value, $args['values']))
			return "$this->label must be an item from the list of available choices.";
		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				for (arg in args.values)
					if (args.values[arg] == value)
						return true;
				
				return label + " must be an item from the list of available choices.";
				
			}';
	}
}

// case-insensitive
class ValStatic extends Validator {
	
	function validate ($value, $args) {
		$val = isset($args['val']) ? $args['val'] : '';
		if (strtolower($value) != strtolower($val))
			return "$this->label is not the correct value";
		
		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				if (args.val && (args.val.toLowerCase() != value.toLowerCase()))
					return label + " is not the correct value";
				return true;
			}';
	}
}

class ValTheme extends Validator {
	function validate ($value, $args) {
		$checkval = json_decode($value);
		$errortext = "";
		if (!$checkval->theme)
			$errortext .= " " . _L("Theme must be a valid choice.");
		if (!((strlen($checkval->color) == 6) && is_numeric('0x'.substr($checkval->color, 0, 2)) && is_numeric('0x'.substr($checkval->color, 2, 2)) && is_numeric('0x'.substr($checkval->color, 4, 2))))
			$errortext .= " " . _L("Primary Color must be a valid Hex representation of your color choice.");
		if (!is_numeric($checkval->ratio))
			$errortext .= " " . _L("Ratio must be a number.");
		if ($errortext)
			return $this->label . $errortext;
		else
			return true;
	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				vals = value.evalJSON();
				var errortext = "";
				if (!vals.theme)
					errortext += " Theme must be a valid choice.";
				if (!(vals.color.length == 6))
					errortext += " Primary Color must be a valid Hex representation of your color choice.";
				if (!parseFloat(vals.ratio))
					errortext += " Ratio must be a number.";
				if (errortext)
					return errortext;
				else
					return true;
			}';
	}
}

//alpha
//alphanumeric
//phoneeasycall ??



?>
