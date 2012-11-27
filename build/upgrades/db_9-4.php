<?
require_once("upgrades/db_9-4_oldcode.php");


function upgrade_9_4 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_9-4_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			// create any phone classroom templates
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $db;
		
			if (!updateClassroomTemplate_9_4())
				return false;
				
			// restore global db connection
			$_dbcon = $savedbcon;
	}
	
	return true;
}


function updateClassroomTemplate_9_4() {
	$messagegroup = DBFind("MessageGroup_9_4", "from messagegroup mg inner join job j on (j.messagegroupid = mg.id) where j.type = 'alert' and j.status = 'repeating' and mg.type = 'classroomtemplate'", "mg");
	
	// Valid to not have template setup
	if (!$messagegroup)
		return true;
	
	// hard delete existsing phone templates, should only happen if testing phone classroom prior to update
	QuickUpdate("delete m.* ,mp.*
			from messagegroup mg
			inner join message m on (mg.id = m.messagegroupid)
			inner join messagepart mp on (m.id = mp.messageid)
			where
			mg.type='classroomtemplate' and
			m.type='phone' and m.subtype='voice'");
	
	$classroomlanguages = array(
			"bs" => "Bosnian",
			"de" => "German",
			"el" => "Greek",
			"en" => "English",
			"es" => "Spanish",
			"fa" => "Persian",
			"fr" => "French",
			"hi" => "Hindi",
			"hmn" => "Hmong",
			"ht" => "Haitian",
			"hy" => "Armenian",
			"ja" => "Japanese",
			"km" => "Khmer",
			"ko" => "Korean",
			"lo" => "Lao",
			"lt" => "Lithuanian",
			"pa" => "Panjabi",
			"pl" => "Polish",
			"pt" => "Portuguese",
			"ru" => "Russian",
			"so" => "Somali",
			"th" => "Thai",
			"uk" => "Ukrainian",
			"vi" => "Vietnamese",
			"zh" => "Chinese"
	);
	
	foreach($classroomlanguages as $code => $name) {
		$template = '<template>
		<message name="classroom">
		<audio cmid="file://prompts/classroom/' . $code . '/wrapper-hello.wav" />' .
		($code=="en"?'
				<tts gender="female" language="english">Regarding: </tts>':'') . '
				<tts gender="female" language="english">\speed=15 ${f01}.</tts>
				<tts gender="female" language="english">\speed=15 ${f02}.</tts>
				<!-- $beginBlock sections -->
				<audio cmid="file://prompts/classroom/' . $code . '/wrapper-following-comments-by-teacher.wav" />
				<tts gender="female" language="english">.\speed=15 ${userfirstname}.</tts>
				<tts gender="female" language="english">\speed=15 ${userlastname}.</tts>
				<audio cmid="file://prompts/classroom/' . $code . '/wrapper-comments-follows.wav" />
				<!-- $beginBlock events -->
				${comment}
				<audio cmid="file://prompts/silence/1.wav" />
				<!-- $endBlock events -->
				<!-- $endBlock sections -->
				<audio cmid="file://prompts/classroom/' . $code . '/wrapper-visit-district-website.wav" />
		</message>
</template>' . "\n\n";
		
		// create message
		$message = new Message_9_4();
		$message->messagegroupid = $messagegroup->id;
		$message->userid = $messagegroup->userid;
		$message->name = $messagegroup->name;
		$message->description = $messagegroup->description;
		$message->type = 'phone';
		$message->subtype = 'voice';
		$message->autotranslate = 'none';
		$message->modifydate = date("Y-m-d H:i:s");
		$message->languagecode = $code;
		if (!$message->create())
			return false;
		
		// create messagepart
		$messagepart = new MessagePart_9_4();
		$messagepart->messageid = $message->id;
		$messagepart->type = "T";
		$messagepart->txt = $template;
		$messagepart->sequence = 0;
		if (!$messagepart->create())
			return false;
	}

	// success
	return true;
}
?>