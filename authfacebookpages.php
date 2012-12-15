<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting("_hasfacebook") || !$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Custom Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class FacebookAuthPages extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		
		// the value holds the currently authorized facebook pages
		// [pageid,pageid,...]
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>';
		
		$pages = json_decode($value);
		$haspages = (count($pages) > 0);
		
		// auth a user to add more pages
		$perms = "publish_stream,offline_access,manage_pages";
		$str .= '
			<style>
				.fbpagelist {
					width: 50%;
					border: 1px dotted gray;
					padding: 3px;
					margin-bottom: 10px;
				}
				.fbauth .fbname {
					font-weight: bold;
				}
				.fbauth .fberror {
					font-weight: bold;
					color: red;
				}
				.fbnoauth {
					color: gray;
				}
			</style>
			<div>
				<div id="fb-root"></div>
				<div id="'. $n. 'fbnoauthpages" class="fbpagelist" style="'. ($haspages?"display:none;":"display:block;") .'">
					'. _L("Select Authorized Pages to restrict which Facebook Pages users may post to.") .'
				</div>
				<table id="'. $n. 'fbauthpages" class="fbpagelist" style="'. ($haspages?"display:block":"display:none") .'"><tbody>
					<tr><th colspan=3>'. _L("Authorized Pages") .'<th></tr>
				</tbody></table>
				<div id="'. $n. 'fbdisconnected">
					<div style="clear:both;">'. _L("Connect to a Facebook account to authorize additional Pages.") .'</div>
					'. icon_button("Connect to Facebook", "custom/facebook", 
						"try { 
							FB.login(loadFbPagesFromAuthResponse.curry('$n'), {scope: '$perms'});
						} catch (e) { 
							alert('". _L("Could not connect to Facebook")."'); 
						}"). '
				</div>
				<div id="'. $n. 'fbconnected" style="display:none">
					<div id="'. $n. 'fbnonewpages" style="display:none">
						'. _L("You are either not an administrator of a Facebook Page, or all of your administered Pages are already authorized.") .'
					</div>
					<div id="'. $n. 'fbhasnewpages" style="clear:both">
						'. _L("You may authorize one or more of the following Pages."). '
					</div>
					<table id="'. $n. 'fbnewpages" class="fbpagelist"><tbody></tbody></table>
					'. icon_button(_L("Disconnect from Facebook"), "custom/facebook", "$('".$n."fbconnected').hide(); $('".$n."fbdisconnected').show();") .'
				</div>
			</div>
			';
		
		
		return $str;
	}
	
	function renderJavascript($value) {
		global $SETTINGS;
		$n = $this->form->name."_".$this->name;
		
		$str = '// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", status: true, cookie: false, xfbml: true});
					
					loadFbPagesFromList("'. $n .'");
				};
				(function() {
					var e = document.createElement("script");
					e.type = "text/javascript";
					e.async = true;
					e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
					document.getElementById("fb-root").appendChild(e);
				}());';
		return $str;
		
	}
	
	function renderJavascriptLibraries() {
		
		$str = '<script type="text/javascript">
				
				// remove the facebook page from the authorized list of pages and from the form item value
				function removeFbPage(accountid, e, event) {
					// remove the current account info item
					$(e + accountid).remove();
					
					var currentpages = $(e).value.evalJSON();
					delete currentpages[currentpages.indexOf(accountid)];
					$(e).value = Object.toJSON(currentpages);
					
					// add the page we just removed to the new pages list
					$(e + "fbnewpages").show();
					$(e + "fbnonewpages").hide();
					$(e + "fbhasnewpages").show();
					var addbutton = icon_button("Add","add",e + "add-" + accountid);
					addFbPageElement(e, $(e + "fbnewpages"), accountid, addbutton, addFbPage.curry(accountid, e), "fbnoauth");
					
					// if there are no more accounts authorized
					currentpages = $(e).value.evalJSON();
					if (currentpages.size() < 1) {
						$(e + "fbnoauthpages").show();
						$(e + "fbauthpages").hide();
					}
				}
				
				// add a new page into list of authorized pages
				function addFbPage(accountid, e, event) {
					// remove the current account info item
					$(e + accountid).remove();
					
					// if there are no more new pages that could be added
					if ($(e + "fbnewpages").down("tr") == undefined) {
						$(e + "fbnewpages").hide();
						$(e + "fbnonewpages").show();
						$(e + "fbhasnewpages").hide();
					}
					
					var fbauthpages = $(e + "fbauthpages");
					var fbnoauthpages = $(e + "fbnoauthpages");
					fbauthpages.show();
					fbnoauthpages.hide();
					
					var currentpages = $(e).value.evalJSON();
					currentpages[currentpages.size()] = accountid;
					$(e).value = Object.toJSON(currentpages);
					
					// dont reload everything, just add the new account into the auth section
					var removebutton = icon_button("Remove","cross",e + "remove-" + accountid);
					addFbPageElement(e, fbauthpages, accountid, removebutton, removeFbPage.curry(accountid, e), "fbauth");
				}
				
				// load facebook page info from list of account ids
				function loadFbPagesFromList(e) {
					var accountlist = $(e).value.evalJSON();
					fbauthpages = $(e + "fbauthpages");
					fbnoauthpages = $(e + "fbnoauthpages");
					
					// if there are any accounts to list
					if (accountlist.size() > 0) {
						
						fbnoauthpages.hide();
						fbauthpages.show();
						
						accountlist.each(function(accountid) {
							var removebutton = icon_button("Remove","cross",e + "remove-" + accountid);
							addFbPageElement(e, fbauthpages, accountid, removebutton, removeFbPage.curry(accountid, e), "fbauth");
						});
					} else {
						fbnoauthpages.show();
						fbauthpages.hide();
					}
				}
				
				// handle updateing information when the user allows or disallows the facebook application
				function loadFbPagesFromAuthResponse(e, res) {
					var access_token = "";
					if (res != null && res.authResponse) {
						access_token = res.authResponse.accessToken;
					}
					
					// if we have an access token. display the pages selection
					if (access_token) {
						$(e + "fbconnected").show();
						$(e + "fbdisconnected").hide();
					} else {
						// no access token, show the connect button
						$(e + "fbconnected").hide();
						$(e + "fbdisconnected").show();
					}
					
					// get the pages this user administrates
					FB.api("/me/accounts", { access_token: access_token, type: "page" }, function(r) {
						var fbnewpages = $(e + "fbnewpages");
						var fbnonewpages = $(e + "fbnonewpages");
						var fbhasnewpages = $(e + "fbhasnewpages");
						
						// if there are any authorized pages returned
						if (r && !r.error && r.data !== undefined) {
							
							fbnewpages.update();
							fbnonewpages.hide();
							fbhasnewpages.show();
							
							// go over all the pages this person administrates
							var hasnewpages = false;
							var currentpages = $(e).value.evalJSON();
							r.data.each(function(account) {
								// if this account id isnt already in our list of authorized pages
								if (currentpages.indexOf(account.id) == -1) {
									hasnewpages = true;
									var addbutton = icon_button("Add","add",e + "add-" + account.id);
									addFbPageElement(e, fbnewpages, account.id, addbutton, addFbPage.curry(account.id, e), "fbnoauth");
								}
							});
							
							// if we added no pages. report this information
							if (!hasnewpages) {
								fbnewpages.hide();
								fbnonewpages.show();
								fbhasnewpages.hide();
							}
						}
					}); // end facebook api call
				}
				
				// get an account element with all the facebook page info and an attached button
				function addFbPageElement(e, container, accountid, button, onclick, cssclass) {
					// temporarily create a loading element
					var containerbody = $(container).down("tbody");
					if (containerbody == undefined) {
						containerbody = new Element("tbody");
						$(container).insert(containerbody);
					}
					// set up the table elements for the fb page data
					var accountelement = new Element("tr", { id: e + accountid }).insert(
							new Element("td", { "id": e + "pagepic" + accountid, "class": "fbimg" }).insert(
								new Element("img", { src: "img/ajax-loader.gif" }))
						).insert(
							new Element("td", { "id": e + "pagedetail" + accountid, "class": cssclass }).insert(
								"Loading...")
						).insert(new Element("td").insert(button));
						
					containerbody.insert(accountelement);
					button.observe("click", onclick);
					
					FB.api("/" + accountid, { }, function(r) {
						var fbpagename;
						var fbpagecategory;
						var fbpagenameclass;
						if (r && !r.error) {
							fbpagename = r.name.escapeHTML();
							fbpagecategory = r.category.escapeHTML();
							fbpagenameclass = "fbname";
						} else {
							fbpagename = "'.escapehtml(_L("No data returned.")).'";
							fbpagecategory = "'.escapehtml(_L("Page may no longer exist or is unpublished.")).'";
							fbpagenameclass = "fberror";
						}
						// remove the temporary loading image
						$(e + "pagepic" + accountid).update(
							new Element("img", { "src": "https://graph.facebook.com/"+ accountid +"/picture?type=square" }));
						// set the name and category
						$(e + "pagedetail" + accountid).update();
						$(e + "pagedetail" + accountid).insert(
								new Element("td", { "class": cssclass }).insert(
									new Element("div", { "class": fbpagenameclass }).update(fbpagename)
								).insert(
									new Element("div", { "class": "fbcategory" }).update(fbpagecategory))
							);
					}); // end facebook api call
				}
				
				</script>';
		return $str;		
	}
	
}

