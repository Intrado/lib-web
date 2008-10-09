<?

class Access extends DBMappedObject {

	var $name;
	var $description;

	var $permissions;

	function Access ($id = NULL) {
		$this->permissions = false;
		$this->_allownulls = true;
		$this->_tablename = "access";
		$this->_fieldlist = array("name","description");
//		$this->_relations['permission'] = new DBRelationMap('Permission', $this->permissions, 'accessid', $this->id);
		
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function loadPermissions($force = false) {
		if ($this->id && ($force || $this->permissions === false)) {
			$this->permissions = DBFindMany("Permission", "from permission where accessid=$this->id");
		}
	}
	
	function getValue($action) {
		$this->loadPermissions();
		$permission = $this->getPermission($action);
		return $permission ? $permission->value : false;
	}
	
	function getPermission($action) {
		$this->loadPermissions();
		foreach($this->permissions as $permission)
			if($permission->name == $action)
				return $permission;
	}
}

?>