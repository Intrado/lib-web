<?

class Import extends DBMappedObject {

	var $uploadkey;
	var $userid;
	var	$listid;
	var $name;
	var $description;
	var $notes;
	var $status;
	var $type;
	var $datatype = "person";
	var $ownertype;
	var $updatemethod;
	var $lastrun;
	var $datamodifiedtime;
	var $datalength = 0;
	var $skipheaderlines = 0;
	var $managernotes;
	

	function Import ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "import";
		$this->_fieldlist = array("uploadkey","userid", "listid", "name", "description", "notes", "status", "type","datatype","ownertype", "updatemethod", "lastrun","datamodifiedtime","datalength","skipheaderlines","managernotes");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function runNow($importid = null) {
		if (!isset($importid))
			$importid = $this->id;

		QuickUpdate("call start_import($importid)");
		QuickUpdate("update import set status='queued' where id=? and status != 'running'", false, array($importid));
	}


	function upload ($data) {
		$this->datalength = strlen($data);
		QuickUpdate("delete from importmicroupdate where importid = ?", false, array($this->id));
		return QuickUpdate("update import set data=?,datalength=?,datamodifiedtime=now() where id=?", false, array($data,$this->datalength,$this->id));
	}

	function download () {
		return QuickQuery("select data from import where id=?", false, array($this->id));
	}

	// Always remove links to userAssociations, but don't actually remove them. We want the user to retain their privileges
	function unlinkUserAssociations() {
		return QuickUpdate("update userassociation set importid = null where importid = ?", false, array($this->id));
	}

	public function removeUserAssociations() {
		return QuickUpdate("delete from userassociation where importid=? and sectionid != 0", false, array($this->id));
	}
	
	// Roles are currently only used by TAI, so removing them is appropriate for the base kona application
	function removeRoles() {
		return QuickUpdate("delete from role where importid = ?", false, array($this->id));
	}
	
	// will remove enabled flag for all users associated with this import
	function disableUsers() {
		return QuickUpdate("update user set enabled=0 where importid = ?", false, array($this->id));
	}
	
	function unlinkUsers() {
		return QuickUpdate("update user set importid = null, lastimport = now() where importid = ?", false, array($this->id));
	}

	public function removePersonGuardians() {
		return QuickUpdate("delete from personguardian where importid=?", false, array($this->id));
	}

	public function removePersonAssociations() {
		if ($this->datatype == "section") {
			// have to clean up any person associations which use this import's section data
			return QuickUpdate("delete from personassociation where type = 'section' and sectionid in (select id from section where importid = ?)",
						false, array($this->id));
		} else {
			return QuickUpdate("delete from personassociation where importid = ?", false, array($this->id));
		}
	}

	public function removeGroupData() {
		return QuickUpdate("delete from groupdata where importid = ?", false, array($this->id));
	}

	public function softDeletePeople() {
		return QuickUpdate("update person set deleted = 1 where importid = ?", false, array($this->id));
	}
	
	public function unlinkPeople() {
		return QuickUpdate("update person set importid = null, lastimport = now() where importid = ?", false, array($this->id));
	}

	public function recalculatePersonDataValues() {
		$fields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct mapto from importfield 
										where (mapto like 'f%' or mapto like 'g%') and importid = ?)", false, array($this->id));
		foreach ($fields as $field)
			$field->updatePersonDataValues();
	}

	public function removeSections() {
		return QuickUpdate("delete from section where importid = ?", false, array($this->id));
	}

	function destroy($destroychildren = false) {
		// NOTE delete an import logic should be identical to running the same import without any data in it
		// only fullsync imports should delete data

		switch ($this->datatype) {
			// For user imports, the desired functionality is that the user accounts remain in the system
			// additionaly, for FULL type imports, we want the user account to be moved to disabled
			case "user" :
				// When removing a "Full-sync" user import, we should disable the linked user accounts
				if ($this->updatemethod == "full")
					$this->disableUsers();

				$this->unlinkUserAssociations();
				$this->removeRoles();
				$this->unlinkUsers();
				break;

			case "person" :
				// NOTE: "create only" and "create update" person imports don't delete any data when run with an empty file
				// Only remove data when deleting a "update, create, delete" import
				if ($this->updatemethod == "full") {
					$this->removePersonGuardians();
					$this->removePersonAssociations();
					$this->removeGroupData();

					$this->softDeletePeople();
					$this->recalculatePersonDataValues();
				}
				$this->unlinkPeople();
				break;

			case "section" :
				// delete all userassociation with this importid, DO NOT remove sectionid=0 do not inadvertently grant access to persons they should not see.
				$this->removeUserAssociations();
				$this->removePersonAssociations();
				$this->removeSections();
				break;

			case "enrollment" :
				$this->removePersonAssociations();
				break;
		}
		// NOTE do not hard delete import related data - feature request to someday soft delete imports CS-4473

		// import alert rules will be deleted when checked. 

		//delete import
		QuickUpdate("delete from importfield where importid = ?", false, array($this->id));
		QuickUpdate("delete from importjob where importid = ?", false, array($this->id));
		QuickUpdate("delete from importlogentry where importid = ?", false, array($this->id));
		QuickUpdate("delete from importmicroupdate where importid = ?", false, array($this->id));
		DBMappedObject::destroy($destroychildren);
	}
}

?>