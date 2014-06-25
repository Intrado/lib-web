<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("loadtemplatedata.php");
require_once("createtemplates.php");
require_once("../obj/ValSmsText.val.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");
require_once("obj/ValUrlComponent.val.php");
require_once("obj/ValUrl.val.php");
require_once("obj/LogoRadioButton.fi.php");
require_once("obj/LanguagesItem.fi.php");
require_once("obj/ValInboundNumber.val.php");
require_once("../obj/ValInteger.val.php");
require_once("../obj/ApiClient.obj.php");
require_once("../obj/CmaApiClient.obj.php");
require_once("../obj/ValCmaAppId.val.php");

// For Quick Tip TAI table activation stuffs
require_once("loadtaitemplatedata.php");
require_once("inc/customersetup.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/User.obj.php");
require_once("../obj/Organization.obj.php");
require_once("../obj/Voice.obj.php");
require_once("../obj/VoiceProviderManager.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if ($_GET['id'] === "new") {
		$_SESSION['customerid'] = null;
	} else {
		$_SESSION['customerid']= $_GET['id']+0;	
	}
	redirect();	
}


if (!isset($_SESSION['customerid']) && !$MANAGERUSER->authorized("newcustomer"))
	exit("Not Authorized");

if (!$MANAGERUSER->authorized("editcustomer")) {
	unset($_SESSION['customerid']);
	exit("Not Authorized");
}





////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Helper Functions
////////////////////////////////////////////////////////////////////////////////

function update_jobtypeprefs($min, $max, $type, $custdb){
	$runquery = false;

	$emergencyjobtypeids = QuickQueryList("select id from jobtype where systempriority = 1 and not deleted",false, $custdb);
	if(!$emergencyjobtypeids)
	return;

	$currentprefs = QuickQueryList("select jobtypeid,max(sequence) from jobtypepref where jobtypeid in (" . implode($emergencyjobtypeids,",") . ") and type = '" . $type . "' group by jobtypeid",true, $custdb);

	$query = "insert into jobtypepref (jobtypeid,type, sequence,enabled)
						values ";
	$values = array();
	for($i = $min-1; $i < $max; $i++){
		foreach($emergencyjobtypeids as $emergencyjobtypeid) {
			if(!isset($currentprefs["$emergencyjobtypeid"]) || $currentprefs["$emergencyjobtypeid"] < $i){
				$values[] = "(" . $emergencyjobtypeid . ", '" . $type . "', " . $i . ", 1)";
				$runquery = true;
			}
		}
	}
	if($runquery){
		$values = implode(", ", $values);
		$query .= $values;
		QuickUpdate($query, $custdb);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


// Disable upgrading to phone classroom while active comments have been selected
// If phone is enabled and comments are in a incomplete state. 
class ValClassroom extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $custdb;

		//error_log("Val " . $value);
		
		// for new customer, $custdb is null so we skip this validation
		// for edit customer, we validate no alerts today
		if ($custdb != null && $value != $args['currentsetting'] && $value == "emailandphone") {
			if (QuickQuery("SELECT 1 FROM `alert` WHERE date = curdate()",$custdb))
				return "Unable to set classroom to \"Email and Phone\" since there are active classroom alerts for today for this customer";
		}
		return true;
	}
}
	
////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$googlangs = array(
	"af" => "Afrikaans",
	"ar" => "Arabic",
	"be" => "Belarusian",
	"bg" => "Bulgarian",
	"ca" => "Catalan",
	"cs" => "Czech",
	"cy" => "Welsh",
	"da" => "Danish",
	"de" => "German",
	"el" => "Greek",
	"en" => "English",
	"es" => "Spanish",
	"et" => "Estonian",
	"fa" => "Persian",
	"fi" => "Finnish",
	"fr" => "French",
	"ga" => "Irish",
	"gl" => "Galician",
	"hi" => "Hindi",
	"hr" => "Croatian",
	"ht" => "Haitian",
	"hu" => "Hungarian",
	"id" => "Indonesian",
	"is" => "Icelandic",
	"it" => "Italian",
	"he" => "Hebrew",
	"ja" => "Japanese",
	"ko" => "Korean",
	"lt" => "Lithuanian",
	"lv" => "Latvian",
	"mk" => "Macedonian",
	"ms" => "Malay",
	"mt" => "Maltese",
	"nl" => "Dutch",
	"no" => "Norwegian",
	"pl" => "Polish",
	"pt" => "Portuguese",
	"ro" => "Romanian",
	"ru" => "Russian",
	"sk" => "Slovak",
	"sl" => "Slovenian",
	"sq" => "Albanian",
	"sr" => "Serbian",
	"sv" => "Swedish",
	"sw" => "Swahili",
	"th" => "Thai",
	"fil" => "Filipino",
	"tr" => "Turkish",
	"uk" => "Ukrainian",
	"vi" => "Vietnamese",
	"yi" => "Yiddish",
	"zh" => "Chinese"
);
asort($googlangs); // Sort by value and not by country code

