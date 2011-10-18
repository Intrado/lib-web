<?
/*
alpha
alphanumeric
xalphanumeric
text

number
float
bool

ipaddr

array
*/


/***************** NewForm *****************

*/

function NewForm($formname, $extrahtml = "") {

	echo "<form name=\"$formname\" method=\"post\" action=\"" . $_SERVER["REQUEST_URI"] . "\" enctype=\"multipart/form-data\" " . $extrahtml . " >";
	echo "<input type=\"hidden\" name=\"frm[" . $formname . "][timestamp]\" value=\""
			. $_SESSION['formdata'][$formname]['timestamp'] . "\">";
}

/***************** EndForm *****************

*/
function EndForm() {
	echo "</form>\n";
}


/***************** Inputs *****************
prints various types of inputs based on formdata.
calls CheckFormItem, puts red * for bad/missing values
*/

function NewFormItem ($form, $section, $item, $type, $option=40, $optionvalue="nooption", $extrahtml = "") {


	if ($type != "submit" && !isset($_SESSION['formdata'][$form][$section][$item]))
		error_log("Attempt to call NewFormItem with non initialized object $form,$section,$item");


	switch($type) {
	case "text":
		echo "<input $extrahtml type=\"text\" name=\"frm[" . $form . "][" . $section
				. "][" . $item . "][value]\" value=\""
				. escapehtml($_SESSION['formdata'][$form][$section][$item]['value']) . "\" size=\"$option\" "
				. "maxlength=\"" . ($optionvalue === 'nooption' ? $option : $optionvalue) . "\">";
		break;
	case "hidden":
		echo "<input $extrahtml type=\"hidden\" name=\"frm[" . $form . "][" . $section
				. "][" . $item . "][value]\" value=\""
				. escapehtml($_SESSION['formdata'][$form][$section][$item]['value']) . "\">";
		break;
	case "textarea":
		$rows = ($optionvalue === "nooption" || $optionvalue == NULL) ? $option/6 : $optionvalue;
		echo "<textarea $extrahtml name=\"frm[" . $form . "][" . $section
				. "][" . $item . "][value]\" "
				. "cols=\"" . $option . "\" rows=\"" . $rows . "\">";
		echo escapehtml($_SESSION['formdata'][$form][$section][$item]['value']);
		echo "</textarea>";
		break;
	case "password":
		echo "<input $extrahtml type=\"password\" name=\"frm[" . $form . "][" . $section
				. "][" . $item . "][value]\" value=\""
				. escapehtml($_SESSION['formdata'][$form][$section][$item]['value']) . "\" size=\"$option\" "
				. "maxlength=\"" . ($optionvalue === 'nooption' ? $option : $optionvalue) . "\">";
		break;
	case "checkbox":
		echo "<input $extrahtml type=\"checkbox\" name=\"frm[" . $form . "][" . $section
				. "][" . $item . "][value]\" value=\""
				. $_SESSION['formdata'][$form][$section][$item]['maxval'] . "\" ";

		if($_SESSION['formdata'][$form][$section][$item]['value'] ==
			$_SESSION['formdata'][$form][$section][$item]['maxval'])
		echo "checked";
		echo ">";

		//this is a hack for checkboxes. we must preset the value in formdata
		//because if check box is not checked, it will not return anything.
		//this causes checkboxes to be sticky.

		$_SESSION['formdata'][$form][$section][$item]['value'] = $_SESSION['formdata'][$form][$section][$item]['minval'];

		//now checkbox is set correctly in form
		//when user submits form and if unchecked, will not have a checked value.

		break;
	case "radio":

		//allow override on items formdata value. this is almost always the case for radio inputs
		if($optionvalue==="nooption") {
			$usevalue = $_SESSION['formdata'][$form][$section][$item]['value'];
		} else {
			$usevalue = $optionvalue;
		}

		echo "<input $extrahtml type=\"radio\" name=\"frm[" . $form . "][" . $section
				. "][" . $item . "][value]\" value=\""
				. escapehtml($usevalue) . "\" ";

		if(	$usevalue == $_SESSION['formdata'][$form][$section][$item]['value'] ) {
			echo "checked";
		}

		echo ">";

		break;
	case "selectstart":
		echo "<select $extrahtml name=\"frm[" . $form . "][" . $section
				. "][" . $item . "][value]\">";

		break;
	case "selectoption":
		//allow override on items formdata value. this is almost always the case for select inputs
		if($optionvalue==="nooption") {
			$usevalue = $_SESSION['formdata'][$form][$section][$item]['value'];
		}
		else {
			$usevalue = $optionvalue;
		}

		//use custom display value. this is almost always the case for select inputs
		if($option==40) {
			$usename = $optionvalue;
		}
		else {
			$usename = $option;
		}


		echo "<option $extrahtml value=\"" . escapehtml($usevalue) . "\" ";

		//when checking if this should be selected, check against the difference of "0" and ""
		//but allow "5" and 5 to be equivalent.
		if(	$usevalue == $_SESSION['formdata'][$form][$section][$item]['value'] &&
			strlen($usevalue) == strlen($_SESSION['formdata'][$form][$section][$item]['value'])) {
			echo "selected";
		}

		echo ">";

		echo escapehtml($usename) . "</option>";

		break;
	case "selectend":
		echo "</select>";
		break;

	case "selectmultiple":
		//option is the size of the select box
		//optionvalue is the map of name => value pairs
		$usevalue = null;
		if(isset($_SESSION['formdata'][$form][$section][$item]['value']))
			$usevalue = $_SESSION['formdata'][$form][$section][$item]['value'];
		//this is a hack for multiselects. we must unset the value in formdata
		//because if nothing is selected the old data will not be overwritten.
		unset($_SESSION['formdata'][$form][$section][$item]['value']);

		if ($usevalue == null)
			$usevalue = array();

		if ($option == 40) {
			$useoption = min(5,count($optionvalue));
		} else {
			$useoption = min($option,count($optionvalue));
		}

		echo "<select $extrahtml size=\"" . $useoption . "\" multiple name=\"frm[" . $form . "][" . $section
				. "][" . $item . "][value][]\">";
		foreach ($optionvalue as $value => $name) {
			echo "<option value=\"" . escapehtml($value) . "\" ";
			if (in_array($value,$usevalue)) {
				echo "selected";
			}
			echo ">";
			echo escapehtml($name) . "</option>";
		}
		echo "</select>";

		break;
	case "image":
		$n = $option==40 ? $item : $option;
		echo '<input alt="' . $n . '" type="image" name="submit[' . $form . '][' . $section . ']" src="img/b1_' . $n . '.gif" onMouseOver="this.src=\'img/b2_' . $n . '.gif\';" onMouseOut="this.src=\'img/b1_' . $n . '.gif\';">';
		break;
	case "submit":
		echo "<input $extrahtml type=submit value=\"" . ($item ? $item : "Submit") . "\" name=submit[" . $form . "][" . $section . "]>";
		break;
	case "reset":
		echo "<input $extrahtml type=\"reset\" name=\"Reset\" value=\"Reset\">";
		break;

	}

	if( $type != "submit" &&
		$type != "reset" &&
		$type != "selectoption" &&
		$type != "image" &&
		$type != "selectstart") {
		if($err = CheckFormItem($form,$section,$item)) {
			echo "<font color=#FF0000>";
			switch($err)
			{
			case "range":
				echo "* Out of Range";
				break;
			case "type":
				echo "* Invalid Data";
				break;
			case "missing":
				echo "* Required";
				break;
			}
			echo "</font>";
		}
	}

	echo "\n";
}

