<?

function getpreviewformdata($fields,$fielddata,$fielddefaults,$msgType) {
	global $USER;
	$formdata = array();
	if (isset($fields) && count($fields) && $msgType == 'phone') {
		foreach ($fields as $field => $fieldmap) {
			if ($fieldmap->isOptionEnabled("firstname")) {
				$formdata[$field] = array (
					"label" => $fieldmap->name,
					"value" => $USER->firstname,
					"validators" => array(),
					"control" => array("TextField", "maxlength" => 50, "size"=>20),
					"helpstep" => 1
				);
			} else if ($fieldmap->isOptionEnabled("lastname")) {
				$formdata[$field] = array (
					"label" => $fieldmap->name,
					"value" => $USER->lastname,
					"validators" => array(),
					"control" => array("TextField", "maxlength" => 50, "size"=>20),
					"helpstep" => 1
				);
			} else if ($fieldmap->isOptionEnabled("language")) {
				$formdata[$field] = array (
					"label" => $fieldmap->name,
					"value" => $fielddefaults[$field],
					"validators" => array(),
					"control" => array("SelectMenu", "values" => Language::getLanguageMap()),
					"helpstep" => 1
				);
			} else if ($fieldmap->isOptionEnabled("multisearch")) {
				$formdata[$field] = array (
					"label" => $fieldmap->name,
					"value" => $fielddefaults[$field],
					"validators" => array(),
					"control" => array("SelectMenu", "values" => $fielddata[$fieldmap->fieldnum]),
					"helpstep" => 1
				);
			} else if ($fieldmap->isOptionEnabled("reldate")) {
				$formdata[$field] = array (
					"label" => $fieldmap->name,
					"value" => $fielddefaults[$field]?$fielddefaults[$field]:date("m/d/Y", strtotime("now")),
					"validators" => array(),
					"control" => array("TextDate", "size"=>12),
					"helpstep" => 1
				);
			} else {
				$formdata[$field] = array (
					"label" => $fieldmap->name,
					"value" => $fielddefaults[$field],
					"validators" => array(),
					"control" => array("TextField", "maxlength" => 20, "size"=>20),
					"helpstep" => 1
				);
			}
		}
	}
	return $formdata;
}

//find all unique fields and values used in this message
function getpreviewfieldmapdata($messageid) {
	global $USER;
	$fields = array();
	$fielddata = array();
	$fielddefaults = array();

	$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct fieldnum from messagepart where messageid=?)", false, array($messageid));
	if (count($messagefields) > 0) {
		foreach ($messagefields as $fieldmap) {
			$fields[$fieldmap->fieldnum] = $fieldmap;
			if (!$fieldmap->isOptionEnabled("language") && $fieldmap->isOptionEnabled("multisearch")) {
				$limit = DBFind('Rule', "from rule r inner join userassociation ua on r.id = ua.ruleid where ua.userid=? and type = 'rule' and r.fieldnum=?", "r", array($USER->id, $fieldmap->fieldnum));
				$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
				$fielddata[$fieldmap->fieldnum] = QuickQueryList("select value,value from persondatavalues where fieldnum=? $limitsql order by value limit 5000", true, false, array($fieldmap->fieldnum));
			}
		}
		// Get message parts so we can find the default values, if specified in the message
		$messageparts = DBFindMany("MessagePart", "from messagepart where messageid = ? and type = 'V'", false, array($messageid));

		foreach ($messageparts as $messagepart)
			$fielddefaults[$messagepart->fieldnum] = $messagepart->defaultvalue;
	}
	return array($fields,$fielddata,$fielddefaults);
}


?>
