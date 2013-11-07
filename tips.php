<?
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/date.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Tips.obj.php");

//TODO: add authorize setting for tips
// if (!$USER->authorize('viewtips')) {
// 	redirect('unauthorized.php');
// }	

////////////////////////////////////////////////////////////////////////////////
// Initialize SESSION and handle request params;
////////////////////////////////////////////////////////////////////////////////

if (!isset($_SESSION['tips'])) {
	$_SESSION['tips'] = array();
}

$orgid = 		isset($_POST['tips_orgid']) ? $_POST['tips_orgid'] : 
				isset($_SESSION['tips']['orgid']) ? $_SESSION['tips']['orgid'] : null;
$categoryid = 	isset($_POST['tips_categoryid']) ? $_POST['tips_categoryid'] : 
				isset($_SESSION['tips']['categoryid']) ? $_SESSION['tips']['categoryid'] : null;
$date = 		isset($_POST['tips_date']) ? $_POST['tips_date'] : 
				isset($_SESSION['tips']['date']) ? $_SESSION['tips']['date'] : null;

$_SESSION['tips']['orgid'] = $orgid;
$_SESSION['tips']['categoryid'] = $categoryid;
$_SESSION['tips']['date'] = $date;

$options = $_SESSION['tips'];


////////////////////////////////////////////////////////////////////////////////
// Initialize new Tips object; 
// uses $options ($_SESSION['tips']) data to init form elements (value settings), 
// SQL query and table rendering
////////////////////////////////////////////////////////////////////////////////
$tips = new Tips();

$tips->setTitle('Tip Submissions');
$tips->setPagingStart($_GET['pagestart']);
$tips->setFormData($options);

$tips->setForm(new Form('tips', $tips->getFormData(), null, array( submit_button(_L(' Search Tips'), 'search', "find"))));

$tips->getForm()->ajaxsubmit = false;
$tips->getForm()->handleRequest();

// if user submits a search, update session with form data then reload self
if ($tips->getForm()->getSubmit()) {
	$options = $_SESSION['tips'] = $tips->getForm()->getData();
	redirect($_SERVER[PHP_SELF]);
}

$tips->renderNav();
$tips->setSearchQuery($options);
$tips->renderSearchResults($options);
$tips->renderFooter();
$tips->renderJavascript();
?>
