<?
//expects $PERSONID and $person to be set
if($PERSONID){

	NewForm($f);
?>
	<table border="1px" cellpadding="3" cellspacing="1">
		<tr>
			<th colspan="2">Contact Detail</th>
			<th width="50%">Contact Settings</th>
		<tr>
<?
			$type = "phone";
			foreach($phones as $phone){
?>
				<tr>
					<td>Phone <?=$phone->sequence+1?>:</td>
					<td>
					<? 
						if(!$lockedphones[$phone->sequence]){ 
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
							echo button("Change", "show('" . $type . "table" . $phone->sequence . "');
													hide('" . $type . "edit" . $phone->sequence ."');
													hide('" . $type . $phone->sequence . "enabledjobtypes');", 
										null, "id='" . $type . "edit" . $phone->sequence . "'");
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
					<td>Email <?=$email->sequence+1?>: </td>
					<td><? NewFormItem($f, $s, "email" . $email->sequence, "text", 40, 100, 'id="email' . $email->sequence . '"'); ?>
						<?=button("Copy Email", "var email=new getObj('email" . $email->sequence . "').obj; email.value='" . $_SESSION['portaluser']['portaluser.username'] . "'") ?></td>
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
			if(getSystemSetting("_hassms")){
				$type= "sms";
				foreach($smses as $sms){
?>
				<tr>
					<td>SMS <?=$sms->sequence+1?>:</td>
					<td><? NewFormItem($f, $s, "sms" . $sms->sequence, "text", 40, 100); ?></td>
					<td>
						<table id="smstable<?=$sms->sequence?>" style="display:none">
							<tr>
								<td>
<?
									displayJobtypeForm($f, $s, "sms", $sms->sequence, $jobtypes);
?>									
								</td>
							</tr>
						</table>
						<div id="<?=$type?><?=$sms->sequence?>enabledjobtypes" ><?=displayEnabledJobtypes($contactprefs, $defaultcontactprefs, $type, $sms->sequence, $jobtypes)?></div>
						<div style="float:right">
<?
							echo button("Change", "show('" . $type . "table" . $sms->sequence . "'); 
													hide('" . $type . "edit" . $sms->sequence ."');
													hide('" . $type . $sms->sequence . "enabledjobtypes');",
										null, "id='" . $type . "edit" . $sms->sequence . "'");
?>
						</div>
					</td>
				</tr>
<?
				}
			}
?>
	</table>
	<div><? NewFormItem($f, $s, "savetoall", "checkbox"); ?> Save To All Contacts</div>
<?
	
	echo submit($f, $s, "Save");
	EndForm();
}
?>