<?

class ValLists extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;

		if (strpos($value, 'pending') !== false)
			return _L('Please finish adding this rule, or unselect the field');

		$listids = json_decode($value);
		if ($listids == null || count($listids) == 0)
			return _L("Please add a list");
			
		//check ownership of each list
		foreach ($listids as $listid) {
			if ($listid === 'addme')
				continue;
			if (!userOwns('list', $listid))
				return _L('You have specified an invalid list');
		}
		
		if (isset($args['skipemptycheck']) && $args['skipemptycheck'])
			return true;
		
		//check to see if they are all empty
		foreach ($listids as $listid) {
			if ($listid === 'addme') {
				return true; //stop looking as soon as we find any non empty list
			}
		
			$list = new PeopleList($listid);
			$renderedlist = new RenderedList2($list);
			$renderedlist->initWithList($list);
			$renderedlist->pagelimit = 0; //save a bit of memory by not trying to get anyone, just calc totals
			if ($renderedlist->getTotal() > 0)
				return true; //stop looking as soon as we find any non empty list
		}
		return _L('All of the lists are empty');
	}
}

?>