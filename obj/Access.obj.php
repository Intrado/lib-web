<?

class Access extends DBMappedObject {

	var $name;
	var $description;

	var $permissions;

	function Access ($id = NULL) {
		$this->permissions = array();
		$this->_allownulls = true;
		$this->_tablename = "access";
		$this->_fieldlist = array("name","description");
		$this->_relations['permission'] = new DBRelationMap('Permission', $this->permissions, 'accessid', $this->id);
		
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function getValue($action) {
		$permission = $this->getPermission($action);
		return $permission ? $permission->value : false;
	}
	
	function getPermission($action) {
		foreach($this->permissions as $permission)
			if($permission->name == $action)
				return $permission;
	}
	
	function setPermission($action, $value) {
		$permission = $this->getPermission($action);
		if($value) {
			if($permission) {
				$permission->value = $value;
				$permission->update();
			} else {
				$permission = new Permission();
				$permission->name = $action;
				$permission->value = $value;
				$this->_relations['permission']->add($permission);

			}
		} else {
			if($permission) {
				$permission->destroy();
			}
		}
	}
}

?>