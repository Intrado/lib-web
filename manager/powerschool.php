<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");

require_once("ps/manager-tools/DownloadPlugin.php");

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

$downloadPlugin = new DownloadPlugin();

$forms = array();

foreach ($downloadPlugin->getPlugins() as $pluginName) {

	$formdata = $downloadPlugin->getPluginForm($pluginName);

	$buttons = array(submit_button(_L('Download'), "submit", "tick"));

	$forms[$pluginName] = new Form($pluginName, $formdata, array(), $buttons);

	$forms[$pluginName]->ajaxsubmit = false;
}

$forms = array_reverse($forms);

foreach ($forms as $form) {
	$form->handleRequest();

	if ($form->getSubmit()) {
		$errors = $form->validate();

		if (!$errors) {

			$data = $form->getData();
			$destinationType = $data["pluginDestination"];

			// setup link text based on plugin destination
			$linkText = "Contact Manager";
			if ($destinationType == 'infocenter') {
				$linkText = 'InfoCenter';
			}

			// this will comment out the link to contact manager if the none option is selected
			$includeStart = '';
			$includeEnd = '';
			if ($destinationType == '') {
				$includeStart = '<!--';
				$includeEnd = '-->';
			}

			// necessary parameters to create 'sso-admin' plugin
			$pluginCompileParams = array(
				"customerUrlPrefix" => $SETTINGS["feature"]["customer_url_prefix"],
				"customerUrl" => $customerName,
				"portalAuthUrl" => $SETTINGS["feature"]["portalauth_url"],
				"portalAuthPort" => $SETTINGS["feature"]["portalauth_port"],
				"linkText" => $linkText,
				"includeStart" => $includeStart,
				"includeEnd" => $includeEnd
			);

			// create a full lists of necessary parameters
			$finalParams = array_merge($pluginCompileParams, $form->getData());


			// set version and plugin name and any other predefined private values
			$downloadPlugin->setParameters($finalParams);

			// get list of plugin-specific filenames with need to be compiled
			$filenamesToCompile = $downloadPlugin->getFilenamesToCompile();

			// create multi-dimensional array with filepaths and the compiled data
			$fileDatas = $downloadPlugin->compilePlugin($finalParams, $filenamesToCompile);

			// create the ZIP archive
			$zipFile = $downloadPlugin->zipPlugin($fileDatas);

			// setup the filename depending on plugin type
			$pluginName = $finalParams['pluginName'];
			$fileName = $downloadPlugin->getFilename($customerName, $destinationType, $pluginName);
			// set headers for a ZIP file
			$downloadPlugin->setZipMimeHeaders($fileName);

			// read out the file
			readfile($zipFile);

			// delete it
			unlink($zipFile);

			// do not render form
			die;
		}
	}
}

$TITLE = 'PowerSchool Tools';
$PAGE = 'commsuite:customers';

include_once("nav.inc.php");

startWindow(_L('PowerSchool: ' . $customerName));
?>

<?
foreach ($forms as $frm) {
	$header = $downloadPlugin::getHeaderText($frm->name);

	echo "<h1>{$header}</h1><hr />";
	echo $frm->render();
	echo '<br />';
}
?>
<br />

<?
endWindow();

include_once("navbottom.inc.php");
?>
