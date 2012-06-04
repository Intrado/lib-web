<?
/*
 * Given parameters (userid list, date range) generate stats for display on dashboard.
 */
class DashboardStats {
	var $total_broadcasts;
	var $total_languages;
	var $total_senders;
	
	function generateStats($useridList, $start_datetime, $end_datetime) {
		// sql query parameters, always in same order for all stats
		$params = array();
		$params[] = $start_datetime;
		$params[] = $end_datetime;
		$params = array_merge($params, $useridList);
		
		// broadcasts
		$query = "select count(*) from job j " .
			"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
			"j.activedate >= ? and j.activedate <= ? and " .
			"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
			
		$this->total_broadcasts = QuickQuery($query, null, $params);

		// senders
		$query = "select count(distinct(j.userid)) from job j " .
			"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
			"j.activedate >= ? and j.activedate <= ? and " .
			"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
			
		$this->total_senders = QuickQuery($query, null, $params);
		
		// languages
		$query = "select count(distinct(m.languagecode)) from message m " .
			"join messagegroup mg on (mg.id = m.messagegroupid) " .
			"join job j on (j.messagegroupid = mg.id) " .
			"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
			"j.activedate >= ? and j.activedate <= ? and " .
			"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
			
		$this->total_languages = QuickQuery($query, null, $params);
		
	}
}
?>
