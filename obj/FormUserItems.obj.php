<?
////////////////////////////////////////////////////////////////////////////////
// Form Items
////////////////////////////////////////////////////////////////////////////////
class TextPasswordStrength extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$max = isset($this->args['maxlength']) ? 'maxlength="'.$this->args['maxlength'].'"' : "";
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$generator = isset($this->args['generator']) && $this->args['generator'] === true;
		
		$str = '<table style="border-width:0px; border-spacing:0px; padding:0px;">
					<tr>
						<td style="padding:0px"><input id="'.$n.'" name="'.$n.'" type="password" value="'.escapehtml($value).'" '.$max.' '.$size.'/></td>
						<td>&nbsp;'._L("Password Strength").':</td>
						<td>
							<table style="border-width:0px; border-spacing:0px; padding:0px; border-style:solid; border-color:black">
								<tr>
									<td style="padding:0px"><div id="'.$n.'0" style="width:15px; height:12px; -moz-border-radius:5px; background-color:gray;"></div></td>
									<td style="padding:0px"><div id="'.$n.'1" style="width:15px; height:12px; -moz-border-radius:5px; background-color:gray;"></div></td>
									<td style="padding:0px"><div id="'.$n.'2" style="width:15px; height:12px; -moz-border-radius:5px; background-color:gray;"></div></td>
									<td style="padding:0px"><div id="'.$n.'3" style="width:15px; height:12px; -moz-border-radius:5px; background-color:gray;"></div></td>
									<td style="padding:0px"><div id="'.$n.'4" style="width:15px; height:12px; -moz-border-radius:5px; background-color:gray;"></div></td>
								</tr>
							</table>
						</td>
						' . ($generator?'<td>' . icon_button("Generate", "key","generatePassword();") . '</td>':'') . '
						
					</tr>
				</table>
				<script type="text/javascript">
					var specialchars = ["\~","\`","\@","\#","\$","\%","\^","\&","\*","\(","\)","\_","\+","\-","\=","\;","\:","\{","\}","\[","\]","\|","\\\\","\/","\?","\>","\<"];
					function checkPasswordStrength() {
						var minlen = '. $this->args['minlength']. ';
						var pass = $("'.$n.'").value;
						var int = 0;
						var spe = 0;
						var len = 0;
						var xlen = 0;
						var ucase = 0;
						if (pass.length < minlen)
							return;
						if (pass.length > 5)
							len = 1;
						if (pass.length > 10)
							xlen = 1;
						for (var i = 0; i < pass.length; i++) {
							if (parseInt(pass.charAt(i)) > 0)
								int = 1;
							if ($A(specialchars).indexOf(pass.charAt(i)) > -1)
								spe = 1;
							if (pass.charAt(i).toUpperCase() <= "Z" && pass.charAt(i).toUpperCase() >= "A" && (pass.charAt(i) == pass.charAt(i).toUpperCase()))
								ucase = 1;
						}
						var strength = int + spe + len + xlen + ucase;
						for (var i = 0; i < 5; i++) {
							if (i < strength)
								$("'.$n.'"+ (i.toString())).setStyle({backgroundColor: "green"});
							else
								$("'.$n.'"+ (i.toString())).setStyle({backgroundColor: "lightgrey"});
						}
					}
					
					' . ($generator?'
					function generatePassword() {
						var password = "";
						for (var i = 0; i < 6; i++) {
							//get random number from 33 to 127 (character ! to ?) and convert it to a character
							password += String.fromCharCode((parseInt(Math.random() * 1000) % 94) + 33);
						}
						$("'.$n.'").value = password;
						alert("Generated Password: " + password);
						checkPasswordStrength();
					}':'') . 
					
					'$("'.$n.'").observe("keyup", checkPasswordStrength);
				</script>';
		return $str;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////
class ValLogin extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		if (User::checkDuplicateLogin($value, $args['userid']))
			return "$this->label " . _L("already exists, please choose another.");
		else
			return true;
	}
}

class ValAccesscode extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		if (User::checkDuplicateAccesscode($value, $args['userid']))
			return "$this->label " . _L("already exists, please choose another.");
		else
			return true;
	}
}

class ValPassword extends Validator {
	var $onlyserverside = true;
	function passwordcheck($password){
		$tally = 0;
		if($password == 'nopasswordchange'){
			return true;
		}
		if(ereg("[0-9]", $password))
			$tally++;
		if(ereg("[a-zA-Z]", $password))
			$tally++;
		if(ereg("[\!\@\#\$\%\^\&\*]", $password))
			$tally++;

		if($tally >= 2)
			return true;

		return false;
	}
	function validate ($value, $args, $requiredvalues) {
		if ($value == 'nopasswordchange')
			return true;
		if ($detail = validateNewPassword(
				isset($requiredvalues['login'])? $requiredvalues['login']: $args['login'], 
				$value, 
				isset($requiredvalues['firstname'])? $requiredvalues['firstname']: $args['firstname'], 
				isset($requiredvalues['lastname'])? $requiredvalues['lastname']: $args['lastname']
			))
			return "$this->label ". _L("is invalid") ." ".$detail;
		if (!$this->passwordcheck($value))
			return "$this->label ". _L("is invalid.") ." " . _L("Must contain a letter and a number or symbol.");
		
		$validateComplexPassword = isset($args['complex']) && $args['complex'] === true;
		if ($validateComplexPassword && ($detail = isNotComplexPass($value)) && !ereg("^0*$", $value))
			return "$this->label ". _L("is invalid") ." ".$detail;

		return true;
	}
}

class ValPin extends Validator {
	function validate ($value, $args, $requiredvalues) {
		if ($value === "00000")
			return true;
		$pin = ereg_replace("[^0-9]*","",$value);
		$accesscode = isset($requiredvalues['accesscode'])? $requiredvalues['accesscode']: $args['accesscode'];
		if (!$accesscode)
			return "$this->label ". _L("cannot have a PIN without a Phone User ID.");
		if ($pin === $accesscode)
			return "$this->label ". _L("cannot equal Phone User ID.");
		if (isSequential($pin))
			return "$this->label ". _L("cannot have sequential numbers.");
		if (isAllSameDigit($pin))
			return "$this->label ". _L("all digits cannot be the same.");
		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args, requiredvalues) {
				if (value == "00000")
					return true;
				var accesscode = "";
				if (typeof(requiredvalues.accesscode) !== "undefined")
					accesscode = requiredvalues.accesscode;
				else
					accesscode = args.accesscode;
				if (accesscode.length == 0)
					return label + " '. addslashes(_L("cannot have a PIN without a Phone User ID.")). '";
				if (value == accesscode)
					return label + " '. addslashes(_L("cannot be equal to Phone User ID.")). '";
				if (isSequential(value))
					return label + " '. addslashes(_L("cannot have sequential numbers.")). '";
				if (isAllSameDigit(value))
					return label + " '. addslashes(_L("all digits cannot be the same.")). '";
				return true;
			}';
	}
}
?>
