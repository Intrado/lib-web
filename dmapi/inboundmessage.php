<?
// phone inbound, prompt to record a message, commit it, and loop through languages

include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("inboundutils.inc.php");

global $BFXML_VARS;


function promptRecordMessage($skipwelcome=false)
{
?>
<voice>
	<message name="intro">
				<field name="dummy" type="menu" timeout="10000">
					<prompt repeat="1">
<? if (!$skipwelcome) { ?>
						<audio cmid="file://prompts/inbound/Welcome.wav" />
<? } ?>
						<audio cmid="file://prompts/inbound/BeginRecording.wav" />
					</prompt>
					<timeout>
						<audio cmid="file://prompts/GoodBye.wav" />
						<hangup />
					</timeout>
				</field>
				<goto message="record" />
	</message>

	<message name="record">
		<field name="recordaudio" type="record" max="300">
			<prompt>
				<!-- here we could add something depending on what language they were recording at the time -->
				<audio cmid="file://prompts/inbound/RecordAtBeep.wav" />
			</prompt>
		</field>
		<goto message="confirm" />
	</message>

	<message name="confirm">
		<setvar name="playedprompt" value="no" />
		<field name="saveaudio" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<if name="playedprompt" value="no">
					<then>
						<audio cmid="file://prompts/inbound/PlayBack.wav" />
						<audio var="recordaudio" />
					</then>
					<else />
				</if>
				<audio cmid="file://prompts/inbound/SaveMessage2.wav" />
				<setvar name="playedprompt" value="yes" />
			</prompt>

			<choice digits="1">
				<uploadaudio name="recordaudio" />
				<audio cmid="file://prompts/inbound/Saved.wav" />
			</choice>

			<choice digits="2">
				<goto message="confirm" />
			</choice>

			<choice digits="3">
				<goto message="record" />
			</choice>

			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>

			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<audio cmid="file://prompts/inbound/Error.wav" />
		<hangup />
	</message>
</voice>
<?
}

function promptMultiLang()
{
	$languages = $_SESSION['languageList'];
?>
<voice>
	<message name="multilang">
		<field name="domultilang" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/AdditionalLanguages.wav" />
			</prompt>
			<choice digits="2">
				<goto message="chooselang" />
			</choice>

			<choice digits="1" />
			<choice digits="2" />

			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="chooselang">
		<field name="langtorecord" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/SelectLanguage.wav" />

<?
			$listindex = 1;
			foreach ($languages as $lang)
			{
?>
				<audio cmid="file://prompts/inbound/Press<?= $listindex ?>For.wav" />
				<tts gender="female"><?= escapehtml($lang) ?></tts>
<?
				$listindex++;
			}
?>
			</prompt>

<?
			$listindex = 1;
			foreach ($languages as $lang)
			{
?>
				<choice digits="<?= $listindex ?>" />
<?
				$listindex++;
			}
?>

			<choice digits="0" />
			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<audio cmid="file://prompts/inbound/Error.wav" />
		<hangup />
	</message>
</voice>
<?
}


function buildLanguageList()
{
	global $languages;

	if (isset($_SESSION['languageList'])) return; // only load this once

	$user = new User($_SESSION['userid']);

	$query = "select value from persondatavalues where fieldnum='f03' ".
				"and value != '' and value is not null order by refcount desc limit 9";

	$languages = QuickQueryList($query);

	// its a rare case that a customer does not have any persondata (they must import something)
	// but if so, take the customers languages from the language table
	if ($languages == NULL || count($languages) == 0) {
		$languages = QuickQueryList("select name from language order by name");
	}

	// "English" is always the default, so remove english from the list
	foreach ($languages as $index => $value) {
		if (strcasecmp($value ,"english") == 0) {
			unset($languages[$index]);
			break;
		}
	}

	//add default to the list as the first option (it will get removed when the user records their first/default message
	$languages = array_values($languages);
	array_unshift($languages,"default");


	$_SESSION['languageList'] = $languages;

	$_SESSION['langindex'] = 0; // default

}

function commitMessage($contentid)
{

	$languages = $_SESSION['languageList'];
	$langi = $_SESSION['langindex'];
	$language = $languages[$langi];

	loadTimezone();
	$name = "Call In (".$language.") - ".date("M j, Y G:i:s");

	$audioFile = new AudioFile();
	$audioFile->userid = $_SESSION['userid'];
	$audioFile->name = $name;
	$audioFile->description = "";
	$audioFile->contentid = $contentid; //$BFXML_VARS['recordaudio'];
	$audioFile->recordDate = date("Y-m-d G:i:s");

	$message = new Message();
	$message->userid = $_SESSION['userid'];
	$message->type = "phone";
	$message->name = $name;
	$message->description = "";

	$messagePart = new MessagePart();
	$messagePart->type = "A";

	// now commit to database
	$audioFile->create();
	$audiofileid = $audioFile->id;
	//error_log("audiofileid: ".$audiofileid);
	if ($audiofileid) {
		$message->create();
		$messageid = $message->id;
		//error_log("messageid: ".$messageid);
		if ($messageid) {
			$messagePart->messageid = $messageid;
			$messagePart->audiofileid = $audiofileid;
			$messagePart->sequence = 0;
			$messagePart->create();

			// if default language then set session msgid, else add to map of msgid-lang to add into joblanguage
			// ok to assume English is their default language, customer has no options for this (yet)
			if ($language =="default") {
				$_SESSION['messageid'] = $messageid;
			} else {
				if (!isset($_SESSION['msglangmap'])) $_SESSION['msglangmap'] = array();

				$_SESSION['msglangmap'][$languages[$langi]] = $messageid;
			}
			return true;
		}
	}

	return false; // some kind of error
}


///////////////////////////////

if($REQUEST_TYPE == "new"){
	?>
	<error>inboundmessage: wanted continue, got new </error>
	<hangup />
	<?
} else if($REQUEST_TYPE == "continue") {

	// if they recorded a message, and asked to save
	if (isset($BFXML_VARS['saveaudio'])) {

		buildLanguageList();
		$languages = $_SESSION['languageList'];
		$langi = $_SESSION['langindex'];

		// check that menu pressed is 1 to save message
		if ($BFXML_VARS['saveaudio'] == "1" &&
			commitMessage($BFXML_VARS['recordaudio'] +0)) {

			// remove language from the list
			unset($languages[$langi]);
			$languages = array_values($languages);
			$_SESSION['languageList'] = $languages;
			//error_log("langc : ".count($languages));

			if (count($languages) >= 1) {
				promptMultiLang();
			} else {
				// no more languages, move on to select the list
				forwardToPage("inboundlist.php");
			}
		} else {
			forwardToPage("inboundgoodbye.php");
		}
	// if they choose to record additional languages
	} else if (isset($BFXML_VARS['domultilang'])) {

		$selectedLang = 0;
		if (isset($BFXML_VARS['langtorecord'])) {
			$selectedLang = $BFXML_VARS['langtorecord'] +0;
		}
		$languages = $_SESSION['languageList'];

		if ($BFXML_VARS['domultilang'] == 2 &&
			$selectedLang != 0 && $selectedLang <= count($languages))
		{
			$_SESSION['langindex'] = $selectedLang-1;
			promptRecordMessage(true);
		}
		else
		{
			// no more languages, move on to select the list
			forwardToPage("inboundlist.php");
		}
	// prompt them to record a message
	} else {
		promptRecordMessage();
	}

} else {
	//huh, they must have hung up
	$_SESSION = array();
	?>
	<ok />
	<?
}

?>
