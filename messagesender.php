<?php
    // This file will be duplicated in the Kona project as 'messagesender.php', but is living here for documentation purposes.
    require_once("inc/common.inc.php");
?>

<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>School Messenger : Message Sender</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="messagesender/stylesheets/app.css">
    <script type="text/javascript" src="messagesender/javascripts/vendor.js"></script>
    <script type="text/javascript" src="messagesender/javascripts/app.js"></script>
    <script type="text/javascript">
        (function() {
            window.BOOTSTRAP_DATA = {};
            var orgID = -1;
            var languages, features, options, facebookPages, initUserResponse;

            var fetch = function(url) {
                return $.ajax({
                    url: 'api/2/organizations/' + orgID + url,
                    type: 'GET',
                    dataType: 'json'
                });
            };

            var user = $.ajax({
                url:  'api/2/users/'+ <?= $USER->id ?> +'?expansions=roles/jobtypes,roles/feedcategories,roles/callerids,preferences,tokens',
                type: 'GET',
                dataType: 'json'
            });

            user.done(function(userResponse) {
                var roles = userResponse.roles[0];
                var org = roles.organization;
                orgID = org.id;

                languages     = fetch('/languages');
                features      = fetch('/settings/features');
                options       = fetch('/settings/options');
                facebookPages = fetch('/settings/facebookpages');

                $.when(languages, features, options, facebookPages)
                    .done(function(languagesRes, featuresRes, optionsRes, facebookPagesRes) {
                        org.languages = languagesRes[0].languages;
                        org.settings  = {
                            features:       featuresRes[0].features,
                            options:        optionsRes[0].options,
                            facebookpages:  facebookPagesRes[0].facebookPages,
                            facebookAppID:  <?= $SETTINGS['facebook']['appid'] ?>
                        }
                        window.BOOTSTRAP_DATA.user = userResponse;
                        window.require('initialize');
                    });
            });

            user.fail(function(userResponse) {
                console.log('error creating bootstrap data:', userResponse);
            });

        })();
    </script>
</head>
<body class="newui" id="ms">
    <div id="messagesender-shell"></div>

    <script type="text/javascript" src="script/jquery.json-2.3.min.js"></script>
    <script type="text/javascript" src="script/jquery.timer.js"></script>
    <script type="text/javascript" src="script/jquery.moment.js"></script>
    <script type="text/javascript" src="script/jquery.easycall.js"></script>
    <script type="text/javascript" src="script/message_sender.emailattach.js"></script>
    <script type="text/javascript" src="script/ckeditor/ckeditor4/ckeditor.js"></script>
    <script type="text/javascript" src="script/ckeditor/ckeditor4/adapters/jquery.js"></script>
    <script type="text/javascript" src="script/speller/spellChecker.js"></script>
    <script type="text/javascript" src="script/niftyplayer.js.php"></script>

</body>
</html>
