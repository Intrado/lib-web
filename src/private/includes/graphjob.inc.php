<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 2/2/16
 * Time: 6:17 AM
 */

function graph_job_summary($type, $size, $jobId) {
	$fieldColors = array(
		"remaining" => "#e84f1f",
		"pending" => "#fc9524",
		"notcontacted" => "#48a3be",
		"contacted" => "#55aa28"
	);

	$fieldLabels = array(
		"remaining" => "Queued",
		"pending" => "Sent",
		"notcontacted" =>"Not Delivered",
		"contacted" => "Delivered"
	);

	$total = 0;
	$typeLabel = "";
	$fields = array();

	$readonlyconn = readonlyDBConnect();
	switch ($type) {
		case "phone":
			$info = JobSummaryReport::getPhoneInfo($jobId, $readonlyconn);
			$total = $info['totalwithphone'];
			$typeLabel = "Phones";
			$fields = array("remaining","notcontacted","contacted");
			break;
		case "email":
			$info = JobSummaryReport::getEmailInfo($jobId, $readonlyconn);
			$total = $info['totalwithemail'];
			$typeLabel = "Emails";
			$fields = array("remaining","notcontacted","contacted");
			break;
		case "sms":
			$info = JobSummaryReport::getSmsInfo($jobId, $readonlyconn);
			$total = $info['totalwithsms'];
			$typeLabel = "SMS";
			$fields = array("remaining","pending","notcontacted","contacted");
			break;
		case "device":
			$info = JobSummaryReport::getDeviceInfo($jobId, $readonlyconn);
			$total = $info['totalwithdevice'];
			$typeLabel = "Push";
			$fields = array("remaining","notcontacted","contacted");
			break;
		default:
			error_log("Invalid type reveiced.  Got " + $type);
	}

	$centerLabel = $typeLabel . "\n" . number_format($total);

	//TODO use $SESSION_READONLY = true instead of session_write_close()
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

	$data = array();
	$colors = array();
	$legends = array();

	foreach ($fields as $field) {
			$data[$field] = $info[$field];
			$colors[$field] = $fieldColors[$field];
			$legends[$field] = $fieldLabels[$field] . " " . number_format($info[$field]);
	}

	ob_start();
	output_ring_pie_graph_with_legend($centerLabel, array_values($data), array_values($colors), array_values($legends),$size);
	$graph = ob_get_contents();
	ob_end_clean();

	return $graph;
}