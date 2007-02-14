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
} else{

	if($BFXML_VARS['saveaudio'] == 1){
		$user = new user($specialtask->getData('userid'));
		$audio = new AudioFile();
		$audio->userid =$specialtask->getData('userid');
	
		if(!isset($BFXML_VARS['name']) || $BFXML_VARS['name']=="" || $BFXML_VARS['name']==null) {
			if($BFXML_VARS['origin'] == "start") {
				$BFXML_VARS['name'] = $specialtask->getData('name');
			} else if ($BFXML_VARS['origin'] == "cisco") {
				$BFXML_VARS['name'] = "IP phone - " . date("M d, Y G:i:s");
			} else {
				$BFXML_VARS['name'] = $specialtask->getData('name');
			}
		}
		if(QuickQuery("select * from audiofile where userid = '$USER->id' and deleted = 0 
					and name = '" . DBSafe($BFXML_VARS['name']) ."'"))
			$BFXML_VARS['name'] = $BFXML_VARS['name']."-".date("M d, Y G:i:s");
		
		$audio->name = $BFXML_VARS['name'] . "-" . $specialtask->getData('count');
		$audio->contentid = $BFXML_VARS['recordaudio'];
		$audio->recorddate = date("Y-m-d G:i:s");
		$audio->update();

		$BFXML_VARS['audiofileid'] = $audio->id;
		$BFXML_VARS['saveaudio']=NULL;
		$BFXML_VARS['recordaudio']=NULL;
		
		//then make a message if not from audio
		if($BFXML_VARS['origin'] != "audio"){
			
			$message = new Message();
			$messagename = $specialtask->getData('name') . " - " . $specialtask->getData('count');
			if(QuickQuery("Select count(*) from message where userid=$USER->id and deleted = '0' 
							and name = '$messagename'")) 
				$messagename = $messagename . " - " . date("M d, Y G:i:s");
			$message->name = $messagename;
			$message->type = "phone";
			$message->userid = $user->id;
			$message->create();
		
			$part = new MessagePart();
			$part->messageid = $message->id;
			$part->type = "A";
			$part->audiofileid = $audio->id;
			$part->sequence = 0;
		
			$part->create();
		
			if(!$tempmessage=$specialtask->getData('messages')) {
				$messages = array();
			} else {
				$messages = unserialize($tempmessage);
			}
			$messages[$specialtask->getData('count')] = $message->id;
			$messagestring = serialize($messages);
			$specialtask->setData('messages', $messagestring);
			$specialtask->update();
		} else {
			if(!$tempmessage=$specialtask->getData('messages')) {
				$messages = array();
			} else {
				$messages = unserialize($tempmessage);
			}
			$messages[$specialtask->getData('count')] = $audio->id;
			$messagestring = serialize($messages);
			$specialtask->setData('messages', $messagestring);
			$specialtask->update();
		}
	}
	
	if($BFXML_VARS['recordnext'] == 1 || $BFXML_VARS['continue']==1) {
		$count = $specialtask->getData('count');
		if($count != null){
			$count++;
		} else {
			$count = 1;
		}
		$specialtask->setData('count', $count);
		$specialtask->setData("progress", "recording");
		$specialtask->update();
		?>
		
		<voice sessionid="<?=$SESSIONID ?>">
			<? if($BFXML_VARS['recordnext']==1){ ?>
				<message name="ready">
					<field name="ready" type="menu" timeout="20000" sticky="true">
						<prompt repeat="1">
							<tts gender="female" language="english">When you're ready to record your next message, press 1 and follow the prompts.</tts>
						</prompt>
						<choice digits="1">
							<goto message="record" />
						</choice>
					</field>
				</message>
			<? } ?>
			
			<message name="record">
				<field name="recordaudio" type="record" max="300">
					<prompt>
						<? 
						if($count != 1){
							?><tts gender="female" language="english">Now recording message <?=$count?> </tts><?
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
						<audio cmid="file://prompts/Confirm.wav" />
						<setvar name="playedprompt" value="yes" />
					</prompt>
	
					<choice digits="1">
						<uploadaudio name="recordaudio" />
						<tts gender="female" language="english">Your message has been saved. </tts>
						<goto message="continue" />
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
				
			<message name="continue">
				<field name="recordnext" type="menu" timeout="5000" sticky="true">
					<prompt repeat="1">
						<tts gender="female" language="english">If you'd like to record another message, press 1.</tts>
						<tts gender="female" language="english">If you're finished recording, press 2 to exit the system.</tts>
					</prompt>				
				</field>
			</message>
		</voice>
	<?
	} else {
		$specialtask->setData('progress', 'Done');
		$specialtask->status = "done";
		$specialtask->update();
		forwardToPage("callme3.php");
	}
}
?>