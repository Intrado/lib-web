<?

class Publish extends DBMappedObject {

	var $userid;
	var $action;
	var $type;
	var $messagegroupid;
	var $listid;
	var $organizationid;

	function Publish ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "publish";
		$this->_fieldlist = array("userid", "action", "type", "messagegroupid", "listid", "organizationid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	// set the type id eg: messagegroupid for messagegroup publish objects
	function setTypeId ($id) {
		switch ($this->type) {
			case "messagegroup":
				$this->messagegroupid = $id;
				break;
			case "list":
				$this->listid = $id;
				break;
		}
		// no match, failed
		return false;
	}
}

function _findPublishObjects($type, $id, $action) {
	$args = array($action, $id);
	switch ($type) {
		case "messagegroup":
			return DBFindMany("publish", "from publish where action = ? and type = 'messagegroup' and messagegroupid = ?", false, $args);
			break;
		case "list":
			return DBFindMany("publish", "from publish where action = ? and type = 'list' and listid = ?", false, $args);
			break;
		default:
			return false;
	}
	return false;
}

// get all the publications for a specific type and specific messagegroup or list id
function getPublications ($type, $id) {
	return _findPublishObjects($type, $id, 'publish');
}

// get all the subscriptions for a specific type and specific messagegroup or list id
function getSubscriptions ($type, $id) {
	return _findPublishObjects($type, $id, 'subscribe');
}

?>