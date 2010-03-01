<?


class ValLists extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;
		if (strpos($value, 'pending') !== false)
			return _L('Please finish adding this rule, or unselect the field');

		$listids = json_decode($value);
		if (empty($listids))
			return _L("Please add a list");

		$allempty = true;
		foreach ($listids as $listid) {
			if ($listid === 'addme') {
				$allempty = false;
				continue;
			}
			if (!userOwns('list', $listid))
				return _L('You have specified an invalid list');
			$list = new PeopleList($listid + 0);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			if ($renderedlist->total >= 1)
				$allempty = false;
		}
		if ($allempty && !(isset($args['jobtype']) && $args['jobtype'] == 'repeating'))
			return _L('All of the selected lists are empty');
		return true;
	}
}

?>
