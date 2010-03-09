<?
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', '1');

require_once("common.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");

if (!$MANAGERUSER->authorized("imports"))
	exit("Not Authorized");

// Set session variables if user got here from the customerimports.php
if (isset($_GET['cid']) && isset($_GET['importid'])) {
	$_SESSION['cid'] = $_GET['cid'] + 0;
	$_SESSION['importid'] = $_GET['importid'] + 0;
	redirect();
}

// Check session variables
if (!isset($_SESSION['cid']) && !isset($_SESSION['importid']))
	die("You got here without using the proper URL.  Please return to the imports page and use the Import Alert links");

$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s left join customer c on (s.id = c.shardid) where c.id = {$_SESSION['cid']}");
$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_{$_SESSION['cid']}");
if (!$custdb)
	die("Connection failed for {$custinfo[0]}, c_{$_SESSION['cid']}");
// Useful data
$displayname = QuickQuery("select value from setting where name = 'displayname'", $custdb);
$timezone = QuickQuery("select value from setting where name = 'timezone'", $custdb);
$import = QuickQueryRow("select id, name, description, lastrun, updatemethod, length(data) as filesize, datamodifiedtime, alertoptions from import where id = {$_SESSION['importid']}", true, $custdb);
$alertoptions = sane_parsestr($import['alertoptions']);

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$daysinweek = array("su", "m", "tu", "w", "th", "f", "s");
$f = "importalerts";
$s = "main";

date_default_timezone_set($timezone);

$reloadform = false;
if (CheckFormSubmit($f, $s) || CheckFormSubmit($f, "Clear")) {
	if (CheckFormInvalid($f)) {
		error("Form was edited in another window, reloading data");
		$reloadform = true;
	} else {
		MergeSectionFormData($f, $s);

		// Gets rid of error message if submitting for Scheduled Days but input for Stale Data is invalid; don't care about Stale Data if Scheduled Days is chosen
		if (GetFormData($f, $s, "scheduled"))
			PutFormData($f, $s, "staledatamindays", "");

		// Input validation
		if (CheckFormSection($f, $s)) {
			error("There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly");
		} else if (GetFormData($f,$s,'filesizemax') >= 1 && GetFormData($f,$s,'filesizemin') >= 1 && GetFormData($f,$s,'filesizemax') < GetFormData($f,$s,'filesizemin')) {
			error("Max size must be greater than min size", "If you don't want a max size, set it to blank");
		} else if ($bad = checkemails(GetFormdata($f,$s,'emailaddresses'))) {
			error("Please make sure email addresses are valid.");
		// No errors found
		} else {
			$newalertoptions = array();
			// Filesize
			if (GetFormData($f,$s,'filesizemin'))
				$newalertoptions['minsize'] = GetFormData($f,$s,'filesizemin');
			if (GetFormData($f,$s,'filesizemax'))
				$newalertoptions['maxsize'] = GetFormData($f,$s,'filesizemax');
			// Stale Data
			if (!GetFormData($f, $s, "scheduled") && GetFormData($f,$s,'staledatamindays')) {
				$newalertoptions['daysold'] = GetFormData($f,$s,'staledatamindays');
			// Scheduled Day
			} else if (GetFormData($f, $s, "scheduled")) {
				$fscheduleddays = array();
				foreach ($daysinweek as $i => $day) {
					if (GetFormData($f, $s, $day) == 1)
						$fscheduleddays[] = $i;
				}
				if (!empty($fscheduleddays)) {
					$newalertoptions['dow'] = implode(",", $fscheduleddays);
					$newalertoptions['time'] = date("H:i", strtotime(GetFormData($f,$s,'scheduledtime')));
					$newalertoptions['scheduledwindowminutes'] = GetFormData($f,$s,'scheduledwindowminutes');
				}
			}
			// Email Addresses
			if (GetFormdata($f,$s,'emailaddresses')) {
				$newalertoptions['emails'] = GetFormdata($f,$s,'emailaddresses');
			}
			
			// Update the database only if there are any changes, or if the Clear button was pressed.
			$alertoptionsurl = http_build_query($newalertoptions, false, "&");
			$existingoptions = substr($import['alertoptions'], 0, strpos($import['alertoptions'], "&lastnotified"));
			if (CheckFormSubmit($f, "Clear") || $alertoptionsurl != $existingoptions)
				QuickUpdate("update import set alertoptions = ? where id = ?", $custdb, array($alertoptionsurl, $_SESSION['importid']));
			
			ClearFormData($f);
			redirect("customerimports.php");
		}
	}
} else {
	$reloadform = true;
}