// TODO: ValFacebookAuthPages


////////////////////////////////////////////////////////////////////////////////
// Data processing
////////////////////////////////////////////////////////////////////////////////
// get currently authorized pages from the db
$pages = getFbAuthorizedPages();
$authorizewall = getSystemSetting("fbauthorizewall");

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	'tips' => array(
		"label" => _L('Tips'),
		"fieldhelp" => _L("Please click on the Guide button for more information about administering Facebook pages."),
		"control" => array("FormHtml", "html" => '
			<ul>
				<li class="wizbuttonlist">'._L('This page allows you to restrict users to posting on authorized Facebook Pages.').'</li>
				<li class="wizbuttonlist">'._L('Connect to Facebook to add Pages you administrate.').'</li>
				<li class="wizbuttonlist">'._L('If no Pages are authorized, users may post to any Facebook Page they administrate.').'</li>
			</ul>
			'),
		"helpstep" => 1),
	"authorizedpages" => array(
		"label" => _L('Authorize Pages'),
		"fieldhelp" => _L("This section contains the Pages which you have authorized. Users may post to these Pages."),
		"value" => json_encode($pages),
		"validators" => array(),
		"control" => array("FacebookAuthPages"),
		"helpstep" => 1
	),
	"authorizewall" => array(
		"label" => _L('Authorize User Timeline'),
		"fieldhelp" => _L("Select this to give users the option of posting to their Facebook Timeline."),
		"value" => $authorizewall,
		"validators" => array(),
		"control" => array("CheckBox", "label" => _L("Allow users to post to their Timeline")),
		"helpstep" => 2
	)
);

$helpsteps = array (
	_L('You may restrict users to posting to certain Facebook Pages by creating a list of authorized Pages. You will need to be an administrator on the Pages you wish to authorize. Additionally, users who may post to these Pages must have permission set on Facebook.'),
	_L('This option allows users to have the option of posting to their Facebook Timeline in addition to, or instead of, your authorized Pages.')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"), icon_button(_L('Cancel'),"cross",null,"settings.php"));
$form = new Form("templateform",$formdata,$helpsteps,$buttons);

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
		
		$pages = json_decode($postdata['authorizedpages']);
		$authorizewall = $postdata['authorizewall'];
		
		Query("BEGIN");
		
		setFbAuthorizedPages($pages);
		
		if ($authorizewall)
			setSystemSetting("fbauthorizewall", 1);
		else
			setSystemSetting("fbauthorizewall", "");
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("settings.php");
		else
			redirect("settings.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = _L('Facebook Authorized Pages');

include_once("nav.inc.php");

startWindow(_L('Pages'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>