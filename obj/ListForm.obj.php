<?php
/****** TODO *********************************************************************************************
+ Add functionality for 'add myself', bug #
*********************************************************************************************************/
class ListForm extends Form {
	function ListForm ($name) {
		$formdata['listids'] = array(
			'label' => 'A list',
			'value' => '',
			//'control' => array("HiddenField"),
			'validators' => array( array("ValRequired"), array("ValLists") )
		);

		$this->ruleEditorGuideContents = 
		
		parent::Form($name, $formdata, null);
	}
	
	function render () {
		global $USER;
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		$listidsName = $this->name . '_listids';
		
		$formdataJSON = json_encode($this->formdata);
		$posturlJSON = json_encode($posturl);
					
		// HTML
		$str = "
			<table id='listFormWorkspace' width='100%' style='clear:both; margin-bottom:40px'>
				<tr>
					<td colspan=100>
						<h2 style=\"padding-left: 5px; background: repeat-x url('img/header_bg.gif')\">"._L('List')."</h2>
					</td>
				</tr>
				<tr>
					<!-- MAIN CONTENT AREA -->
					
					<td valign=top style='padding:0;margin:0; '>
							<div style='clear:both;'>
								".icon_button(_L('Build List Using Rules'),'application_form_edit', null, null, ' id="buildListButton" style="display:none;margin;0 "')."<span style='clear:both'></span>
								<div id='buildListWindow' style='clear:both; padding:0;margin:0;margin-bottom:30px; display:none;'>
									<div id='ruleWidgetContainer' style='clear:both; white-space:nowrap;'>
									</div>
								</div>
								<div style='clear:both'></div>
								<hr style='clear:both; border: solid 2px rgb(200,200,200); display:none' id='divider' />
								<div style='clear:both; margin-top:5px'>
									".icon_button(_L('Choose an Existing List'),'application_view_list', null, null, ' id="chooseListChoiceButton" style="display:none" ')."<span style='clear:both'></span>
									<div id='chooseListWindow' style='display:none; clear:both'>
										<table style=''><tr>
											<td valign=top>
												<div id='listSelectboxContainer'></div>
											</td>
											<td valign=top>
													<div id='listchooseStatus'></div>
													<div id='listchooseTotalsContainer' style='display:none'>
													<table>
															<tr><th valign=top style='text-align:left'>"._L('List Total')."</th><td valign=top id='listchooseTotal'>0</td></tr>
															<tr><td valign=top style='text-align:left'>"._L('Matched by Rules')."</td><td valign=top id='listchooseTotalRule'>0</td></tr>
															<tr><td valign=top style='text-align:left'>"._L('Additions')."</td><td valign=top id='listchooseTotalAdded'>0</td></tr>
															<tr><td valign=top style='text-align:left'>"._L('Skips')."</td><td valign=top id='listchooseTotalRemoved'>0</td></tr>
														</table>
													</div>
											</td>
										</tr></table>
									</div>
								</div>
								<div style='clear:both'></div>
							</div>
					</td>
					<td valign=top width='50%'>
						<div id='allListsWindow' style='overflow:hidden;' >
							<table width='100%' cellspacing='1' cellpadding='3' class='list' style='table-layout:fixed; font-size:90%;'>
								<thead>
									<tr class='listHeader'>
										<th width='70%' style='overflow: hidden; overflow: hidden; white-space: nowrap; text-align:left'>"._L('List Name')."</th>
										<th width='20%' style='overflow: hidden; text-align:left'>Count</th>
										<th width='16'></th>
									</tr>
								</thead>
								<tbody id='listsTableBody'>
								</tbody>
								<tfoot>
									<tr>
										<td class='border'>
											<b>"._L('Total')."</b>
										</td>
										<td class='border' colspan=2>
											<b><span id='listGrandTotal'>0</span></b><span style='vertical-align:middle' id='listsTableStatus'></span>
										</td>
									</tr>
								</tfoot>
							</table>
						</div>
						<div id='listRulesPreview'></div>
					</td>
				</tr>
			</table>
			
			<!-- FORM -->
			<div class='newform_container' style='clear:both; margin-top: 10px'>
				<!-- Validation Message -->
				<div id='listChoose_listids_fieldarea'>
					<img id='listChoose_listids_icon' src='img/pixel.gif'/>
					<span id='listChoose_listids_msg'></span>
				</div>
				<form class='newform' id='{$this->name}' name='{$this->name}' method='POST' action='{$posturl}'>
					".implode('', $this->buttons)."
					<input name='{$this->name}-formsnum' type='hidden' value='{$this->serialnum}'/>
					<input id='{$listidsName}' name='{$listidsName}' type='hidden' value='{$this->formdata['listids']['value']}'/>
				</form>
			</div>
			<br style='clear: both'/>
		";
		
		// JAVASCRIPT
		$str .= "
			<script type='text/javascript' src='script/datepicker.js'></script>
			<script type='text/javascript' src='script/rulewidget.js.php'></script>
			<script type='text/javascript' src='script/listform.js.php'></script>
			<script type='text/javascript'>
					
				document.observe('dom:loaded', function() {
					// Initiatiate Page.
					listform_load('{$this->name}', {$formdataJSON}, {$posturlJSON});
					
					ruleWidget.refresh_guide(true);
					ruleWidget.startup();
				});
			</script>";
		return $str;
	}
}
?>