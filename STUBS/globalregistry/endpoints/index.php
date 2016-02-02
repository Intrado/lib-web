<?php

// POSTDATA -> '[{"id": 1, "subType": "LANDLINE", "block": { "call" : false, "sms": false }, "consent": {"call": "PENDING", "sms": "PENDING"}}]'
$response = '';
switch ($_SERVER['REQUEST_METHOD']) {
	case 'PATCH':
		// ref: http://php.net/manual/en/wrappers.php.php#wrappers.php.input
		$postData = file_get_contents('php://input');
		$postThings = json_decode($postData);
		$responseData = array();
		foreach ($postThings as $postThing) {
			$metadata = new StdClass();
			$metadata->block = $postThing->block;
			$metadata->consent = $postThing->consent;
			$metadata->createdDate = '123456879101112';
			$metadata->destination = "phone#{$postThing->id}";
			$metadata->id = $postThing->id;
			$metadata->type = 'PHONE';
			$responseData[] = $metadata;
		}
		header('Content-type: application/json');
		$response = json_encode($responseData);
		break;

	default:
		$response = 'Unsupported request method';
		break;
}
die($response);

