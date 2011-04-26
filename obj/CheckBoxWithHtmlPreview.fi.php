<?
class CheckBoxWithHtmlPreview extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="checkbox" value="true" '. ($value ? 'checked' : '').'
				onclick="showhidepreview(\''. $n .'\')"/>
			<div id="'.$n.'-checked" name="'.$n.'" style="border: 1px solid gray; overflow: auto; padding: 4px; max-height: 150px; display: '. ($value ? 'block' : 'none') .'">
				'. $this->args['checkedhtml'] .'
			</div>
			<div id="'.$n.'-unchecked" name="'.$n.'" style="border: 1px solid gray; overflow: auto; padding: 4px; max-height: 150px; color: gray; display: '. ($value ? 'none' : 'block') .'">
				'. $this->args['uncheckedhtml'] .'
			</div>
			<script type="text/javascript">
				function showhidepreview(e) {
					e = $(e);
					if (e.checked) {
						$(e.id + "-checked").show();
						$(e.id + "-unchecked").hide();
					} else {
						$(e.id + "-checked").hide();
						$(e.id + "-unchecked").show();
					}
				}
			</script>
		';
		return $str;
	}
}
?>