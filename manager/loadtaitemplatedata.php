<?
// NOTE: You must escape all single quotes

function loadTaiTemplateData() {

	// array of all email/sms templates
	$templates = array();

	////////////////////////////
	// Unread Messages Report
	////////////////////////////
	$templates['tai_unreadmessagesreport']['email']['html']['en']['subject'] = 'Unread Messages for: ${date}';
	$templates['tai_unreadmessagesreport']['email']['html']['en']['fromname'] = 'Talk About It (Reports)';
	$templates['tai_unreadmessagesreport']['email']['html']['en']['fromaddr'] = 'reports@letstai.com';
	$templates['tai_unreadmessagesreport']['email']['html']['en']['body'] = '
<!-- $beginBlock summary -->
<!-- $beginBlock organization -->\'${okey}\' <!-- $endBlock organization -->
0-24: ${interval0}
24-48: ${interval1}
48-72: ${interval2}
<!-- $beginBlock topic -->
${topicname}: ${topictotal}
<!-- $endBlock topic -->
total: ${total}
<!-- $endBlock summary -->';

	$templates['tai_unreadmessagesreport']['email']['plain']['en']['subject'] = 'Unread Messages for: ${date}';
	$templates['tai_unreadmessagesreport']['email']['plain']['en']['fromname'] = 'Talk About It (Reports)';
	$templates['tai_unreadmessagesreport']['email']['plain']['en']['fromaddr'] = 'reports@letstai.com';
	$templates['tai_unreadmessagesreport']['email']['plain']['en']['body'] = '
<!-- $beginBlock summary -->
<!-- $beginBlock organization -->\'${okey}\' <!-- $endBlock organization -->
0-24: ${interval0}
24-48: ${interval1}
48-72: ${interval2}
<!-- $beginBlock topic -->
${topicname}: ${topictotal}
<!-- $endBlock topic -->
total: ${total}
<!-- $endBlock summary -->';

	$templates['tai_unreadmessagesreport']['sms']['plain']['en']['body'] =
'Talk About It - Unread Messages Report for: ${date}
There are currently ${total_all} total unread messages for your associated schools';

	return $templates;
}

?>