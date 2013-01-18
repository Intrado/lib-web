<?
//displays checkbox for each jobtype
function newdisplayJobtypeForm($f, $s, $type, $sequence, $jobtypes){
	$id = "t{$type}s{$sequence}";
	
	?><td class="bottomBorder" width="100%">
	
	<table class="preference_wrap"><tr><td>
	<div class="onoffswitch">
    <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="<?= $id?>_switch" onclick="togglePreferences('<?= $id?>',this.checked);" checked />
    <label class="onoffswitch-label" for="<?= $id?>_switch" onclick="function () {// mobile safari doesn't obey lables unless emty function is inserted here}">
    <div class="onoffswitch-inner"></div>
    <div class="onoffswitch-switch"></div>
    </label>
    </div>

    </td><td>
	<?
	echo  "<div id=\"{$id}\" class=\"view_preference_button\">" . action_link(_L("Options"), "cog", null,"$('{$id}_content').toggleClassName('minhide');return false;") . "</div>";

	?>
	</td></tr>
	</table>
	
	<? 
    echo  "<div id=\"{$id}_content\" class=\"view_preference_content minhide\">";
    echo "<ul class=\"jobtypepreferences\">";
    foreach($jobtypes as $jobtype){
    	if($type!="sms" || ($type=="sms" && !$jobtype->issurvey)){
    			
    		echo "<li>";
    		NewFormItem($f, $s, $type . $sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1);
    		echo  $jobtype->name . "</li>";
    	} else {
    		echo "&nbsp;";
    	}
    }
    echo "</ul>";
    echo  "</div>";
    ?>
	</td>
	<?
}


//expects $PERSONID and $person to be set
if($PERSONID){

	NewForm($f);
?>
	<table cellpadding="3" cellspacing="1" width="100%">
<?
		$type = "phone";
?>
		<tr class="windowRowHeader" >
			<th align="left" colspan="2"><?=format_delivery_type($type); ?></th>
		</tr>
<?
			foreach($phones as $phone){
?>
				<tr>
					<td class="bottomBorder" >
					<? 
						if(!$lockedphones[$phone->sequence]){ 
							NewFormItem($f, $s, "phone" . $phone->sequence, "tel", 14,null, " id=\"phones{$phone->sequence}\" class=\"phonepreference\"");
						} else {
							if($phone->phone)
								echo Phone::format($phone->phone);
							else
								echo "&nbsp;";
						}
					?>
					</td>
<?
						newdisplayJobtypeForm($f, $s, "phone", $phone->sequence, $jobtypes);
?>
				</tr>
<?
			}
		$type = "email";
?>
			<tr class="windowRowHeader" >
				<th align="left"  colspan="2"><?=format_delivery_type($type); ?></th>
			</tr>
<?
			foreach($emails as $email){
?>
				<tr>
					<td class="bottomBorder" >
						<? 
							if(!$lockedemails[$email->sequence]){ 
								NewFormItem($f, $s, "email" . $email->sequence, "email", 15, 100, " id=\"emails{$email->sequence}\" class=\"emailpreference\"");
							} else {
								if($email->email)
									echo $email->email;
								else
									echo "&nbsp;";
							}
						?>
					</td>
<?
					newdisplayJobtypeForm($f, $s, "email", $email->sequence, $jobtypes);
?>									
				</tr>
<?
			}
			if(getSystemSetting("_hassms")){
				$type = "sms";
?>
				<tr class="windowRowHeader" >
					<th align="left" colspan="2"><?=format_delivery_type($type); ?></th>
				</tr>
<?
				foreach($smses as $sms){
?>
				<tr>
					<td class="bottomBorder" >
						<? 
							if(!$lockedsms[$sms->sequence]){
								NewFormItem($f, $s, "sms" . $sms->sequence, "tel", 14,null," id=\"smss{$sms->sequence}\" class=\"smspreference\"");
							} else {
								if($sms->sms)
									echo Phone::format($sms->sms);
								else
									echo "&nbsp;";
							}
						?>
					</td>
<?
					newdisplayJobtypeForm($f, $s, "sms", $sms->sequence, $jobtypes);
?>
				</tr>
<?
				}
			}
?>
	</table>
	<div><? NewFormItem($f, $s, "savetoall", "checkbox"); ?> <?=_L("Save To All Contacts")?></div>
	<br>
	
<?
	echo icon_button(_L("Save"),"accept","removeBlankFields();submitForm('$f','$s');");
	EndForm();
}
?>

<script type="text/javascript">
	// initialize destiniation fields
	document.observe('dom:loaded', function() {
		$$('input.phonepreference').each(function(item) {
			initiateDestination('<?=_L("Phone")?>',item);			
		});

		$$('input.emailpreference').each(function(item) {
			initiateDestination('<?=_L("Email")?>',item);			
		});

		$$('input.smspreference').each(function(item) {
			initiateDestination('<?=_L("SMS")?>',item);			
		});
	});
	
	function initiateDestination(type,item) {
		var prefswitch = $('t' + item.id + '_switch');


		// Initiate the jobtypoe preference checkboxes debending on if the value fro the destination is set
		// ie if the phone number is not set the options can not be set to on  
		if (item.value == "") {
			prefswitch.checked = false;
			$$('#t' + item.id + '_content input').each(function(i) {
				i.observe('click',updateSwitch.curry(item.id));
				i.checked = false;
			});
		} else {
			var isOn = false;
			$$('#t' + item.id + '_content input').each(function(i) {
				i.observe('click',updateSwitch.curry(item.id));
				if (i.checked)
					isOn = true;
			});
			prefswitch.checked = isOn;	
		}

		// Set labels in blank field
		blankFieldValue(item, type);

		// toggle the on/off switch whne value is entered for the destination
		item.observe('change',function(i) {
			var prefswitch = $('t' + i.element().id + '_switch');
			if (i.element().value == "") {
				prefswitch.checked = false;
			} else {
				prefswitch.checked = true;
			}
			togglePreferences('t' + i.element().id, prefswitch.checked);
		});
		item.focus();
		item.blur();
	}

	
	// Toggle the jobtype preferences if blanking out or filling in the destination
	// or ckicking the on off switch
	function togglePreferences(id,checked) {
		value = $(id.substring(1)).getStyle('color');
		if (value == "gray") {
			$(id + '_switch').checked = false;
			return;
		}
		
		$$('#' + id + '_content input').each(function(item) {
			item.checked = checked;
		});
	}

	// Update the on/off switch when jobtype preferences are checked or unchecked
	function updateSwitch(id,event) {
		// Disable checking options if no destination is provided
		if ($(id).getStyle('color') == "gray") {
			event.element().checked = false;
			return;
		}

		// Update the on/off switch if any jobtye option is on or all are off
		var prefswitch = $('t' + id + '_switch');
		var isOn = false;
		$$('#t' + id + '_content input').each(function(i) {
			if (i.checked)
				isOn = true;
		});
		prefswitch.checked = isOn;		
	}

	// Here is a little hack that wipes out the blank field label on submit
	function removeBlankFields() {
		$$('input.phonepreference, input.emailpreference, input.smspreference').each(function(item) {
			if(item.getStyle('color') == "gray") {
				item.stopObserving('change');
				item.value = "";
			}
		});
	}	
</script>