<?
include_once("inc/common.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("obj/DMCallerIDRoute.obj.php");

if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}
if(isset($_GET['dmid'])){
	$_SESSION['dmid'] = $_GET['dmid'] +0;
	redirect();
} else {
	$dmid = $_SESSION['dmid'];
}

$calleridroutes = DBFindMany("DMCallerIDRoute", "from dmcalleridroute where dmid = " . $dmid . " and callerid != '' order by callerid ASC");
$defaultcalleridroute = DBFind("DMCallerIDRoute", "from dmcalleridroute where dmid = " . $dmid . " and `callerid` = ''");
if(!$defaultcalleridroute)
	$defaultcalleridroute = new DMCallerIDRoute();
$newcalleridroute = new DMCallerIDRoute();
$newcalleridroute->id = "new";
$calleridroutes[] = $newcalleridroute;

$f = "template";
$s = "main";
$reloadform = 0;


$checkformdelete = false;

foreach($calleridroutes as $calleridroute){
	if(CheckFormSubmit($f, "delete_dm_" . $calleridroute->id)){
		$checkformdelete = true;
	}
}

if(CheckFormSubmit($f,$s) || $checkformdelete || CheckFormSubmit($f, "add") || CheckFormSubmit($f, "deleteall"))
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

			$matches = array();
			$duplicatematches = array();
			$default = false;
			$duplicatedefaults = false;
			foreach($calleridroutes as $calleridroute){
				if(!isset($matches[GetFormData($f, $s, "dm_" . $calleridroute->id ."_callerid")])){
					$matches[GetFormData($f, $s, "dm_" . $calleridroute->id ."_callerid")] = 1;
				} else {
					$duplicatematches[GetFormData($f, $s, "dm_" . $calleridroute->id ."_callerid")] = true;
				}
			}

			if(count($duplicatematches)){
				error("You have multiple caller id routes with the same caller id", array_keys($duplicatematches));
			} else if(CheckFormSubmit($f, "add") && GetFormData($f, $s, "dm_new_callerid") == ""){
				error("You cannot add a route with an empty caller id");
			} else {
				$routechange = false;
				foreach($calleridroutes as $calleridroute){
					if(CheckFormSubmit($f, "deleteall")){
						$calleridroute->destroy();
						$calleridroutechange=true;
						continue;
					}
					if(CheckFormSubmit($f, "delete_dm_" . $calleridroute->id)){
						$calleridroute->destroy();
						$routechange=true;
						continue;
					}
					if($calleridroute->id != "new"
						&&
						($calleridroute->callerid != GetFormData($f, $s, "dm_" . $calleridroute->id ."_callerid")
						|| $calleridroute->prefix != GetFormData($f, $s, "dm_" . $calleridroute->id ."_prefix")
						)
					){
						$routechange = true;
					}
					$calleridroute->dmid = $dmid;
					$calleridroute->callerid = GetFormData($f, $s, "dm_" . $calleridroute->id ."_callerid");
					$calleridroute->prefix = GetFormData($f, $s, "dm_" . $calleridroute->id ."_prefix");
					if($calleridroute->id == "new" && CheckFormSubmit($f, "add")){
						$routechange = true;
						$calleridroute->create();
					} else if($calleridroute->id != "new"){
						$calleridroute->update();
					}
				}

				if(CheckFormSubmit($f, "deleteall")){
					$defaultcalleridroute->destroy();
				} else {
					if($defaultcalleridroute->prefix != GetFormData($f, $s, "default_prefix")){
						$routechange = true;
					}

					$defaultcalleridroute->dmid = $dmid;
					$defaultcalleridroute->callerid = "";
					$defaultcalleridroute->prefix = GetFormData($f, $s, "default_prefix");
					$defaultcalleridroute->update();
				}
				if($routechange){
					QuickUpdate("update custdm set routechange=1 where dmid = " . $dmid);
				}

				if(CheckFormSubmit($f,$s))
					redirect("dms.php");
				else if(CheckFormSubmit($f, "upload"))
					redirect("uploadroutes.php?dmid=" . $dmid);
				redirect();
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	foreach($calleridroutes as $calleridroute){
		PutFormData($f, $s, "dm_" . $calleridroute->id ."_callerid", $calleridroute->callerid, "number");
		PutFormData($f, $s, "dm_" . $calleridroute->id ."_prefix", $calleridroute->prefix, "number");
	}
	PutFormData($f, $s, "default_prefix", $defaultcalleridroute->prefix, "number");
}


$PAGE="admin:settings";
$TITLE="Telco Settings";
include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, $s, "Done"), submit($f, "upload", "Upload Routes"), button("Delete All", "if(confirm('Are you sure you want to delete ALL caller id routes?')) submitForm('" . $f . "', 'deleteall')"));

startWindow("Caller ID Route Plans");
?>
<table cellpadding="3" cellspacing="1" class="list" width="100%">
	<tr class="listHeader">
		<th align="left">Caller ID</th>
		<th align="left">Prefix</th>
		<th align="left">Actions</th>

	</tr>
<?
		$alt = 0;
		foreach($calleridroutes as $calleridroute){
			if($calleridroute->id == "new") continue;
			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
				<td><? NewFormItem($f, $s, "dm_" . $calleridroute->id ."_callerid", "text", 14, 20, "id='dm_" . $calleridroute->id . "_callerid' "); ?></td>
				<td><? NewFormItem($f, $s, "dm_" . $calleridroute->id ."_prefix", "text", 10, 20); ?></td>
				<td><?=button("Delete", "if(confirmDelete()) submitForm('" . $f . "', 'delete_dm_" . $calleridroute->id. "')");?></td>
			</tr>
<?
		}
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
			<td>Default</td>
			<td><? NewFormItem($f, $s, "default_prefix", "text", 10, 20); ?></td>
			<td></td>
		</tr>
<?
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
			<td><? NewFormItem($f, $s, "dm_" . $calleridroute->id ."_callerid", "text", 14, 20, "id='dm_" . $calleridroute->id . "_callerid' "); ?></td>
			<td><? NewFormItem($f, $s, "dm_" . $calleridroute->id ."_prefix", "text", 10, 20); ?></td>
			<td><?=submit($f, "add", "Add"); ?></td>
		</tr>
	</table>
<?
endWindow();
buttons();
EndForm();
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > Please remember to reset the DM if you've made any changes
</div>
<?
include_once("navbottom.inc.php");
?>