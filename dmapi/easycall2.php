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
				$BFXML_VARS['name'] = "Audio - "  . date("M d, Y G:i:s");
			}
		}
		if(QuickQuery("select * from audiofile where userid = '$user->id' and deleted = 0 
					and name = '" . DBSafe($BFXML_VARS['name']) ."'"))
			$BFXML_VARS['name'] = $BFXML_VARS['name']."-".date("M d, Y G:i:s");
		
		$audio->name = $BFXML_VARS['name'] . "-" . $specialtask->getData('currlang');
		$audio->contentid = $BFXML_VARS['recordaudio'];
		$audio->recorddate = date("Y-m-d G:i:s");
		$audio->update();

		$BFXML_VARS['audiofileid'] = $audio->id;
		$BFXML_VARS['saveaudio']=NULL;
		$BFXML_VARS['recordaudio']=NULL;
			
		$message = new Message();
		$messagename = $specialtask->getData('name') . " - " . $specialtask->getData('currlang');
		if(QuickQuery("Select count(*) from message where userid=$user->id and deleted = '0' 
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
	
		if(!$tempmessage=$specialtask->getData('messagelangs')) {
			$messagelangs = array();
		} else {
			$messagelangs = unserialize($tempmessage);
		}
		$messagelangs[$specialtask->getData('currlang')] = $message->id;
		$messagelangstring = serialize($messagelangs);
		$specialtask->setData('messagelangs', $messagelangstring);
		$specialtask->update();

	}
	if($REQUEST_TYPE == "result") {
		$messages = $specialtask->getData('messagelangs');
		if($messages)
			$messages = unserialize($messages);
		$lang = $specialtask->getData('languagelist');
		if($lang)
			$lang = explode("|", $lang);
		
		$specialtask->update();
		if(count($lang) > count($messages)) {
			$specialtask->status = "done";
			$specialtask->setData("progress", "Hung up");
			$specialtask->update();
			forwardToPage("easycall3.php");
		} else {
			$specialtask->setData("countlang", count($lang));
			$specialtask->setData("countmessages", count($messages));
			$specialtask->status = "done";
			$specialtask->setData("progress", "Done");
			$specialtask->update();
			forwardToPage("easycall3.php");
		}
	} else {	
		$langlist = $specialtask->getData('languagelist');
		$langlist = explode("|", $langlist);
		$count = $specialtask->getData("count");
		if($count==null){
			$count = 0;
		}
		if($count < count($langlist)) {
			
			//shifts off first array entry
			
			$currlang = $langlist[$count];
			$specialtask->setData('currlang', $currlang);
			
			//output the audio since there is a current language
			$count++;
			$specialtask->setData("count", $count);
			$specialtask->setData("progress", "recording");
			$specialtask->update();
			?>
			
			<voice sessionid="<?=$SESSIONID ?>">
		
				<message name="record">
					<field name="recordaudio" type="record" max="300">
						<prompt>
						<? 
							$tempmess = $specialtask->getData("languagelist");
							if($tempmess){
								$tempmess = explode("|", $tempmess);
								if(count($tempmess) > 1){
								?>
									<tts gender="female" language="english">Now recording <?=$currlang?> </tts>
								<?
								}
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
