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
	static function getAuthorizedOrgKeys($userid = false) {
		global $USER;
		
		if ($userid === false)
			$userid = $USER->id;
		
		static $validorgkeys = false; // Cache of valid organization ids.
	
		if ($validorgkeys === false) {
			if (QuickQuery("select 1 from userassociation where userid = ? and type in ('organization', 'section') limit 1", false, array($userid))) {
				$validorgkeys = QuickQueryList("
					(select o.id as oid, o.orgkey as okey
					from userassociation ua
						inner join organization o on (ua.organizationid = o.id)
					where ua.userid = ? and ua.type = 'organization')
					UNION
					(select o.id as oid, o.orgkey as okey
					from userassociation ua
						inner join section s on
							(ua.sectionid = s.id and ua.type = 'section')
						inner join organization o on
							(s.organizationid = o.id)
					where ua.userid = ?)
					order by okey",
					true, false, array($userid, $userid)
				);
			} else { // Unrestricted
				$validorgkeys = QuickQueryList('select id, orgkey from organization where not deleted order by orgkey', true, false);
			}
		}
		
		return $validorgkeys;
	}
}
?>
