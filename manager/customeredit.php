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
require_once("createtemplates.php");
require_once("../inc/themes.inc.php");
require_once("../obj/FormBrandTheme.obj.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");
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

class LogoRadioButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<div id='.$n.' class="radiobox"><table>';
		$counter = 1;
		foreach ($this->args['values'] as $radiovalue => $radiohtml) {
			$id = $n.'-'.$counter;
			$str .= '<tr><td><input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /></td><td><label for="'.$id.'"><div style="width: 100%; border: 2px outset; background-color: white; color: black; margin-left: 0px;">'.($radiohtml).'</div></label></td></tr>
				';
			$counter++;
		}
		$str .= '</table></div>';
		return $str;
	}
}

class LanguagesItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
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
		$str = "
				<input id='$n' name='$n' type='hidden' value='$value' />
				<div id='$n-removelang' style='display:none'>" . icon_button("Remove", "delete") . "</div>
				<div id='$n-disp'></div>
				<table><td style=\"border: 1px solid black;\">
				Language Lookup:<br />
				<table><tr><td>
				<select id='newlanginputselect' onchange='languageselect();'>
				<option value=0> -- Select Common Language -- </option>";
				foreach ($googlangs as $code => $googlang) {
					$ttsLangSup = '';
					if (isset($this->args['ttslangs'][strtolower($googlang)]))
						$ttsLangSup .= " (TTS Support)";
					$str .= "<option value='" . str_pad($code,3) . " $googlang' >$googlang $ttsLangSup</option>";
				}
				$str .= '
				</select>
				</td><td>&nbsp;&nbsp;&nbsp;or&nbsp;&nbsp;&nbsp;</td><td><input id="searchbox" type="text" size="30" /></td><td>' . icon_button("Search", "magnifier","searchlanguages();") . '</td></tr></table>
				<table id="searchresult" style=""><tr><td></td></tr></table>
				<table style="display:inline;"><tr><td>Code: 
				<div style="display:inline;font-weight: bold;" id="newlangcodedisp">N/A</div> Name: 
				<input id="newlangcode" type="hidden" maxlength="50" size="25" />
				<input id="newlanginput" type="text" maxlength="50" size="25" />
				</td><td>' . icon_button("Add", "add","changelanguage('$n')") . '</td></tr></table>
				</td></tr>
				</table>
				';
		return $str;
	}
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
		$str = "
			function updatelanguage(code,name) {
				var langs = \$H($('$n').value.evalJSON(true));
				langs.set(code.strip(),name.strip());
				$('$n').value = langs.toJSON();		
			}
			function removelanguage(code) {
				var langs = \$H($('$n').value.evalJSON(true));
				langs.unset(code.strip());
				$('$n').value = langs.toJSON();
				renderlanguages();
			}
			function renderlanguages() {
				var langs = \$H($('$n').value.evalJSON(true));
				var table = new Element('table');
				langs.each(function(lang) {

					var tablecontent = new Element('tr');
					var input = new Element('input', { 'type': 'text', 'value': lang.value});
					
					if (lang.key != 'en') {
						input.observe('change',function(e) {
							updatelanguage(lang.key,e.element().getValue());
						});
					} else {
						input.disabled = true;
					}
					tablecontent.insert(new Element('td').insert(input));

					if (lang.key != 'en') {
						var removebutton = new Element('div').update($('$n-removelang').innerHTML);
						removebutton.observe('click',function(e) {
							removelanguage(lang.key);
						});
						tablecontent.insert(new Element('td').insert(removebutton));		
					}
					table.insert(tablecontent);
				});	
				$('$n-disp').update(table);		
				form_do_validation($('{$this->form->name}'), $('$n'));
			}
			
			document.observe('dom:loaded', renderlanguages);
				
			function languageselect() {
				var s = $('newlanginputselect');
				if (s.selectedIndex !== 0) {
					var value = s.options[s.selectedIndex].value;
					$('newlanginput').value = value.substring(4);
					$('newlangcode').value = value.substring(0,3);
					$('newlangcodedisp').update(value.substring(0,3));
				}
			}
			
			function addlang(code,name) {
				$('newlangcode').value = code;
				$('newlanginput').value = name;
				$('newlanginputselect').selectedIndex = 0;
				$('searchresult').update('');
				$('newlangcodedisp').update(code);
			}
			
			function changelanguage(formitemid){
				var code = $('newlangcode').value;
				var language = $('newlanginput').value;
				if (code && language) {
					var langs = \$H($(formitemid).value.evalJSON(true));
					langs.set(code.strip(),language.strip());
					$(formitemid).value = langs.toJSON();
				}
				$('newlanginputselect').selectedIndex = 0;
				$('searchresult').update('');
				$('newlangcodedisp').update('N/A');
				$('newlangcode').value = '';
				$('newlanginput').value = '';
				renderlanguages();
			}	
	
			function searchlanguages() {
				var searchtxt = $('searchbox').value;
				new Ajax.Request('languagesearch.php',
				{
					method:'get',
					parameters: {searchtxt: searchtxt},
					onSuccess: function(response){
						var result = response.responseJSON;
						var items = new Element('tbody',{width:'100%'});
						var header = new Element('tr').addClassName('listHeader');
			
						if(result) {
							header.insert(new Element('th').update('Code'));
							header.insert(new Element('th',{align:'left'}).update('Language'));
			
							items.insert(header);
							var i = 0;
							\$H(result).each(function(itm) {
								var row = new Element('tr');
								if(i%2)
									row.addClassName('listAlt');
								row.insert(new Element('td',{align:'right'}).update(itm.key));
								row.insert(new Element('td').update('<a href=\"#\" onclick=\"addlang(\'' + itm.key + '\',\'' + itm.value + '\');return false;\">' + itm.value + '</a>'));
								items.insert(row);
								i++;
							});
						} else {
							header.insert(new Element('th').update('No Language Found containing the search sting \"' + searchtxt + '\"'));
							items.insert(header);
			
						}
						$('searchresult').update(items);
					}
				});
			}";
		return $str;
	}
}

