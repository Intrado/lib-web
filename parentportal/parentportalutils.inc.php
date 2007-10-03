<?

function getContactIDs($portaluserid){
	return QuickQueryList("select personid from personportal where portaluserid = '$portaluserid'");
}

function getContacts($portaluserid) {
	$contactList = getContactIDs($portaluserid);
	return DBFindMany("Person", "from person where id in ('" . implode("','", $contactList) . "') and not deleted");
}


?>