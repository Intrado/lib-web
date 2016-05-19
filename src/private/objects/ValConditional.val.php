<?
// Is required if "has" are set and "not" are not set
class ValConditional extends Validator {
	var $conditionalrequired = true;
	
	function validate ($value, $args, $requiredvalues) {
		$has = isset($args['has'])?$args['has']:array();
		$not = isset($args['not'])?$args['not']:array();
		
		$hasvalues = true;
		foreach ($has as $field) {
			$reqval = $requiredvalues[$field];
			if ((is_array($reqval) && !count($reqval)) || (!is_array($reqval) && mb_strlen($reqval) == 0)) {
				$hasvalues = false;
				break;
			}
		}
		$notvalues = true;
		foreach ($not as $field) {
			$reqval = $requiredvalues[$field];
			if ((is_array($reqval) && count($reqval)) || (!is_array($reqval) && mb_strlen($reqval) > 0)) {
				$notvalues = false;
				break;
			}
		}
		
		$isemptyvalue = (is_array($value) && !count($value)) || (!is_array($value) && mb_strlen($value) == 0);
		if ($hasvalues && $notvalues && $isemptyvalue)
			return "$this->label is required";
		return true;
	}
	
	function getJSValidator () {
		return
			'function (name, label, value, args, requiredvalues) {
				var has = args["has"]?args["has"]:[];
				var not = args["not"]?args["not"]:[];
				
				var hasvalues = true;
				has.each(function (field) {
					if (requiredvalues[field].length == 0)
						hasvalues = false;
				});
				var notvalues = true;
				not.each(function (field) {
					if (requiredvalues[field].length != 0)
						notvalues = false;
				});
				
				if (hasvalues && notvalues && value.length == 0)
					return label + " is required";
				return true;
			}';
	}
}
?>