class ValLanguages extends Validator {
	function validate ($value) {
		$languages = json_decode($value,true);
		if(!is_array($languages) || !isset($languages['en'])) {
			return 'English is required for ' . $this->label;
		}
		return true;
	}
	function getJSValidator () {
		return
		'function (name, label, value, args) {
			var langs = $H(value.evalJSON(true));
			if (langs.length == 0)
				return label + " is required";
			if (!langs.get("en"))
				return "English is required for " + label;

			return true;
		}';
	}
}

class ValInboundNumber extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$query = "select count(*) from customer where inboundnumber=?";
		if (($args["customerid"] && QuickQuery($query . " and id!=?",false,array($value,$args["customerid"]))) ||
			(!$args["customerid"] && QuickQuery($query,false,array($value)))) {		
			return 'Number is already in use for ' . $this->label;
		}
		return true;
	}
}
class ValUrlComponent extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {		
		// Allow legacy urlcomponents to be sorter than 5 characters but all new ones should be 5 or more
		if (($args["urlcomponent"] && strlen($args["urlcomponent"]) >= 5 && strlen($value) < 5) ||
			(!$args["urlcomponent"] && strlen($value) < 5)) {
			return 'URL path must be 5 or more characters';
		}		
		
		$query = "select count(*) from customer where urlcomponent=?";
		if (($args["customerid"] && QuickQuery($query . " and id!=?",false,array($value,$args["customerid"]))) ||
		(!$args["customerid"] && QuickQuery($query,false,array($value)))) {
			return 'URL path is already in use';
		}
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
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
					'areacode' => '',
					'inboundnumber' => '',
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
					'_hasselfsignup' => '',
					'_hasportal' => '',
					'_hasfacebook' => '0',
					'_hastwitter' => '0',
					'autoreport_replyname' => 'SchoolMessenger',
					'autoreport_replyemail' => 'autoreport@schoolmessenger.com',
					'_renewaldate' => '',
					'_callspurchased' => '',
					'_maxusers' => '',
					'_timeslice' => '450',
					'loginlockoutattempts' => '5',
					'logindisableattempts' => '0',
					'loginlockouttime' => '5',
					'_brandtheme' => 'classroom',
					'_brandprimary' => '3e693f',
					'_brandratio' => '.2');

$customerid = null;
if (isset($_SESSION['customerid'])) {
	$customerid = $_SESSION['customerid'];
	$query = "select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled, c.oem, c.oemid, c.nsid, c.notes from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'";
	$custinfo = QuickQueryRow($query,true);
	$custdb = DBConnect($custinfo["dbhost"], $custinfo["dbusername"], $custinfo["dbpassword"], "c_$customerid");
	if (!$custdb) {
		exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid");
	}

	$query = "select name,value from setting";
	$settings = array_merge($settings, QuickQueryList($query,true,$custdb));
}

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

