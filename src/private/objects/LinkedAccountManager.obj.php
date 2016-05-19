<?

/**
 * A utility class of fetching associated accounts and disassociating accounts
 *
 */
class LinkedAccountManager {

	function __construct() {

	}

	/**
	 * Get associated accounts for given person id
	 * @param string $personId person id
	 * @return array list of accounts mapped by account id
	 */
	function getAssociatedAccounts($personId) {
		$query = "select portaluserid from user where id in (select userid from useraccess where personid=?)";
		$accounts = QuickQueryList($query, false, false, array($personId));
		if (count($accounts)) {
			return $this->getAccountDetails($accounts);
		} else {
			return array();
		}
	}

	/**
	 *  Get account details for given ids
	 * @param array $accountIds array of account ids
	 * @return array list of accounts mapped by account id
	 */
	function getAccountDetails($accountIds) {
		$associates = getPortalUsers($accountIds);
		return $associates;
	}

	/**
	 * Dissociate person's linked account
	 * @param type $personId person id
	 * @param type $portalUserId portal user id
	 * @return type count of deleted rows
	 */
	function disassociateAccount($personId, $portalUserId) {
		$count = QuickUpdate("delete from useraccess where personid=? and userid in (select id from user where portaluserid=?)", false, array($personId, $portalUserId));
		return $count;
	}

}

?>