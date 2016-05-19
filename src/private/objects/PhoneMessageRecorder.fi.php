<?
class PhoneMessageRecorder extends FormItem {
	var $languages;
	var $defaultPhone;
	var $phoneMinDigits;
	var $phoneMaxDigits;

	function PhoneMessageRecorder($form, $name, $args) {
		parent::FormItem($form, $name, $args);
		global $USER;

		$this->languages = (isset($this->args['languages']) ? $this->args['languages'] : array("en" => "English"));
		$this->defaultPhone = (isset($this->args['phone'])) ? Phone::format(escapehtml($this->args['phone'])) : Phone::format($USER->phone);
		$this->phoneMinDigits = (isset($this->args['phonemindigits']) ? $this->args['phonemindigits'] : 10);
		$this->phoneMaxDigits = (isset($this->args['phonemaxdigits']) ? $this->args['phonemaxdigits'] : 10);
	}

	function render ($value) {
		$n = $this->form->name."_".$this->name;

		// EasyCall DOM elem to attach easyCall; hidden elem that stores the audiofile id values
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value ? $value : '{}').'" />
				<link rel="stylesheet" type="text/css" href="assets/css/easycall_widget.css" >';
		return $str;
	}

	function renderJavascriptLibraries() {
		return '<script type="text/javascript" src="assets/js/jquery.json-2.3.min.js"></script>
				<script type="text/javascript" src="assets/js/jquery.timer.js"></script>
				<script type="text/javascript" src="assets/js/jquery.easycall.js"></script>';
	}

	function renderJavascript($value) {
		reset($this->languages);
		return "
			jQuery(function($) {
				var options = {
					languages: ". json_encode($this->languages). ",
					defaultcode: '". key($this->languages). "',
					defaultphone: '{$this->defaultPhone}',
					phonemindigits: {$this->phoneMinDigits},
					phonemaxdigits: {$this->phoneMaxDigits}
				};
				var form = $('#{$this->form->name}');
				var element = $('#{$this->form->name}_{$this->name}');
				element.attachEasyCall(options);

				// on update events, trigger form validation for the hidden input
				element.on('easycall:update', function(event) {
					form_do_validation(form[0], element[0]);
				});

				// on preview events, launch the audiofile previewer
				element.on('easycall:preview', function(event, data) {
					audioPreviewModal(data.recordingId);
				});
			}(jQuery));
		";
	}
}
?>