$logos = array(); 
if ($customerid && $settings['_logocontentid'] != '') {
	$logos = array( "Saved" => 'No change - Preview: <div style="display:inline;"><img src="customerlogo.img.php?id=' . $customerid .'" width="70px" alt="" /></div>');
}
// Content for logo selector
$logos += array( 	"AutoMessenger" => '<img src="mimg/auto_messenger.jpg" alt="AutoMessenger" title="AutoMessenger" />',
					"SchoolMessenger" => '<img src="mimg/logo_small.gif" alt="SchoolMessenger" title="SchoolMessenger" />',
					"Skylert" => '<img src="mimg/sky_alert.jpg" alt="Skylert" title="Skylert" />');

// Locations and mimetype for default logos
$defaultlogos = array(
					"AutoMessenger" => array("filelocation" => "mimg/auto_messenger.jpg",
											"filetype" => "image/jpg"),
					"SchoolMessenger" => array("filelocation" => "mimg/logo_small.gif",
											"filetype" => "image/gif"),
					"Skylert" => array("filelocation" => "mimg/sky_alert.jpg",
										"filetype" => "image/jpg")
);


$shards = QuickQueryList("select id, name from shard where not isfull order by id",true);

$dmmeathod = array('' => '--Choose a Method--', 'asp' => 'CommSuite (fully hosted)','hybrid' => 'CS + SmartCall + Emergency','cs' => 'CS + SmartCall (data only)');

$helpstepnum = 1;
$helpsteps = array("TODO");
$formdata = array(_L('Basics'));

$formdata["enabled"] = array(
						"label" => _L('Enabled'),
						"value" => isset($custinfo)?$custinfo["enabled"]:"",
						"validators" => array(),
						"control" => array("CheckBox"),
						"helpstep" => $helpstepnum
);

//Unable to change shard on this form
if (!$customerid) {
	$formdata["shard"] = array(
							"label" => _L('Shard'),
							"value" => "",
							"validators" => array(
								array("ValRequired"),
								array("ValInArray", "values" => array_keys($shards))
								),
							"control" => array("SelectMenu", "values" => array("" =>_L("-- Select a Shard --")) + $shards),
							"helpstep" => $helpstepnum
	);
}

$formdata["dmmethod"] = array(
						"label" => _L('DM Method'),
						"value" => $settings['_dmmethod'],
						"validators" => array(
							array("ValRequired"),
							array("ValInArray", "values" => array_keys($dmmeathod))
							),
						"control" => array("SelectMenu", "values" => array("" =>_L("-- Select a Method --")) + $dmmeathod),
						"helpstep" => $helpstepnum
);
$formdata["timezone"] = array(
						"label" => _L('Time zone'),
						"value" => $settings['timezone'],
						"validators" => array(
							array("ValRequired"),
							array("ValInArray", "values" => $timezones)
						),
						"control" => array("SelectMenu", "values" => array_merge(array("" =>_L("-- Select a Timezone --")),array_combine($timezones,$timezones))),
						"helpstep" => $helpstepnum
);

$formdata["displayname"] = array(
						"label" => _L('Display Name'),
						"value" => $settings['displayname'],
						"validators" => array(
							array("ValRequired"),
							array("ValLength","min" => 3,"max" => 50)
						),
						"control" => array("TextField","size" => 30, "maxlength" => 51),
						"helpstep" => $helpstepnum
					);

$formdata["organizationfieldname"] = array(
						"label" => _L("'Organization' Display Name"),
						"value" => $settings['organizationfieldname'],
						"validators" => array(
							array("ValLength","min" => 3,"max" => 50)
						),
						"control" => array("TextField","size" => 30, "maxlength" => 51),
						"helpstep" => $helpstepnum
);

$formdata["urlcomponent"] =	array(
						"label" => _L('URL Path'),
						"value" => $settings['urlcomponent'],
						"validators" => array(
							array("ValRequired"),
							array("ValLength","max" => 30),
							array("ValUrlComponent", "customerid" => $customerid, "urlcomponent" => $settings['urlcomponent'])
						),
						"control" => array("TextField","size" => 30, "maxlength" => 51),
						"helpstep" => $helpstepnum
					);

