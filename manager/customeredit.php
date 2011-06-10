<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");
require_once("../obj/Language.obj.php");


if (!$MANAGERUSER->authorized("editcustomer"))
	exit("Not Authorized");

if (isset($_GET['id'])) {
	$_SESSION['currentid']= $_GET['id']+0;
	redirect();
}
if (isset($_SESSION['currentid'])) {
	$currentid = $_SESSION['currentid'];
	$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled, c.oem, c.oemid, c.nsid, c.notes from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
	if (!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Functions
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
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$f = "customer";
$s = "edit";

$googlangs = array(	"ar" => "Arabic",
					"bg" => "Bulgarian",
					"ca" => "Catalan",
					"zh" => "Chinese",
					"hr" => "Croatian",
					"cs" => "Czech",
					"da" => "Danish",
					"nl" => "Dutch",
					"fil" => "Filipino",
					"fi" => "Finnish",
					"fr" => "French",
					"de" => "German",
					"el" => "Greek",
					"he" => "Hebrew",   //or iw
					"hi" => "Hindi",
					"id" => "Indonesian", // or in
					"it" => "Italian",
					"ja" => "Japanese",
					"ko" => "Korean",
					"lv" => "Latvian",
					"lt" => "Lithuanian",
					"no" => "Norwegian",
					"pl" => "Polish",
					"pt" => "Portuguese",
					"ro" => "Romanian",
					"ru" => "Russian",
					"sr" => "Serbian",
					"sk" => "Slovak",
					"sl" => "Slovenian",
					"es" => "Spanish",
					"sv" => "Swedish",
					"uk" => "Ukrainian",
					"vi" => "Vietnamese");

$timezones = array(	"US/Alaska",
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
					"US/Samoa"	);
$reloadform = 0;

$refresh = 0;
$languages = DBFindMany("Language","from language order by id", false,false, $custdb);
$ttslangs = QuickQueryList("select id, language from ttsvoice", true, $custdb);
$ttslangs = array_flip($ttslangs);

if(CheckFormSubmit($f,"Save") || CheckFormSubmit($f, "Return")) {
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			$displayname = GetFormData($f,$s,"name");
			$timezone = GetFormData($f, $s, "timezone");
			$hostname = GetFormData($f, $s, "hostname");
			$inboundnumber  = GetFormData($f, $s, "inboundnumber");
			$maxphones = GetFormData($f, $s, "maxphones");
			$maxemails = GetFormData($f, $s, "maxemails");
			$callerid = GetFormData($f, $s, "callerid");
			$areacode = GetFormData($f, $s, "areacode");
			$autoname = GetFormData($f, $s, 'autoname');
			$autoemail = GetFormData($f, $s, 'autoemail');
			$renewaldate = GetFormData($f, $s, 'renewaldate');
			$callspurchased = ereg_replace("[^0-9]*","",GetFormData($f, $s, 'callspurchased'));
			$surveyurl = GetFormData($f, $s, 'surveyurl');
			$maxusers = GetFormData($f, $s, 'maxusers');
			$managernote = GetFormData($f, $s, 'managernote');
			$hasldap = GetFormData($f, $s, 'hasldap');
			$hassms = GetFormData($f, $s, 'hassms');
			$enablesmsoptin = GetFormData($f, $s, "enablesmsoptin");
			$smscustomername = trim(GetFormData($f, $s, "smscustomername"));
			$maxsms = GetFormData($f, $s, 'maxsms');
			$hasportal = GetFormData($f, $s, 'hasportal');
			$hasselfsignup = GetFormData($f, $s, 'hasselfsignup');
			$hasfacebook = GetFormData($f, $s, 'hasfacebook');
			$hastwitter = GetFormData($f, $s, 'hastwitter');
			$hassmapi = GetFormData($f, $s, 'hassmapi');
			$tinydomain = GetFormData($f, $s, 'tinydomain');
			$emaildomain = trim(GetFormData($f, $s, 'emaildomain'));
			$emaildomainerror = validateDomainList($emaildomain);
			$fileerror = false;
			$logoname = "";
			$loginpicturename ="";
			if(isset($_FILES['uploadlogo']) && $_FILES['uploadlogo']['tmp_name']) {

				$logoname = secure_tmpname("uploadlogo",".img");
				if(!move_uploaded_file($_FILES['uploadlogo']['tmp_name'],$logoname)) {
					$fileerror=true;
				} else if (!is_file($logoname) || !is_readable($logoname)) {
					$fileerror=true;
				}
			}
			if(isset($_FILES['uploadloginpicture']) && $_FILES['uploadloginpicture']['tmp_name']) {

				$loginpicturename = secure_tmpname("uploadloginpicture",".img");
				if(!move_uploaded_file($_FILES['uploadloginpicture']['tmp_name'],$loginpicturename)) {
					$fileerror=true;
				} else if (!is_file($loginpicturename) || !is_readable($loginpicturename)) {
					$fileerror=true;
				}
			}
			$subscriberloginpicturename = "";
			if (isset($_FILES['uploadsubscriberloginpicture']) && $_FILES['uploadsubscriberloginpicture']['tmp_name']) {

				$subscriberloginpicturename = secure_tmpname("uploadsubscriberloginpicture",".img");
				if (!move_uploaded_file($_FILES['uploadsubscriberloginpicture']['tmp_name'], $subscriberloginpicturename)) {
					$fileerror = true;
				} else if (!is_file($subscriberloginpicturename) || !is_readable($subscriberloginpicturename)) {
					$fileerror = true;
				}
			}

			if (($inboundnumber != "") && QuickQuery("SELECT COUNT(*) FROM customer WHERE inboundnumber ='" . DBSafe($inboundnumber) . "' and id != '" . $currentid . "'")) {
				error('Entered 800 Number Already being used', 'Please Enter Another');
			} else if (QuickQuery("SELECT COUNT(*) FROM customer WHERE urlcomponent='" . DBSafe($hostname) ."' AND id != $currentid")) {
				error('URL Path Already exists', 'Please Enter Another');
			} else if (strlen($inboundnumber) > 0 && !ereg("[0-9]{10}",$inboundnumber)) {
				error('Bad Toll Free Number Format, Try Again');
			} else if ((strlen($custinfo[3]) >= 5) && (strlen($hostname) < 5)){
				error('Customer URL\'s cannot be shorter than 5 unless their account was already made');
			} else if(GetFormData($f, $s, "timeslice") == ""){
				error("Timeslice cannot be blank");
			} else if(!eregi("[0-9A-F]{6}", GetFormData($f, $s, "_brandprimary"))){
				error("That is not a valid 'Primary Color'");
			} else if(GetFormData($f, $s, "_brandratio") < 0 || GetFormData($f, $s, "_brandratio") > .5){
				error("The ratio can only be between 0 and .5(50%)");
			} else if($fileerror){
				error('Unable to complete file upload. Please try again');
			} else if ($hasportal && $hasselfsignup) {
				error("Customer cannot have both Contact Manager and Self-Signup features, please select only one");
			} else if ($emaildomainerror !== true) {
				error($emaildomainerror);
			} else if ($smscustomername == "") {
				error('SMS Customer Name cannot be blank');
			} else if (strlen($smscustomername) > 50) {
				error('SMS Customer Name cannot exceed 50 characters');
			} else if (!ereg(getSmsRegExp(),$smscustomername)) {
				error('SMS Customer Name has invalid characters');
			} else {

				QuickUpdate("update customer set
						urlcomponent = '" . DBSafe($hostname) ."',
						inboundnumber = '" . DBSafe($inboundnumber) ."',
						enabled=" . (GetFormData($f,$s,"enabled") + 0) .",
						oem='" . DBSafe(strtolower(GetFormData($f, $s, "oem"))) . "',
						oemid='" . DBSafe(GetFormData($f, $s,"oemid")) . "',
						nsid='" . DBSafe(GetFormData($f, $s, "nsid")) . "',
						notes='" . DBSafe($managernote) . "'
						where id = '$currentid'");

				// notify authserver to refresh the customer cache
				refreshCustomer($currentid);

				// if timezone changed (rare occurance, but we must update scheduled jobs and report records on the shard database)
				if ($timezone != getCustomerSystemSetting('timezone', false, true, $custdb)) {
					$currentid = $_SESSION['currentid'];
					$shardinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id = '$currentid'");
					$sharddb = DBConnect($shardinfo[0], $shardinfo[1], $shardinfo[2], "aspshard");
					if(!$sharddb) {
						exit("Connection failed for customer: $currentid, shardhost: $shardinfo[0]");
					}
					QuickUpdate("update qjob set timezone='".$timezone."' where customerid=".$currentid, $sharddb);
					QuickUpdate("update qschedule set timezone='".$timezone."' where customerid=".$currentid, $sharddb);
					QuickUpdate("update qreportsubscription set timezone='".$timezone."' where customerid=".$currentid, $sharddb);
				}

				if (!GetFormData($f,$s,"enabled"))
					setCustomerSystemSetting("disablerepeat", "1", $custdb);

				setCustomerSystemSetting("urlcomponent", $hostname, $custdb);
				setCustomerSystemSetting("displayname", $displayname, $custdb);
				setCustomerSystemSetting("inboundnumber", $inboundnumber, $custdb);
				setCustomerSystemSetting("timezone", $timezone, $custdb);

				update_jobtypeprefs(getCustomerSystemSetting('maxphones', 1, true, $custdb), $maxphones, "phone", $custdb);
				setCustomerSystemSetting("maxphones", $maxphones, $custdb);

				update_jobtypeprefs(getCustomerSystemSetting('maxemails', 1, true, $custdb),$maxemails, "email", $custdb);
				setCustomerSystemSetting("maxemails", $maxemails, $custdb);

				update_jobtypeprefs(getCustomerSystemSetting('maxsms', 1, true, $custdb), $maxsms, "sms", $custdb);
				setCustomerSystemSetting('maxsms', $maxsms, $custdb);

				setCustomerSystemSetting('callerid', Phone::parse($callerid), $custdb);
				setCustomerSystemSetting('defaultareacode', $areacode, $custdb);
				setCustomerSystemSetting('autoreport_replyname', $autoname, $custdb);
				setCustomerSystemSetting('autoreport_replyemail', $autoemail, $custdb);
				setCustomerSystemSetting('surveyurl', $surveyurl, $custdb);

				if($renewaldate != "" || $renewaldate != NULL){
					if($renewaldate = strtotime($renewaldate)) {
						$renewaldate = date("Y-m-d", $renewaldate);
					}
				}

				setCustomerSystemSetting('_renewaldate', $renewaldate, $custdb);
				setCustomerSystemSetting('_callspurchased', $callspurchased, $custdb);
				if($maxusers == "")
					$maxusers = "unlimited";
				setCustomerSystemSetting('_maxusers', $maxusers, $custdb);
				setCustomerSystemSetting('_hasldap', $hasldap, $custdb);
				setCustomerSystemSetting('_hassms', $hassms, $custdb);
				setCustomerSystemSetting('enablesmsoptin', $enablesmsoptin, $custdb);
				setCustomerSystemSetting('smscustomername', $smscustomername, $custdb);

				setCustomerSystemSetting('_hasportal', $hasportal, $custdb);
				setCustomerSystemSetting('_hassurvey', GetFormData($f, $s, 'hassurvey'), $custdb);
				setCustomerSystemSetting('_hascallback', GetFormData($f, $s, 'hascallback'), $custdb);
				setCustomerSystemSetting('callbackdefault', GetFormData($f, $s, 'callbackdefault'), $custdb);
				setCustomerSystemSetting('_hasenrollment', GetFormData($f, $s, 'hasenrollment'), $custdb);
				setCustomerSystemSetting('_hastargetedmessage', GetFormData($f, $s, 'hastargetedmessage'), $custdb);
				setCustomerSystemSetting('_hasselfsignup', $hasselfsignup, $custdb);
				setCustomerSystemSetting('_hasfacebook', $hasfacebook, $custdb);
				setCustomerSystemSetting('_hastwitter', $hastwitter, $custdb);
				setCustomerSystemSetting('_hassmapi', $hassmapi, $custdb);
				setCustomerSystemSetting('_timeslice', GetFormData($f, $s, 'timeslice'), $custdb);
				setCustomerSystemSetting('organizationfieldname', GetFormData($f, $s, 'organizationfieldname'), $custdb);

				setCustomerSystemSetting('loginlockoutattempts', GetFormData($f, $s, 'loginlockoutattempts'), $custdb);
				setCustomerSystemSetting('logindisableattempts', GetFormData($f, $s, 'logindisableattempts'), $custdb);
				setCustomerSystemSetting('loginlockouttime', GetFormData($f, $s, 'loginlockouttime'), $custdb);

				// this is a hack - subscriber languages English and Spanish are hardcoded - language needs redo post 7.0 release
				QuickUpdate("update persondatavalues set editlock=0 where fieldnum='f03'", $custdb);
				if ($hasselfsignup) {
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

				$oldlanguages = GetFormData($f, $s, "oldlanguages");
				foreach($oldlanguages as $oldlanguage){
					$lang = "Language" . $oldlanguage;
					if ($oldlanguage != 1) {
						if(GetFormData($f, $s, $lang) === "") {
							QuickUpdate("delete from language where id = $oldlanguage", $custdb);
						} else {
							QuickUpdate("update language set name='" . GetFormData($f, $s, $lang) . "' where id = '" . $oldlanguage . "'", $custdb);
						}
					}
				}

				if(GetFormData($f,$s, "newlang")!="" && GetFormData($f,$s, "newlangcode")!=""){
					QuickUpdate("insert into language(name,code) values ('" . trim(GetFormData($f, $s, "newlang")) . "','" . trim(GetFormData($f, $s, "newlangcode")) . "')", $custdb);
				}

				//Logo
				if($logoname){
					$newlogofile = file_get_contents($logoname);
					if($newlogofile){
						QuickUpdate("INSERT INTO content (contenttype, data) values
									('" . $_FILES['uploadlogo']['type'] . "', '" . base64_encode($newlogofile) . "')", $custdb);
						$logocontentid = $custdb->lastInsertId();
						setCustomerSystemSetting('_logocontentid', $logocontentid, $custdb);
					}
				}

				// Login image
				if($loginpicturename){
					$newloginpicturefile = file_get_contents($loginpicturename);
					if($newloginpicturefile){
						QuickUpdate("INSERT INTO content (contenttype, data) values
									('" . $_FILES['uploadloginpicture']['type'] . "', '" . base64_encode($newloginpicturefile) . "')", $custdb);
						$loginpicturecontentid = $custdb->lastInsertId();
						setCustomerSystemSetting('_loginpicturecontentid', $loginpicturecontentid, $custdb);
					}
				}

				// Subscriber Login image
				if ($subscriberloginpicturename) {
					$newsubscriberloginpicturefile = file_get_contents($subscriberloginpicturename);
					if($newsubscriberloginpicturefile){
						QuickUpdate("INSERT INTO content (contenttype, data) values
									('" . $_FILES['uploadsubscriberloginpicture']['type'] . "', '" . base64_encode($newsubscriberloginpicturefile) . "')", $custdb);
						$subscriberloginpicturecontentid = $custdb->lastInsertId();
						setCustomerSystemSetting('_subscriberloginpicturecontentid', $subscriberloginpicturecontentid, $custdb);
					}
				}

				setCustomerSystemSetting('_productname', GetFormData($f, $s, 'productname'), $custdb);
				$theme = DBSafe(GetFormData($f, $s, 'theme'));
				setCustomerSystemSetting('_brandtheme', $theme, $custdb);
				setCustomerSystemSetting('_brandprimary', GetFormData($f, $s, '_brandprimary') ? GetFormData($f, $s, '_brandprimary') : $COLORSCHEMES[$theme]['_brandprimary'], $custdb);
				setCustomerSystemSetting('_brandtheme1', $COLORSCHEMES[$theme]['_brandtheme1'], $custdb);
				setCustomerSystemSetting('_brandtheme2', $COLORSCHEMES[$theme]['_brandtheme2'], $custdb);
				setCustomerSystemSetting('_brandratio', GetFormData($f, $s, '_brandratio') ? GetFormData($f, $s, '_brandratio') : $COLORSCHEMES[$theme]['_brandratio'], $custdb);

				setCustomerSystemSetting('_logoclickurl', TrimFormData($f, $s, "_logoclickurl"), $custdb);

				setCustomerSystemSetting('_supportemail', DBSafe(GetFormData($f, $s, "_supportemail")), $custdb);
				setCustomerSystemSetting('_supportphone', Phone::parse(GetFormData($f, $s, "_supportphone")), $custdb);

				setCustomerSystemSetting('emaildomain', DBSafe($emaildomain), $custdb);

				setCustomerSystemSetting('tinydomain', GetFormData($f, $s, 'tinydomain'), $custdb);
				
				if(getCustomerSystemSetting('_dmmethod', '', true, $custdb)!='asp' && GetFormData($f, $s, "_dmmethod") == 'asp'){
					$aspquery = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
					$aspsharddb = DBConnect($aspquery[0], $aspquery[1], $aspquery[2], "aspshard");
					QuickUpdate("delete from specialtaskqueue where customerid = " . $currentid, $aspsharddb);
					QuickUpdate("update qjob set dispatchtype = 'system' where customerid = " . $currentid . " and status = 'active'", $aspsharddb);
				}

				setCustomerSystemSetting('_dmmethod', DBSafe(GetFormData($f, $s, "_dmmethod")), $custdb);

				setCustomerSystemSetting('softdeletemonths', DBSafe(GetFormData($f, $s, "softdeletemonths")), $custdb);
				setCustomerSystemSetting('harddeletemonths', DBSafe(GetFormData($f, $s, "harddeletemonths")), $custdb);

				if(CheckFormSubmit($f, "Return")){
					redirect("customers.php");
				} else {
					redirect(); //the annoying custinfo above needs to be reloaded
				}
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ) {

	ClearFormData($f);
	if($refresh){
		$languages = DBFindMany("Language","from language order by id", false,false, $custdb);
	}
	PutFormData($f,$s,'name',getCustomerSystemSetting('displayname', "", true, $custdb),"text",1,50,true);
	PutFormData($f,$s,'hostname',$custinfo[3],"text",1,255,true);
	PutFormData($f,$s,'inboundnumber',getCustomerSystemSetting('inboundnumber', false, true, $custdb),"phone",10,10);
	PutFormData($f,$s,'timezone', getCustomerSystemSetting('timezone', false, true, $custdb), "text", 1, 255);

	PutFormData($f,$s,'callerid', Phone::format(getCustomerSystemSetting('callerid', false, true, $custdb)),"phone",10,10, true);
	PutFormData($f,$s,'areacode', getCustomerSystemSetting('defaultareacode', false, true, $custdb),"text", 3, 3);

	$currentmaxphone = getCustomerSystemSetting('maxphones', 1, true, $custdb);
	PutFormData($f,$s,'maxphones',$currentmaxphone,"number",$currentmaxphone,"nomax",true);
	$currentmaxemail = getCustomerSystemSetting('maxemails', 1, true, $custdb);
	PutFormData($f,$s,'maxemails',$currentmaxemail,"number",$currentmaxemail,"nomax",true);
	$currentmaxsms = getCustomerSystemSetting('maxsms', 1, true, $custdb);
	PutFormData($f,$s,'maxsms',$currentmaxsms,"number",$currentmaxsms,"nomax",true);

	PutFormData($f,$s,'autoname', getCustomerSystemSetting('autoreport_replyname', false, true, $custdb),"text",1,255);
	PutFormData($f,$s,'autoemail', getCustomerSystemSetting('autoreport_replyemail', false, true, $custdb),"email",1,255);

	PutFormData($f,$s,'renewaldate', getCustomerSystemSetting('_renewaldate', false, true, $custdb), "text", 1, 255);
	PutFormData($f,$s,'callspurchased', getCustomerSystemSetting('_callspurchased', false, true, $custdb), "text");

	PutFormData($f,$s,"surveyurl", getCustomerSystemSetting('surveyurl', false, true, $custdb), "text", 0, 100);
	$maxusers = getCustomerSystemSetting('_maxusers', false, true, $custdb);
	if($maxusers == "unlimited")
		$maxusers = "";
	PutFormData($f,$s,"maxusers", $maxusers, "number", 0);
	PutFormData($f,$s,"managernote", $custinfo[8], "text", 0, 255);

	// LDAP
	PutFormData($f,$s,"hasldap", getCustomerSystemSetting('_hasldap', false, true, $custdb), "bool", 0, 1);
	// TODO pick a disk agent, store in 'authdiskuuid'
	
	// SMS
	PutFormData($f,$s,"hassms", getCustomerSystemSetting('_hassms', false, true, $custdb), "bool", 0, 1);
	PutFormData($f, $s, "enablesmsoptin", getCustomerSystemSetting('enablesmsoptin', true, true, $custdb), "bool", 0, 1);
	PutFormData($f, $s, "smscustomername", getCustomerSystemSetting('smscustomername', "SchoolMessenger", false, $custdb));

	PutFormData($f,$s,"hassurvey", getCustomerSystemSetting('_hassurvey', true, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,"hasportal", getCustomerSystemSetting('_hasportal', false, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,"hasselfsignup", getCustomerSystemSetting('_hasselfsignup', false, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,"hascallback", getCustomerSystemSetting('_hascallback', false, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,'callbackdefault', getCustomerSystemSetting('callbackdefault', 'inboundnumber', true, $custdb), null, null, null);
	PutFormData($f,$s,"hasenrollment", getCustomerSystemSetting('_hasenrollment', false, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,"hastargetedmessage", getCustomerSystemSetting('_hastargetedmessage', false, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,"hasfacebook", getCustomerSystemSetting('_hasfacebook', false, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,"hastwitter", getCustomerSystemSetting('_hastwitter', false, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,"hassmapi", getCustomerSystemSetting('_hassmapi', false, true, $custdb), "bool", 0, 1);
	PutFormData($f,$s,"organizationfieldname", getCustomerSystemSetting('organizationfieldname', "School", true, $custdb), "text", 0, 255, true);
	PutFormData($f,$s,"timeslice", getCustomerSystemSetting('_timeslice', 450, true, $custdb), "number", 60, 1800);
	PutFormData($f, $s, "loginlockoutattempts", getCustomerSystemSetting('loginlockoutattempts', 5, true, $custdb), "number", 0);
	PutFormData($f, $s, "logindisableattempts", getCustomerSystemSetting('logindisableattempts', 0, true, $custdb), "number", 0);
	PutFormData($f, $s, "loginlockouttime", getCustomerSystemSetting('loginlockouttime', 5, true, $custdb), "number", 0);

	$oldlanguages = array();
	foreach($languages as $language){
		$oldlanguages[] = $language->id;
		$lang = "Language" . $language->id;
		// only allow language changes to non english languages
		if ($language->id > 1) 
			PutFormData($f, $s, $lang, $language->name, "text");
	}
	PutFormData($f, $s, "oldlanguages", $oldlanguages);
	PutFormData($f, $s, "newlangcode", "", "text");
	PutFormData($f, $s, "newlang", "", "text");

	PutFormData($f,$s,"enabled",$custinfo[4], "bool",0,1);

	PutFormData($f,$s,'productname', getCustomerSystemSetting('_productname', "", true, $custdb), "text", 0, 255, true);



	PutFormData($f,"Save","Save", "");
	PutFormData($f,"Return","Save and Return", "");
	PutFormData($f,"Save","Add", "");

	//Color Scheme stuff
	PutFormData($f, $s, "theme", getCustomerSystemSetting('_brandtheme', "Classroom", true, $custdb), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "_brandratio", getCustomerSystemSetting('_brandratio', ".2", true, $custdb), "text", true);
	PutFormData($f, $s, "_brandprimary", getCustomerSystemSetting('_brandprimary', "3e693f", true, $custdb), "text", true);
	PutFormData($f, $s, "_logoclickurl", getCustomerSystemSetting('_logoclickurl', "", true, $custdb), "text", "nomin", "nomax", true);

	PutFormData($f, $s, "_supportemail", getCustomerSystemSetting('_supportemail', "", true, $custdb), "email", "nomin", "nomax", true);
	PutFormData($f, $s, "_supportphone", Phone::format(getCustomerSystemSetting('_supportphone', "", true, $custdb)), "phone", 10, 10, true);

	PutFormData($f, $s, "emaildomain", getCustomerSystemSetting('emaildomain', "", true, $custdb), "text", 0, 255);
	
	PutFormData($f, $s, "tinydomain", getCustomerSystemSetting('tinydomain', "", true, $custdb));

	PutFormData($f, $s, "_dmmethod", getCustomerSystemSetting('_dmmethod', "", true, $custdb), "array", array('asp','hybrid','cs'), null, true);

	PutFormData($f, $s, "oem", $custinfo[5], "text");
	PutFormData($f, $s, "oemid", $custinfo[6], "text");
	PutFormData($f, $s, "nsid", $custinfo[7], "text");

	PutFormData($f, $s, "softdeletemonths", getCustomerSystemSetting('softdeletemonths', "6", false, $custdb));
	PutFormData($f, $s, "harddeletemonths", getCustomerSystemSetting('harddeletemonths', "24", false, $custdb));
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

include_once("nav.inc.php");
?><script src="picker.js?<?=rand()?>"></script><?

//custom newform declaration to catch if manager password is submitted
NewForm($f);

?>
<br>
<table>
<tr>
	<td><? NewFormItem($f, "Save","Save", 'submit');?>
	<? NewFormItem($f, "Return","Save and Return", 'submit');?></td>
</tr>
<tr><td> <b style="color: red;">ENABLED</b> </td><td><? NewFormItem($f, $s, 'enabled', 'checkbox', 40,'',"onchange='if (this.checked == 0) confirm(\"Are you sure you want to DISABLE this customer?\")? this.checked = false : this.checked = true;'") ?>Unchecking this box will disable this customer!  All repeating jobs will be stopped.  All scheduled jobs must be canceled manually.</td></tr>
<tr><td> Delivery Mechanism Method </td>
	<td><?
			NewFormItem($f, $s, '_dmmethod', 'selectstart');
			NewFormItem($f, $s, '_dmmethod', 'selectoption', '--Choose a Method--', '');
			NewFormItem($f, $s, '_dmmethod', 'selectoption', 'CommSuite (fully hosted)', 'asp');
			NewFormItem($f, $s, '_dmmethod', 'selectoption', 'CS + SmartCall + Emergency', 'hybrid');
			NewFormItem($f, $s, '_dmmethod', 'selectoption', 'CS + SmartCall (data only)', 'cs');
			NewFormItem($f, $s, '_dmmethod', 'selectend');
		?>
		<span>
			<?= in_array(getCustomerSystemSetting('_dmmethod', "", true, $custdb), array('hybrid','cs')) ? '<b style="color: red;">Changing this to "CommSuite" will cause jobs to go out on the system!</b>' : "" ?>
		</span>
	</td>
</tr><tr><td>Customer display name: </td><td> <? NewFormItem($f, $s, 'name', 'text', 25, 50); ?></td></tr>
<tr><td>URL path name: </td><td><? NewFormItem($f, $s, 'hostname', 'text', 25, 255); ?> (Must be 5 or more characters)</td></tr>
<tr><td>800 inbound number: </td><td><? NewFormItem($f, $s, 'inboundnumber', 'text', 10, 10); ?></td></tr>
<tr><td>Timezone: </td><td>
<?
	NewFormItem($f, $s, 'timezone', "selectstart");
	foreach($timezones as $timezone) {
		NewFormItem($f, $s, 'timezone', "selectoption", $timezone, $timezone);
	}
	NewFormItem($f, $s, 'timezone', "selectend");
?>
</td></tr>
<tr><td>Default Caller ID: </td><td> <? NewFormItem($f, $s, 'callerid', 'text', 25, 255) ?></td></tr>
<tr><td>Default Area Code: </td><td> <? NewFormItem($f, $s, 'areacode', 'text', 25, 255) ?></td></tr>
<tr><td>AutoReport Name: </td><td><? NewFormItem($f,$s,'autoname','text',25,50); ?></td></tr>
<tr><td>AutoReport Email: </td><td><? NewFormItem($f,$s,'autoemail','text',25,255); ?></td></tr>
<tr><td>Survey URL: </td><td><? NewFormItem($f, $s, 'surveyurl', 'text', 30, 100); ?></td></tr>
<tr><td>Max Phones: </td><td> <? NewFormItem($f, $s, 'maxphones', 'text', 3) ?></td></tr>
<tr><td>Max E-mails: </td><td> <? NewFormItem($f, $s, 'maxemails', 'text', 3) ?></td></tr>
<tr><td>Max SMS: </td><td> <? NewFormItem($f, $s, 'maxsms', 'text', 3) ?></td></tr>
<tr><td>Timeslice(between 60-1800): </td><td> <? NewFormItem($f, $s, 'timeslice', 'text', 3) ?> This is multiplied by 2 to get the number of seconds per job. timelice of 450 = 900 seconds = 15 minutes.</td></tr>
<tr><td>Renewal Date: </td><td><? NewFormItem($f, $s, 'renewaldate', 'text', 25, 255) ?></td></tr>
<tr><td>Calls Purchased: </td><td><? NewFormItem($f, $s, 'callspurchased', 'text', 25, 255) ?></td></tr>
<tr><td>Users Purchased: </td><td><? NewFormItem($f, $s, 'maxusers', 'text', 25, 255) ?></td></tr>
<tr><td width="30%">Failed login attempts to cause lockout:</td><td><? NewFormItem($f,$s,'loginlockoutattempts','text', 2) ?> 1 - 15 attempts, or 0 to disable</td></tr>
<tr><td>Failed login attempts before account disable:</td><td><? NewFormItem($f,$s,'logindisableattempts','text', 2) ?> 1 - 15 attempts, or 0 to disable</td></tr>
<tr><td>Number of minutes for login lockout:</td><td><? NewFormItem($f,$s,'loginlockouttime','text', 2) ?> 1 - 60 minutes</td></tr>
<tr><td></td><th align="left">Language:/ Google and TTS Support:</th>
<?
foreach($languages as $language){
	$lang = "Language" . $language->id;
	?><tr><td><?=$lang?></td><td><div style="display:inline"><?=str_pad($language->code,3)?></div>
	<?
	if ($language->id > 1) {
		NewFormItem($f, $s, $lang, 'text', 25, 50, "id='$lang' onkeyup=\"var s = new getObj('$lang"."_select'); s.obj.selectedIndex = 0;\" onchange=\"var sel = new getObj('$lang"."_select');	for (var i in sel.obj.options) if (this.value == sel.obj.options[i].value) sel.obj.selectedIndex = i;\"");
		?>
		<select disabled id='<?="$lang"."_select"?>' onchange="if (this.selectedIndex != 0) {var o = new getObj('<?=$lang?>'); o.obj.value = this.options[this.selectedIndex].value;}">
		<option value=0> -- No Translation Support -- </option>
		<?foreach ($googlangs as $code => $googlang) {
			$ttsLangSup = '';
			if (isset($ttslangs[strtolower($googlang)]))
				$ttsLangSup .= " (TTS Support)";
			?>
			<option value="<?= str_pad($code,3) . " " . $googlang?>" <?=($code == $language->code)?"selected":""?>><?=$googlang . $ttsLangSup?></option>
		<?}?>
		</select>
	<?} else {
		echo $language->name;
		//This Language should always be set to English 
	}?>
	</td></tr><?
}
?>
<tr ><td>New Language: </td><td style="border: 1px solid black;">
		To add a new language, select a commonly used language or use the search box.<br>
		<select id='newlanginputselect' onchange="var o = new getObj('newlanginput');var h = new getObj('newlangcode');if (this.selectedIndex !== 0) { var value = this.options[this.selectedIndex].value; o.obj.value = value.substring(4); h.obj.value = value.substring(0,3); $('newlangcodedisp').update(value.substring(0,3));}">
		<option value=0> -- Select Common Language -- </option>
		<?foreach ($googlangs as $code => $googlang) {
			$ttsLangSup = '';
			if (isset($ttslangs[strtolower($googlang)]))
				$ttsLangSup .= " (TTS Support)";
			?>

			<option value="<?= str_pad($code,3) . " " . $googlang?>" ><?=$googlang . $ttsLangSup?></option>
		<?}?>
		</select>
		<div>
		Search: <input id="searchbox" type="text" size="15" /> (type search term and press ENTER)
		</div>
		<table id="searchresult" style=""><tr><td></td></tr></table>
		
		<div style="display:inline" id="newlangcodedisp">___</div><?
		NewFormItem($f, $s, 'newlangcode', 'hidden', 25, 50, "id='newlangcode'");
		NewFormItem($f, $s, 'newlang', 'text', 25, 50, "id='newlanginput' onkeyup=\"var s = new getObj('newlanginputselect'); s.obj.selectedIndex = 0;\"")?>
		<? NewFormItem($f, "Save","Add", 'submit');?>
</td></tr>

<tr><td> Has LDAP </td><td><? NewFormItem($f, $s, 'hasldap', 'checkbox') ?> LDAP</td></tr>

<tr><td> Has SMS </td><td><? NewFormItem($f, $s, 'hassms', 'checkbox') ?> SMS</td></tr>
<tr><td> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enable SMS Opt-in </td><td><? NewFormItem($f, $s, 'enablesmsoptin', 'checkbox') ?> Opt-in</td></tr>
<tr><td> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;SMS Customer Name: </td><td><? NewFormitem($f, $s, 'smscustomername', 'text', 25, 50) ?></td></tr>

<tr><td> Has Survey </td><td><? NewFormItem($f, $s, 'hassurvey', 'checkbox') ?> Survey</td></tr>
<tr><td> Has Contact Manager </td><td><? NewFormItem($f, $s, 'hasportal', 'checkbox') ?> Contact Manager</td></tr>
<tr><td> Has Self-Signup </td><td><? NewFormItem($f, $s, 'hasselfsignup', 'checkbox') ?> Self-Signup</td></tr>
<tr><td> Has Callback </td><td><? NewFormItem($f, $s, 'hascallback', 'checkbox') ?> Callback</td></tr>
<tr><td> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Callback CallerID </td><td>
<?
	NewFormItem($f,$s,'callbackdefault','selectstart');
		NewFormItem($f,$s,'callbackdefault','selectoption','Inbound Number','inboundnumber');
		NewFormItem($f,$s,'callbackdefault','selectoption','Default CallerID','callerid');
	NewFormItem($f,$s,'callbackdefault','selectend');
?>
</td></tr>
<tr><td> Has Enrollment </td><td><? NewFormItem($f, $s, 'hasenrollment', 'checkbox') ?> Enrollment </td></tr>
<tr><td> Has Classroom Comments </td><td><? NewFormItem($f, $s, 'hastargetedmessage', 'checkbox') ?> Classroom Comments</td></tr>
<tr><td> Has Facebook </td><td><? NewFormItem($f, $s, 'hasfacebook', 'checkbox') ?> Facebook</td></tr>
<tr><td> Has Twitter </td><td><? NewFormItem($f, $s, 'hastwitter', 'checkbox') ?> Twitter</td></tr>
<tr><td> Has SMAPI </td><td><? NewFormItem($f, $s, 'hassmapi', 'checkbox') ?> SMAPI</td></tr>
<tr><td>Notes: </td><td><? NewFormitem($f, $s, 'managernote', 'textarea', 30) ?></td></tr>
<tr><td>OEM: </td><td><? NewFormitem($f, $s, 'oem', 'text', 10, 50) ?></td></tr>
<tr><td>OEM ID: </td><td><? NewFormitem($f, $s, 'oemid', 'text', 10, 50) ?></td></tr>
<tr><td>NetSuite ID: </td><td><? NewFormitem($f, $s, 'nsid', 'text', 10, 50) ?></td></tr>

<tr>
	<td>Logo:</td>
	<td><img src='customerlogo.img.php?id=<?=$currentid?>'></td>
</tr>
<tr>
	<td>New Logo:</td>
	<td><input type='file' name='uploadlogo' size='30'></td>
</tr>

<tr><td> 'Organization' Display Name: </td><td><? NewFormItem($f, $s, 'organizationfieldname', 'text', 10, 50) ?></td></tr>

<tr>
	<td>ProductName:</td>
	<td><? NewFormItem($f,$s,'productname', 'text', 30, 255)?></td>
</tr>
<tr>
	<td>Theme:</td>
	<td>
		<?
			NewFormItem($f, $s, "theme", "selectstart", null, null, "onchange='resetPrimaryAndRatio(this.value)'");
			if(count($COLORSCHEMES)){
				foreach($COLORSCHEMES as $index => $scheme){
					NewFormItem($f, $s, "theme", "selectoption", $scheme['displayname'], $index);
				}
			}
			NewFormItem($f, $s, "theme", "selectend");
		?>
	</td>
</tr>
<tr>
	<td>Primary Color(in hex):</td>
	<td><? NewFormItem($f, $s, "_brandprimary", "text", 0, 10, "id='brandprimary'") ?><img src="img/sel.gif" onclick="TCP.popup(new getObj('brandprimary').obj)"/></td>
</tr>
<tr>
	<td>Ratio of Primary to Background</td>
	<td><? NewFormItem($f, $s, "_brandratio", "text", 0, 3, "id='brandratio'") ?></td>
</tr>
<tr>
	<td>Logo Click URL</td>
	<td><? NewFormItem($f, $s, "_logoclickurl", "text", 30, 255); ?></td>
</tr>

<tr>
	<td>Login Picture:</td>
	<td><img width="100px" src='customerloginpicture.img.php?id=<?=$currentid?>'></td>
</tr>
<tr>
	<td>New Login Picture:</td>
	<td><input type='file' name='uploadloginpicture' size='30'></td>
</tr>

<tr>
	<td>Subscriber Login Picture:</td>
	<td><img width="100px" src='customerloginpicture.img.php?subscriber&id=<?=$currentid?>'></td>
</tr>
<tr>
	<td>New Subscriber Login Picture:</td>
	<td><input type='file' name='uploadsubscriberloginpicture' size='30'></td>
</tr>

<tr>
	<td>Support Email:</td>
	<td><? NewFormItem($f, $s, "_supportemail", "text", 30, 100); ?></td>
</tr>

<tr>
	<td>Support Phone:</td>
	<td><? NewFormItem($f, $s, "_supportphone", "text", 14); ?></td>
</tr>

<tr>
	<td>Email Domain:</td>
	<td><? NewFormItem($f, $s, "emaildomain", "text", 30, 255); ?></td>
</tr>

<tr>
	<td>Tiny Domain:</td>
	<td>
<?
	NewFormItem($f, $s, "tinydomain", "selectstart");
	foreach ($SETTINGS['feature']['tinydomain'] as $tinydomain)
		NewFormItem($f,$s,'tinydomain','selectoption',$tinydomain,$tinydomain);
	NewFormItem($f,$s,'tinydomain','selectend');
?>
	</td>
</tr>

<tr>
	<td>Auto Message Expire (soft delete)</td>
	<td>
<?
	NewFormItem($f,$s,'softdeletemonths','selectstart');
		NewFormItem($f,$s,'softdeletemonths','selectoption','Disabled','0');
		NewFormItem($f,$s,'softdeletemonths','selectoption','6 Months','6');
		NewFormItem($f,$s,'softdeletemonths','selectoption','12 Months','12');
		NewFormItem($f,$s,'softdeletemonths','selectoption','18 Months','18');
	NewFormItem($f,$s,'softdeletemonths','selectend');
?>
	</td>
</tr>

<tr>
	<td>Auto Report Expire (<font style="color: red">HARD</font> delete)</td>
	<td>
<?
	NewFormItem($f,$s,'harddeletemonths','selectstart');
		NewFormItem($f,$s,'harddeletemonths','selectoption','Disabled','0');
		NewFormItem($f,$s,'harddeletemonths','selectoption','6 Months','6');
		NewFormItem($f,$s,'harddeletemonths','selectoption','12 Months','12');
		NewFormItem($f,$s,'harddeletemonths','selectoption','18 Months','18');
		NewFormItem($f,$s,'harddeletemonths','selectoption','24 Months','24');
		NewFormItem($f,$s,'harddeletemonths','selectoption','36 Months','36');
		NewFormItem($f,$s,'harddeletemonths','selectoption','48 Months','48');
	NewFormItem($f,$s,'harddeletemonths','selectend');
?>
	</td>
</tr>

<tr>
	<td><? NewFormItem($f, "Save","Save", 'submit');?>
	<? NewFormItem($f, "Return","Save and Return", 'submit');?></td>
</tr>

</table>

<?

EndForm();
include_once("navbottom.inc.php");

?>
<script>

	var colorscheme = new Array();

<?
	//Make js array of colorschemes
	foreach($COLORSCHEMES as $index => $properties){
?>
		colorscheme['<?=$index?>'] = new Array();
		colorscheme['<?=$index?>']['_brandprimary'] = '<?=$properties['_brandprimary']?>';
		colorscheme['<?=$index?>']['_brandratio'] = '<?=$properties['_brandratio']?>';
<?
	}
?>

	function resetPrimaryAndRatio(value){

		new getObj('brandprimary').obj.value = colorscheme[value]['_brandprimary'];
		new getObj('brandratio').obj.value = colorscheme[value]['_brandratio'];
	}


function addlang(code,name) {
	$('newlangcode').value = code;
	$('newlanginput').value = name;
	var s = new getObj('newlanginputselect'); s.obj.selectedIndex = 0;
	$('searchresult').update('');
	$('newlangcodedisp').update(code);
}

function search(event) {
	if (Event.KEY_RETURN == event.keyCode) {
		event.stop();
		var searchtxt = event.target.getValue();

		new Ajax.Request('languagesearch.php',
		{
			method:'get',
			parameters: {searchtxt: searchtxt},
			onSuccess: function(response){
				var result = response.responseJSON;
				var items = new Element('tbody',{width:'100%'});
				var header = new Element('tr').addClassName("listHeader");

				if(result) {
					header.insert(new Element('th').update('Code'));
					header.insert(new Element('th',{align:'left'}).update('Language'));

					items.insert(header);
					var i = 0;
					$H(result).each(function(itm) {
						var row = new Element('tr');
						if(i%2)
							row.addClassName("listAlt");
						row.insert(new Element('td',{align:"right"}).update(itm.key));
						row.insert(new Element('td').update('<a href="#" onclick="addlang(\'' + itm.key + '\',\'' + itm.value + '\');return false;">' + itm.value + '</a>'));
						items.insert(row);
						i++;
					});
				} else {
					header.insert(new Element('th').update('No Language Found containing the search sting "' + searchtxt + '"'));
					items.insert(header);

				}
				$('searchresult').update(items);

			}
		});
	}
}


document.observe("dom:loaded", function() {
	var searchBox = $('searchbox');
	searchBox.observe('keypress', search);
});
</script>