/***************** NewFormSelect *****************

*/

function NewFormSelect ($f,$s,$item,$map) {
	NewFormItem($f,$s,$item,"selectstart");

	if ($map) {
		foreach ($map as $value => $name) {
			NewFormItem($f,$s,$item,"selectoption",$name,$value);
		}
	}
	NewFormItem($f,$s,$item,"selectend");
}

/***************** GetFormData *****************

*/

function GetFormData ($form, $section, $item) {
	if (isset($_SESSION['formdata'][$form][$section][$item]['value'])) {
		return $_SESSION['formdata'][$form][$section][$item]['value'];
	} else {
		return null;
	}
}

/***************** TrimFormData *****************

Will trim the form item and put it back into the form.
Return the trimmed value if it exists

*/
function TrimFormData ($form, $section, $item) {
	if (isset($_SESSION['formdata'][$form][$section][$item]['value'])) {
		$_SESSION['formdata'][$form][$section][$item]['value'] = trim($_SESSION['formdata'][$form][$section][$item]['value']);
		return $_SESSION['formdata'][$form][$section][$item]['value'];
	} else {
		return null;
	}
}

/***************** PutFormData *****************

*/

function PutFormData ($form, $section, $item,
						$value		="",
						$datatype	="referencedata",
						$min		="nomin",
						$max		="nomax",
						$req		= false,
						$lastmod	= "now" ) {

	$_SESSION['formdata'][$form][$section][$item] = array();

	$_SESSION['formdata'][$form][$section][$item]['value'] = $value;
	$_SESSION['formdata'][$form][$section][$item]['datatype'] = $datatype;
	$_SESSION['formdata'][$form][$section][$item]['maxval'] = $max;
	$_SESSION['formdata'][$form][$section][$item]['minval'] = $min;
	$_SESSION['formdata'][$form][$section][$item]['required'] = $req;
	$_SESSION['formdata'][$form][$section][$item]['lastmodtime'] = $lastmod;


	//set default min, max for bool to 0,1
	if($datatype == "bool" &&
		$min == "nomin" &&
		$max == "nomax") {
		$_SESSION['formdata'][$form][$section][$item]['minval'] = 0;
		$_SESSION['formdata'][$form][$section][$item]['maxval'] = 1;
	}
}

