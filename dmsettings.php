<?
include_once("inc/common.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("obj/DMRoute.obj.php");

if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}
if(isset($_GET['dmid'])){
	$_SESSION['dmid'] = $_GET['dmid'] +0;
	redirect();
} else {
	$dmid = $_SESSION['dmid'];
}

$limit = "";
$max = 500;
$pagestart = 0;
if(isset($_GET['pagestart'])){
	$pagestart = $_GET['pagestart']+0;
}
$limit = " limit " . $pagestart . ", $max ";

$dmname = QuickQuery("select name from custdm where dmid = " . $dmid);
$routes = DBFindMany("DMRoute", "from dmroute where dmid = " . $dmid . " and `match` != '' order by length(`match`) desc, `match` ASC $limit");
$routes = resequence($routes, "match");
$matchlist = QuickQueryList("select `match` from dmroute where dmid = " . $dmid . " and `match` != ''");
$defaultroute = DBFind("DMRoute", "from dmroute where dmid = " . $dmid . " and `match` = ''");
if(!$defaultroute)
	$defaultroute = new DMRoute();
$newroute = new DMRoute();
$newroute->id = "new";
$routes[] = $newroute;

$f = "dmsettings";
$s = "main";
$reloadform = 0;


$checkformdelete = false;

foreach($routes as $route){
	if(CheckFormSubmit($f, "delete_dm_" . $route->id)){
		$checkformdelete = true;
	}
}

if(CheckFormSubmit($f,$s) || $checkformdelete || CheckFormSubmit($f, "add") || CheckFormSubmit($f, "upload") || CheckFormSubmit($f, "deleteall"))
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

		TrimFormData($f, $s, "default_strip");
		TrimFormData($f, $s, "default_prefix");
		TrimFormData($f, $s, "default_suffix");
		foreach($routes as $route){
			TrimFormData($f, $s, "dm_" . $route->id ."_match");
			TrimFormData($f, $s, "dm_" . $route->id ."_strip");
			TrimFormData($f, $s, "dm_" . $route->id ."_prefix");
			TrimFormData($f, $s, "dm_" . $route->id ."_suffix");
		}

		//do check

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			if(CheckFormSubmit($f, "deleteall")){
				QuickUpdate("delete from dmroute where dmid = " . $dmid);
				QuickUpdate("update custdm set routechange=1 where dmid = " . $dmid);
				redirect();
			}

			$matches = array();
			$duplicatematches = array();
			$default = false;
			$duplicatedefaults = false;
			$modmatchlist =  array_flip(array_diff($matchlist, array_keys($routes)));
			foreach($routes as $route){
				$routematch = GetFormData($f, $s, "dm_" . $route->id ."_match");
				if(isset($modmatchlist[$routematch])){
					$duplicatematches[$routematch] = true;
				} else if(!isset($matches[$routematch])){
					$matches[$routematch] = 1;
				} else {
					$duplicatematches[$routematch] = true;
				}
			}

			if(count($duplicatematches)){
				error("You have multiple routes with the same match string", array_keys($duplicatematches));
			} else if(CheckFormSubmit($f, "add") && GetFormData($f, $s, "dm_new_match") == ""){
				error("You cannot add a route with an empty match string");
			} else {
				$routechange = false;
				foreach($routes as $route){
					$updateroute = false;
					if(CheckFormSubmit($f, "delete_dm_" . $route->id)){
						$route->destroy();
						$routechange=true;
						continue;
					}
					if($route->id != "new"
						&&
						($route->match != GetFormData($f, $s, "dm_" . $route->id ."_match")
						|| $route->strip != GetFormData($f, $s, "dm_" . $route->id ."_strip")
						|| $route->prefix != GetFormData($f, $s, "dm_" . $route->id ."_prefix")
						|| $route->suffix != GetFormData($f, $s, "dm_" . $route->id ."_suffix")
						)
					){
						$routechange = true;
						$updateroute = true;
					}
					$route->dmid = $dmid;
					$route->match = GetFormData($f, $s, "dm_" . $route->id ."_match");
					$route->strip = GetFormData($f, $s, "dm_" . $route->id ."_strip");
					$route->prefix = GetFormData($f, $s, "dm_" . $route->id ."_prefix");
					$route->suffix = GetFormData($f, $s, "dm_" . $route->id ."_suffix");
					if($route->id == "new" && CheckFormSubmit($f, "add")){
						$routechange = true;
						$route->create();
					} else if($updateroute) {
						$route->update();
					}
				}

				if($defaultroute->strip != GetFormData($f, $s, "default_strip")
					|| $defaultroute->prefix != GetFormData($f, $s, "default_prefix")
					|| $defaultroute->suffix != GetFormData($f, $s, "default_suffix")
					){
						$routechange = true;
				}

				$defaultroute->dmid = $dmid;
				$defaultroute->match = "";
				$defaultroute->strip = GetFormData($f, $s, "default_strip");
				$defaultroute->prefix = GetFormData($f, $s, "default_prefix");
				$defaultroute->suffix = GetFormData($f, $s, "default_suffix");
				$defaultroute->update();
				if($routechange){
					QuickUpdate("update custdm set routechange=1 where dmid = " . $dmid);
				}

				if(CheckFormSubmit($f,$s))
					redirect("dms.php");
				else if(CheckFormSubmit($f, "upload"))
					redirect("uploadroutes.php?dmid=" . $dmid);
				redirect("?pagestart=" . $pagestart);
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	foreach($routes as $route){
		PutFormData($f, $s, "dm_" . $route->id ."_match", $route->match, "number");
		PutFormData($f, $s, "dm_" . $route->id ."_strip", $route->strip, "number", 0, 99);
		PutFormData($f, $s, "dm_" . $route->id ."_prefix", $route->prefix, "number");
		PutFormData($f, $s, "dm_" . $route->id ."_suffix", $route->suffix, "number");
	}
	PutFormData($f, $s, "default_strip", $defaultroute->strip, "number", 0, 99);
	PutFormData($f, $s, "default_prefix", $defaultroute->prefix, "number");
	PutFormData($f, $s, "default_suffix", $defaultroute->suffix, "number");
}

