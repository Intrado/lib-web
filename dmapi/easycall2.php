<?
include_once("../inc/settings.ini.php");
include_once("../inc/utils.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/User.obj.php");
include_once("../obj/SpecialTask.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");

$specialtask = new specialtask($SESSIONDATA['specialtaskid']);
$phone = $specialtask->getData('phonenumber');
//$callerid = $specialtask->getData('callerid');

if($REQUEST_TYPE == "new") {
	?>
	<error> Got new when wanted continue </error>
	<?
} else {
	if(isset($BFXML_VARS['saveaudio']) &&  $BFXML_VARS['saveaudio']== 1){
		$user = new user($specialtask->getData('userid'));
		$audio = new AudioFile();
		$audio->userid =$specialtask->getData('userid');
		$name = $specialtask->getData('name') . " - " . $specialtask->getData('currlang');
		if(QuickQuery("select count(*) from audiofile where userid = '$user->id' and deleted = 0
					and name = '" . DBSafe($name) ."'"))
			$name = $name ."-". date("M d, Y G:i:s");

		$audio->name = $name;
		$audio->contentid = $BFXML_VARS['recordaudio'];
		$audio->recorddate = date("Y-m-d G:i:s");
		$audio->update();

		$BFXML_VARS['audiofileid'] = $audio->id;
		$BFXML_VARS['saveaudio']=NULL;
		$BFXML_VARS['recordaudio']=NULL;

		$message = new Message();
		$messagename = $specialtask->getData('name') . " - " . $specialtask->getData('currlang');
		if(QuickQuery("Select count(*) from message where userid=$user->id and deleted = '0'
						and name = '" . DBSafe($messagename) . "'"))
			$messagename = $messagename . " - " . date("M d, Y G:i:s");
		$message->name = $messagename;
		$message->description = "Easy Call - " . $messagename;
		$message->type = "phone";
		$message->userid = $user->id;
		$message->create();

		$part = new MessagePart();
		$part->messageid = $message->id;
		$part->type = "A";
		$part->audiofileid = $audio->id;
		$part->sequence = 0;
		$part->create();

		$count = $specialtask->getData('count');
		$messnum = "message" . $count;
		$messageid = $message->id;
		$specialtask->setData($messnum, $messageid);
		$count++;
		$specialtask->setData("count", $count);
		$specialtask->update();

	}
	if($REQUEST_TYPE == "result") {

		$count = $specialtask->getData("count");
		$totalamount = $specialtask->getData("totalamount");
		if($count < $totalamount) {
			$specialtask->status = "done";
			$specialtask->setData("progress", "Hung up");
			$specialtask->update();
			forwardToPage("easycall3.php");
		} else {
			$specialtask->status = "done";
			$specialtask->setData("progress", "Done");
			$specialtask->update();
			forwardToPage("easycall3.php");
		}
	} else {
		$count = $specialtask->getData("count");
		$langnum = "language" . $count;
		$currlang = $specialtask->getData($langnum);
		if($currlang) {
			$specialtask->setData("progress", "Recording");
			$specialtask->setData('currlang', $currlang);
			$specialtask->update();
			?>

			<voice sessionid="<?=$SESSIONID ?>">

				<message name="record">
					<field name="recordaudio" type="record" max="300">
						<prompt>
						<?
							if($currlang != "Default"){
								?>
									<audio cmid="file://prompts/NowRecordInLanguage.wav" />
									<tts gender="female" language="english"><?=htmlentities($currlang, ENT_COMPAT, "UTF-8")?></tts>
								<?
							}
						?>
							<audio cmid="file://prompts/Record.wav" />
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
									<audio cmid="file://prompts/PlayBack.wav" />
									<audio var="recordaudio" />
								</then>
								<else />
							</if>
							<audio cmid="file://prompts/inbound/SaveMessage2.wav" />
							<setvar name="playedprompt" value="yes" />
						</prompt>

						<choice digits="1">
							<uploadaudio name="recordaudio" />
							<audio cmid="file://prompts/Saved.wav" />
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
					</field>
				</message>
			</voice>
	<?
		} else {
			$specialtask->status = "done";
			$specialtask->setData("progress", "Done");
			$specialtask->update();
			forwardToPage("easycall3.php");
		}
	}
}

?>
