<?php
// This file will be duplicated in the Kona project as 'messagesender.php', but is living here for documentation purposes.
require_once("inc/common.inc.php");

// Required to load validations
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValSmsText.val.php");
require_once("obj/ValTtsText.val.php");
require_once("obj/TextAreaAndSubjectWithCheckbox.val.php");
require_once("obj/ValFacebookPage.val.php");
require_once("obj/ValLists.val.php");
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/ValMessageGroup.val.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/TraslationItem.fi.php");
require_once("obj/CallerID.fi.php");
require_once("obj/ValDuplicateNameCheck.val.php");


// FIXME: Copy-pasted from message_sender.php

class ValEasycall extends Validator
{
    var $onlyserverside = true;

    function validate($value, $args)
    {
        global $USER;
        if (!$USER->authorize("starteasy"))
            return "$this->label " . _L("is not allowed for this user account");
        $values = json_decode($value);
        if (!$values || $values == json_decode("{}"))
            return "$this->label " . _L("has messages that are not recorded");
        foreach ($values as $langcode => $afid) {
            $audiofile = DBFind("AudioFile", "from audiofile a where a.id = ? and (a.userid = ?
                                                or exists (select 1 from publish p where p.userid = ? and p.action = 'subscribe' and p.type = 'messagegroup' and p.messagegroupid = a.messagegroupid))
                                                ", "a", array($afid, $USER->id, $USER->id));
            if (!$audiofile)
                return "$this->label " . _L("has invalid or missing messages");
        }
        return true;
    }
}

class ValHasMessage extends Validator
{
    var $onlyserverside = true;

    function validate($value, $args)
    {
        global $USER;
        if ($value == 'pick') {
            // find if there are any message groups the user owns or subscribes to
            $hasowned = QuickQuery("
                                        select 1
                                        from messagegroup mg
                                        where not mg.deleted and mg.userid = ?
                                        limit 1", false, array($USER->id));
            $hassubscribed = QuickQuery("
                                        select 1
                                        from publish p
                                                                        where p.userid = ? and action = 'subscribe' and type = 'messagegroup'
                                        limit 1", false, array($USER->id));
            if (!$hasowned && !$hassubscribed)
                return "$this->label: " . _L('You have no saved or subscribed messages.');
        }
        return true;
    }
}

class ValMessageTypeSelect extends Validator
{

    function validate($value, $args)
    {
        // MUST contain one of phone, email or sms
        if (!array_intersect(array('phone', 'email', 'sms'), $value))
            return "$this->label " . _L('requires one message of type Phone, Email or SMS Text.');
        return true;
    }

    function getJSValidator()
    {
        return '
                function (name, label, value, args) {
                var isvalid = false;
                $A(value).each(function (val) {
                if (val == "phone" || val == "email" || val == "sms")
                isvalid = true;
        });
        if (!isvalid)
        return label + " ' . _L("requires one message of type Phone, Email or SMS Text.") . '";
        return true;
        }
        ';
    }
}

class ValTranslationCharacterLimit extends Validator
{
    function validate($value, $args, $requiredvalues)
    {
        $textlength = strlen($requiredvalues[$args['field']]);
        if ($textlength > 5000)
            return "$this->label is unavalable if the message is more than 5000 characters. The message is currently $textlength characters.";
        return true;
    }

    function getJSValidator()
    {
        return
            'function (name, label, value, args, requiredvalues) {
                //alert("valLength");
                var textlength = requiredvalues[args["field"]].length;
                if (textlength > 5000)
                return this.label +" is unavalable if the message is more than 5000 characters. The message is currently " + textlength + " characters.";
                return true;
        }';
    }
}

class ValTimePassed extends Validator
{
    var $onlyserverside = true;

    function validate($value, $args, $requiredvalues)
    {
        $timediff = (time() - strtotime($requiredvalues[$args['field']] . " " . $value));
        if ($timediff > 0)
            return "$this->label: " . _L('Must be in the future.');
        return true;
    }
}


class ValConditionalOnValue extends Validator
{
    var $conditionalrequired = true;

    function validate($value, $args, $requiredvalues)
    {
        $required = true;
        foreach ($args['fields'] as $field => $testvalue) {
            if ($requiredvalues[$field] != $testvalue)
                $required = false;
        }
        if ($required && !$value)
            return "$this->label is required.";

        return true;
    }

