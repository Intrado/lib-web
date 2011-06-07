<?
class PhoneMessageRecorder extends FormItem {

	function render ($value) {
		$n = $this->form->name."_".$this->name;
		
		// should we use a custom name to store the audiofiles with?
		$name = escapehtml(_L("Message"));
		if (isset($this->args['name']))
			$name = escapehtml($this->args['name']);
		
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

		.phonemessagerecordercontent {
			min-height: 24px;
			padding-bottom: 6px;
			white-space: nowrap;
		}
		</style>';

		$str .= '
		<div>
			<div id="'.$n.'_content" class="phonemessagerecordercontent"></div>
		</div>
		<script type="text/javascript">
		setupMessageRecorderButtons("'.$n.'", "'.$name.'");
		</script>
		';
		
		return $str;
	}

	function renderJavascriptLibraries() {
		global $USER;
		// include the easycall javascript object and setup to record
		$str = '<script type="text/javascript" src="script/easycall.js.php"></script>';
		$str .= '
		<script type="text/javascript">

		function setupMessageRecorderButtons(e, name) {
			e = $(e);
			var value = e.value.evalJSON();
			var formname = e.up("form").name;
			var content = $(e.id+"_content");

			if (value.m || value.af) {
				var playbtn = icon_button("'.escapehtml(_L('Play')).'", "fugue/control");
				var rerecordbtn = icon_button("'.escapehtml(_L('Re-record')).'", "diagona/16/118");

				playbtn.observe("click", function () {
					var value = e.value.evalJSON();
					if (value.m)
						popup("previewmessage.php?id=" + value.m, 400, 400);
					else if (value.af)
						popup("previewaudio.php?close=1&id="+value.af, 400, 500);
				});

				function curry (fn,obj) {
					return new function() {
						fn(obj);
					}
				}

				rerecordbtn.observe("click", function () {
					setupMessageRecorderEasyCall(e, name);
				});

				content.update();
				content.insert(playbtn);
				content.insert(rerecordbtn);
			} else {
				setupMessageRecorderEasyCall(e, name);
			}
		}

		function setupMessageRecorderEasyCall (e, name) {
			e = $(e);
			var content = $(e.id+"_content");

			new EasyCall(e, content, "'.Phone::format($USER->phone).'", name + " " + curDate());

			content.observe("EasyCall:update", function(event) {
				e.value = "{\"af\":" + event.memo.audiofileid + "}";
				setupMessageRecorderButtons(e);
				Event.stopObserving(content,"EasyCall:update");
			});
		}
		</script>
		';

		return $str;
	}
}
?>