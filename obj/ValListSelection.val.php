<?


/*
 * Validate that lists selected in the list selection box
 * are either owned or subscribed by the user and not 
 * deleted or of alert type. 
 * 
 * Currently used by job.php and survey.php for list selection 
 * */
class ValFormListSelect extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $USER;
		
		// build the arguement array
		$args = array();
		foreach ($value as $id)
			$args[] = $id;
		$args[] = $USER->id;
		foreach ($value as $id)
			$args[] = $id;
		$args[] = $USER->id;
		
		// get the valid lists
		$validlists = QuickQueryList("
			(select l.id as id, l.name as name
			from list l
				inner join publish p on
					(l.id = p.listid)
			where l.id in (". DBParamListString(count($value)) .")
				and not l.deleted
				and l.type != 'alert'
				and p.action = 'subscribe'
				and p.type = 'list'
				and p.userid = ?)
			UNION
			(select id, name
			from list
			where id in (". DBParamListString(count($value)) .")
				and type != 'alert'
				and userid = ?)",
			true, false, $args);
		
		// see if any of the value lists are not in the valid lists
		foreach ($value as $id) {
			if (!isset($validlists[$id]))
				return _L("%s has invalid list selections", $this->label);
		}
		return true;
	}
}

?>