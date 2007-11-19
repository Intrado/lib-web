<?
//expects $PERSONID and $person to be set
if($PERSONID){

	NewForm($f);
?>
	<table cellpadding="3" cellspacing="1" width="100%">
<?
		$type = "phone";
?>
		<tr class="listHeader">
			<th align="left" colspan="<?=count($jobtypes)+2; ?>"><?=format_delivery_type($type); ?></th>
		</tr>
		<tr class="windowRowHeader" >
			<th align="left">Contact&nbsp;Type</th>
			<th align="left">Destination</th>
<?
			foreach($jobtypes as $jobtype){
				?><th><?=jobtype_info($jobtype)?></th><?
			}
?>
		</tr>
<?
			foreach($phones as $phone){
?>
				<tr>
					<th align="left" class="bottomBorder" valign="top" style="padding-top: 6px;"><?=destination_label("phone",$phone->sequence)?></th>
					<td class="bottomBorder" >
					<? 
						if(!$lockedphones[$phone->sequence]){ 
							NewFormItem($f, $s, "phone" . $phone->sequence, "text", 14, null);
						} else {
							if($phone->phone)
								echo Phone::format($phone->phone);
							else
								echo "&nbsp;";
						}
					?>
					</td>
<?
						displayJobtypeForm($f, $s, "phone", $phone->sequence, $jobtypes);
?>
				</tr>
<?
			}
		$type = "email";
?>
			<tr class="listHeader">
				<th align="left"  colspan="<?=count($jobtypes)+3; ?>"><?=format_delivery_type($type); ?></th>
			</tr>
			<tr class="windowRowHeader" >
				<th align="left">Contact&nbsp;Type</th>
				<th align="left">Destination</th>
<?
				foreach($jobtypes as $jobtype){
					?><th><?=jobtype_info($jobtype)?></th><?
				}
?>
			</tr>
<?
			foreach($emails as $email){
?>
				<tr>
					<th align="left" class="bottomBorder" valign="top" style="padding-top: 6px;"><?=destination_label("email",$email->sequence)?>:</th>
					<td class="bottomBorder" >
						<? 
							if(!$lockedemails[$email->sequence]){ 
								NewFormItem($f, $s, "email" . $email->sequence, "text", 40, 100, 'id="email' . $email->sequence . '"');
							} else {
								if($email->email)
									echo $email->email;
								else
									echo "&nbsp;";
							}
						?>
					</td>
<?
					displayJobtypeForm($f, $s, "email", $email->sequence, $jobtypes);
?>									
				</tr>
<?
			}
			if(getSystemSetting("_hassms")){
				$type = "sms";
?>
				<tr class="listHeader">
					<th align="left" colspan="<?=count($jobtypes)+3; ?>"><?=format_delivery_type($type); ?></th>
				</tr>
				<tr class="windowRowHeader" >
					<th align="left">Contact&nbsp;Type</th>
					<th align="left">Destination</th>
<?
					foreach($jobtypes as $jobtype){
						?><th><?=jobtype_info($jobtype)?></th><?
					}
?>
				</tr>
<?
				foreach($smses as $sms){
?>
				<tr>
					<th align="left" class="bottomBorder" valign="top" style="padding-top: 6px;"><?=destination_label("sms",$sms->sequence)?>:</th>
					<td class="bottomBorder" >
						<? 
							if(!$lockedsms[$sms->sequence]){
								NewFormItem($f, $s, "sms" . $sms->sequence, "text", 14);
							} else {
								if($sms->sms)
									echo Phone::format($sms->sms);
								else
									echo "&nbsp;";
							}
						?>
					</td>
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
	<br>
<?
	echo submit($f, $s, "Save");
	EndForm();
}
?>