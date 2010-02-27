<?
class Organization extends DBMappedObject {
	var $orgkey;
	var $deleted;
	

	function Organization ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "organization";
		$this->_fieldlist = array(
			"orgkey",
			"deleted"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	// Returns an array of organizationid => orgkey for organizations that
	// that the user can add as a rule.
	static function getAuthorizedOrgKeys() {
		global $USER;
		
		static $validorgkeys = false; // Cache of valid organization ids.
	
		if ($validorgkeys === false) {
			if (QuickQuery('select 1 from userassociation where userid = ? limit 1', false, array($USER->id))) {
				$validorgkeys = QuickQueryList('
					select o.id, o.orgkey
					from userassociation ua
						inner join organization o on (ua.organizationid = o.id)
					where ua.userid = ? and ua.type = "organization"',
					true, false, array($USER->id)
				);
			} else { // Unrestricted
				$validorgkeys = QuickQueryList('select id, orgkey from organization where not deleted', true, false);
			}
		}
		
		return $validorgkeys;
	}
}
?>
