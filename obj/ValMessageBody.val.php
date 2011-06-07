<?
class ValMessageBody extends Validator {
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
		
		if (isset($args['messagegroupid']))
			$audiofileids = MessageGroup::getReferencedAudioFileIDs($args['messagegroupid']);
		
		$message = new Message();
		$errors = array();
		$parts = $message->parse($text,$errors, null, isset($audiofileids) ? $audiofileids : null); // Fill in with voice id later
		if (count($errors) > 0)	{
			$str = "There was an error parsing the message: ";
			foreach($errors as $error)
			{
				$str .= "\n" . $error;
			}
			
			return $str;
		}
		
		if (isset($args['type']) && $args['type'] == 'phone') {
			if ((isset($translationdata) && $translationdata->enabled) || (isset($args['autotranslator']) && $args['autotranslator'])) {
				foreach ($parts as $part) {
					if ($part->type == 'A')
						return _L('Translation messages may not have audio parts.');
				}
			}
		} else if (isset($args['maximages'])) {
			$imagecount = 0;
			foreach ($parts as $part) {
				if ($part->type == 'I')
					$imagecount++;
				if ($imagecount > $args['maximages'])
					return _L('You may only include %s images.', $args['maximages']);
			}
		}
		
		return true;
	}
}
?>