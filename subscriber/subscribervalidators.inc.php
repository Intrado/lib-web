<?
require_once("../obj/Validator.obj.php");


class ValSubscriberPassword extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args) {
		if (0 == QuickQuery("select count(*) from subscriber where id=? and `password`=password(?)", false, array($_SESSION['subscriberid'], $value)))
            return "$this->label is not the correct password";
        
        return true;
    }
}

?>
