<?

class ValTextAreaPhone extends Validator {
	function validate ($value, $args) {
		$msgdata = json_decode($value);
		$textlength = mb_strlen($msgdata->text);
		if (!$textlength)
			return $this->label." "._L("cannot be blank.");
		else if ($textlength > 4000) {
			return $this->label." "._L("cannot be more than 4000 characters.");
		}
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				var msgdata = value.evalJSON();
				var textlength = msgdata.text.length;
				if (!textlength)
					return label + " '.addslashes(_L('cannot be blank.')).'";
				else if(textlength > 4000)
					return label + " '.addslashes(_L('cannot be more than 4000 characters.')).'";
				return true;
			}';
	}
}
?>