function SetRequired ($form, $section, $item, $req) {
	$_SESSION['formdata'][$form][$section][$item]['required'] = $req;
}

/***************** CheckFormSubmit *****************

*/

function CheckFormSubmit ($form, $section) {
	return isset($_POST['submit'][$form][$section]) ? $_POST['submit'][$form][$section] : false;
}

/***************** CheckFormInvalid *****************

*/

function CheckFormInvalid ($form) {
	return ($_SESSION['formdata'][$form]['timestamp'] != $_POST['frm'][$form]['timestamp']);
}

/***************** CheckFormItem *****************
checks values with ranges and things

returns 0 if everything is good
otherwise returns:
"range"
"missing"
"type"


*/
function CheckFormItem($form, $section, $item) {

	$theitem = $_SESSION['formdata'][$form][$section][$item];

	//check for missing data
	if($theitem['required']) {
		if(!isset($theitem['value']) || $theitem['value'] == "") {
			return "missing";
		}
	} else {
	 //if no data required dont check data unless there is data
		if(!isset($theitem['value']) || $theitem['value'] == "") {
			return 0;
		}
	}

	switch($theitem['datatype']) {

	//### start textish types ###
	case "alpha":
		if(!preg_match("/^[a-zA-Z]*$/", $theitem['value'])) {
			return "type";
		}
		//###overflow to next (will match if this matched)
	case "alphanumeric":
		if(!preg_match("/^[a-zA-Z0-9]*$/", $theitem['value'])) {
			return "type";
		}
		//###overflow to next (will match if this matched)
	case "xalphanumeric":
		if(!preg_match("/^[a-zA-Z0-9\.\_\-]*$/", $theitem['value'])) {
			return "type";
		}
		//###overflow to next (will match if this matched)
	case "text":

		if($theitem['minval'] != "nomin") {
			if(strlen($theitem['value']) < $theitem['minval']) {
				return "range";
			}
		}

		if($theitem['maxval'] != "nomax") {
			if(strlen($theitem['value']) > $theitem['maxval']) {
				return "range";
			}
		}
		break;

	case "phoneeasycall":
		if (Phone::validateEasyCall($theitem['value'])) {
			return "type";
		}
		break;

	case "phone":
		if (Phone::validate($theitem['value'])) {
			return "type";
		}
		break;

	case "email":
		$email = $theitem['value'];

	 	#
	    # RFC822 Email Parser
	    #
	    # By Cal Henderson <cal@iamcal.com>
	    # This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
	    # http://creativecommons.org/licenses/by-sa/2.5/
	    #
	    # $Revision: 1.25 $
	    # http://www.iamcal.com/publish/articles/php/parsing_email/

	    ##################################################################################

		##################################################################################
		# Beginning of Creative Commons Email Parser Code
		##################################################################################

        $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';

        $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';

        $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
            '\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';

        $quoted_pair = '\\x5c[\\x00-\\x7f]';

        $domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";

        $quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";

        $domain_ref = $atom;

        $sub_domain = "($domain_ref|$domain_literal)";

        $word = "($atom|$quoted_string)";
		// original code allows a domain to only contain a single sub_domain.  Code has been
		// changed to require 2 domain parts ex.  "example.com"  instead of just "example"
        $domain = "$sub_domain\\x2e$sub_domain(\\x2e$sub_domain)*";

        $local_part = "$word(\\x2e$word)*";

        $addr_spec = "$local_part\\x40$domain";

		##################################################################################
		# End of Creative Commons Email Parser Code
		##################################################################################

        if(!preg_match("!^$addr_spec$!", $email)){
        	return 'type';
        }

		break;
	//### start number types ###
	case "number":
		if(!preg_match("/^[0-9]*$/", $theitem['value'])) {
			return "type";
		}
		//###overflow to next (will match if this matched)
	case "float":
		if(!preg_match("/^[0-9\.]*$/", $theitem['value'])) {
			return "type";
		}

		if($theitem['minval'] != "nomin") {
			if($theitem['value'] < $theitem['minval']) {
				return "range";
			}
		}

		if($theitem['maxval'] != "nomax") {
			if($theitem['value'] > $theitem['maxval']) {
				return "range";
			}
		}
		break;
	//### start network types ###
	case "ipaddr":
		if(!preg_match("/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/",
						$theitem['value'],
						$regs_array)) {
			return "type";
		}

		//max 255.255.255.255
		if($regs_array[1] > 255 || $regs_array[2] > 255 ||
				$regs_array[3] > 255 || $regs_array[4] > 255 ) {
			return "range";
		}

		if($theitem['minval'] != "nomin") {
			$minoctets = explode (".", $theitem['minval']);
			$octets = explode (".", $theitem['value']);

			if( ($octets[0] < $minoctets[0]) ||
				 ($octets[1] < $minoctets[1]) ||
				 ($octets[2] < $minoctets[2]) ||
				 ($octets[3] < $minoctets[3]) ) {
				return "range";
			}
		}

		if($theitem['maxval'] != "nomax") {
			$maxoctets = explode (".", $theitem['maxval']);
			$octets = explode (".", $theitem['value']);

			if( ($octets[0] > $maxoctets[0]) ||
				 ($octets[1] > $maxoctets[1]) ||
				 ($octets[2] > $maxoctets[2]) ||
				 ($octets[3] > $maxoctets[3]) ) {
				return "range";
			}
		}

		break;
	//### start misc types ###
	case "bool":

		if( ($theitem['value'] != $theitem['minval']) &&
			($theitem['value'] != $theitem['maxval']) ) {
			return "type";
		}


		break;
	case "array":
		if ($theitem['minval'] != "nomin" && is_array($theitem['minval'])) {
			if (is_array($theitem['value'])) {
				foreach ($theitem['value'] as $value) {
					if (!in_array($value,$theitem['minval'])) {
						return "type";
					}
				}
			} else {
				if (!in_array($theitem['value'],$theitem['minval']))
					return "type";
			}
		}

		break;
	}

	return 0;
}

