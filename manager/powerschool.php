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

$postValues = array();

foreach( $_POST as $postKey => $postValue) {
	if(strpos($postKey, 'download-') !== false) {
		$keyName = str_replace('download-', '', $postKey);
		
		// we can use this property to get the name of the plugin requested
		if(strpos($keyName, '-formsnum') !== false) {
			$keyName = str_replace('-formsnum', '', $keyName);
			
			$postValue = $keyName;
			$keyName = 'pluginName';
		}
	
		// if the key has an underscore then it is a specific property we need
		if(strpos($keyName, '_') !== false) {
			$exploded = explode('_', $keyName);
			$keyName = $exploded[1];
		}
		
		$postValues[$keyName] = $postValue;
	}
}

// handle post request via submit button
if (! empty($postValues)) {
	
	$pluginCompileParams = array(
		"customerUrlPrefix" => $SETTINGS["feature"]["customer_url_prefix"],
		"customerUrl"		=> $customerName,
		"portalAuthUrl"		=> $SETTINGS["feature"]["portalauth_url"],
		"portalAuthPort"	=> $SETTINGS["feature"]["portalauth_port"]
	);
	
	// create a full lists of necessary parameters
	$finalParams = array_merge($pluginCompileParams, $postValues);
	
	// set version and plugin name and any other predefined private values
	$downloadPlugin->setParameters($finalParams);
	
	$filenamesToCompile = $downloadPlugin->getFilenamesToCompile();
	
	// create multi-dimensional array with filepaths and the compiled data
	$fileDatas = $downloadPlugin->compilePlugin($finalParams, $filenamesToCompile);
	
	$zipFile = $downloadPlugin->zipPlugin($fileDatas);
	
	$downloadPlugin->setZipMimeHeaders($customerName);

	readfile($zipFile);
	unlink($zipFile);
}

$forms = array();

foreach ($downloadPlugin->getPlugins() as $pluginName) {
	
	$formdata = $downloadPlugin->getPluginForm($pluginName);
	
	$buttons = array(submit_button(_L('Download'),"submit","tick"));
	
	$forms[$pluginName] = new Form('download-'.$pluginName, $formdata, array(), $buttons);
	
	$forms[$pluginName]->ajaxsubmit = false; 
	
}

// display page

$TITLE = 'PowerSchool Tools';
$PAGE = 'commsuite:customers';

include_once("nav.inc.php");

startWindow(_L('PowerSchool: ' . $customerName));
?>

<?
foreach ($forms as $frm) {
	$header = str_replace('-', ' ', $frm->name);
	$header = ucwords($header);
	
	echo "<h1>{$header} Plugin</h1><hr />";
	echo $frm->render();
	echo '<br />'; 
}
?>
<br />

<?
endWindow();

include_once("navbottom.inc.php");
?>
