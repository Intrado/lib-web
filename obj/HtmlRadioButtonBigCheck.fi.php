<?

class HtmlRadioButtonBigCheck extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>
			<div id="'.$n.'-container" class="htmlradiobuttonbigcheck cf">';
		$count = 0;
		foreach ($this->args['values'] as $val => $html)  {
			$id = $n.'-'.$count++;
			$str .= '<div class="creation_method">
				<label for="'.$id.'"></label><button type="button" style=" width: 100%;" onclick="htmlRadioButtonBigCheck_doCheck(\''.$this->form->name.'\', \''.$n.'\',  \''.$id.'\', \''.$n.'-container\', \''.$val.'\')">'.($html).'</button>
				<img id="'.$id.'" name="'.$id.'" class="htmlRadioButtonBigCheck_checkImg" src="'.(($value == $val)?'img/bigradiobutton_checked.gif':'img/bigradiobutton.gif').'" onclick="htmlRadioButtonBigCheck_doCheck(\''.$this->form->name.'\', \''.$n.'\',  \''.$id.'\', \''.$n.'-container\', \''.$val.'\')" />
				</div>';
		}
		$str .= '
			<script>
				function htmlRadioButtonBigCheck_doCheck(form, formitem, checkimg, container, value) {
					var form = $(form);
					var formitem = $(formitem);
					var checkimg = $(checkimg);
					var container =  $(container);
					formitem.value = value;
					container.select(\'[class="htmlRadioButtonBigCheck_checkImg"]\').each( function(i) {
						$(i).src = "img/bigradiobutton.gif";
					});
					checkimg.src = "img/bigradiobutton_checked.gif";
					// set helper step and validate
					var formvars = document.formvars[form.name];
					var fieldset = formitem.up("fieldset");
					var step = fieldset.id.substring(fieldset.id.lastIndexOf("_")+1)-1;
					form_go_step(form,null,step);
					form_do_validation(form, formitem);
				}
			</script>
		';
		return $str;
	}
}
?>