$formdata["logo"] = array(
						"label" => _L('Logo'),
						"value" => ($customerid && $settings['_logocontentid'] != '')?"Saved":'',
						"validators" => array(
							array("ValRequired"),
							array("ValInArray", "values" => array_keys($logos))
							),
						"control" => array("LogoRadioButton", "values" => $logos),
						"helpstep" => $helpstepnum
					);
$formdata["logoclickurl"] = array(
						"label" => _L('Logo Click URL'),
						"value" => $settings['_logoclickurl'],
						"validators" => array(
							array("ValRequired"),
							array("ValLength","min" => 3,"max" => 50)
						),
						"control" => array("TextField","size" => 30, "maxlength" => 51),
						"helpstep" => $helpstepnum
);
$formdata["productname"] = array(
						"label" => _L('Brand'),
						"value" => $settings['_productname'],
						"validators" => array(
							array("ValRequired"),
							array("ValLength","min" => 3,"max" => 50)
						),
						"control" => array("TextField","size" => 30, "maxlength" => 51),
						"helpstep" => $helpstepnum
					);

$formdata["supportemail"] = array(
						"label" => _L('Support Email'),
						"value" => $settings['_supportemail'],
						"validators" => array(
							array("ValRequired"),
							array("ValLength","max" => 255),
							array("ValEmail")
						),
						"control" => array("TextField","maxlength"=>255,"min"=>3,"size"=>35),
						"helpstep" => $helpstepnum
);
$formdata["supportphone"] = array(
						"label" => _L('Support Phone'),
						"value" => $settings['_supportphone'],
						"validators" => array(
							array("ValRequired"),
							array("ValPhone")
						),
						"control" => array("TextField","size" => 15, "maxlength" => 20),
						"helpstep" => $helpstepnum
);

$formdata["callerid"] = array(
						"label" => _L('Default Caller ID'),
						"value" => $settings['callerid'],
						"validators" => array(
							array("ValRequired"),
							array("ValPhone")
						),
						"control" => array("TextField","size" => 15, "maxlength" => 20),
						"helpstep" => $helpstepnum
);

$formdata["areacode"] = array(
						"label" => _L('Default Area Code'),
						"value" => $settings['areacode'],
						"validators" => array(
							array('ValNumber')
						),
						"control" => array("TextField","size" => 3, "maxlength" => 3),
						"helpstep" => $helpstepnum
);

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


$formdata["maxphones"] = array(
						"label" => _L('Max Phones'),
						"value" => $settings['maxphones'],
						"validators" => array(
							array('ValNumber')
						),
						"control" => array("TextField","size" => 4, "maxlength" => 4),
						"helpstep" => $helpstepnum
);

$formdata["maxemails"] = array(
						"label" => _L('Max Emails'),
						"value" => $settings['maxemails'],
						"validators" => array(
							array('ValNumber')
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
							array("ValLength","max" => 255),
							array("ValDomainList")
						),
						"control" => array("TextField","maxlength"=>255,"min"=>3,"size"=>35),
						"helpstep" => $helpstepnum
);

$tinydomains = $SETTINGS['feature']['tinydomain'];
$formdata["tinydomain"] = array(
						"label" => _L('Tiny Domain'),
						"value" => $settings['tinydomain'],
						"validators" => array(
							array("ValInArray", "values" => $tinydomains)
						),
						"control" => array("SelectMenu", "values" => array("" =>_L("-- Select a Domain --")) + array_combine($tinydomains, $tinydomains)),
						"helpstep" => $helpstepnum
);

$automessageexpire = array(
	"0" => "Disabled",
	"6" => "6 Months",
	"12" => "12 Months",
	"18" => "18 Months");

$formdata["softdeletemonths"] = array(
						"label" => _L('Auto Message Expire (soft delete)'),
						"value" => $settings['softdeletemonths'],
						"validators" => array(
							array("ValInArray", "values" => array_keys($automessageexpire))
						),
						"control" => array("SelectMenu", "values" => $automessageexpire),
						"helpstep" => $helpstepnum
);

$autoreportexpire = array(
	"0" => "Disabled",
	"6" => "6 Months",
	"12" => "12 Months",
	"18" => "18 Months",
	"24" => "24 Months",
	"36" => "36 Months",
	"48" => "48 Months");