/***************** CheckFormSection *****************

calls CheckFormItem for all items in section of form
returns 0 if all is good
otherwise returns 1
*/
function CheckFormSection($form, $section) {
	if(isset($_SESSION['formdata'][$form][$section])) {
		foreach($_SESSION['formdata'][$form][$section] as $key => $value) {
			if(CheckFormItem($form, $section, $key) !== 0) {
				//error("Fail on $key");
				return 1;
			}
		}
	}
	return 0;
}


/***************** MergeSectionFormData *****************

*/

function MergeSectionFormData ($form, $section) {

	$frm = $_POST['frm'];

	if(isset($frm[$form][$section])) {
		//for each item in frm
		foreach ($frm[$form][$section] as $keyitem => $valueitem) {
			//for each param in item
			foreach($frm[$form][$section][$keyitem] as $keyparam => $valueparam) {

				if (is_array($valueparam)) {
					$_SESSION['formdata'][$form][$section][$keyitem][$keyparam] = array();
					foreach ($valueparam as $valueparamitem) {
						if(get_magic_quotes_gpc()) {
							$_SESSION['formdata'][$form][$section][$keyitem][$keyparam][] = stripslashes($valueparamitem);
						} else {
							$_SESSION['formdata'][$form][$section][$keyitem][$keyparam][] = $valueparamitem;
						}
					}
				} else {
					if(get_magic_quotes_gpc()) {
						$_SESSION['formdata'][$form][$section][$keyitem][$keyparam] = stripslashes($valueparam);
					} else {
						$_SESSION['formdata'][$form][$section][$keyitem][$keyparam] = $valueparam;
					}
				}
			}
		}
	}
}

/***************** ClearFormData *****************
resets the formdata for specified form
*/
function ClearFormData($form) {
	$_SESSION['formdata'][$form] = array();

	$_SESSION['formdata'][$form]['timestamp'] = time();
}


/***************** PopulateObject *****************

*/
function PopulateObject ($form, $section, &$obj, $items) {
	foreach ($items as $item) {
		$obj->$item = GetFormData($form,$section, $item);
	}
}

/***************** PopulateForm *****************
0 = form and object field name
1 = type
2 = min
3 = max
4 = requried
*/
function PopulateForm ($form, $section, $obj, $fields) {
	foreach ($fields as $field) {
		$fieldname = $field[0];

		PutFormData($form,$section,$fieldname,$obj->$fieldname,
			$field[1],
			(isset($field[2]) ? $field[2] : "nomin"),
			(isset($field[3]) ? $field[3] : "nomax"),
			(isset($field[4]) ? $field[4] : false));
	}
}


?>