<?
class SMSAggregator extends FormItem {
	
	function render ($value) {
		
		// code copied from SelectMenu form item in Form.obj.php
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$str = '<select id='.$n.' name="'.$n.'" '.$size .' >';
		foreach ($this->args['values'] as $selectvalue => $selectname) {
			$checked = $value == $selectvalue;
			$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>
				';
		}
		$str .= '</select>';
		
		// then we add a couple divs for displaying vendor and short code information
		$str .='<div id="smsShortCodeData" style></div>';
		return $str;
	}
	
//	function renderJavascript($value) {
//		global $SETTINGS;
//		$n = $this->form->name."_".$this->name;
//		
//		$str = '// Facebook javascript API initialization, pulled from facebook documentation
//				window.fbAsyncInit = function() {
//					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", status: true, cookie: false, xfbml: true});
//					
//					// load the initial list of pages if possible
//					updateFbPagesRo("'.$n.'", "'.$n.'fbpages");
//				};
//				(function() {
//					var e = document.createElement("script");
//					e.type = "text/javascript";
//					e.async = true;
//					e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
//					document.getElementById("fb-root").appendChild(e);
//				}());
//				';
//		return $str;
//	}

}

?>