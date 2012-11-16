<?
class ValInboundNumber extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$query = "select count(*) from customer where inboundnumber=?";
		if (($args["customerid"] && QuickQuery($query . " and id!=?",false,array($value,$args["customerid"]))) ||
			(!$args["customerid"] && QuickQuery($query,false,array($value)))) {		
			return 'Number is already in use for ' . $this->label;
		}
		return true;
	}
}
?>