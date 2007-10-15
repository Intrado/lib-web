<?
//expects $PERSONID and $person to be set
if($PERSONID){

	$phones = $person->getPhones();
	$emails = $person->getEmails();

	if($accessiblePhonesSetting = getSystemSetting("accessiblePhones"))
		$accessiblePhonesSetting = explode(",", $accessiblePhonesSetting);
	else
		$accessiblePhonesSetting = array();

	$accessiblePhones = array_fill(0, getSystemSetting("maxphones")-1, 0);
	foreach($accessiblePhonesSetting as $accessible)
		$accessiblePhones[$accessible] = true;


	/****************** main message section ******************/

	$f = "contactpreferences";
	$s = "main";
	$reloadform = 0;


	if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "all"))
	{
		//check to see if formdata is valid
		if(CheckFormInvalid($f))
		{
			error('Form was edited in another window, reloading data');
			$reloadform = 1;
		}
		else
		{
			MergeSectionFormData($f, $s);

			//do check

			if( CheckFormSection($f, $s) ) {
				error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
			} else {
				//submit changes
				if(isset($ADDWIZARD) && CheckFormSubmit($f, "all")){
					//TODO: save data to all persons in same customerid

					//clear pid stack for current customer id
					unset($_SESSION['pidlist'][$customerid]);
				} else if(isset($ADDWIZARD) && CheckFormSubmit($f,$s)){
					unset($_SESSION['pidlist'][$customerid][$personindex]);
				}
				
				redirect();
			}
		}
	} else {
		$reloadform = 1;
	}

	if( $reloadform )
	{
		ClearFormData($f);
		if($PERSONID){
			foreach($emails as $email)
				PutFormData($f, $s, "email" . $email->sequence, $email->email, "email", 0, 100);
			foreach($phones as $phone){
				PutFormData($f, $s, "phone" . $phone->sequence, Phone::format($phone->phone), "phone", 0, 100);
				foreach($jobtypes as $jobtype)
					PutFormData($f, $s, "phonejobtype_" . $jobtype->id . "_" . $phone->sequence, "0", "bool" , 0, 1);
			}
		}
	}
}

?>
	<table border="1" cellpadding="3" cellspacing="1">
<?
		if($PERSONID){
			foreach($phones as $phone){
?>
				<tr>
					<td>Phone <?=$phone->sequence+1?>: <div id="phone<?=$phone->sequence?>"><?= Phone::format($phone->phone) ?></div>
					<?NewFormItem($f, $s, "phone" . $phone->sequence, "text", 14, null, 'id="phoneform' . $phone->sequence . '" style="display:none"');?></td>
					<td>
<?
						if($accessiblePhones[$phone->sequence]){
							echo button("Edit", "show('phonesave" . $phone->sequence . "'); hide('phoneedit" . $phone->sequence . "'); 
										show('phonetable" . $phone->sequence . "'); show('phoneform" . $phone->sequence . "');
										hide('phone" . $phone->sequence . "');", 
										null, "id='phoneedit" . $phone->sequence . "'");
						}
?>
						<table id="phonetable<?=$phone->sequence?>" style="display:none">
							<tr>
								<td>
<?
									foreach($jobtypes as $jobtype){
										?><div style="float: left;"><?=NewFormItem($f, $s, "phonejobtype_" . $jobtype->id . "_" . $phone->sequence, "checkbox");?><?=$jobtype->name?></div><?
									}
?>									
								</td>
							</tr>
						</table>
<?
						echo button("Save", null, null, 'id="phonesave' . $phone->sequence . '" style="display:none"');
?>
					</td>
				</tr>
<?
			}
			foreach($emails as $email){
?>
				<tr>
					<td>Email <?=$email->sequence+1?>: <?=$email->email ?></td>
					<td>
<?
						echo button("Edit", "show('emailsave" . $email->sequence . "'); hide('emailedit" . $email->sequence . "');", null, "id='emailedit" . $email->sequence . "'");
						echo button("Save", null, null, 'id="emailsave' . $email->sequence . '" style="display:none"');
?>
					</td>
				</tr>
<?
			}
		}
?>
	</table>