<?
// NOTE: You must escape all single quotes

function loadTaiTemplateData() {

	// array of all email/sms templates
	$templates = array();

	////////////////////////////
	// Unread Messages Report
	////////////////////////////
	$templates['tai_unreadmessagesreport']['html']['en']['subject'] = 'Unread Messages for: ${date}';
	$templates['tai_unreadmessagesreport']['html']['en']['fromname'] = 'Talk About It (Reports)';
	$templates['tai_unreadmessagesreport']['html']['en']['fromaddr'] = 'reports@letstai.com';
	$templates['tai_unreadmessagesreport']['html']['en']['body'] = 'TODO: template body';

	$templates['tai_unreadmessagesreport']['plain']['en']['subject'] = 'Unread Messages for: ${date}';
	$templates['tai_unreadmessagesreport']['plain']['en']['fromname'] = 'Talk About It (Reports)';
	$templates['tai_unreadmessagesreport']['plain']['en']['fromaddr'] = 'reports@letstai.com';
	$templates['tai_unreadmessagesreport']['plain']['en']['body'] = 'TODO: template body';


	return $templates;
}

?>