$ttslangs = array(
	'en' => 'english',
	'es' => 'spanish',
	'ca' => 'catalan',
	'zh' => 'chinese',
	'nl' => 'dutch',
	'fi' => 'finnish',
	'fr' => 'french',
	'de' => 'german',
	'el' => 'greek',
	'it' => 'italian',
	'pl' => 'polish',
	'pt' => 'portuguese',
	'ru' => 'russian',
	'sv' => 'swedish',
    'ko' => 'korean',
    'ja' => 'japanese'
);

// default settings
$settings = array(
	'_dmmethod' => '',
	'timezone' => '',
	'displayname' => '',
	'organizationfieldname' => 'School',
	'urlcomponent' => '',
	'_logocontentid' => '',
	'_logoclickurl' => '',
	'_productname' => '',
	'_supportemail' => '',
	'_supportphone' => '',
	'callerid' => '',
	'defaultareacode' => '',
	'inboundnumber' => '',
	'maxguardians' => '0',
	'maxphones' => '1',
	'maxemails' => '1',
	'emaildomain' => '',
	'tinydomain' => '',
	'softdeletemonths' => '6',
	'harddeletemonths' => '24',
	'disablerepeat' => '0',
	'_hassurvey' => '0',
	'surveyurl' => '',
	'_hassms' => '0',
	'maxsms' => '1',
	'smscustomername' => '',
	'enablesmsoptin' => '0',
	'_hassmapi' => '0',
	'_hascallback' => '0',
	'callbackdefault' => 'inboundnumber',
	'_hasldap' => '0',
	'_hasenrollment' => '0',
	'_hastargetedmessage' => '0',
	'_hasphonetargetedmessage' => '0',
	'_hasselfsignup' => '',
	'_hasportal' => '',
	'_hasinfocenter' => '',
	'_hasfacebook' => '0',
	'_hastwitter' => '0',
	'_hasfeed' => '0',
	'_allowoldmessagesender' => '0',
	'_hasquicktip' => '0',
	'_haspdfburst' => '0',
	'_cmaappid' => '',
	'autoreport_replyname' => 'SchoolMessenger',
	'autoreport_replyemail' => 'autoreport@schoolmessenger.com',
	'_renewaldate' => '',
	'_callspurchased' => '',
	'_maxusers' => '',
	'_timeslice' => '450',
	'loginlockoutattempts' => '5',
	'logindisableattempts' => '0',
	'loginlockouttime' => '5',
	'_amdtype' => "ivr",
	'_defaultttsprovider' => 'neospeech'
);

$timezones = array(
	"US/Alaska",
	"US/Aleutian",
	"US/Arizona",
	"US/Central",
	"US/East-Indiana",
	"US/Eastern",
	"US/Hawaii",
	"US/Indiana-Starke",
	"US/Michigan",
	"US/Mountain",
	"US/Pacific",
	"US/Samoa"
);

$customerid = null;
if (isset($_SESSION['customerid'])) {
	$customerid = $_SESSION['customerid'];
	$query = "select s.dbhost, c.urlcomponent, c.enabled, c.oem, c.oemid, c.nsid, c.notes, s.dbusername as shardusername, s.dbpassword as shardpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'";
	$custinfo = QuickQueryRow($query,true);
	// connect to customer database as the shard user (needed to create tables for new products)
	$custdb = DBConnect($custinfo["dbhost"], $custinfo["shardusername"], $custinfo["shardpassword"], "c_$customerid");
	if (!$custdb) {
		exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid, as shard user");
	}

	$query = "select name,value from setting";
	$settings = array_merge($settings, QuickQueryList($query,true,$custdb));
}


$logos = array(); 
if ($customerid && $settings['_logocontentid'] != '') {
	$logos = array( "Saved" => 'No change - Preview: <div style="display:inline;"><img src="customerlogo.img.php?id=' . $customerid .'" width="70px" alt="" /></div>');
}
// Content for logo selector
$logos += array(
	"AutoMessenger" => '<img src="mimg/auto_messenger.jpg" alt="AutoMessenger" title="AutoMessenger" />',
	"SchoolMessenger" => '<img src="mimg/logo_small.gif" alt="SchoolMessenger" title="SchoolMessenger" />',
	"Skylert" => '<img src="mimg/sky_alert.jpg" alt="Skylert" title="Skylert" />'
);

