<?php

$baseDir = '/usr/commsuite/www'
$iniPath = '/usr/commsuite/www/inc/settings.ini.php';

$SETTINGS = parse_ini_file($iniPath, true);
$customerId = 101;

require_once("{$baseDir}/obj/ApiClient.obj.php");
require_once("{$baseDir}/obj/NetsuiteApiClient.obj.php");

$apiClient = new ApiClient(
	$SETTINGS['netsuite']['url'],
	array(
		"Authorization: NLAuth nlauth_account={$SETTINGS['netsuite']['account']}, nlauth_email={$SETTINGS['netsuite']['user']}, nlauth_signature={$SETTINGS['netsuite']['pass']}, nlauth_role={$SETTINGS['netsuite']['role']}"
	)
);

$netsuiteApi = new NetsuiteApiClient(
	$apiClient,
	$SETTINGS['netsuite']['uriFeedback']
);


// Send the data to NetSuite
$netsuiteApi->feedbackSet('ASP_Id', $customerId);
$netsuiteApi->feedbackSet('firstName', 'test first Name');
$netsuiteApi->feedbackSet('lastName', 'test last Name');
$netsuiteApi->feedbackSet('emailAddress', 'testemail@testhost.com');
$netsuiteApi->feedbackSet('phoneNum', 'test phone');
$netsuiteApi->feedbackSet('feedbackCategory', 1);
$netsuiteApi->feedbackSet('feedbackText', 'test feedback Text - ignore this!');
$netsuiteApi->feedbackSet('userId', 1);
$netsuiteApi->feedbackSet('feedbackType', 1);
$netsuiteApi->feedbackSet('userPage', 'test user page where this request was initiated');
$netsuiteApi->feedbackSet('trackingId', 'testUserTrackingIdMadeUpByTheClientWhichDoesntMatter');
$netsuiteApi->feedbackSet('sessionData', 'n/a');

// Show a different result view depending on success/error response from API...
if ($netsuiteApi->captureUserFeedback()) {
	echo "PASS!\n\n";
}
else {
	echo "FAIL!\n\n";
}

