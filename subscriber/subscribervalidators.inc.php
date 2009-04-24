<?

// this password is the current subscriber password
class ValSubscriberPassword extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args) {
		if (0 == QuickQuery("select count(*) from subscriber where id=? and `password`=password(?)", false, array($_SESSION['subscriberid'], $value)))
            return "$this->label is not the correct password";
        
        return true;
    }
}

// this password cannot be like firstname, lastname, username (create account)
class ValPassword extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args, $requiredvalues) {
		if ($detail = validateNewPassword($requiredvalues['username'], $value, $requiredvalues['firstname'], $requiredvalues['lastname']))
			return "$this->label is invalid.  ".$detail;

		return true;
    }
}

// this password cannot be like firstname, lastname, username (change my pass, already logged in)
class ValChangePassword extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args) {
		if ($detail = validateNewPassword($_SESSION['subscriber.username'], $value, $_SESSION['subscriber.firstname'], $_SESSION['subscriber.lastname']))
			return "$this->label is invalid.  ".$detail;

		return true;
    }
}

?>
