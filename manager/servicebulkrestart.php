<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("Server.obj.php");
require_once("Service.obj.php");
require_once("JmxClient.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$SETTINGS['servermanagement']['manageservers'] || !$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$serverlist = QuickQueryList("select id, hostname from server", true);

// Form Items
$formdata = array();
$formdata["server"] = array( 
		"label" => _L('Server(s)'),
		"value" => "",
		"validators" => array(
			array("ValInArray", 'values' => array_keys($serverlist))),
		"control" => array("MultiCheckBox", 'values' => $serverlist),
		"helpstep" => 1
	);

$helpsteps = array ();

$buttons = array(submit_button(_L('Restart'),"submit","lightning"),
				icon_button(_L('Cancel'),"cross",null,"serverlist.php"));
$form = new Form("servicebulkrestartform",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		// for each server, build query args
		$args = array();
		$arglist = "";
		foreach ($postdata['server'] as $serverid) {
			$args[] = $serverid;
			if ($arglist != "")
				$arglist .= ",?";
			else
				$arglist = "?";
		}
		
		// get the active services
		$servicelist = DBFindMany("Service", "from service s 
			inner join server se on (se.id = s.serverid and (se.runmode = s.runmode or s.runmode = 'all'))
			where s.serverid in ($arglist) 
				and s.type = 'commsuite'",
			"s", $args);
		
		$_SESSION['servicebulkrestart'] = array();
		foreach ($servicelist as $service) {
			$server = new Server($service->serverid);
			
			$jettyport = $service->getAttribute("jettyport");
			$hostname = $server->hostname;
			$restartcmd = explode(" ", $service->getAttribute("jmxrestartcmd"));
				
			$jmxclient = new JmxClient("http://$hostname:$jettyport");
			
				$response = $jmxclient->exec(array_shift($restartcmd), array_shift($restartcmd), $restartcmd);
			
			$_SESSION['servicebulkrestart'][] = array(
					'hostname' => $hostname,
					'cmd' => $service->getAttribute("jmxrestartcmd"),
					'retval' => isset($response['error']),
					'output' => isset($response['error'])?$response['error']:$response['value']);
		}
		if ($ajax)
			$form->sendTo("serverlist.php");
		else
			redirect("serverlist.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "service:bulkrestart";
$TITLE = _L('Bulk Service Restart');

include_once("nav.inc.php");

startWindow(_L('Bulk Restart Services'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>