$formdata["harddeletemonths"] = array(
						"label" => _L('Auto Report Expire (HARD delete)'),
						"value" => $settings['harddeletemonths'],
						"validators" => array(
							array("ValInArray", "values" => array_keys($autoreportexpire))
						),
						"control" => array("SelectMenu", "values" => $autoreportexpire),
						"helpstep" => $helpstepnum
);


$formdata["notes"] = array(
						"label" => _L('Notes'),
						"value" => isset($custinfo)?$custinfo["notes"]:"",
						"validators" => array(),
						"control" => array("TextArea", "rows" => 3, "cols" => 100),
						"helpstep" => $helpstepnum
);

$formdata[] = _L("Languages");

$languages = $customerid?QuickQueryList("select code, name from language",true,$custdb):array("en" => "English", "es" => "Spanish");
$formdata["languages"] = array(
						"label" => _L('Languages'),
						"value" => json_encode($languages),
						"validators" => array(
							array("ValRequired"),
							array("ValLanguages")),
						"control" => array("LanguagesItem", 
							"ttslangs" => $customerid?QuickQueryList("select language,id from ttsvoice", true, $custdb):array()),
						"helpstep" => $helpstepnum
);

$formdata[] = _L("SMS");
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
							array('ValNumber')
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
							array("ValLength","max" => 50),
							array("ValRegExp","pattern" => getSmsRegExp())
						),
						"control" => array("TextField","maxlength"=>50,"size"=>25),
						"helpstep" => $helpstepnum
);
$formdata[] = _L("API");
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
							array('ValNumber'),
							array("ValLength","max" => 50)
						),
						"control" => array("TextField","maxlength"=>50,"size"=>4),
						"helpstep" => $helpstepnum
);
$formdata["oemid"] = array(
						"label" => _L('OEM id'),
						"value" => isset($custinfo)?$custinfo["oemid"]:"",
						"validators" => array(
							array('ValNumber'),
							array("ValLength","max" => 50)
						),
						"control" => array("TextField","maxlength"=>50,"size"=>4),
						"helpstep" => $helpstepnum
);



$formdata[] = _L("Callback");

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