// Locations and mimetype for default logos
$defaultlogos = array(
	"AutoMessenger" => array("filelocation" => "mimg/auto_messenger.jpg", "filetype" => "image/jpeg"),
	"SchoolMessenger" => array("filelocation" => "mimg/logo_small.gif", "filetype" => "image/gif"),
	"Skylert" => array("filelocation" => "mimg/sky_alert.jpg", "filetype" => "image/jpeg")
);


$shards = QuickQueryList("select id, name from shard where not isfull order by id",true);

$dmmethod = array('' => '--Choose a Method--', 'asp' => 'CommSuite (fully hosted)','hybrid' => 'CS + SmartCall + Emergency','cs' => 'CS + SmartCall (data only)');

if ($customerid)
	$shortcodegroupname = QuickQuery("select description from shortcodegroup where id = (select shortcodegroupid from customer where id = ?)", null, array($customerid));
else
	$shortcodegroupname = QuickQuery("select description from shortcodegroup where id = 1"); // hardcoded id=1 is the default group for new customers


$helpstepnum = 1;
$formdata = array(_L('Basics'));
// -----------------------------------------------------------------------------

include("inc/customerRequiredFormItems.inc.php");

$availablenumbers = QuickQueryList("select phone from tollfreenumbers");
$tollfreenumbers = array();
foreach ($availablenumbers as $number) {
	$tollfreenumbers[$number] = Phone::format($number);
}
if ($settings['inboundnumber'] != "")
	$tollfreenumbers = array($settings['inboundnumber'] => $settings['inboundnumber']) + $tollfreenumbers;

$formdata["inboundnumber"] = array(
	"label" => _L('800 Inbound number'),
	"value" => $settings['inboundnumber'],
	"validators" => array(
		array("ValPhone"),
		array("ValInArray", "values" => array_keys($tollfreenumbers))
	),
	"control" => array("SelectMenu", "values" => array("" =>_L("-- Select a Toll Free Number --")) + $tollfreenumbers),
	"helpstep" => $helpstepnum
);


$formdata["maxguardians"] = array(
	"label" => _L('Max Guardians'),
	"value" => $settings['maxguardians'],
	"validators" => array(
		array('ValNumber', 'min' => $settings['maxguardians']>=0?$settings['maxguardians']:0, 'max' => 99)
	),
	"control" => array("TextField","size" => 4, "maxlength" => 4),
	"helpstep" => $helpstepnum
);

$formdata["maxphones"] = array(
	"label" => _L('Max Phones'),
	"value" => $settings['maxphones'],
	"validators" => array(
		array('ValNumber', 'min' => $settings['maxphones']>0?$settings['maxphones']:1, 'max' => 99)
	),
	"control" => array("TextField","size" => 4, "maxlength" => 4),
	"helpstep" => $helpstepnum
);

$formdata["maxemails"] = array(
	"label" => _L('Max Emails'),
	"value" => $settings['maxemails'],
	"validators" => array(
		array('ValNumber', 'min' => $settings['maxemails']>0?$settings['maxemails']:1, 'max' => 99)
	),
	"control" => array("TextField","size" => 4, "maxlength" => 4),
	"helpstep" => $helpstepnum
);

$formdata["autoreportreplyname"] = array(
	"label" => _L('AutoReport Name'),
	"value" => $settings['autoreport_replyname'],
	"validators" => array(
		array("ValRequired")
	),
	"control" => array("TextField","maxlength"=>255,"min"=>3,"size"=>35),
	"helpstep" => $helpstepnum
);

$formdata["autoreportreplyemail"] = array(
	"label" => _L('AutoReport Email'),
	"value" => $settings['autoreport_replyemail'],
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 255),
		array("ValEmail")
	),
	"control" => array("TextField","maxlength"=>255,"min"=>3,"size"=>35),
	"helpstep" => $helpstepnum
);

$formdata["emaildomain"] = array(
	"label" => _L('Email Domain'),
	"value" => $settings['emaildomain'],
	"validators" => array(
		array("ValLength","max" => 65535),
		array("ValDomainList")
	),
	"control" => array("TextField","maxlength"=>65535,"min"=>3,"size"=>35),
	"helpstep" => $helpstepnum
);

$tinydomains = $SETTINGS['feature']['tinydomain'];
$formdata["tinydomain"] = array(
	"label" => _L('Tiny Domain'),
	"value" => $settings['tinydomain'],
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => $tinydomains)
	),
	"control" => array("SelectMenu", "values" => array("" =>_L("-- Select a Domain --")) + array_combine($tinydomains, $tinydomains)),
	"helpstep" => $helpstepnum
);

