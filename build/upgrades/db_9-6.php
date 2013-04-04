<?

function upgrade_9_6 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			//FIXME this file has nothing for this rev, no point in applying it!
			apply_sql("upgrades/db_9-6_pre.sql", $customerid, $db, 1);
			
			// insert customerproduct 'cs' for all non-TAI customers
			// insert customerproduct 'cm' for all contact manager enabled customers
			
			//FIXME this is not an appropriate way to determine if a customer has the TAI product enabled.
			$hasTai = QuickQuery("select 1 from setting where name like '_tai%'", $db);
			if ($hasTai === false) {
				// not tai, insert commsuite
				$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES (?,'cs',?,?,1)";
				QuickUpdate($query, $authdb, array($customerid, time(), time()));
				
				$hasCm = QuickQuery("select value from setting where name like '_hasportal'", $db);
				if ($hasCm) {
					$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES (?,'cm',?,?,1)";
					QuickUpdate($query, $authdb, array($customerid, time(), time()));
				}
			} // else has tai, do nothing
	}
	
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}


?>