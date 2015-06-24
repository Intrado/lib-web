<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");

require_once("ps/sso-admin/manager-tools/DownloadPlugin.php");

if (!$MANAGERUSER->authorized("powerschool"))
	exit("Not Authorized");

if (!isset($_GET['cid']))
	exit("Missing customer id");


$currentid = $_GET['cid'] + 0;
$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");

$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
if (!$custdb) {
	exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
}
$customerName = $custinfo[3];


$pluginVersion = $_POST["downloadplugin_type"];
$downloadPlugin = new DownloadPlugin($pluginVersion);

// handle post request via submit button
if($_POST["downloadplugin_type"]) {
    
    $pluginCompileParams = array(
        "customerUrlPrefix"  => $SETTINGS["feature"]["customer_url_prefix"],
        "customerUrl"        => $customerName,
        "portalAuthUrl"      => $SETTINGS["feature"]["portalauth_url"],
        "portalAuthPort"     => $SETTINGS["feature"]["portalauth_port"]
    );
    
    $pluginXML = $downloadPlugin->compilePlugin($pluginCompileParams);
    
    $zipFile = $downloadPlugin->zipPlugin($pluginXML);

    //prepare the proper content type
    // http headers for zip downloads
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"powerschool-plugin-{$customerName}.zip\"");
    header("Content-Transfer-Encoding: binary");
    
    ob_clean();
    flush();
    
    readfile($zipFile);
    unlink($zipFile); 
}

// get list of available plugin versions
$pluginVersions = DownloadPlugin::getVersions();

// get controls for the $formdata array
$formControlValues = DownloadPlugin::getFormArray($pluginVersions);

$formdata = array(
	"type" => array(
		"label" => _L('Plugin Version'),
		"value" => $pluginVersions[0],
		"validators" => array(),
            "control" => array("SelectMenu","values" => $formControlValues),
		"helpstep" => 1
	)
);

$buttons = array(submit_button(_L('Download'),"submit","tick"));

$form = new Form("downloadplugin",$formdata,array(),$buttons);
$form->ajaxsubmit = false;

// display page

$TITLE = 'PowerSchool Tools';
$PAGE = 'commsuite:customers';

include_once("nav.inc.php");

startWindow(_L('PowerSchool: ' . $customerName));

?>

<h1>PowerSchool Integration Plugin</h1>
<hr />
<?
	echo $form->render();
?>
<br />

<?
endWindow();

include_once("navbottom.inc.php");
?>
