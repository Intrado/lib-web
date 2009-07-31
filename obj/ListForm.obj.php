<?php
/****** TODO *********************************************************************************************
+ Remove unused pendingList; 1) when clicking done 2) when removing all rules from active pending list)
+ Do not allow adding a blank rule (multisearch)
+ Refactor unnecessary functions, better named IDs
+ Benchmark DOM manipulation speed/memory usage in various browsers, particularly for Multisearch persondatavalues.
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
			<table id='listFormWorkspace' width='100%' style='clear:both'>
				<tr>
					<td colspan=100>
						<h2 style=\"padding-left: 5px; background: repeat-x url('img/header_bg.gif')\">"._L('List')."</h2>
					</td>
				</tr>
				<tr>
					<!-- MAIN CONTENT AREA -->
					
					<td valign=top style='padding:0;margin:0; '>
							<div style='clear:both'>
								".icon_button(_L('Build List Using Rules'),'application_form_edit', null, null, ' id="buildListButton" style="display:none;margin;0 "')."<span style='clear:both'></span>
								<div id='buildListWindow' style='clear:both; padding:0;margin:0;display:none;'>
									<div id='ruleWidgetContainer' style='clear:both; white-space:nowrap;'>
									</div>
									<div></div>
								</div>
								<hr style='clear:both; border: solid 2px rgb(200,200,200); display:none' id='divider' />
								<div style='clear:both'>
									".icon_button(_L('Choose an Existing List'),'arrow_turn_left', null, null, ' id="chooseListChoiceButton" style="display:none" ')."<span style='clear:both'></span>
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
							</div>
					</td>
					<td valign=top>
						<div id='allListsWindow' class='border' style='width:170px; overflow:hidden' >
							<table width='100%' style='table-layout:fixed; font-size:90%; border-collapse: collapse'>
								<thead>
									<tr>
										<th style='width:90px; overflow: hidden; white-space: nowrap; text-align:left'> </th>
										<th style='width:16px'></th>
										<th colspan=100 style='width:50px'></th>
									</tr>
									<tr><td colspan=100 id='listsTableStatus'></td></tr>
								</thead>
								<tbody id='listsTableBody'>
								</tbody>
							</table>
							<div style='text-align:left;white-space: nowrap; padding-top:10px'><b>"._L('Grand Total')."</b> <span id='listGrandTotal'>0</span></div>
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