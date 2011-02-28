<?

// this password is the current subscriber password
class ValSubscriberPassword extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args) {
		if (!subscriberVerifyPassword($value))
            return _L('%1$s is not the correct password.', $this->label);
        
        return true;
    }
}

// this password cannot be like firstname, lastname, username (create account)
class ValPassword extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args, $requiredvalues) {
		if ($detail = validateNewPassword($requiredvalues['username'], $value, $requiredvalues['firstname'], $requiredvalues['lastname']))
			return _L('%1$s is invalid.  ', $this->label) . $detail;

		return true;
    }
}

// this password cannot be like firstname, lastname, username (change my pass, already logged in)
class ValChangePassword extends Validator {
    var $onlyserverside = true;
    
    function validate ($value, $args) {
		if ($detail = validateNewPassword($_SESSION['subscriber.username'], $value, $_SESSION['subscriber.firstname'], $_SESSION['subscriber.lastname']))
			return _L('%1$s is invalid.  ', $this->label) . $detail;

		return true;
    }
}

?>
