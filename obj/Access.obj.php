<?

class Access extends DBMappedObject {

	var $name;
	var $description;

	var $permissions = false;

	function Access ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "access";
		$this->_fieldlist = array("name","description");
//		$this->_relations['permission'] = new DBRelationMap('Permission', $this->permissions, 'accessid', $this->id);
		
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function loadPermissions($force = false) {
		if ($force || $this->permissions === false) {
			if ($this->id)
				$this->permissions = DBFindMany("Permission", "from permission where accessid=$this->id");
			else
				$this->permissions = array();
		}
	}
	
	function getValue($action, $default = false) {
		$this->loadPermissions();
		$permission = $this->getPermission($action);
		return $permission ? $permission->value : $default;
	}
	
	function getPermission($action) {
		$this->loadPermissions();
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
				$permission->accessid = $this->id;
				$permission->create();
			}
		} else {
			if($permission) {
				$permission->destroy();
			}
		}
	}
}

?>