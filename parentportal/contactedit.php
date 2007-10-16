<?
//expects $PERSONID and $person to be set
if($PERSONID){

	NewForm($f);
?>
	<table border="1" cellpadding="3" cellspacing="1">
		<tr>
			<th>Contact Detail</th>
			<th>Contact Settings</th>
		<tr>
<?
			$type = "phone";
			foreach($phones as $phone){
?>
				<tr>
					<td>Phone <?=$phone->sequence+1?>: 
					<? 
						if($accessiblePhones[$phone->sequence]){ 
							NewFormItem($f, $s, "phone" . $phone->sequence, "text", 14, null);
						} else {
							echo Phone::format($phone->phone);
						}
					?>
					</td>
					<td>
						<table id="phonetable<?=$phone->sequence?>" style="display:none">
							<tr>
								<td>
<?
									displayJobtypeForm($f, $s, "phone", $phone->sequence, $jobtypes);
?>									
								</td>
							</tr>
						</table>
						<div id="<?=$type?><?=$phone->sequence?>enabledjobtypes" ><?=displayEnabledJobtypes($contactprefs, $defaultcontactprefs, $type, $phone->sequence, $jobtypes)?></div>
						<div style="float:right">
<?
						if($accessiblePhones[$phone->sequence]){
							echo button("Change", "show('" . $type . "table" . $phone->sequence . "');
													hide('" . $type . "edit" . $phone->sequence ."');
													hide('" . $type . $phone->sequence . "enabledjobtypes');", 
										null, "id='" . $type . "edit" . $phone->sequence . "'");
						} else {
							echo "&nbsp;";
						}
?>
						</div>
					</td>
				</tr>
<?
			}
			$type= "email";
			foreach($emails as $email){
?>
				<tr>
					<td>Email <?=$email->sequence+1?>: <? NewFormItem($f, $s, "email" . $email->sequence, "text", 40, 100); ?></td>
					<td>
						<table id="emailtable<?=$email->sequence?>" style="display:none">
							<tr>
								<td>
<?
									displayJobtypeForm($f, $s, "email", $email->sequence, $jobtypes);
?>									
								</td>
							</tr>
						</table>
						<div id="<?=$type?><?=$email->sequence?>enabledjobtypes" ><?=displayEnabledJobtypes($contactprefs, $defaultcontactprefs, $type, $email->sequence, $jobtypes)?></div>
						<div style="float:right">
<?
							echo button("Change", "show('" . $type . "table" . $email->sequence . "'); 
													hide('" . $type . "edit" . $email->sequence ."');
													hide('" . $type . $email->sequence . "enabledjobtypes');",
										null, "id='" . $type . "edit" . $email->sequence . "'");
?>
						</div>
					</td>
				</tr>
<?
			}
?>
	</table>
	<div><? NewFormItem($f, $s, "savetoall", "checkbox"); ?> Save To All Contacts</div>
<?
	
	echo submit($f, $s, "Save");
	EndForm();
}
?>