$formdata["softdeletemonths"] = array(
	"label" => _L('Auto Message Expire Months (soft delete)'),
	"value" => $settings['softdeletemonths'],
	"validators" => array(
		array('ValNumber', 'min' => 0, 'max' => 30000)
	),
	"control" => array("TextField","size" => 6, "maxlength" => 6),
	"helpstep" => $helpstepnum
);

$formdata["harddeletemonths"] = array(
	"label" => _L('Auto Report Expire Months (HARD delete)'),
	"value" => $settings['harddeletemonths'],
	"validators" => array(
		array('ValNumber', 'min' => 0, 'max' => 30000)
	),
	"control" => array("TextField","size" => 6, "maxlength" => 6),
	"helpstep" => $helpstepnum
);

$formdata[] = _L("Languages");

$formdata["ttsprovider"] = array(
	"label" => _L('TTS Provider'),
	"value" => $settings['_defaultttsprovider'],
	"validators" => array(),
	"control" => array("SelectMenu", "values" => array("loquendo" => "Loquendo", "neospeech" => "NeoSpeech")),
	"helpstep" => $helpstepnum
);

// -----------------------------------------------------------------------------

$languages = $customerid?QuickQueryList("select code, name from language",true,$custdb):array("en" => "English", "es" => "Spanish");
$formdata["languages"] = array(
	"label" => _L('Languages'),
	"value" => json_encode($languages),
	"validators" => array(
		array("ValRequired"),
		array("ValLanguages")),
	"control" => array("LanguagesItem", 
		"ttslangs" => $customerid?QuickQueryList("select languagecode from ttsvoice", false, $custdb):array_keys($ttslangs),
		"googlelangs" => $googlangs),
	"helpstep" => $helpstepnum
);

$formdata[] = _L("SMS");
// -----------------------------------------------------------------------------

