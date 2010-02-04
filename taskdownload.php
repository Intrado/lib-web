<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Import.obj.php");
require_once("obj/ImportField.obj.php");
require_once("obj/Schedule.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Person.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$id = $_GET['id'] + 0;
$import = new Import($id);

//see if this is a zip file using PK zip header magic numbers see http://www.garykessler.net/library/file_sigs.html
$data =  $import->download();
if (strlen($data) > 4 && $data[0] == "P" && $data[1] == "K" && $data[2] == "\x03" && $data[3] == "\x04") {
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=data.zip");
	header("Content-type: application/zip");
} else {
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=data.csv");
	header("Content-type: application/vnd.ms-excel");
}

echo $data;

?>