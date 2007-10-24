<?
//expects $PERSONID and $person to be set
if($PERSONID){

	NewForm($f);
?>
	<table cellpadding="3" cellspacing="1">
		<tr class="listheader" >
			<th colspan="2">Contact Details</th>
<?
			foreach($jobtypes as $jobtype){
				?><th><?=jobtype_info($jobtype)?></th><?
			}
?>
		<tr>
<?
			$type = "phone";
			foreach($phones as $phone){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone <?=$phone->sequence+1?>:</th>
					<td class="bottomBorder" >
					<? 
						if(!$lockedphones[$phone->sequence]){ 
							NewFormItem($f, $s, "phone" . $phone->sequence, "text", 14, null);
						} else {
							echo Phone::format($phone->phone);
						}
					?>
<?
						displayJobtypeForm($f, $s, "phone", $phone->sequence, $jobtypes);
?>									

				</tr>
<?
			}
			$type= "email";
			foreach($emails as $email){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Email <?=$email->sequence+1?>: </th>
					<td class="bottomBorder" >
						<div style="float:left"><? NewFormItem($f, $s, "email" . $email->sequence, "text", 40, 100, 'id="email' . $email->sequence . '"'); ?></div>
						<?=button("Copy Email", "var email=new getObj('email" . $email->sequence . "').obj; email.value='" . $_SESSION['portaluser']['portaluser.username'] . "'") ?>
						
					</td>
<?
					displayJobtypeForm($f, $s, "email", $email->sequence, $jobtypes);
?>									
					</td>
				</tr>
<?
			}
			if(getSystemSetting("_hassms")){
				$type= "sms";
				foreach($smses as $sms){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">SMS <?=$sms->sequence+1?>:</th>
					<td class="bottomBorder" ><? NewFormItem($f, $s, "sms" . $sms->sequence, "text", 14); ?></td>
<?
					displayJobtypeForm($f, $s, "sms", $sms->sequence, $jobtypes);
?>									
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