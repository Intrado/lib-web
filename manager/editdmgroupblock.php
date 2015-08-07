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
require_once("../obj/Phone.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");

if(isset($_GET['crmbid'])){
	if ($_GET['crmbid'] === "new") {
		$_SESSION['crmbid'] = null;
	} else {
		$crmbid = $_GET['crmbid'] + 0;
		$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
		if (!QuickQuery("select count(*) from carrierratemodelblock where id = ?", $lcrdbcon, array($crmbid))) {
			echo "Invalid DM Block.";
			exit();
		}
		$_SESSION['crmbid'] = $crmbid;
	}
	redirect();
} else {
	$crmbid = $_SESSION['crmbid'];
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Custom Form Items and Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
$crmbinfo = QuickQueryRow("select id,carrierRateModelClassname, pattern, notes from carrierratemodelblock where id=?", true,$lcrdbcon,array($crmbid));

$helpstepnum = 1;

$helpsteps[] = "TODO: Rare Model";
$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
$carrierratemodels = QuickQueryList("select distinct classname, classname from carrierratemodel order by classname",true,$lcrdbcon);
$formdata["carrierRateModelClassname"] = array(
	"label" => _L('Rate Model'),
	"value" => $crmbinfo["carrierRateModelClassname"],
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($carrierratemodels))
	),
	"control" => array("SelectMenu", "values" => array('' => "None") + $carrierratemodels),
	"helpstep" => $helpstepnum
);

$helpstepnum++;

$helpsteps[] = "TODO: Pattern";
$formdata["pattern"] = array(
	"label" => _L('Pattern'),
	"value" => $crmbinfo["pattern"],
	"validators" => array(
		array("ValRequired")
	),
	"control" => array("TextField","size" => 50, "maxlength" => 255),
	"helpstep" => $helpstepnum
);

$helpstepnum++;
$helpsteps[] = "TODO: Notes";
$formdata["notes"] = array(
	"label" => _L('Notes'),
	"value" => $crmbinfo['notes'],
	"validators" => array(),
	"control" => array("TextArea", "rows" => 3, "cols" => 40),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
	icon_button(_L('Cancel'),"cross",null,"dmgroupblock.php"));
$form = new Form("editdmgroupblock",$formdata,null,$buttons);


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
		$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
		Query("BEGIN",$lcrdbcon);

		$pattern =  $postdata["pattern"];

		if ($crmbid) {
			$originalDmGroup = QuickQueryRow("select * from carrierratemodelblock where id = " . $crmbid, true, $crmbinfo, false);
			QuickUpdate("update carrireratemodelblock set
								carrierRateModelClassname=?,
								pattern=?,
								notes=?
								where id=?", $lcrdbcon,
				array($postdata["carrireRateModelId"], $postdata["pattern"], $postdata["notes"], $crmbid));
		} else {
			QuickUpdate("insert into carrierratemodelblock (carrierRateModelClassname, pattern, notes) values (?,?,?)" , $lcrdbcon,
				array($postdata["carrierRateModelClassname"], $postdata["pattern"], $postdata["notes"]));
			$result = QuickQueryRow("select LAST_INSERT_ID() as id", true, $lcrdbcon);
			$crmbid =$result["id"];
		}

		Query("COMMIT",$lcrdbcon);
		if ($ajax)
			$dmType == $form->sendTo("dmgroupblock.php");
		else
			$dmType == redirect("dmgroupblock.php");
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('DM Block');
$PAGE = 'commsuite:dmblocking';

include_once("nav.inc.php");

startWindow(_L('DM Block'));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>
