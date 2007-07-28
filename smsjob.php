<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/text.inc.php");
include_once("obj/SmsJob.obj.php");
include_once("obj/PeopleList.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/FieldMap.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	redirect();
}


$PEOPLELISTS = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");


/****************** main message section ******************/

$f = "smsjob";
$s = "main";
$reloadform = 0;


if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'send'))
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

		//trim message
		$txt = GetFormData($f,$s,"txt");
		$txt = trim(str_replace("\r\n","\n",$txt));
		PutFormData($f,$s,"txt",$txt,"text",10,160,true);

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//fill in the smsjob and save it
			$smsjob = new SmsJob();

			PopulateObject($f,$s,$smsjob,array("name", "description","listid","txt"));
			$smsjob->userid = $USER->id;
			$smsjob->status = "new";
			$smsjob->sentdate = QuickQuery("select now()");
			$smsjob->sendoptout = 0;
			$smsjob->create();

			$smsjobid = $smsjob->id;
			$listid = $smsjob->listid;


			//run the list query and make the smsmsg records

			$usersql = $USER->userSQL("p","pd");
			//get and compose list rules
			$listrules = DBFindMany("Rule","from listentry le, rule r where le.type='R'
					and le.ruleid=r.id and le.listid='$listid' order by le.sequence", "r");
			if (count($listrules) > 0)
				$listsql = "1" . Rule::makeQuery($listrules, "pd");
			else
				$listsql = "0";//dont assume anyone is in the list if there are no rules

			$query = "
			insert into smsmsg (smsjobid, personid, sequence, phone)
			(select $smsjobid as smsjobid, p.id as personid, ph.sequence as sequence, ph.phone as phone
			from person p
			left join persondata pd on (pd.personid = p.id)
			inner join phone ph on (ph.personid = p.id and ph.smsenabled=1 and ph.phone != '')
			left join listentry le on (p.id=le.personid and le.listid=$listid)
			where $usersql and $listsql and le.type is null and p.userid is null order by p.id,ph.sequence)

			union all

			(select $smsjobid as smsjobid, p.id as personid, ph.sequence as sequence, ph.phone as phone
			from listentry le
			straight_join person p on (p.id=le.personid)
			inner join phone ph on (ph.personid = p.id and ph.smsenabled=1 and ph.phone != '')
			where p.customerid = $USER->customerid
			and le.listid=$listid and le.type='A'
			order by le.id,ph.sequence)
			";

			QuickUpdate($query);


			//delete blocked numbers from the list

			$query = "delete s from smsmsg s inner join blockednumber b on
									(s.phone=b.pattern and b.customerid = $USER->customerid
									and b.type in ('both','sms'))
					where s.smsjobid=$smsjobid";
			QuickUpdate($query);

			//update the smsjob status so it gets processed

			$smsjob->status = "queued";
			$smsjob->update();

			sleep(2);

			redirect("smsjobs.php");
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	PutFormData($f,$s,"name","","text",0,50,true);
	PutFormData($f,$s,"description","","text",0,50,false);
	PutFormData($f,$s,"listid",0,"number","nomin","nomax",true);

	PutFormData($f,$s,"txt","","text",10,160,true);

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:sms";
$TITLE = "SMS Job Editor: " . (GetFormData($f,$s,"name") ? GetFormData($f,$s,"name") : "New SMS Job");

include_once("nav.inc.php");
NewForm($f);


buttons(button('Cancel',null,"smsjobs.php"), submit($f, 'send','Submit Job'));

startWindow('Survey Information');
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%" >Job Name</td>
					<td><? NewFormItem($f,$s,"name","text", 30,50); ?></td>
				</tr>
				<tr>
					<td>Description</td>
					<td><? NewFormItem($f,$s,"description","text", 30,50); ?></td>
				</tr>
				<tr>
					<td>List</td>
					<td>
						<?
						NewFormItem($f,$s,"listid", "selectstart");
						NewFormItem($f,$s,"listid", "selectoption", "-- Select a list --", NULL);
						foreach ($PEOPLELISTS as $plist) {
							NewFormItem($f,$s,"listid", "selectoption", $plist->name, $plist->id);
						}
						NewFormItem($f,$s,"listid", "selectend");
						?>
					</td>
				</tr>
				<tr>
					<td>Text Message</td>
					<td>
						<? /* TODO add char counter */ ?>

						<script>

						function limit_chars(field) {
							if (field.value.length > 160)
								field.value = field.value.substring(0,160);
							var status = new getObj('charsleft');
							var remaining = 160 - field.value.length;
							if (remaining <= 0)
								status.obj.innerHTML="<b style='color:red;'>0</b>";
							else if (remaining <= 20)
								status.obj.innerHTML="<b style='color:orange;'>" + remaining + "</b>";
							else
								status.obj.innerHTML=remaining;
						}
						</script>


						<? NewFormItem($f,$s,"txt","textarea",40,5,"onkeydown='limit_chars(this);' onkeyup='limit_chars(this);'"); ?>
						<br>
						<span id="charsleft"><?= 160 - strlen(GetFormData($f,$s,"txt")) ?></span> characters remaining.
					</td>

				</tr>
			</table>
		</td>
	</tr>
</table>
<?
endWindow();


buttons();
EndForm();


include_once("navbottom.inc.php");
?>