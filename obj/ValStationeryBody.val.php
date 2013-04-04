<?
class ValStationeryBody extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if (!empty($args['translationitem'])) {
			$translationdata = json_decode($value);
			if ($translationdata->override || !$translationdata->enabled)
				$text = $translationdata->text;
			else if ($translationdata->enabled && !$translationdata->override)
				$text = $translationdata->englishText;
		} else {
			$text = $value;
		}
	
		// SMK added check to ensure there is at least one "editableBlock" in the stationery 2013-03-01
		// ref: http://dtbaker.com.au/random-bits/preg_match-across-multiple-lines.html
		// ref: http://php.net/manual/en/reference.pcre.pattern.modifiers.php
		//testreg = '<blah><div style="whatever" class="editableBlock primaryBlock">This <strong>is</strong> editable</div></blah>';
		if (! preg_match('(<(\w+)(.*?)class="editableBlock(.*)</\1>)ms', $text, $matches)) {
			return('No editable blocks found!');
		}

		//FIXME ValStationeryBody shouldn't be doing anything with audio files, they aren't supported in HTML email
		if (isset($args['messagegroupid']))
			$audiofileids = MessageGroup::getReferencedAudioFileIDs($args['messagegroupid']);
		
		$message = new Message();
		$errors = array();
		//FIXME ValStationeryBody shouldn't be doing anything with audio files, they aren't supported in HTML email
		$parts = $message->parse($text,$errors, null, isset($audiofileids) ? $audiofileids : null); // Fill in with voice id later
		if (count($errors) > 0)	{
			$str = "There was an error parsing the message: ";
			foreach($errors as $error)
			{
				$str .= "\n" . $error;
			}
			
			return $str;
		}
		
		//FIXME ValStationeryBody shouldn't be doing anything with the "phone" type, it isn't supported in HTML email
		if (isset($args['type']) && $args['type'] == 'phone') {
			if ((isset($translationdata) && $translationdata->enabled) || (isset($args['autotranslator']) && $args['autotranslator'])) {
				foreach ($parts as $part) {
					if ($part->type == 'A')
						return _L('Translation messages may not have audio parts.');
				}
			}
		} else {
			$imagecount = 0;
			foreach ($parts as $part) {
				if ($part->type == 'I') {
					if (!contentAllowed($part->imagecontentid))
						return _L('Unknown image content');
					$imagecount++;
				}
				if (isset($args['maximages']) && $imagecount > $args['maximages'])
					return _L('You may only include %s images.', $args['maximages']);
			}
		}
		
		return true;
	}
}
?>