$formdata["hassms"] = array(
	"label" => _L('Has SMS'),
	"value" => $settings['_hassms'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["maxsms"] = array(
	"label" => _L('Max SMS'),
	"value" => $settings['maxsms'],
	"validators" => array(
		array('ValNumber', 'min' => $settings['maxsms']>0?$settings['maxsms']:1, 'max' => 99)
	),
	"control" => array("TextField","size" => 4, "maxlength" => 4),
	"helpstep" => $helpstepnum
);

$formdata["enablesmsoptin"] = array(
	"label" => _L('SMS - Enable Opt-in'),
	"value" => $settings['enablesmsoptin'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["smscustomername"] = array(
	"label" => _L('SMS Customer Name'),
	"value" => $settings['smscustomername'],
	"validators" => array(
		array("ValConditionallyRequired", "field" => "hassms"),
		array("ValLength","max" => 50),
		array("ValSmsText")
	),
	"requires" => array("hassms"),
	"control" => array("TextField","maxlength"=>50,"size"=>25),
	"helpstep" => $helpstepnum
);

$formdata["shortcodegroupname"] = array(
	"label" => _L("Shortcode Group"),
	"control" => array("FormHtml","html"=>"<div>".$shortcodegroupname."</div>"),
	"helpstep" => $helpstepnum
);

$formdata[] = _L("API");
// -----------------------------------------------------------------------------

$formdata["hassmapi"] = array(
	"label" => _L('Has SMAPI'),
	"value" => $settings['_hassmapi'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["oem"] = array(
	"label" => _L('OEM'),
	"value" => isset($custinfo)?$custinfo["oem"]:"",
	"validators" => array(
		array("ValLength","max" => 50)
	),
	"control" => array("TextField","maxlength"=>50,"size"=>4),
	"helpstep" => $helpstepnum
);

$formdata["oemid"] = array(
	"label" => _L('OEM id'),
	"value" => isset($custinfo)?$custinfo["oemid"]:"",
	"validators" => array(
		array("ValLength","max" => 50)
	),
	"control" => array("TextField","maxlength"=>50,"size"=>4),
	"helpstep" => $helpstepnum
);

$formdata[] = _L("Callback");
// -----------------------------------------------------------------------------

$formdata["hascallback"] = array(
	"label" => _L('Has Callback'),
	"value" => $settings['_hascallback'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["callbackdefault"] = array(
	"label" => _L('Callback Default'),
	"value" => $settings['callbackdefault'],
	"validators" => array(
		array("ValInArray", "values" => array("inboundnumber","callerid"))
	),
	"control" => array("SelectMenu", "values" => array("inboundnumber" => "Inbound Number","callerid" => "Default CallerID")),
	"helpstep" => $helpstepnum
);

$formdata[] = _L("Additional Features");
// -----------------------------------------------------------------------------
$portaloption = 'none';
if ($settings['_hasportal'])
	$portaloption = "contactmanager";
elseif ($settings['_hasselfsignup'])
	$portaloption = "selfsignup";
elseif ($settings['_hasinfocenter'])
	$portaloption = "infocenter";

$formdata["portal"] = array(
	"label" => _L('Portal'),
	"value" => $portaloption,
	"validators" => array(),
	"control" => array("SelectMenu","values" => array("none" => "None", "contactmanager" => "Contact Manager", "selfsignup" => "Self-Signup", "infocenter" => "InfoCenter")),
	"helpstep" => $helpstepnum
);

$formdata["hassurvey"] = array(
	"label" => _L('Has Survey'),
	"value" => $settings['_hassurvey'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["hasldap"] = array(
	"label" => _L('Has LDAP'),
	"value" => $settings['_hasldap'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["hasenrollment"] = array(
	"label" => _L('Has Enrollment'),
	"value" => $settings['_hasenrollment'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$classroomstate = $settings['_hastargetedmessage']?($settings['_hasphonetargetedmessage']?"emailandphone":"emailonly"):"disabled";
$formdata["hasclassroom"] = array(
	"label" => _L('Classroom Comments'),
	"value" => $classroomstate,
	"validators" => array(
		array("ValInArray", "values" => array("disabled","emailonly","emailandphone")),
		array("ValClassroom","currentsetting" => $classroomstate)
	),
	"control" => array("SelectMenu","values" => array("disabled" => "Disabled", "emailonly" => "Email Only", "emailandphone" => "Email and Phone")),
	"helpstep" => $helpstepnum
);

$formdata["hasfacebook"] = array(
	"label" => _L('Has Facebook'),
	"value" => $settings['_hasfacebook'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);
$formdata["hastwitter"] = array(
	"label" => _L('Has Twitter'),
	"value" => $settings['_hastwitter'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);
$formdata["hasfeed"] = array(
	"label" => _L('Has Feed'),
	"value" => $settings['_hasfeed'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);
$formdata["allowoldmessagesender"] = array(
	"label" => _L('Allow Deprecated Message Sender'),
	"value" => $settings['_allowoldmessagesender'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["hasquicktip"] = array(
	"label" => _L('Has Quick Tip'),
	"value" => $settings['_hasquicktip'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata ["haspdfburst"] = array (
	"label" => _L('Has Secure Document Delivery'),
	"value" => $settings ['_haspdfburst'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum 
);

$formdata["cmaappid"] = array(
	"label" => _L('CMA App. ID'),
	"value" => ($settings['_cmaappid'] ? $settings['_cmaappid'] : ''),
	"validators" => array(
		array('ValCmaAppId')
	),
	"control" => array("TextField","size"=>20),
	"helpstep" => $helpstepnum
);

// Answering machine detection methods
$amdtypes = array(
	"ivr" => "Default / IVR Intro",
	"ivrmessageasintro" => "Message as Intro",
	"machinedetect" => "Voice / Beep Detect");

$formdata[] = _L("Misc. Settings");
// -----------------------------------------------------------------------------

$formdata["amdtype"] = array(
	"label" => _L('AMD Type'),
	"value" => ($settings['_amdtype']?$settings['_amdtype']:"ivr"),
	"validators" => array(
		array("ValInArray", "values" => array_keys($amdtypes))
	),
	"control" => array("SelectMenu", "values" => $amdtypes),
	"helpstep" => $helpstepnum
);

$formdata["renewaldate"] = array(
	"label" => _L('Renewal Date'),
	"value" => $settings['_renewaldate'],
	"validators" => array(
		array('ValDate', "min" => date("m/d/Y", time()))
	),
	"control" => array("TextDate", "size"=>12),
	"helpstep" => $helpstepnum
);

$formdata["callspurchased"] = array(
	"label" => _L('Calls Purchased'),
	"value" => $settings['_callspurchased'],
	"validators" => array(
		array('ValNumber')
	),
	"control" => array("TextField","size"=>4),
	"helpstep" => $helpstepnum
);

$formdata["maxusers"] = array(
	"label" => _L('Users Purchased'),
	"value" => $settings['_maxusers']!="unlimited"?$settings['_maxusers']:"",
	"validators" => array(
		array('ValNumber')
	),
	"control" => array("TextField","size"=>4),
	"helpstep" => $helpstepnum
);

$formdata["timeslice"] = array(
	"label" => _L('Timeslice'),
	"value" => $settings['_timeslice'],
	"validators" => array(
		array("ValRequired"),
		array('ValNumber', "min" => 60, "max" => 18000)
	),
	"control" => array("TextField","size"=>4),
	"helpstep" => $helpstepnum
);

$formdata["loginlockoutattempts"] = array(
	"label" => _L('Failed login attempts to cause lockout'),
	"value" => $settings['loginlockoutattempts'],
	"validators" => array(
		array('ValNumber', "min" => 0, "max" => 15)
	),
	"control" => array("SelectMenu", "values" => array(0 => "Disable") + array_combine(range(1, 15),range(1, 15))),
	"helpstep" => $helpstepnum
);

$formdata["logindisableattempts"] = array(
	"label" => _L('Failed login attempts before account disable'),
	"value" => $settings['logindisableattempts'],
	"validators" => array(
		array('ValNumber', "min" => 0, "max" => 15)
	),
	"control" => array("SelectMenu", "values" => array(0 => "Disable") + array_combine(range(1, 15),range(1, 15))),
	"helpstep" => $helpstepnum
);

$formdata["loginlockouttime"] = array(
	"label" => _L('Number of minutes for login lockout'),
	"value" => $settings['loginlockouttime'],
	"validators" => array(
		array('ValNumber', "min" => 1, "max" => 60)
	),
	"control" => array("SelectMenu", "values" => array_combine(range(1, 60),range(1, 60))),
	"helpstep" => $helpstepnum
);

$thispage = "customeredit.php";
$returntopage = "customers.php";

$buttons = array(submit_button(_L("Save"),"save","tick"),submit_button(_L("Save and Return"),"done","tick"),
				icon_button(_L('Cancel'),"cross",null,$returntopage));
$form = new Form("newcustomer",$formdata,null,$buttons);
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
		$templates = loadTemplateData(false);

		Query("BEGIN");
		// Craete new customer if It does not exist 
		if (!$customerid) {
			$custdb = createnewcustomer($postdata["shard"]);
			$customerid = $_SESSION['customerid'];
		}
		
		saveRequiredFields($custdb,$customerid,$postdata);
		
		$query = "update customer set inboundnumber=?,oem=?,oemid=? where id = ?";
		
		QuickUpdate($query,false,array(
				$postdata["inboundnumber"],
				$postdata["oem"],
				$postdata["oemid"],
				$customerid
		));
		
		// if inbound changed
		if ($postdata["inboundnumber"] != $settings['inboundnumber']){
			// Remove phone from available toll free Numbers
			if ($postdata["inboundnumber"] != "") {
				QuickUpdate("delete from tollfreenumbers where phone=?",false,array($postdata["inboundnumber"]));
			}
			// Put back unused phone to available toll free Numbers
			if ($settings["inboundnumber"] != "") {
				QuickUpdate("insert into tollfreenumbers (phone) values (?)",false,array($settings['inboundnumber']));
			}
			setCustomerSystemSetting("inboundnumber", $postdata["inboundnumber"], $custdb);
		}
		
		setCustomerSystemSetting('maxguardians', $postdata["maxguardians"], $custdb);
		
		update_jobtypeprefs(1, $postdata["maxphones"], "phone", $custdb);
		setCustomerSystemSetting('maxphones', $postdata["maxphones"], $custdb);
		
		update_jobtypeprefs(1, $postdata["maxemails"], "email", $custdb);
		setCustomerSystemSetting('maxemails', $postdata["maxemails"], $custdb);
		setCustomerSystemSetting('autoreport_replyname', $postdata["autoreportreplyname"], $custdb);
		setCustomerSystemSetting('autoreport_replyemail', $postdata["autoreportreplyemail"], $custdb);
		setCustomerSystemSetting('emaildomain', $postdata["emaildomain"], $custdb);
		setCustomerSystemSetting('tinydomain', $postdata["tinydomain"], $custdb);
		setCustomerSystemSetting('softdeletemonths', $postdata["softdeletemonths"], $custdb);
		setCustomerSystemSetting('harddeletemonths', $postdata["harddeletemonths"], $custdb);
		
		// Add/Remove Languages
		$submittedlanguages = json_decode($postdata["languages"],true);
		foreach($submittedlanguages as $code => $name) {
			if (isset($languages[$code])) {
				if ($code != 'en' && $languages[$code] != $name) {
					// Name changed for this language
					QuickUpdate("update language set name=? where code=?",$custdb,array($name,$code));
				}
				unset($languages[$code]);
			} else {
				// Add Language since it did not exist already
				QuickUpdate("insert into language (code, name) values (?, ?)", $custdb, array($code, $name));
			}
		}
		foreach($languages as $code => $name) {
			// Remove all unwanted languages except English
			if ($code != 'en') {
				QuickUpdate("delete from language where code=?", $custdb, array($code));
			}
		}
		
		// this is a hack - subscriber languages English and Spanish are hardcoded - language needs redo post 7.0 release
		QuickUpdate("update persondatavalues set editlock=0 where fieldnum='f03'", $custdb);
		if ($postdata["portal"] == "selfsignup") {
			// English
			if (QuickQuery("select count(*) from persondatavalues where fieldnum='f03' and value='en'", $custdb)) {
				QuickUpdate("update persondatavalues set editlock=1 where fieldnum='f03' and value='en'", $custdb);
			} else {
				QuickUpdate("insert into persondatavalues (fieldnum, value, refcount, editlock) values ('f03','en',0,1)", $custdb);
			}
			// Spanish
			if (QuickQuery("select count(*) from persondatavalues where fieldnum='f03' and value='es'", $custdb)) {
				QuickUpdate("update persondatavalues set editlock=1 where fieldnum='f03' and value='es'", $custdb);
			} else {
				QuickUpdate("insert into persondatavalues (fieldnum, value, refcount, editlock) values ('f03','es',0,1)", $custdb);
			}
		}
		
		setCustomerSystemSetting('_hassms', $postdata["hassms"]?'1':'0', $custdb);
		
		update_jobtypeprefs(1, $postdata["maxsms"], "sms", $custdb);
		setCustomerSystemSetting('maxsms', $postdata["maxsms"], $custdb);
		setCustomerSystemSetting('enablesmsoptin', $postdata["enablesmsoptin"]?'1':'0', $custdb);
		setCustomerSystemSetting('smscustomername', $postdata["smscustomername"], $custdb);

		setCustomerSystemSetting('_hassmapi', $postdata["hassmapi"]?'1':'0', $custdb);
		// Set oem,oemid and nsid in authserver customer table
		
		setCustomerSystemSetting('_hascallback', $postdata["hascallback"]?'1':'0', $custdb);
		setCustomerSystemSetting('callbackdefault', $postdata["callbackdefault"], $custdb);

		//update settings
		setCustomerSystemSetting('_hasportal', ($postdata["portal"] === 'contactmanager') ? '1' : '0', $custdb);
		setCustomerSystemSetting('_hasselfsignup', ($postdata["portal"] === 'selfsignup') ? '1' : '0', $custdb);
		setCustomerSystemSetting('_hasinfocenter', ($postdata["portal"] === 'infocenter') ? '1' : '0', $custdb);

		//handle authserver.customerproduct table
		updateCustomerProduct($customerid, 'cm', $postdata["portal"] === 'contactmanager');
		updateCustomerProduct($customerid, 'ic', $postdata["portal"] === 'infocenter');

		setCustomerSystemSetting('_hassurvey', $postdata["hassurvey"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hasldap', $postdata["hasldap"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hasenrollment', $postdata["hasenrollment"]?'1':'0', $custdb);
		
		$originalProvider = $settings['_defaultttsprovider'];
		$originalDMMethod = $settings['_dmmethod'];
		//check either DM method is changed or provider is changed.
		if ($originalProvider != $postdata["ttsprovider"] || $originalDMMethod != $postdata["dmmethod"]) {
			switchTTSProviderTo($postdata["ttsprovider"], $postdata["dmmethod"], $custdb);
		}

		$phonetargetedmessage = false;
		switch($postdata["hasclassroom"]) {
			case "disabled":
				setCustomerSystemSetting('_hasphonetargetedmessage','0', $custdb);
				setCustomerSystemSetting('_hastargetedmessage','0', $custdb);
				break;
			case "emailandphone":
				$phonetargetedmessage = true;
				//continue and enable _hastargetedmessage
			case "emailonly":
				// Remove phone template to disable the already configured customers
				if (!$phonetargetedmessage) {
					QuickUpdate("delete m.* ,mp.*
							from messagegroup mg 
							inner join message m on (mg.id = m.messagegroupid)
							inner join messagepart mp on (m.id = mp.messageid)
							where
							mg.type='classroomtemplate' and 
							m.type='phone' and m.subtype='voice'",
							$custdb);
				}
				setCustomerSystemSetting('_hasphonetargetedmessage',$phonetargetedmessage?'1':'0', $custdb);
				setCustomerSystemSetting('_hastargetedmessage','1', $custdb);
				break;
		}

		setCustomerSystemSetting('_hasfacebook', $postdata["hasfacebook"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hastwitter', $postdata["hastwitter"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hasfeed', $postdata["hasfeed"]?'1':'0', $custdb);
		
		setCustomerSystemSetting('_allowoldmessagesender', $postdata["allowoldmessagesender"]?'1':'0', $custdb);

		setCustomerSystemSetting('_amdtype', $postdata["amdtype"], $custdb);
	
		setCustomerSystemSetting('_renewaldate', ($postdata['renewaldate']!=""?date("Y-m-d", strtotime($postdata['renewaldate'])):""), $custdb);
		setCustomerSystemSetting('_callspurchased', $postdata['callspurchased'], $custdb);
		setCustomerSystemSetting('_maxusers', ($postdata['maxusers']!=""?$postdata['maxusers']:"unlimited"), $custdb);
		
		setCustomerSystemSetting('_timeslice', $postdata['timeslice'], $custdb);
		setCustomerSystemSetting('loginlockoutattempts', $postdata['loginlockoutattempts'], $custdb);
		setCustomerSystemSetting('logindisableattempts', $postdata['logindisableattempts'], $custdb);
		setCustomerSystemSetting('loginlockouttime', $postdata['loginlockouttime'], $custdb);

		// SSD feature...
		setCustomerSystemSetting('_haspdfburst', $postdata["haspdfburst"] ? '1' : '0', $custdb);
		
		// QuickTip requires that we add the [dis|en]able the feature...
		setCustomerSystemSetting('_hasquicktip', $postdata["hasquicktip"] ? '1' : '0', $custdb);

		// ... and if it was disabled and is now enabled, add the TAI tables to this customer which QuickTip uses
		// and add the quicktip alert email template
		if ($postdata["hasquicktip"] && (! $settings['_hasquicktip'])) {
			$savedbcon = $_dbcon;
			$_dbcon = $custdb;
			tai_setup($customerid);

			$templateName = 'quicktipalert';
			$hasQtAlertTemplate = QuickQuery('select 1 from template where type = ?', $custdb, array($templateName));
			if (!$hasQtAlertTemplate) {
				$messageGroupId = createEmailTemplateMessageGroup($templateName, $templates[$templateName]);
				QuickQuery('insert into template (type, messagegroupid) values (?,?)', $custdb, array($templateName, $messageGroupId));
			}

			$_dbcon = $savedbcon;
		}

		// CMA App ID is integer value only at this time.
		setCustomerSystemSetting('_cmaappid', $postdata['cmaappid'], $custdb);

		Query("COMMIT");
		if($button == "done") {
			if ($ajax)
				$form->sendTo($returntopage);
			else
				redirect($returntopage);
		} else {
			if ($ajax)
				$form->sendTo($thispage);
			else
				redirect($thispage);
		}
	}
}


/**
 * Enable/disable product in authserver.customerproduct
 * @param type $customerid customer id
 * @param type $product product name
 * @param type $enabled is product enabled or not
 */
function updateCustomerProduct($customerid, $product, $enabled) {
	$hasproduct = $enabled ? 1 : 0;
	$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE enabled = ?, modifiedtimestamp = ?";
	QuickUpdate($query, false, array($customerid, $product, time(), time(), $hasproduct, $hasproduct, time()));
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = $customerid?_L('Edit Customer'):_L('New Customer');
$PAGE = "commsuite:customers";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>

<script type="text/javascript">

document.observe('dom:loaded', function() {
	$('newcustomer_logo').observe("change", function (event) {
		var e = event.element();
		var savedname = '<?= $settings['_productname'] ?>';
		$('newcustomer_productname').value = (e.value && e.type == "radio" && e.value != "Other" && e.value != "Saved")?e.value:savedname;
	});
	
	$('newcustomer_displayname').observe("change", function (event) {
		var e = event.element();
		if ($('newcustomer_hassms').checked) {
			$('newcustomer_smscustomername').value = e.value;
		}
	});
	$('newcustomer_hassms').observe("change", function (event) {
		if ($('newcustomer_hassms').checked) {
			$('newcustomer_enablesmsoptin').checked = 1;
			$('newcustomer_smscustomername').value = $('newcustomer_displayname').value;
		} else {
			$('newcustomer_enablesmsoptin').checked = 0;
			$('newcustomer_smscustomername').value = '';
		}
	});
	$('newcustomer_enabled').observe("change", function (event) {
		//var checkbox = event.Element();
		var checkbox = $('newcustomer_enabled');
		if (checkbox.checked == 0) 
			checkbox.checked = !confirm("Are you sure you want to DISABLE this customer?");
	});
});
<? Validator::load_validators(array("ValInboundNumber","ValUrlComponent","ValSmsText","ValLanguages","ValUrl","ValClassroom", "ValInteger", 'ValCmaAppId'));?>
</script>
<?

startWindow($customerid?_L('Edit Customer'):_L('New Customer'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
