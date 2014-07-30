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

	/** downloads, unzips if neccessary, and returns a file pointer to the csv file contained in this import. Please close it when you're done.
	  * Returns false if unable to extract a csv file from the zip.
	  * NOTE: creates tempfiles, but should unlink them while the file is still open to avoid making the caller's life complicated.
	  * While this will leave the ZipArchive open, php will close the file when this page terminates, allowing the os to reclaim 
	  * the unlinked files space.
	  */
	function openCsvFile () {
		//scan the file
		$importfile = secure_tmpname("importfiledownload",".dat");
		file_put_contents($importfile,$this->download());

		//see if this will open with zip
		$fp = false;
		$zip = new ZipArchive();
		$res = $zip->open($importfile);

		if ($res === true) {
			//try to find best file match
			$entry = $this->scan_zip($zip,"extension");
			if ($entry === false)
				$entry = $this->scan_zip($zip,"largest");
			//see if we found a file, and open a stream for it
			if ($entry !== false)
				$fp = $zip->getStream($entry["name"]);
		} else {
			$fp = @fopen($importfile , "r");
		}

		unlink($importfile); //should remain readable as long as it's still open

		return $fp;
	}

	//helper function to scan a zip file for likely import files
	private function scan_zip($zip,$mode) {
		$max = 0;
		$foundentry = false;
		for ($x = 0; $x < $zip->numFiles; $x++) {
			$entry = $zip->statIndex($x);
			$name = $entry["name"];
			$basename = basename($entry["name"]);
			
			//skip all hidden files that start with '.'
			if (strpos($basename,".") === 0)
				continue;	
			//skip maxosx stuff
			if (strpos($name,"__MACOSX") !== false)
				continue;
			//skip directories
			if ($name[strlen($name)-1] == "/")
				continue;
			//skip empty files
			if ($entry['size'] == 0)
				continue;
			
			switch ($mode) {
				case "largest":
					if ($entry['size'] > $max) {
						$max = $entry['size'];
						$foundentry = $entry;
					}
					break;
				case "extension":
					$bits = explode(".",$basename);				
					if (($count = count($bits)) > 1) {
						$ext = strtolower($bits[$count-1]);
						if ($ext == "csv" || $ext == "txt") {
							$foundentry = $entry;
						}
					}
					break;
			}
		}
		
		return $foundentry;
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