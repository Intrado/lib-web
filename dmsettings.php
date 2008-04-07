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

$routes = DBFindMany("DMRoute", "from dmroute where dmid = " . $dmid . " order by `match` desc");
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

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,"done") || $checkformdelete)
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
		if(CheckFormSubmit($f, $s)){
			SetRequired($f, $s, "dm_new_match", !GetFormData($f, $s, "dm_new_default"));
		} else {
			SetRequired($f, $s, "dm_new_match", false);
		}

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			$matches = array();
			$duplicatematches = array();
			$default = false;
			$duplicatedefaults = false;
			foreach($routes as $route){

				if(GetFormData($f, $s, "dm_" . $route->id ."_match") != ''){
					if(!isset($matches[GetFormData($f, $s, "dm_" . $route->id ."_match")])){
						$matches[GetFormData($f, $s, "dm_" . $route->id ."_match")] = 1;
					} else {
						$duplicatematches[GetFormData($f, $s, "dm_" . $route->id ."_match")] = true;
					}
				}
				if($route->id != 'new'){
					if(!$default && GetFormData($f, $s, "dm_" . $route->id ."_match") == ''){
						$default = true;
					} else if(GetFormData($f, $s, "dm_" . $route->id ."_match") == ''){
						$duplicatedefaults = true;
					}
				}
			}

			if(count($duplicatematches)){
				error("You have multiple routes with the same match string", array_keys($duplicatematches));
			} else if($default && GetFormData($f, $s, "dm_" . $route->id ."_default")){
				error("You cannot have multiple default routes");
			} else if($duplicatedefaults){
				error("You cannot have multiple default routes");
			} else {

				foreach($routes as $route){
					if(CheckFormSubmit($f, "delete_dm_" . $route->id)){
						$route->destroy();
						continue;
					}

					if($route->id=='new' && !GetFormData($f, $s, "dm_" . $route->id ."_default") && GetFormData($f, $s, "dm_" . $route->id ."_match") == ''){
						continue;
					}
					$route->dmid = $dmid;
					$route->match = GetFormData($f, $s, "dm_" . $route->id ."_match");
					$route->strip = GetFormData($f, $s, "dm_" . $route->id ."_strip");
					$route->prefix = GetFormData($f, $s, "dm_" . $route->id ."_prefix");
					$route->suffix = GetFormData($f, $s, "dm_" . $route->id ."_suffix");
					if($route->id == "new"){
						$route->create();
					}
					$route->update();
				}

				if(CheckFormSubmit($f,"done"))
					redirect("dms.php");
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
		if($route->id == 'new')
			PutFormData($f, $s, "dm_" . $route->id ."_default", 0, "bool", 0, 1);
		PutFormData($f, $s, "dm_" . $route->id ."_match", $route->match, "number");
		PutFormData($f, $s, "dm_" . $route->id ."_strip", $route->strip, "number", 0, 99);
		PutFormData($f, $s, "dm_" . $route->id ."_prefix", $route->prefix, "number");
		PutFormData($f, $s, "dm_" . $route->id ."_suffix", $route->suffix, "number");
	}
}

function dm_default($obj, $name){
	global $f, $s;
	if($obj->match == '' && $obj->id == 'new')
		NewFormItem($f, $s, "dm_" . $obj->id ."_default", "checkbox", null, null, "id='dm_" . $obj->id . "_default' onclick='new getObj(\"dm_" . $obj->id . "_match\").obj.disabled = this.checked' ");
}

function dm_match($obj, $name){
	global $f, $s;
	NewFormItem($f, $s, "dm_" . $obj->id ."_match", "text", 10, 20, "id='dm_" . $obj->id . "_match' ");
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

$titles = array("default" => "Default",
				"match" => "Match",
				"strip" => "Strip",
				"prefix" => "Prefix",
				"suffix" => "Suffix",
				"actions" => "Actions");

$formatters = array("default" => "dm_default",
					"match" => "dm_match",
					"strip" => "dm_strip",
					"prefix" => "dm_prefix",
					"suffix" => "dm_suffix",
					"actions" => "dm_add");

$PAGE="admin:settings";
$TITLE="Telco Settings";
include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, "done", "Done"));

startWindow("Route Plans");
	showObjects($routes, $titles, $formatters);
endWindow();

buttons();

EndForm();

include_once("navbottom.inc.php");
?>
<script>
	new getObj('dm_new_match').obj.disabled = new getObj('dm_new_default').obj.checked;
</script>