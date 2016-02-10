<?php

// POSTDATA -> '{"destinations": ["8883231414", "8775551212"]}'
$response = '';
switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
                // ref: http://php.net/manual/en/wrappers.php.php#wrappers.php.input
                $postData = file_get_contents('php://input');
                $postSearch = json_decode($postData);
		$postPhones = $postSearch->destinations;
                $responseData = array();
                $id = 1;
                foreach ($postPhones as $postPhone) {
                        $metadata = new stdClass();
                        $metadata->block = new stdClass();
                        $metadata->block->call = false;
                        $metadata->block->sms = false;
                        $metadata->consent = new stdClass();
                        $metadata->consent->call = 'PENDING';
                        $metadata->consent->sms = 'PENDING';
			$metadata->createdDate = '2016-02-01T23:12:57.902Z';
			$metadata->destination = $postPhone;
			$metadata->id = $id++;
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

