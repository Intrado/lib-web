<? 


// Translation widget
class TranslationItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if($value == null)
			$value == "";
			
		$language = "english";
		$gender = "female";	
			
		if(is_array($value)) {
			$language = $value["language"];
			$gender = $value["gender"];
			$value = $value["value"];
		}
	
		$str = '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'"/>';
		$str .= icon_button(_L("Play"),"fugue/control","
				var content = $('" . $n . "').getValue();
					if(content != '')
						popup('previewmessage.php?text=' + encodeURIComponent(content) + '&language=$language&gender=$gender', 400, 400);");
		return $str;
	}
}

class ValTranslation extends Validator {
	function validate ($value, $args) {
		if(is_array($value)) {
			return true;
		}
		if (!$value)	
			return $this->label . " is Required";
		else
			return true;

	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {			
				checkval = value.evalJSON();
				if (value == "")
					return label + " is Required";
				return true;
			}';
	}
}


?>