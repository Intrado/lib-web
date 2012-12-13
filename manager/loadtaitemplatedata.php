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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Unread Messages Report for: ${date}</title>
</head>
<body style="background:#C7E3A2">
<table width="540px"><tr><td>
<div style="border:1px solid #8DC540;border-radius:8px 8px 8px 8px;box-shadow:0 2px 8px rgba(0, 0, 0, 0.15);background:white;padding:12px;margin:20px">
    <p style="font-size:18px;font-weight:bold;">Unread Messages Report Summary</p>
    <p>Generated on ${date}</p>
    <!-- $beginBlock summary -->
    <div style="border:1px solid #486929;border-radius:8px 8px 8px 8px;box-shadow:0 2px 8px rgba(0, 0, 0, 0.15);background:white;padding:8px;">
        <table width="100%">
            <tr><th>Organization(s): <!-- $beginBlock organization -->\'${okey}\' <!-- $endBlock organization --></th></tr>
            <tr><th>Total unread messages: ${total}</th></tr>
            <tr><td>
                <div style="border:1px solid #8DC540;border-radius:8px 8px 8px 8px;box-shadow:0 2px 8px rgba(0, 0, 0, 0.15);background:#EDF6E0;padding:10px;">
                    <table width="100%">
                        <tr><th align="center" colspan="3">Message totals by age (in hours)</th></tr>
                        <tr><th align="center">0-24</th><th align="center">24-48</th><th align="center">48-72</th></tr>
                        <tr><td align="center">${interval0}</td><td align="center">${interval1}</td><td align="center">${interval2}</td></tr>
                    </table>
                </div>
                <br>
                <div style="border:1px solid #8DC540;border-radius:8px 8px 8px 8px;box-shadow:0 2px 8px rgba(0, 0, 0, 0.15);background:#EDF6E0;padding:10px;">
                    <table width="100%">
                        <tr><th align="center" colspan="2">Message totals by topic</th></tr>
                        <!-- $beginBlock topic -->
                        <tr><td align="right" width="60%">${topicname}:&nbsp;</td><td align="left" width="40%">${topictotal}</td></tr>
                        <!-- $endBlock topic -->
                    </table>
                </div>
            </td></tr>
        </table>
    </div>
    <br>
    <!-- $endBlock summary -->
    <div style="margin-top:20px;width:100%;">
        <p>You are receiving this report because your contact settings have "Reports" enabled for this email address and the "Unread Message Report" enabled for automatic delivery.</p>
        <p>To manage your contact settings, or view a detailed version of this report, go to <a href="http://letstai.com">letstai.com</a> and log in.</p>
    </div>
</div>
</td></tr></table>
</body>
</html>';

	$templates['tai_unreadmessagesreport']['email']['plain']['en']['subject'] = 'Unread Messages for: ${date}';
	$templates['tai_unreadmessagesreport']['email']['plain']['en']['fromname'] = 'Talk About It (Reports)';
	$templates['tai_unreadmessagesreport']['email']['plain']['en']['fromaddr'] = 'reports@letstai.com';
	$templates['tai_unreadmessagesreport']['email']['plain']['en']['body'] = '
Unread Messages Report Summary
Generated on ${date}

<!-- $beginBlock summary -->
<!-- $beginBlock organization -->Organization(s): \'${okey}\' <!-- $endBlock organization -->
Total unread messages: ${total}

Message totals by age (in hours)
0-24: ${interval0}
24-48: ${interval1}
48-72: ${interval2}

Message totals by topic
<!-- $beginBlock topic -->
${topicname}: ${topictotal}
<!-- $endBlock topic -->
-------------------------------------------------------------------------------

<!-- $endBlock summary -->';

	$templates['tai_unreadmessagesreport']['sms']['plain']['en']['body'] =
'Talk About It - Unread Messages Report for: ${date}
There are currently ${total_all} total unread messages for your associated schools';

	return $templates;
}

?>