$formdata["portal"] = array(
						"label" => _L('Portal'),
						"value" => $settings['_hasportal']?"contactmanager":($settings['_hasselfsignup']?"selfsignup":"none"),
						"validators" => array(),
						"control" => array("RadioButton","values" => array("none" => "None", "contactmanager" => "Contact Manager", "selfsignup" => "Self-Signup")),
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

$formdata["hasclassroom"] = array(
						"label" => _L('Has Classroom Comments'),
						"value" => $settings['_hastargetedmessage'],
						"validators" => array(),
						"control" => array("CheckBox"),
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

$formdata[] = _L("Misc. Settings");
$formdata["nsid"] = array(
						"label" => _L('NetSuite ID'),
						"value" => isset($custinfo)?$custinfo["nsid"]:"",
						"validators" => array(
							array('ValNumber'),
							array("ValLength","max" => 50)
						),
						"control" => array("TextField","maxlength"=>50,"size"=>4),
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
							array('ValNumber', "min" => 60, "max" => 1800)
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

$helpsteps[$helpstepnum++] = _L("Choose a theme for the user interface.<br><br>Additionally, you can select a color which will be blended into the grey parts of certain interface components. The amount of tint is determined by the shader ratio.<br><br> Setting the theme will reset the color and ratio options to the theme defaults.");
$formdata["brandtheme"] = array(
						"label" => _L("Default Theme"),
						"fieldhelp" => _L("Use this to select a different theme for the user interface. Themes can be customized with alternate primary colors (in hex) and primary to background color ratio settings."),
						"value" => json_encode(array(
							"theme"=>$settings['_brandtheme'],
							"color"=>$settings['_brandprimary'],
							"ratio"=>$settings['_brandratio'],
							"customize"=>true
						)),
						"validators" => array(
							array("ValRequired"),
							array("ValBrandTheme", "values" => array_keys($COLORSCHEMES))),
						"control" => array("BrandTheme","values"=>$COLORSCHEMES,"toggle"=>false),
						"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L("Save"),"save","tick"),submit_button(_L("Save and Return"),"done","tick"),
				icon_button(_L('Cancel'),"cross",null,"customers.php"));
$form = new Form("newcustomer",$formdata,$helpsteps,$buttons);
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
		Query("BEGIN");
		// Craete new customer if It does not exist 
		if (!$customerid) {			
			//choose shard info based on selection
			$shardinfo = QuickQueryRow("select id, dbhost, dbusername, dbpassword from shard where id = ?", true,false,array($postdata["shard"]));
			$shardid = $shardinfo['id'];
			$shardhost = $shardinfo['dbhost'];
			$sharduser = $shardinfo['dbusername'];
			$shardpass = $shardinfo['dbpassword'];
			
			$dbpassword = genpassword();
			$limitedpassword = genpassword();
			QuickUpdate("insert into customer (urlcomponent, shardid, dbpassword, limitedpassword)
															values (?, ?, ?, ?)", false, array($postdata["urlcomponent"], $shardid, $dbpassword, $limitedpassword) )
			or dieWithError("failed to insert customer into auth server", $_dbcon);
			
			$customerid = $_dbcon->lastInsertId();
			$custdbname = "c_$customerid";
			$limitedusername = "c_".$customerid."_limited";
			QuickUpdate("update customer set dbusername = '" . $custdbname . "', limitedusername = '" . $limitedusername . "' where id = '" . $customerid . "'");
			
			$custdb = DBConnect($shardhost, $sharduser, $shardpass, "aspshard");
			QuickUpdate("create database $custdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",$custdb)
			or dieWithError("Failed to create new DB ".$custdbname, $custdb);
			$custdb->query("use ".$custdbname)
			or dieWithError("Failed to connect to DB ".$custdbname, $custdb);
			
			// customer db user
			$grantedhost = '%';
			if (isset($SETTINGS['feature']['should_grant_local']) && $SETTINGS['feature']['should_grant_local']) {
				$grantedhost = 'localhost';
			}
			QuickUpdate("drop user '$custdbname'@'$grantedhost'", $custdb); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
			QuickUpdate("create user '$custdbname'@'$grantedhost' identified by '$dbpassword'", $custdb);
			QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $custdbname . * to '$custdbname'@'$grantedhost'", $custdb);
			
			// create customer tables
			$tablequeries = explode("$$$",file_get_contents("../db/customer.sql"));
			$tablequeries = array_merge($tablequeries, explode("$$$",file_get_contents("../db/createtriggers.sql")));
			$tablequeries = array_merge($tablequeries, explode("$$$",file_get_contents("../db/targetedmessages.sql")));
			foreach ($tablequeries as $tablequery) {
				if (trim($tablequery)) {
					$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
					Query($tablequery,$custdb)
					or dieWithError("Failed to execute statement \n$tablequery\n\nfor $custdbname", $custdb);
				}
			}
			
			// subscriber db user
			createLimitedUser($limitedusername, $limitedpassword, $custdbname, $custdb, $grantedhost);
			
			// 'schoolmessenger' user
			createSMUserProfile($custdb, $custdbname);
			
			$query = "INSERT INTO `fieldmap` (`fieldnum`, `name`, `options`) VALUES
										('f01', 'First Name', 'searchable,text,firstname,subscribe,dynamic'),
										('f02', 'Last Name', 'searchable,text,lastname,subscribe,dynamic'),
										('f03', 'Language', 'searchable,multisearch,language,subscribe,static')";
			QuickUpdate($query, $custdb) or dieWithError("SQL:" . $query, $custdb);

			$query = "INSERT INTO `language` (`name`,`code`) VALUES
													('English','en'),
													('Spanish','es')";
			QuickUpdate($query, $custdb) or dieWithError("SQL:" . $query, $custdb);
			
			$query = "INSERT INTO `jobtype` (`name`, `systempriority`, `info`, `issurvey`, `deleted`) VALUES
										('Emergency', 1, 'Emergencies Only', 0, 0),
										('Attendance', 2, 'Attendance', 0, 0),
										('General', 3, 'General Announcements', 0, 0),
										('Survey', 3, 'Surveys', 1, 0)";
			
			QuickUpdate($query, $custdb) or dieWithError(" SQL:" . $query, $custdb);
			
			$query = "INSERT INTO `jobtypepref` (`jobtypeid`,`type`,`sequence`,`enabled`) VALUES
										(1,'phone',0,1),
										(1,'email',0,1),
										(1,'sms',0,1),
										(2,'phone',0,1),
										(2,'email',0,1),
										(2,'sms',0,1),
										(3,'phone',0,1),
										(3,'email',0,1),
										(3,'sms',0,1),
										(4,'phone',0,1),
										(4,'email',0,1),
										(4,'sms',0,0)";
			
			QuickUpdate($query, $custdb) or dieWithError(" SQL:" . $query, $custdb);
			
			// Login Picture
			QuickUpdate("INSERT INTO content (contenttype, data) values
										('image/gif', '" . base64_encode(file_get_contents("mimg/classroom_girl.jpg")) . "')",$custdb);
			$loginpicturecontentid = $custdb->lastInsertId();
			
			$query = "INSERT INTO `setting` (`name`, `value`) VALUES
										('_loginpicturecontentid', '" . $loginpicturecontentid . "')";
			QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);
			
			// Subscriber Login Picture
			QuickUpdate("INSERT INTO content (contenttype, data) values
										('image/gif', '" . base64_encode(file_get_contents("mimg/header_highered3.gif")) . "')",$custdb);
			$subscriberloginpicturecontentid = $custdb->lastInsertId();
			
			$query = "INSERT INTO `setting` (`name`, `value`) VALUES
										('_subscriberloginpicturecontentid', '" . $subscriberloginpicturecontentid . "')";
			QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);
			
			// Classroom Message Category
			$query = "INSERT INTO `targetedmessagecategory` (`id`, `name`, `deleted`, `image`) VALUES
										(1, 'Default', 0, 'blue dot')";
			QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);
			
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $custdb;
				
			// Default Email Templates
			if (!createDefaultTemplates_7_8())
				return false;
				
			// restore global db connection
			$_dbcon = $savedbcon;
			
			// Set Session to make the save button stay on the page 
			$_SESSION['customerid']= $customerid;
		}

		
		$query = "update customer set
								urlcomponent = ?,
								inboundnumber = ?,
								enabled=?,
								oem=?,
								oemid=?,
								nsid=?,
								notes=?
								where id = ?";
				
		QuickUpdate($query,false,array(
			$postdata["urlcomponent"],
			$postdata["inboundnumber"],
			$postdata["enabled"]?'1':'0',
			$postdata["oem"],
			$postdata["oemid"],
			$postdata["nsid"],
			$postdata["notes"],
			$customerid
		));
		
		// notify authserver to refresh the customer cache
		refreshCustomer($customerid);
		
		// if timezone changed (rare occurance, but we must update scheduled jobs and report records on the shard database)
		if ($postdata["timezone"] != getCustomerSystemSetting('timezone', false, true, $custdb)) {
			$customerid = $_SESSION['customerid'];
			$shardinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id = ?", true,false,array($customerid));				
			$sharddb = DBConnect($shardinfo["dbhost"], $shardinfo["dbusername"], $shardinfo["dbpassword"], "aspshard");
			if(!$sharddb) {
				exit("Connection failed for customer: $customerid, shardhost: {$shardinfo["dbhost"]}");
			}
			QuickUpdate("update qjob set timezone=? where customerid=?", $sharddb, array($postdata["timezone"],$customerid));
			QuickUpdate("update qschedule set timezone=? where customerid=?", $sharddb,array($postdata["timezone"],$customerid));
			QuickUpdate("update qreportsubscription set timezone=? where customerid=?", $sharddb,array($postdata["timezone"],$customerid));
		}
		
		if (!$postdata["enabled"]) {
			setCustomerSystemSetting("disablerepeat", "1", $custdb);
			setCustomerSystemSetting("_customerenabled", "0", $custdb);
		} else {
			setCustomerSystemSetting("_customerenabled", "1", $custdb);
		}
		
		
		if(getCustomerSystemSetting('_dmmethod', '', true, $custdb)!='asp' && $postdata["dmmethod"] == 'asp'){
			$aspquery = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'");
			$aspsharddb = DBConnect($aspquery[0], $aspquery[1], $aspquery[2], "aspshard");
			QuickUpdate("delete from specialtaskqueue where customerid = " . $customerid, $aspsharddb);
			QuickUpdate("update qjob set dispatchtype = 'system' where customerid = " . $customerid . " and status = 'active'", $aspsharddb);
		}
		setCustomerSystemSetting('_dmmethod', $postdata["dmmethod"], $custdb);
		setCustomerSystemSetting('timezone', $postdata["timezone"], $custdb);
		setCustomerSystemSetting('displayname', $postdata["displayname"], $custdb);
		setCustomerSystemSetting('organizationfieldname', $postdata['organizationfieldname'], $custdb);
		
		setCustomerSystemSetting('urlcomponent', $postdata["urlcomponent"], $custdb);
		setCustomerSystemSetting('surveyurl', $SETTINGS['feature']['customer_url_prefix'] . "/" . $postdata["urlcomponent"] . "/survey/", $custdb);
		
		// Logo Picture
		$logo = $postdata["logo"];
		if (isset($defaultlogos[$logo])) {
			$logofile = @file_get_contents($defaultlogos[$logo]['filelocation']);
			if($logofile) {
				$query = "INSERT INTO `content` (`contenttype`, `data`) VALUES
										('" . $defaultlogos[$logo]["filetype"] . "', '" . base64_encode($logofile) . "');";
				QuickUpdate($query, $custdb);
				$logocontentid = $custdb->lastInsertId();
				setCustomerSystemSetting('_logocontentid', $logocontentid, $custdb);			
			}
		}

		setCustomerSystemSetting('_logoclickurl', $postdata["logoclickurl"], $custdb);
		setCustomerSystemSetting('_productname',  $postdata["productname"],$custdb);
		setCustomerSystemSetting('_supportemail', $postdata["supportemail"], $custdb);
		setCustomerSystemSetting('_supportphone', $postdata["supportphone"], $custdb);
		setCustomerSystemSetting('callerid', $postdata["callerid"], $custdb);
		setCustomerSystemSetting('areacode', $postdata["areacode"], $custdb);
		
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
		
		switch($postdata["portal"] ) {
			case "contactmanager": 
				setCustomerSystemSetting('_hasportal', 1, $custdb);
				setCustomerSystemSetting('_hasselfsignup', 0, $custdb);
				break;
			case "selfsignup":
				setCustomerSystemSetting('_hasportal', 0, $custdb);
				setCustomerSystemSetting('_hasselfsignup', 1, $custdb);
				break;
			default:
				setCustomerSystemSetting('_hasportal', 0, $custdb);
				setCustomerSystemSetting('_hasselfsignup', 0, $custdb);
		}
		
		setCustomerSystemSetting('_hassurvey', $postdata["hassurvey"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hasldap', $postdata["hasldap"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hasenrollment', $postdata["hasenrollment"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hastargetedmessage', $postdata["hasclassroom"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hasfacebook', $postdata["hasfacebook"]?'1':'0', $custdb);
		setCustomerSystemSetting('_hastwitter', $postdata["hastwitter"]?'1':'0', $custdb);

	
		setCustomerSystemSetting('_renewaldate', ($postdata['renewaldate']!=""?date("Y-m-d", strtotime($postdata['renewaldate'])):""), $custdb);
		setCustomerSystemSetting('_callspurchased', $postdata['callspurchased'], $custdb);
		setCustomerSystemSetting('_maxusers', ($postdata['maxusers']!=""?$postdata['maxusers']:"unlimited"), $custdb);
		
		setCustomerSystemSetting('_timeslice', $postdata['timeslice'], $custdb);
		setCustomerSystemSetting('loginlockoutattempts', $postdata['loginlockoutattempts'], $custdb);
		setCustomerSystemSetting('logindisableattempts', $postdata['logindisableattempts'], $custdb);
		setCustomerSystemSetting('loginlockouttime', $postdata['loginlockouttime'], $custdb);
				
		$newTheme = json_decode($postdata["brandtheme"]);
		setCustomerSystemSetting('_brandtheme', $newTheme->theme,$custdb);
		setCustomerSystemSetting('_brandprimary', $newTheme->color,$custdb);
		setCustomerSystemSetting('_brandratio', $newTheme->ratio,$custdb);
		setCustomerSystemSetting('_brandtheme1', $COLORSCHEMES[$newTheme->theme]["_brandtheme1"],$custdb);
		setCustomerSystemSetting('_brandtheme2', $COLORSCHEMES[$newTheme->theme]["_brandtheme2"],$custdb);
		
		
		
		Query("COMMIT");
		if($button == "done") {
			if ($ajax)
				$form->sendTo("customers.php");
			else
				redirect("customers.php");
		} else {
			if ($ajax)
				$form->sendTo("customeredit.php");
			else
				redirect("customeredit.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = $customerid?_L('Edit Customer'):_L('New Customer');

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
});
<? Validator::load_validators(array("ValBrandTheme","ValInboundNumber","ValUrlComponent","ValRegExp","ValLanguages"));?>
</script>
<?

startWindow($customerid?_L('Edit Customer'):_L('New Customer'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>