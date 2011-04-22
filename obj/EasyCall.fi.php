<?

class EasyCall extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (isset($this->args['languages']) && $this->args['languages'])
			$languages = $this->args['languages'];
		else
			$languages = array();

		$defaultphone = "";
		if (isset($this->args['phone']))
			$defaultphone = escapehtml(Phone::format($this->args['phone']));
		
		if (!$value)
			$value = '{}';
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';

		// set up easycall stylesheet
		$str .= '
		<style type="text/css">
		.easycallcallprogress {
			float:left;
		}
		.easycallunderline {
			padding-top: 3px;
			margin-bottom: 5px;
			border-bottom:
			1px solid gray;
			clear: both;
		}
		.easycallphoneinput {
			margin-bottom: 5px;
			border: 1px solid gray;
		}

		.wizeasycallcontainer {
			padding: 0px;
			margin: 0px;
			white-space:nowrap;
		}
		.wizeasycallaction {
			width: 80%;
			float: right;
			margin-bottom: 5px;
		}
		.wizeasycalllanguage {
			font-size: large;
			float: left;
		}
		.wizeasycallbutton {
			float: left;
		}
		.wizeasycallmaincontainer {
			padding-bottom: 6px;
		}
		.wizeasycallcontent {
			padding-bottom: 6px;
			padding-left: 6px;
			padding-top: 0px;
			margin: 0px;
			white-space:nowrap;
		}
		.wizeasycallaltlangs {
			clear: both;
			padding: 5px;
		}
		</style>';

		$str .='
		<div class="wizeasycallmaincontainer">
			<div id="'.$n.'_content" class="wizeasycallcontent"></div>
			<div id="'.$n.'_altlangs" class="wizeasycallaltlangs" style="display: none">';
		if (count($languages)) {
			$str .= '
				<div style="margin-bottom: 3px;">'._L("Add an alternate language?").'</div>
				<select id="'.$n.'_select" ><option value="0">-- '._L("Select One").' --</option>';
			foreach ($languages as $langcode => $langname)
				$str .= '<option id="'.$n.'_select_'.$langcode.'" value="'.$langcode.'" >'.escapehtml($langname).'</option>';
			$str .= '</select>';
		}
		$str .= '
			</div>
		</div>
		';

		// include the easycall javascript object, extend it's functionality, then load existing values.
		$str .= '<script type="text/javascript" src="script/easycall.js.php"></script>
			<script type="text/javascript" src="script/wizeasycall.js.php"></script>
			<script type="text/javascript">
				// get the current audiofiles from the form data
				var audiofiles = '.$value.';

				// if en (Default) is not set, set it so it must be recorded
				if (typeof(audiofiles["en"]) == "undefined")
					audiofiles["en"] = null;

				// store the language code to name map in a json object, we need this in WizEasyCall
				languages = '.json_encode($languages).';
				languages["en"] = "Default";

				// save default phone into msgphone, this variable tracks changes the user makes to desired call me number
				msgphone = "'.$defaultphone.'";

				// load up all the audiofiles from form data
				Object.keys(audiofiles).each(function(langcode) {

					// create a new wizard easycall
					insertNewWizEasyCall( "'.$n.'", "'.$n.'_content", "'.$n.'_select", langcode );
				});
				
				// listen for selections from the _select element
				if ($("'.$n.'_select")) {
					$("'.$n.'_select").observe("change", function (event) {
						e = event.element();
						if (e.value == 0)
							return;

						var langcode = $("'.$n.'_select").value;

						// create a new wizard easycall
						insertNewWizEasyCall( "'.$n.'", "'.$n.'_content", "'.$n.'_select", langcode );

					});
				}
			</script>';
		return $str;
	}
}
?>