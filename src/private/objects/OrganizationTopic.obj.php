<?
class OrganizationTopic extends DBMappedObject {
	var $organizationid;
	var $topicid;

	function OrganizationTopic($id = NULL) {
		$this->_tablename = "tai_organizationtopic";
		$this->_fieldlist = array("organizationid", "topicid");

		DBMappedObject::DBMappedObject($id);
	}

}