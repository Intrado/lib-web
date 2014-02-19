<?
// Cannot use form.inc because no session has started
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

date_default_timezone_set("US/Pacific");  //to keep php from complaining

apache_note("CS_APP","ml"); //for logging

require_once("../inc/appserver.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/table.inc.php");

$thriftRequires = array(
    "/Base/TBase.php",
    "/Protocol/TProtocol.php",
    "/Protocol/TBinaryProtocol.php",
    "/Protocol/TBinaryProtocolAccelerated.php",
    "/Transport/TTransport.php",
    "/Transport/TSocket.php",
    "/Transport/TBufferedTransport.php",
    "/Transport/TFramedTransport.php",
    "/Exception/TException.php",
    "/Exception/TTransportException.php",
    "/Exception/TProtocolException.php",
    "/Exception/TApplicationException.php",
    "/Type/TType.php",
    "/Type/TMessageType.php",
    "/StringFunc/TStringFunc.php",
    "/Factory/TStringFuncFactory.php",
    "/StringFunc/Core.php",
    "/packages/messagelink/Types.php",
    "/packages/messagelink/MessageLink.php"
);

foreach ($thriftRequires as $require) {
    require_once("../Thrift{$require}");
}

use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;

class ContentLink {
    
    private $code = '';
    private $badcode = false;
    private $TITLE;
    private $urlcomponent;
    private $logosrc;
    private $appservererror;
    private $messageinfo;

    public function __construct($options = array()) {

        if (is_array($options) && $options.length > 0) {
            if (isset($options['code'])) {
                $this->code = $options['code'];
            }
        }

        $this->appservererror = false;

        $this->step1();
        $this->step2();
        $this->step3();
    }

    public function step1() {
        // does this code contain only valid characters? If not, it's bad
        if (!preg_match("/^[-_a-zA-Z0-9]+$/", $this->code)) {
            error_log("Invalid messagelinkcode requested: '" . $this->code . "'");
            $this->badcode = true;
        } else {
            $this->badcode = false;
        }
    }

    public function step2() {
        if (!$this->badcode) {
            list($appserverprotocol, $appservertransport) = initMessageLinkApp();

            if($appserverprotocol == null || $appservertransport == null) {
                error_log("Cannot use AppServer");
                $appservererror = true;
            } else {
                $attempts = 0;
                while(true) {
                    try {
                        $client = new MessageLinkClient($appserverprotocol);
                        // Open up the connection
                        $appservertransport->open();
                        try {
                            $this->messageinfo = $client->getInfo($this->code);
                        } catch (messagelink_MessageLinkCodeNotFoundException $e) {
                            $this->badcode = true;
                            error_log("Unable to find the messagelinkcode: " . urlencode($this->code));
                        }
                        $appservertransport->close();
                        break;
                    } catch (TException $tx) {
                        $attempts++;
                        // a general thrift exception, like no such server
                        error_log("getInfo: Exception Connection to AppServer (" . $tx->getMessage() . ")");
                        $appservertransport->close();
                        if($attempts > 2) {
                            error_log("getInfo: Failed 3 times to get content from appserver");
                            $appservererror = true;
                            break;
                        }
                    }
                }
            }
        }
    }

    public function step3() {
        if ($this->appservererror || $this->badcode) {
            $this->TITLE = "School Messenger";
            $this->urlcomponent = "m";
            $this->logosrc = "img/logo_small.gif";
        } else {
            $this->TITLE = escapehtml($this->messageinfo->customerdisplayname);
            $this->urlcomponent = $this->messageinfo->urlcomponent;
            $this->logosrc = "messagelinklogo.img.php?code=". escapehtml($this->code);
            apache_note("CS_CUST",urlencode($this->messageinfo->urlcomponent)); //for logging

        }
    }

    public function render() {
        echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<meta name="viewport" content="width=device-width" />
	<style type="text/css">
		.navband1 {
			height: 6px;
			background: #3e693f;
		}
		.navband2 {
			height: 2px;
			background: #3399ff;
			margin-bottom: 3px;
		}
		.navlogoarea {
			background: #ebf5ff;
		}
		.menucollapse {
			float: right;
			margin-top: 4px;
			margin-right: 5px;
			border: 2px outset white;
			width: 10px;
			height: 10px;
		}
		.window {
			width: 100%;
		}
		.windowtitle {
			font-size: 12px;
			font-weight: bold;
			padding-left: 5px;
			padding-top: 2px;
			color: #3e693f;
		}
		.windowtable {
		}
	</style>
	<title>Justin Test</title>
</head>
<body style="padding: 0; margin: 0px;font-family: verdana, arial, helvetica, sans-serif;">
	<table class="navlogoarea" border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td bgcolor="white"><div style="padding-left:10px;"><img src="messagelinklogo.img.php?code=ENMZNAGL8zk" alt=""/></div></td>
			<td><img src="img/shwoosh.gif" alt=""/></td>
			<td width="100%"></td>
		</tr>
	</table>
	<div class="navband1"><img src="img/pixel.gif" alt="" /></div>
	<div class="navband2"><img src="img/pixel.gif" alt="" /></div>
	<div style="margin: 15px">

<div class="window">

	<div class="window_title_wrap">
		<div class="window_title_l"></div>
		<h2 class="window_title">Message Preview</h2>
		<div class="window_title_r"></div>
	</div>

	<div class="window_body_wrap">

		<div class="window_left">
			<div class="window_right">
				<div class="window_body cf">
	<div style="margin: 10px; margin-top: 15px; padding: 5px; float: left; border: 1px solid #3e693f">
		Phone Message: Feb 13, 2014 3:16 pm<br />
		<div style="font-size: 20px; margin-bottom: 5px">Justin Test</div>
		<div style="font-size: 16px; margin-bottom: 2px">message link?</div>
		<div style="font-size: 12px">&nbsp;&nbsp;Created with MessageSender</div>
	</div>
	<div style="margin:10px; clear:both;">
		<div id="player"></div>
		<script type="text/javascript" language="javascript" src="script/prototype.js"></script>
		<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
		<script language="JavaScript" type="text/javascript">
	 		embedPlayer("https://testasp.testschoolmessenger.com/m/messagelinkaudio.mp3.php?code=ENMZNAGL8zk","player",5);
		</script>
		<br /><a href="messagelinkaudio.mp3.php?download&code=ENMZNAGL8zk">Click here to download</a>
	</div>
				</div><!-- window_body -->
			</div>
		</div>

	</div><!-- window_body_wrap -->
</div><!-- window -->

<div class="window_foot_wrap">
	<div class="window_foot_left">
		<div class="window_foot_right">
		</div>
	</div>
</div>

	</div>
</body>
</html>
';
    }

}

$options = array();

if (isset($_GET['s'])) {
    $options['messagelinkcode'] = $_GET['s'];
    $options['attachmentlinkcode'] = $_GET['a'];
}
// initialize new MessageLink instance with $options arg
$messageLink = new ContentLink($options);

// render final HTML markup for MessageLink/AttachmentLink page, depending on $options
$messageLink->render();


?>


