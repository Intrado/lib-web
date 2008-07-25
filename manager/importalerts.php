<?
include_once("common.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/html.inc.php");

$clear = false;
if(isset($_GET['cid'])){
	$_SESSION['cid'] = $_GET['cid'] +0;
	$clear = true;
}

if(isset($_GET['importid'])){
	$_SESSION['importid'] = $_GET['importid']+0;
	$clear = true;
}

if($clear){
	redirect();
}

if(isset($_SESSION['importid'])){
	$importid = $_SESSION['importid'];
}

if(isset($_SESSION['cid'])){
	$customerid = $_SESSION['cid'];
}


if(!isset($customerid, $importid)){
	echo "You got here without using the proper URL.  Please return to the imports page and use the Import Alert links";
	exit();
}

$dow = array(1 => "su", 2=>"m", 3=>"tu", 4=>"w", 5=>"th", 6=>"f", 7=>"s");

// DB Connection
$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s left join customer c on (s.id = c.shardid) where c.id = " . $customerid);
$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $customerid);
$customername = QuickQuery("select value from setting where name = 'displayname'", $custdb);
$import = QuickQueryRow("select id, name, description, lastrun, updatemethod, datamodifiedtime, alertoptions from import where id = " . $importid, true, $custdb);
if($import['alertoptions']){
	$importalert = sane_parsestr($import['alertoptions']);
} else {
	$importalert = array();
}
//var_dump($importalert);

$f="importalerts";
$s="main";
$reloadform = 0;