    function getJSValidator()
    {
        return '
                        function (name, label, value, args, requiredvalues) {
                                var required = true;
                                $H(args.fields).each(function (field, testvalue) {
                                        if (requiredvalues[field] != testvalue)
                                                required = false;
                                });
                                                        if (required && !value)
                                        return this.label + " is required.";

                                return true;
                        }';
    }
}

?>

<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>School Messenger : Message Sender</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="messagesender/stylesheets/app.css">
    <script type="text/javascript" src="messagesender/javascripts/vendor.js"></script>
    <script type="text/javascript" src="messagesender/javascripts/app.js"></script>
</head>
<body class="newui" id="ms">
<div id="messagesender-shell"></div>

<script type="text/javascript" src="script/jquery.json-2.3.min.js"></script>
<script type="text/javascript" src="script/jquery.timer.js"></script>
<script type="text/javascript" src="script/jquery.moment.js"></script>
<script type="text/javascript" src="script/jquery.easycall.js"></script>
<script type="text/javascript" src="script/message_sender.emailattach.js"></script>
<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="script/rcieditor.js"></script>
<script type="text/javascript" src="script/speller/spellChecker.js"></script>
<script type="text/javascript" src="script/niftyplayer.js.php"></script>

<script type="text/javascript">
	$(function () {
		window.BOOTSTRAP_DATA = {};
		var orgID = -1;
		var languages, features, options, facebookPages, initUserResponse;

		var fetch = function (url) {
			return $.ajax({
				url:'api/2/organizations/' + orgID + url,
				type:'GET',
				dataType:'json'
			});
		};

		var user = $.ajax({
			url:'api/2/users/' + <?= $USER->id ?> +'?expansions=roles/jobtypes,roles/feedcategories,roles/callerids,preferences,tokens',
			type:'GET',
			dataType:'json'
		});

		user.done(function (userResponse) {
			var roles = userResponse.roles[0];
			var org = roles.organization;
			orgID = org.id;

			languages = fetch('/languages');
			features = fetch('/settings/features');
			options = fetch('/settings/options');
			facebookPages = fetch('/settings/facebookpages');
			formData = $.getJSON("message_sender.php?jsonformdata=true")

			$.when(languages, features, options, facebookPages, formData)
				.done(function (languagesRes, featuresRes, optionsRes, facebookPagesRes, formData) {
					org.languages = languagesRes[0].languages;
					org.settings = {
						features:featuresRes[0].features,
						options:optionsRes[0].options,
						facebookpages:facebookPagesRes[0].facebookPages,
						facebookAppID:  <?= $SETTINGS['facebook']['appid'] ?>
					}
					window.BOOTSTRAP_DATA.user = userResponse;

					window.BOOTSTRAP_DATA.form = {
						template: {
							// get template settings (if loading from template, they will be set in session data)
							subject: <?echo (isset($_SESSION['message_sender']['template']['subject'])?("'". str_replace("'", "\'", $_SESSION['message_sender']['template']['subject']). "'"):"''")?>,
							lists: <?echo (isset($_SESSION['message_sender']['template']['lists'])?$_SESSION['message_sender']['template']['lists']:'[]')?>,
							jtid: <?echo (isset($_SESSION['message_sender']['template']['jobtypeid'])?$_SESSION['message_sender']['template']['jobtypeid']:0)?>,
							mgid: <?echo (isset($_SESSION['message_sender']['template']['messagegroupid'])?$_SESSION['message_sender']['template']['messagegroupid']:0)?>

						},
						snum:formData[0].snum,
						schema:formData[0].formdata,
						name:"msgsndr",
						url:window.location.protocol + "//" + window.location.host + "/" + window.location.pathname.split('/')[1] + "/message_sender.php",
						validators:document.validators
					};

					window.require('initialize');
				});
		});

		user.fail(function (userResponse) {
			console.log('error creating bootstrap data:', userResponse);
		});

		// load required validators into document.validators
		<? Validator::load_validators(array("ValCallerID", "ValConditionalOnValue", "ValConditionallyRequired", "ValDate", "ValDomain", "ValDomainList", "ValDuplicateNameCheck", "ValEasycall", "ValEmail", "ValEmailAttach", "ValEmailList", "ValFacebookPage", "ValFieldConfirmation", "ValHasMessage", "ValInArray", "ValLength", "ValLists", "ValMessageBody", "ValMessageGroup", "ValMessageTypeSelect", "ValNumber", "ValNumeric", "ValPhone", "ValRequired", "ValSmsText", "ValTextAreaAndSubjectWithCheckbox", "ValTimeCheck", "ValTimePassed", "ValTimeWindowCallEarly", "ValTimeWindowCallLate", "ValTranslation", "ValTranslationCharacterLimit", "ValTtsText", "valPhone")); ?>
	});
	
</script>

</body>
</html>
