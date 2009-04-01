<?

abstract class Validator {
	var $name;
	var $label;
	var $onlyserverside = false; //set this if you don't have a JS validator
	
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
	function validate_item ($formdata,$name,$value) {
		$validators = $formdata[$name]['validators'];
		
		foreach ($validators as $validatordata) {
			$validator = $validatordata[0];
			//only validate non empty values (unless its the ValRequired validator)
			if ($validator == "ValRequired" || ((is_array($value) && count($value)) || mb_strlen($value) > 0)) {		
				$obj = new $validator();
				$obj->label = $formdata[$name]['label'];
				$obj->name = $name;
				
				$res = $obj->validate($value, $validatordata);
				
				if ($res !== true)
					return array($validator,$res);
			}
		}
		return true;
	}


	/* abstract */
	function validate($value, $args) {
		return false;
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
class ValEmail extends Validator {
	
	function validate ($value, $args) {
		if (!validEmail($value))
			return "$this->label is not a valid email format";

		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				var reg = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
				if (!reg.test(value))
					return label + " is an invalid email format";

				return true;
			}';
	}
}


//alpha
//alphanumeric
//phone
//phoneeasycall ??
//matches array



?>