<?

// Get the path to kona's base directory
$incdir = dirname(__FILE__);
$basedir = dirname($incdir);
$objdir = "{$basedir}/objects";

// In case the mechanism for checking if we're running under PHPUnit needs to change,
// we check it here and set our own global constant PHPUNIT that we can use everywhere
if (defined('PHPUnit_MAIN_METHOD')) define('PHPUNIT', true);

setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

$SETTINGS = parse_ini_file(dirname($basedir) . "/settings.ini",true);

//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
$BASEURL = "/$CUSTOMERURL";

require_once("{$incdir}/utils.inc.php");
require_once("{$incdir}/db.inc.php");
require_once("{$incdir}/memcache.inc.php");

// If PHPUNIT is NOT running
if (! defined('PHPUNIT')) {

	apache_note("CS_APP","cs"); //for logging
	apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

	// Start up the memcache interface
	init_memcache();
}
require_once("{$incdir}/DBMappedObject.php");
require_once("{$incdir}/DBMappedObjectHelpers.php");
require_once("{$incdir}/DBRelationMap.php");
require_once("XML/RPC.php");
require_once("{$incdir}/auth.inc.php");
require_once("{$incdir}/sessionhandler.inc.php");

require_once("{$objdir}/User.obj.php");
require_once("{$objdir}/Access.obj.php");
require_once("{$objdir}/Permission.obj.php");
require_once("{$objdir}/Rule.obj.php"); //for search and sec profile rules
require_once("{$objdir}/Organization.obj.php"); //for search and sec profile rules
require_once("{$objdir}/Section.obj.php"); //for search and sec profile rules

/**
 * Any PageObject derived class must invoke this function to executePage()
 * This ensures that the test for PHPUNIT environment only needs to exist
 * in this one place.
 */
function executePage($pageObject) {
	// If PHPUNIT is NOT running
	if (! defined('PHPUNIT')) {
		$pageObject.execute();
	}
}


if ((! defined('PHPUNIT')) && (!isset($isindexpage) || !$isindexpage)) {
	doStartSession();
	//force ssl?
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		if (isset($_REQUEST['api'])) {
			header("HTTP/1.1 403 Forbidden");
		}

		exit();
	}

	if (!isset($_SESSION['user']) || !isset($_SESSION['access'])) {
		if (isset($_REQUEST['api'])) {
			header("HTTP/1.1 401 Unauthorized");
		}

		exit();
	} else {
		$USER = &$_SESSION['user'];
		$USER->refresh();
		$USER->optionsarray = false; /* will be reconstructed if needed */
		
		apache_note("CS_USER",urlencode($USER->login)); //for logging

		$ACCESS = &$_SESSION['access'];
		$ACCESS->loadPermissions(true);

		if (!$USER->enabled || $USER->deleted || !$USER->authorize('loginweb')) {
			error_log("Invalid session in subdircommon.inc.php");
			header("HTTP/1.1 401 Unauthorized");
			exit();
		}
	}
}

// load customer/user locale 
//this needs the USER object to already be loaded
require_once("{$incdir}/locale.inc.php");

?>
