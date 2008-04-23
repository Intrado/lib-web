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

$routes = DBFindMany("DMRoute", "from dmroute where dmid = " . $dmid . " and `match` != '' order by length(`match`) desc, `match` ASC");
$defaultroute = DBFind("DMRoute", "from dmroute where dmid = " . $dmid . " and `match` = ''");
if(!$defaultroute)
	$defaultroute = new DMRoute();
$newroute = new DMRoute();
$newroute->id = "new";
$routes[] = $newroute;

$f = "template";
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

		//do check

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			$matches = array();
			$duplicatematches = array();
			$default = false;
			$duplicatedefaults = false;
			foreach($routes as $route){
				if(!isset($matches[GetFormData($f, $s, "dm_" . $route->id ."_match")])){
					$matches[GetFormData($f, $s, "dm_" . $route->id ."_match")] = 1;
				} else {
					$duplicatematches[GetFormData($f, $s, "dm_" . $route->id ."_match")] = true;
				}
			}

			if(count($duplicatematches)){
				error("You have multiple routes with the same match string", array_keys($duplicatematches));
			} else if(CheckFormSubmit($f, "add") && GetFormData($f, $s, "dm_new_match") == ""){
				error("You cannot add a route with an empty match string");
			} else {
				$routechange = false;
				foreach($routes as $route){
					if(CheckFormSubmit($f, "deleteall")){
						$route->destroy();
						$routechange=true;
						continue;
					}
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
					}
					$route->dmid = $dmid;
					$route->match = GetFormData($f, $s, "dm_" . $route->id ."_match");
					$route->strip = GetFormData($f, $s, "dm_" . $route->id ."_strip");
					$route->prefix = GetFormData($f, $s, "dm_" . $route->id ."_prefix");
					$route->suffix = GetFormData($f, $s, "dm_" . $route->id ."_suffix");
					if($route->id == "new" && CheckFormSubmit($f, "add")){
						$routechange = true;
						$route->create();
					} else {
						$route->update();
					}
				}

				if(CheckFormSubmit($f, "deleteall")){
					$defaultroute->destroy();
				} else {
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

function dm_match($obj, $name){
	global $f, $s;
	NewFormItem($f, $s, "dm_" . $obj->id ."_match", "text", 10, 20, "id='dm_" . $obj->id . "_match' ");
	if($obj->match == '' && $obj->id == 'new'){
		echo "Default:";
		NewFormItem($f, $s, "dm_" . $obj->id ."_default", "checkbox", null, null, "id='dm_" . $obj->id . "_default' onclick='new getObj(\"dm_" . $obj->id . "_match\").obj.disabled = this.checked' ");
	}
}
function dm_strip($obj, $name){
	global $f, $s;
	NewFormItem($f, $s, "dm_" . $obj->id ."_strip", "text", 2);
}
function dm_prefix($obj, $name){
	global $f, $s;
	NewFormItem($f, $s, "dm_" . $obj->id ."_prefix", "text", 10, 20);
}
function dm_suffix($obj, $name){
	global $f, $s;
	NewFormItem($f, $s, "dm_" . $obj->id ."_suffix", "text", 10,20);
}
function dm_add($obj, $name){
	global $f, $s;
	if($obj->id == "new"){
		$url = submit($f, $s, "Add");
	} else {
		$url = submit($f, "delete_dm_" .$obj->id, "Delete");
	}
	return $url;
}


$PAGE="admin:settings";
$TITLE="Telco Settings";
include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, $s, "Done"), submit($f, "upload", "Upload Routes"), button("Delete All", "if(confirm('Are you sure you want to delete ALL routes?')) submitForm('" . $f . "', 'deleteall')"));

startWindow("Route Plans");
?>
<table cellpadding="3" cellspacing="1" class="list" width="100%">
	<tr class="listHeader">
		<th align="left">Match</th>
		<th align="left">Strip</th>
		<th align="left">Prefix</th>
		<th align="left">Suffix</th>
		<th align="left">Actions</th>
	</tr>
<?
		$alt = 0;
		foreach($routes as $route){
			if($route->id == "new") continue;
			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
				<td><? NewFormItem($f, $s, "dm_" . $route->id ."_match", "text", 10, 20, "id='dm_" . $route->id . "_match' "); ?></td>
				<td><? NewFormItem($f, $s, "dm_" . $route->id ."_strip", "text", 2); ?></td>
				<td><? NewFormItem($f, $s, "dm_" . $route->id ."_prefix", "text", 10, 20); ?></td>
				<td><? NewFormItem($f, $s, "dm_" . $route->id ."_suffix", "text", 10, 20); ?></td>
				<td><?=button("Delete", "if(confirmDelete()) submitForm('" . $f . "', 'delete_dm_" . $route->id. "')");?></td>

			</tr>
<?
		}
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
			<td>Default</td>
			<td><? NewFormItem($f, $s, "default_strip", "text", 2); ?></td>
			<td><? NewFormItem($f, $s, "default_prefix", "text", 10, 20); ?></td>
			<td><? NewFormItem($f, $s, "default_suffix", "text", 10, 20); ?></td>
			<td></td>
		</tr>
<?
		echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
			<td><? NewFormItem($f, $s, "dm_" . $route->id ."_match", "text", 10, 20, "id='dm_" . $route->id . "_match' "); ?></td>
			<td><? NewFormItem($f, $s, "dm_" . $route->id ."_strip", "text", 2); ?></td>
			<td><? NewFormItem($f, $s, "dm_" . $route->id ."_prefix", "text", 10, 20); ?></td>
			<td><? NewFormItem($f, $s, "dm_" . $route->id ."_suffix", "text", 10, 20); ?></td>
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
<script>
	new getObj('dm_new_match').obj.disabled = new getObj('dm_new_default').obj.checked;
</script>