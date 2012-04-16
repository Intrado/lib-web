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
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$SETTINGS['servermanagement']['manageservers'] || !$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id'])) {
	$_SESSION['servicejmxrequest'] = array();
	$_SESSION['servicejmxrequest']['serviceid'] = $_GET['id'] + 0;
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////
class JmxRequestFormItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$url = $this->args['url'];
		$str = '
		<style type="text/css">
			pre {outline: 1px solid #ccc; padding: 5px; margin: 5px; }
			.string { color: green; }
			.number { color: darkorange; }
			.boolean { color: blue; }
			.null { color: magenta; }
			.key { color: red; }
		</style>
		<div>
			<div style="font-weight:bold;float:left;">'._L("MBean").'</div>
			<div style="float:left;">&nbsp;&nbsp;&nbsp;'._L("example:").'</div>
			<div style="float:left">&nbsp;commsuite:type=config,name=properties</div>
			<div style="clear:both;"></div>
			<input type=text id="'.$n.'-mbean"></input>
			<div style="font-weight:bold;">'._L("Operation").'</div>
			<select id="'.$n.'-menu" onchange="jmxRequestTypeMenuChange(this, \''.$n.'-options\')">
				<option value="0">'._L("-- Select one --").'</option>
				<option value="read">'._L("Read").'</option>
				<option value="exec">'._L("Execute").'</option>
			</select>
			<div id="'.$n.'-options" style="display:none;">
				<div class="jmxRequestOption" id="'.$n.'-options-read" style="display:none;">
					<div style="font-weight:bold;float:left;">'._L("Attribute").'</div>
					<div style="float:left;">&nbsp;&nbsp;('._L("optional").')</div>
					<div style="clear:both;"></div>
					<input type=text id="'.$n.'-options-read-attribute"></input>
				</div>
				<div class="jmxRequestOption" id="'.$n.'-options-exec" style="display:none;">
					<div style="font-weight:bold;">'._L("Operation").'</div>
					<input type=text id="'.$n.'-options-exec-operation"></input>
					<div style="clear:both;"></div>
					
					<div style="font-weight:bold;float:left;">'._L("Argument(s)").'</div>
					<div style="float:left;">&nbsp;&nbsp;('._L("optional, comma seperated list").')</div>
					<div style="clear:both;"></div>
					<input type=text id="'.$n.'-options-exec-arguments"></input>
				</div>
				'.icon_button("Submit", "cog_go", "jmxRequestSubmit('".$url."', '".$n."-mbean', '".$n."-menu', '".$n."-options', '".$n."-result')").'
			</div>
			<div id="'.$n.'-result" style="display:none;">
				<div style="font-weight:bold;">'._L("Results").'</div>
				<div id="'.$n.'-result-data">Doing request...</div>
			</div>
		</div>
		';
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$str = '
		<script type="text/javascript">
			function jmxRequestTypeMenuChange(menu, optionsdiv) {
				var menu = $(menu);
				optionsdiv = $(optionsdiv);
				if (menu.value != 0) {
						optionsdiv.show();
					$$("#" + optionsdiv.id + " div.jmxRequestOption").each(function (thisdiv) {
						thisdiv.hide();
					});
					$(optionsdiv.id + "-" + menu.value).show();
				} else {
					optionsdiv.hide();
				}
			}
			
			function jmxRequestSubmit(url, mbean, menu, options, result) {
				result = $(result);
				$(result.id + "-data").update("Doing request...");
				// do ajax request
				new Ajax.Request("ajax.php", {
					method:"post",
					parameters: {
						"type": "jmxrequest",
						"url": url,
						"mbean": $(mbean).value,
						"jmxtype": $(menu).value,
						"attrib": $(options + "-read-attribute").value,
						"op": $(options + "-exec-operation").value,
						"args": $(options + "-exec-arguments").value
					},
					onSuccess: function(response) {
						result.show();
						$(result.id + "-data").update();
						if (response.responseJSON.error) {
							$(result.id + "-data").insert(
								new Element("pre").update(syntaxHighlight(response.responseJSON.value)));
						}
						$(result.id + "-data").insert(
							new Element("pre").update(syntaxHighlight(response.responseJSON.value)));
					},
					onFailure: function() {
						result.show();
						$(result.id + "-data").update("Ajax request failed");
					}
				});
			}
			
			function syntaxHighlight(json) {
				if (typeof json != "string") {
					json = JSON.stringify(json, undefined, 4);
				}
				json = json.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
				return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
					var cls = "number";
					if (/^"/.test(match)) {
						if (/:$/.test(match)) {
							cls = "key";
						} else {
							cls = "string";
						}
					} else if (/true|false/.test(match)) {
						cls = "boolean";
					} else if (/null/.test(match)) {
						cls = "null";
					}
					return "<span class=\"" + cls + "\">" + match + "</span>";
				});
			}
					
		</script>';
		return $str;
	}
	
}

////////////////////////////////////////////////////////////////////////////////
// Form 
////////////////////////////////////////////////////////////////////////////////
if (isset($_SESSION['servicejmxrequest']['serviceid'])) {
	$serviceid = $_SESSION['servicejmxrequest']['serviceid'];
} else {
	$serviceid = false;
}
$service = new Service($serviceid);

if (!$service->getAttribute("jettyport"))
	exit("Service doesn't support jmx requests");

$server = new Server($service->serverid);
if (!$server->hostname)
	exit("Missing/Invalid server id!");

$url = "http://".$server->hostname.":".$service->getAttribute("jettyport");

$formdata = array(_L('Host: %1$s, Service: %2$s, Mode: %3$s', $server->hostname, $service->type, $service->runmode));
$formdata["jmxrequest"] = array( 
		"label" => _L('Request Parameters'),
		"value" => "",
		"validators" => array(),
		"control" => array("JmxRequestFormItem", "url" => $url),
		"helpstep" => 1
	);

$helpsteps = array ();
$buttons = array(icon_button(_L('Cancel'),"cross",null,"servicelist.php?serverid=". $server->id));
$form = new Form("servicejmxrequest",$formdata,$helpsteps,$buttons);
$form->handleRequest();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "service:jmxrequest";
$TITLE = _L('JMX Request');

include_once("nav.inc.php");

startWindow(_L("Send JMX Request"));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>