$PAGE="admin:settings";
$TITLE="Route Plan Manager: ".escapehtml($dmname);
include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, $s, "Done"), submit($f, "upload", "Upload Routes"), button("Delete All", "if(confirm('Are you sure you want to delete ALL routes?')) submitForm('" . $f . "', 'deleteall')"));
startWindow("Default Route Plan" . help("Settings_DefaultRoutePlan"));
?>
<table cellpadding="3" cellspacing="1" class="list" width="100%">
	<tr class="listHeader">
			<th align="left"></th>
			<th align="left">Strip</th>
			<th align="left">Prefix</th>
			<th align="left">Suffix</th>
	</tr>
	<tr>
		<td>Default</td>
		<td><? NewFormItem($f, $s, "default_strip", "text", 2); ?></td>
		<td><? NewFormItem($f, $s, "default_prefix", "text", 10, 20); ?></td>
		<td><? NewFormItem($f, $s, "default_suffix", "text", 10, 20); ?></td>
	</tr>
</table>
<?
endWindow();
?>
<br>
<?
startWindow("Custom Route Plans" . help("Settings_RoutePlans"));

?>
<table cellpadding="3" cellspacing="1" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">New Custom Route Plan:</th>
		<td class="bottomBorder">
			<table width="50%">
				<tr><td width="30%">Match</td><td><? NewFormItem($f, $s, "dm_" . $route->id ."_match", "text", 10, 20, "id='dm_" . $route->id . "_match' "); ?></td></tr>
				<tr><td>Strip</td><td><? NewFormItem($f, $s, "dm_" . $route->id ."_strip", "text", 2); ?></td></tr>
				<tr><td>Prefix</td><td><? NewFormItem($f, $s, "dm_" . $route->id ."_prefix", "text", 10, 20); ?></td></tr>
				<tr><td>Suffix</td><td><? NewFormItem($f, $s, "dm_" . $route->id ."_suffix", "text", 10, 20); ?></td></tr>
				<tr><td>&nbsp;</td><td><?=submit($f, "add", "Add"); ?></td></tr>
			</table>
		</td>
	</tr>
</table>
<?
showPageMenu(count($matchlist),$pagestart,$max);
?>
<table cellpadding="3" cellspacing="1" class="list" width="100%">
	<tr class="listHeader">
		<th align="left">#</th>
		<th align="left">Match</th>
		<th align="left">Strip</th>
		<th align="left">Prefix</th>
		<th align="left">Suffix</th>
		<th align="left">Actions</th>
	</tr>
<?
		$alt = 0;
		$count = $pagestart;
		foreach($routes as $route){
			if($route->id == "new") continue;
			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
			$count++;
?>
				<td><?=$count?></td>
				<td><? NewFormItem($f, $s, "dm_" . $route->id ."_match", "text", 10, 20, "id='dm_" . $route->id . "_match' "); ?></td>
				<td><? NewFormItem($f, $s, "dm_" . $route->id ."_strip", "text", 2); ?></td>
				<td><? NewFormItem($f, $s, "dm_" . $route->id ."_prefix", "text", 10, 20); ?></td>
				<td><? NewFormItem($f, $s, "dm_" . $route->id ."_suffix", "text", 10, 20); ?></td>
				<td><?=button("Delete", "if(confirmDelete()) submitForm('" . $f . "', 'delete_dm_" . $route->id. "')");?></td>
			</tr>
<?
		}
?>
	</table>
<?
showPageMenu(count($matchlist),$pagestart,$max);
endWindow();
buttons();
EndForm();
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > Please reset the SmartCall Appliance after you save any changes.
</div>
<?
include_once("navbottom.inc.php");
?>
