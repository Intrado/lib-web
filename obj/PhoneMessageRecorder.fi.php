<?
class PhoneMessageRecorder extends FormItem {

	function renderJavascriptLibraries() {
		return '<script type="text/javascript" src="script/jquery.json-2.3.min.js"></script>
				<script type="text/javascript" src="script/jquery.timer.js"></script>
				<script type="text/javascript" src="script/jquery.easycall.js"></script>
				<script src="script/phonemessagerecorder.js"></script>';
	}

	function render ($value) {
		global $USER, $n, $name, $langcode, $phone;
		$n = $this->form->name."_".$this->name;

		$name 			= (isset($this->args['name'])) ? escapehtml($this->args['name']) : escapehtml(_L("Message"));
		$langcode 		= (isset($this->args['langcode'])) ? escapehtml($this->args['langcode']) : "en";
		$phone 			= (isset($this->args['phone'])) ? Phone::format(escapehtml($this->args['phone'])) : Phone::format($USER->phone);

		if (!$value)
			$value = '{}';

		// EasyCall DOM elem to attach easyCall; hidden elem that stores state of easyCall data via .data()
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
				<link rel="stylesheet" type="text/css" href="css/easycall_widget.css" >
				<script type="text/javascript">setupBasicVoiceRecorder("'.$n.'", "'.$langcode.'", "'.$name.'", "'.$phone.'");</script>';

		return $str;
	}
}
?>