if(CheckFormSubmit($f, $s)){
	if(CheckFormInvalid($f)){
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$minsize = ereg_replace("[^0-9]*","",GetFormData($f, $s, "minsize"));
			$maxsize = ereg_replace("[^0-9]*","",GetFormData($f, $s, "maxsize"));
			$emaillist = GetFormData($f, $s, "emails");
			if($maxsize !== "" && $maxsize < $minsize){
				error("Max size must be greater than min size", "If you don't want a max size, set it to blank");
			} elseif($bademaillist = checkemails($emaillist)) {
				error("These emails are invalid", $bademaillist);
			} else {
				$oldimportalert = $importalert;
				//Wipe out any old settings
				$importalert = array();
				$importalert['minsize'] = $minsize;
				$importalert['maxsize'] = $maxsize;
				if(GetFormData($f, $s, "scheduled") == "no"){
					$importalert['daysold'] = GetFormData($f, $s, "daysold");
				} else {
					$newdows = array();
					foreach($dow as $index => $day){
						if(GetFormData($f, $s, $day)){
							$newdows[] = $index;
						}
					}
					$importalert['dow'] = implode(",", $newdows);
					if($importalert['dow'] != "")
						$importalert['time'] = date("H:i", strtotime(GetFormData($f, $s, "time")));
				}
				$importalert['emails'] = DBSafe($emaillist);
				foreach($importalert as $index => $alert){
					if($alert == "")
						unset($importalert[$index]);
				}
				//check all old options compared to new options to see if there is a change
				//if a change exists, erase the last notified flag
				$optionsarray = array("minsize", "maxsize", "dow", "daysold", "time");
				$changed = false;
				foreach($optionsarray as $item){
					if(!isset($importalert[$item]) && !isset($oldimportalert[$item]))
						continue;

					if(!isset($importalert[$item])){
						$changed = true;
						break;
					}
					if(!isset($oldimportalert[$item])){
						$changed = true;
						break;
					}
					if($importalert[$item] != $oldimportalert[$item]){
						$changed = true;
						break;
					}
				}

				if(!$changed && isset($oldimportalert['lastnotified']))
					$importalert['lastnotified'] = $oldimportalert['lastnotified'];

				$importalerturl = http_build_query($importalert, false, "&");
				QuickUpdate("update import set alertoptions = '" . DBSafe($importalerturl) . "' where id = " . $importid, $custdb);
				redirect("customerimports.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	PutFormData($f, $s, "minsize", isset($importalert['minsize']) && $importalert['minsize'] ? number_format($importalert['minsize']) : "", "text");
	PutFormData($f, $s, "maxsize", isset($importalert['maxsize']) && $importalert['maxsize'] ? number_format($importalert['maxsize']) : "", "text");
	PutFormData($f, $s, "daysold", isset($importalert['daysold']) ? $importalert['daysold'] : "", "text");
	PutFormData($f, $s, "managerpassword", "", "text");
	PutFormData($f, $s, "Save", "");

	if(isset($importalert['dow'])){
		$storeddow = array_flip(explode(",", $importalert['dow']));
	} else {
		$storeddow = array();
	}
	foreach($dow as $index => $day){
		PutFormData($f, $s, $day, isset($storeddow[$index]) ? 1 : 0, "bool", 0, 1);
	}
	PutFormData($f, $s, "time", isset($importalert['time']) ? date("g:i a", strtotime($importalert['time'])) : "", "text");
	PutFormData($f, $s, "emails", isset($importalert['emails']) ? $importalert['emails'] : "", "text");
	PutFormData($f, $s, "scheduled", isset($importalert['dow']) ? "yes" : "no");
}


include_once("nav.inc.php");
NewForm($f,"onSubmit='if(new getObj(\"managerpassword\").obj.value == \"\"){ window.alert(\"Enter Your Manager Password\"); return false;}'");
?>
<div>Customer: <?=$customername?></div>
<div>Import Alerts for: <?=$import['name']?></div>
<table>
	<tr><td>Min Size:</td><td><? NewFormItem($f, $s, "minsize", "text", 10, 20)?></td></tr>
	<tr><td>Max Size:</td><td><? NewFormItem($f, $s, "maxsize", "text", 10, 20)?></td></tr>
	<tr>
		<td><? NewFormItem($f, $s, "scheduled","radio", null, "no", "id='no' onclick=\"hide('dow');show('daysold')\"");?>Use Age</td>
		<td><? NewFormItem($f, $s, "scheduled","radio", null, "yes", "id='yes' onclick=\"hide('daysold');show('dow')\"");?>Use Schedule</td>
	</tr>
</table>
<table>
	<tr>
		<td>
			<div id='daysold'>
				<table>
					<tr><td>Days Old:</td><td><? NewFormItem($f, $s, "daysold", "text", 10, 20)?></td></tr>
				</table>
			</div>
			<div id='dow'>
				<table>
					<tr>
						<td>Schedule:</td>
						<td>
							<table border="1px" margin="1px">
								<tr>
<?
								foreach($dow as $day){
									?><th><?=ucfirst($day)?></th><?
								}
?>
									<th>Time</th>
								</tr>
								<tr>
<?
								foreach($dow as $day){
									?><td><? NewFormItem($f, $s, $day, "checkbox"); ?></td><?
								}
?>
									<td><? time_select($f, $s, "time") ?></td>
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
	<tr>
		<td>Emails:</td>
		<td><? NewFormItem($f, $s, "emails", "text", 50, 255);?></td>
	</tr>
	<tr>
		<td>Last Notified:</td>
		<td><?= isset($importalert['lastnotified']) ? date("M j, Y g:i a", $importalert['lastnotified']) : "--Never--" ?></td>
	</tr>
</table>
<div><? NewFormItem($f, $s, "Save", 'submit'); ?><a href="customerimports.php">Cancel</a></div>
<?
managerPassword($f, $s);

EndForm();

include_once("navbottom.inc.php");
?>
<script>
if(new getObj('no').obj.checked){
	hide('dow');
} else {
	hide('daysold');
}


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

function show(name)
{
	var x = new getObj(name);
	if (x.style)
		x.style.display = "block";
}

function hide(name)
{
	var x = new getObj(name);
	if (x.style)
		x.style.display =  "none";
}
</script>