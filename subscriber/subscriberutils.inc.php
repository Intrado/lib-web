<?

function getPhoneReview($phone, $code) {
	$inboundnumber = getCustomerSystemSetting("inboundnumber", "");
	
	$formhtml = '<div style="height: 200px; overflow:auto;">' . 
		_L("You must follow these steps within <b>24 hours</b> to complete this addition to your account.") . '<br><br>' .
		button(_L("Print this page now"), "window.print()") . '<br><br><br>' .
		_L("Step 1: You must call from the phone ") . '<b>' . Phone::format($phone) . '</b>' . _L(" in order to verify your caller ID with our records.") . '<br>' .
		'<img src="img/bug_lightbulb.gif" >&nbsp;&nbsp;' . _L("If your phone service has caller identification blocked, you must first dial *82 to unblock it for this call.") . '<br>' .
		_L("Step 2: Call ") . '<b>' . Phone::format($inboundnumber) . '</b><br>' . 
		'Step 3: When prompted, select option 2.<br>' .
		'Step 4: When prompted, enter this activation code <span style="font-weight:bold; font-size: 140%;">' . $code . '</span><br>' .
		'Step 5: When the call is complete, log back into your account to edit your notification preferences.<br>' .
		'</div>';
	return $formhtml;
}

function getEmailReview($email) {
	$formhtml = '<div style="height: 200px; overflow:auto;">' . 
		_L("You must follow these steps within <b>24 hours</b> to complete this addition to your account.") . '<br><br>' .
		_L("Step 1: Check your email account") . ' <b>' . $email . '</b><br>' .
		_L("Step 2: Click the activation link") . '<br>' . 
		'</div>';
	return $formhtml;
}

function loadSubscriberDisplaySettings() {
	$subscriberID = $_SESSION['subscriberid'];
	
	$_SESSION['personid'] = $pid = QuickQuery("select personid from subscriber where id=?", false, array($subscriberID));
	$_SESSION['custname'] = QuickQuery("select value from setting where name='displayname'");		
	$_SESSION['productname'] = QuickQuery("select value from setting where name='_productname'");
		
	$firstnameField = FieldMap::getFirstNameField();
	$lastnameField = FieldMap::getLastNameField();
	
	$_SESSION['subscriber.username'] = QuickQuery("select username from subscriber where id=?", false, array($subscriberID));
	$_SESSION['subscriber.firstname'] = QuickQuery("select ".$firstnameField." from person where id=?", false, array($pid));
	$_SESSION['subscriber.lastname'] = QuickQuery("select ".$lastnameField." from person where id=?", false, array($pid));

	$theme = QuickQuery("select value from setting where name = '_brandtheme'");
	if ($theme === false)
		$theme = "3dblue";
	$theme1 = QuickQuery("select value from setting where name = '_brandtheme1'");
	if ($theme1 === false)
		$theme1 = "89A3CE";
	$theme2 = QuickQuery("select value from setting where name = '_brandtheme2'");
	if ($theme2 === false)
		$theme2 = "89A3CE";
	$primary = QuickQuery("select value from setting where name = '_brandprimary'");
	if ($primary === false)
		$primary = "26477D";
	$ratio = QuickQuery("select value from setting where name = '_brandratio'");
	if ($ratio === false)
		$ratio = ".3";
	$_SESSION['colorscheme']['_brandtheme']   = $theme;
	$_SESSION['colorscheme']['_brandtheme1']  = $theme1;
	$_SESSION['colorscheme']['_brandtheme2']  = $theme2;
	$_SESSION['colorscheme']['_brandprimary'] = $primary;
	$_SESSION['colorscheme']['_brandratio']   = $ratio;

	$prefs = QuickQuery("select preferences from subscriber where id=?", false, array($subscriberID));
	$preferences = json_decode($prefs, true);
	if (isset($preferences['_locale']))
		$_SESSION['_locale'] = $preferences['_locale'];
	else
		$_SESSION['_locale'] = "en_US"; // US English
}

?>