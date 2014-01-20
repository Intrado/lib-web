<?
class ValInteger extends Validator {

	function validate ($value, $args) {
		$hasmin = isset($args['min']) && $args['min'] !== false;
		if ($hasmin)
			$min = $args['min']+0;
		$hasmax = isset($args['max']) && $args['max'] !== false;
		if ($hasmax)
			$max = $args['max']+0;

		if (!preg_match("/^-?[0-9]*$/",$value))
			return "$this->label must be an integer";

		if ($hasmin && $value < $min)
			return "$this->label cannot be less than $min";
		if ($hasmax && $value > $max)
			return "$this->label cannot be greater than $max";

		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				var re = /^-?[0-9]*$/;

				if (!re.test(value))
					return label + " must be an integer";

				var f = parseFloat(value);
				if (args.min && f < args.min)
					return label + " cannot be less than " + args.min;
				if (args.max && f > args.max)
					return label + " cannot be greater than " + args.max;

				return true;
			}';
	}
}
?>