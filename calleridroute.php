<?
include_once("inc/common.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("obj/Phone.obj.php");
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

$pagestart=0;
$max = 500;
if(isset($_GET['pagestart'])){
	$pagestart = $_GET['pagestart'] +0;
}
$limit = " limit " . $pagestart . ", " . $max . " ";

$dmname = QuickQuery("select name from custdm where dmid = " . $dmid);
$calleridroutes = DBFindMany("DMCallerIDRoute", "from dmcalleridroute where dmid = " . $dmid . " and callerid != '' order by callerid ASC " . $limit);
$calleridroutes = resequence($calleridroutes, "callerid");
$calleridlist = QuickQueryList("select callerid from dmcalleridroute where dmid = " . $dmid . " and callerid != ''");
$defaultcalleridroute = DBFind("DMCallerIDRoute", "from dmcalleridroute where dmid = " . $dmid . " and `callerid` = ''");
if(!$defaultcalleridroute)
	$defaultcalleridroute = new DMCallerIDRoute();
$newcalleridroute = new DMCallerIDRoute();
$newcalleridroute->id = "new";
$calleridroutes[] = $newcalleridroute;

function phoneformattercallback($phone){
	return Phone::format($phone);
}

$f = "calleridroutes";
$s = "main";
$reloadform = 0;

$checkformdelete = false;

foreach($calleridroutes as $calleridroute){
	if(CheckFormSubmit($f, "delete_dm_" . $calleridroute->id)){
		$checkformdelete = true;
	}
}

if(CheckFormSubmit($f,$s) || $checkformdelete || CheckFormSubmit($f, "add") || CheckFormSubmit($f, "deleteall")|| CheckFormSubmit($f, "upload"))
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

			if(CheckFormSubmit($f, "deleteall")){
				QuickUpdate("delete from dmcalleridroute where dmid = " . $dmid);
				QuickUpdate("update custdm set routechange=1 where dmid = " . $dmid);
				redirect();
			}

			$matches = array();
			$duplicatematches = array();
			$default = false;
			$duplicatedefaults = false;
			$modcalleridlist = array_flip(array_diff($calleridlist, array_keys($calleridroutes)));
			foreach($calleridroutes as $calleridroute){
				$callerid = Phone::parse(GetFormData($f, $s, "dm_" . $calleridroute->id ."_callerid"));
				if(isset($modcalleridlist[$callerid])){
					$duplicatematches[$callerid] = true;
				} else if(!isset($matches[$callerid])){
					$matches[$callerid] = 1;
				} else {
					$duplicatematches[$callerid] = true;
				}
			}

			if(count($duplicatematches)){
				$duplicatematches = array_map("phoneformattercallback", array_keys($duplicatematches));
				error("You have multiple caller id routes with the same caller id",$duplicatematches);
			} else if(CheckFormSubmit($f, "add") && GetFormData($f, $s, "dm_new_callerid") == ""){
				error("You cannot add a route with an empty caller id");
			} else {
				$routechange = false;
				$callerid = "";
				foreach($calleridroutes as $calleridroute){
					$updateroute = false;
					$callerid = Phone::parse(GetFormData($f, $s, "dm_" . $calleridroute->id ."_callerid"));
					if(CheckFormSubmit($f, "delete_dm_" . $calleridroute->id)){
						$calleridroute->destroy();
						$routechange=true;
						continue;
					}
					if($calleridroute->id != "new"
						&&
						($calleridroute->callerid != $callerid
						|| $calleridroute->prefix != GetFormData($f, $s, "dm_" . $calleridroute->id ."_prefix")
						)
					){
						$routechange = true;
						$updateroute = true;
					}
					$calleridroute->dmid = $dmid;
					$calleridroute->callerid = $callerid;
					$calleridroute->prefix = GetFormData($f, $s, "dm_" . $calleridroute->id ."_prefix");
					if($calleridroute->id == "new" && CheckFormSubmit($f, "add")){
						$routechange = true;
						$calleridroute->create();
					} else if($updateroute){
						$calleridroute->update();
					}
				}


				if($defaultcalleridroute->prefix != GetFormData($f, $s, "default_prefix")){
					$routechange = true;
				}

				$defaultcalleridroute->dmid = $dmid;
				$defaultcalleridroute->callerid = "";
				$defaultcalleridroute->prefix = GetFormData($f, $s, "default_prefix");
				$defaultcalleridroute->update();

				if($routechange){
					QuickUpdate("update custdm set routechange=1 where dmid = " . $dmid);
				}

				if(CheckFormSubmit($f,$s))
					redirect("dms.php");
				else if(CheckFormSubmit($f, "upload"))
					redirect("uploadcallerid.php?dmid=" . $dmid);
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
		PutFormData($f, $s, "dm_" . $calleridroute->id ."_callerid", Phone::format($calleridroute->callerid), "phone", 10, 10);
		PutFormData($f, $s, "dm_" . $calleridroute->id ."_prefix", $calleridroute->prefix, "number");
	}
	PutFormData($f, $s, "default_prefix", $defaultcalleridroute->prefix, "number");
}


$PAGE="admin:settings";
$TITLE="Caller ID Routes Manager: $dmname";
$DESCRIPTION="Jtapi Caller ID";
include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, $s, "Done"), submit($f, "upload", "Upload Caller ID Routes"), button("Delete All", "if(confirm('Are you sure you want to delete ALL caller id routes?')) submitForm('" . $f . "', 'deleteall')"));

startWindow("Default Caller ID Route Plan" . help("Settings_DefaultCallerIDRoute"));
?>
<table cellpadding="3" cellspacing="1" class="list" width="100%">
	<tr class="listHeader">
		<th align="left">Caller ID</th>
		<th align="left">Prefix</th>
	</tr>
	<tr>
		<td>Default</td>
		<td><? NewFormItem($f, $s, "default_prefix", "text", 10, 20); ?></td>
	</tr>
</table>
<?
endWindow();


startWindow("Custom Caller ID Route Plans" . help("Settings_CallerIDRoutes"));
?>
<table cellpadding="3" cellspacing="1" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">New Custom Caller ID Route:</th>
		<td class="bottomBorder">
			<table width="50%">
				<tr><td>Caller ID</td><td><? NewFormItem($f, $s, "dm_new_callerid", "text", 14, 20, "id='dm_new_callerid' "); ?></td></tr>
				<tr><td>Prefix</td><td><? NewFormItem($f, $s, "dm_new_prefix", "text", 10, 20); ?></td></tr>
				<tr><td>&nbsp;</td><td><?=submit($f, "add", "Add"); ?></td></tr>
			</table>
		</td>
	</tr>
</table>
<?
showPageMenu(count($calleridlist),$pagestart,$max);
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
?>
	</table>
<?
showPageMenu(count($calleridlist),$pagestart,$max);
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