if ($reloadform === true) {
	ClearFormData($f);
	// Alert Options (form input)
	PutFormData($f, $s, "emailaddresses", isset($alertoptions['emails']) ? $alertoptions['emails'] : "", "text");
	PutFormData($f, $s, "filesizemin", isset($alertoptions['minsize']) ? $alertoptions['minsize'] : "", "number", 0);
	PutFormData($f, $s, "filesizemax", isset($alertoptions['maxsize']) ? $alertoptions['maxsize'] : "", "number", 0);
	PutFormData($f, $s, "filesizepercent", "", "number");
	PutFormData($f, $s, "scheduled", isset($alertoptions['dow']) ? 1 : 0, "text");
	PutFormData($f, $s, "staledatamindays", isset($alertoptions['daysold']) ? $alertoptions['daysold'] : "", "number", 1);
	$scheduleddays = (isset($alertoptions['dow'])) ? array_flip(explode(",", $alertoptions['dow'])) : array();
	foreach ($daysinweek as $i => $day) {
		PutFormData($f, $s, $day, isset($scheduleddays[$i]) ? 1 : 0, "bool");
	}
	PutFormData($f, $s, "scheduledtime", isset($alertoptions['time']) ? date("g:i a", strtotime($alertoptions['time'])) : "", "text");
	PutFormData($f, $s, "scheduledwindowminutes", isset($alertoptions['scheduledwindowminutes']) ? $alertoptions['scheduledwindowminutes'] : "", "text");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

include_once("nav.inc.php");
NewForm($f);
?>
<div>Customer: <?=$displayname?></div>
<div>Import Alerts for: <?=$import['name']?></div>
<table>
	<tr>
		<td>Current File Size: </td>
		<td id="actualsizetarget">
			<?=number_format($import['filesize'])?> (bytes)
			&plusmn;
<?
				NewFormItem($f,$s,"filesizepercent", "selectstart", NULL, NULL, "id='filesizepercent'");
				for ($i = 10; $i <= 100; $i += 10) {
					NewFormItem($f,$s,"filesizepercent", "selectoption", $i, $i);
				}
				NewFormItem($f,$s,"filesizepercent", "selectend");
?>
			%
			<button type='button' onclick='calculateminmax()'>Calculate</button></td></tr>
	<tr><td colspan=6><input id="filesizechecking" onblur="togglefilesizecheck(true)" onclick="togglefilesizecheck()" type="checkbox"/><label for="filesizechecking">Enable File Size Checking</label></td></tr>
	<tr><td>Min Size:</td><td><? NewFormItem($f, $s, "filesizemin", "text", 10, 20, "id='minsizeinput' onblur='togglefilesizecheck(true)' onfocus='togglefilesizecheck(false,true)' onkeyup='togglefilesizecheck(true)'")?> (bytes)</td></tr>
	<tr><td>Max Size:</td><td><? NewFormItem($f, $s, "filesizemax", "text", 10, 20, "id='maxsizeinput' onblur='togglefilesizecheck(true)' onfocus='togglefilesizecheck(false,true)' onkeyup='togglefilesizecheck(true)'")?> (bytes)</td></tr>
	<tr><td colspan=6><i>The number of days from the last upload is used to determine when data is stale; for recurring uploads within a week, set a time window instead.</i></td></tr>
	<tr>
		<td><? NewFormItem($f, $s, "scheduled","radio", null, 0, "id='dostaledata' onclick=\"$('scheduleddays').hide();$('staledatamindays').show()\"");?>Stale Data</td>
		<td><? NewFormItem($f, $s, "scheduled","radio", null, 1, "id='doscheduleddays' onclick=\"$('staledatamindays').hide();$('scheduleddays').show()\"");?>Weekly Schedule</td>
	</tr>
</table>
<table>
	<tr>
		<td>
			<div id='staledatamindays'>
				<table>
					<tr><td>How many days before an import is stale?</td><td><? NewFormItem($f, $s, "staledatamindays", "text", 10, 20)?></td></tr>
				</table>
			</div>
			<div id='scheduleddays'>
				<table>
					<tr>
						<td>Schedule:</td>
						<td>
							<table border="1px" margin="1px">
								<tr>
<?
								foreach($daysinweek as $day){
									?><th><?=ucfirst($day)?></th><?
								}
?>
									<th>Time</th>
									<th>Window</th>
								</tr>
								<tr>
<?
								foreach($daysinweek as $day){
									?><td><? NewFormItem($f, $s, $day, "checkbox"); ?></td><?
								}
?>
									<td><? time_select($f, $s, "scheduledtime") ?></td>
									<td>
										&plusmn;
<?
											NewFormItem($f,$s,"scheduledwindowminutes", "selectstart");
											for ($i = 10; $i <= 140; $i += 15) {
												NewFormItem($f,$s,"scheduledwindowminutes", "selectoption", $i, $i);
											}
											NewFormItem($f,$s,"scheduledwindowminutes", "selectend");
?>
											minutes
									</td>
								</tr>

							</table>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
<table>
	<tr><td colspan=6><i>The above settings are optional; failed uploads will be alerted regardless of the above settings.</i></td></tr>
	<tr><td colspan=6><i>If there are no email recipients, no alerts will be sent.</i></td></tr>
	<tr>
		<td>Email Recipients:</td>
		<td><? NewFormItem($f, $s, "emailaddresses", "textarea", 50, 5, "id='emailaddressesinput' style='overflow: auto;'");?>
		<button type="button" id="addmebutton" onclick='addme()'>Add Me</button></td>
	</tr>
	<tr>
		<td>Last Notified:</td>
		<td><?= isset($alertoptions['lastnotified']) ? date("M j, Y g:i a", $alertoptions['lastnotified']) : "--Never--" ?>
			<? NewFormItem($f, "Clear", "Clear", "submit"); ?>
		</td>
	</tr>
</table>
<div><? NewFormItem($f, $s, "Save", "submit"); ?><a href="customerimports.php">Cancel</a></div>
<?

EndForm();

include_once("navbottom.inc.php");
?>
<script>
if(new getObj('dostaledata').obj.checked){
	$('scheduleddays').hide();
} else {
	$('staledatamindays').hide();
}

<?
if (isset($alertoptions['minsize']) || isset($alertoptions['maxsize'])) {
	print "togglefilesizecheck(false, true);";
}
?>

function getObj(name)
{
  if (document.getElementById)
  {
  	this.obj = document.getElementById(name);
  }
  else if (document.all)
  {
	this.obj = document.all[name];
  }
  else if (document.layers)
  {
   	this.obj = document.layers[name];
  }
  if(this.obj)
	this.style = this.obj.style;
}

function addme() {
	var emailsinput = new getObj('emailaddressesinput').obj;
	emailsinput.value += ";" + "<?= $MANAGERUSER->email ?>";
	//emailsinput.value = emailsinput.value.replace(/^[^_@.a-zA-Z0-9]+/, "");
}

function togglefilesizecheck(valuecheck, forcechecked) {
	var checking = new getObj('filesizechecking').obj;
	var mininput = new getObj('minsizeinput').obj;
	var maxinput = new getObj('maxsizeinput').obj;

	if (forcechecked) {
		checking.checked = true;
	}

	if (valuecheck && mininput.value == "" && maxinput.value == "") {
		checking.checked = false;
	}

	if (!checking.checked) {
		mininput.value = "";
		maxinput.value = "";
	}
}

function calculateminmax() {
	var mininput = new getObj('minsizeinput').obj;
	var maxinput = new getObj('maxsizeinput').obj;

	var percent = new getObj('filesizepercent').obj;

	mininput.value = parseInt(<?=$import['filesize']?> * (1.0 - parseFloat(percent.value/100.0)));
	maxinput.value = parseInt(<?=$import['filesize']?> * (1.0 + parseFloat(percent.value/100.0)));

	togglefilesizecheck(true, true);
}
</script>
