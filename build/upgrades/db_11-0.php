<?
function upgrade_11_0($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 2);
		case 2:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 3);
		case 3:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 4);
		case 4:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 5);

			//enable ICRA for all existing customers
			Query("BEGIN", $db);
			if (!QuickQuery("select count(*) from setting where name = ?", $db, array("_hasicra"))) {
				$portal = QuickQuery("select value from setting where name = ?", $db, array("_hasportal"));
				QuickUpdate("insert into setting (name, value) values ('_hasicra',?)", false, array($portal ? 0 : 1));
			}
			Query("COMMIT", $db);
		case 5:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 6);
			
			// if quicktip customer, insert default organization settings
			Query("BEGIN", $db);
			$quickTipSettingName = "_hasquicktip";
			// if customer has quicktip feature
			if (QuickQuery("select 1 from setting where name = ? and value = '1' and organizationid is null", $db, array($quickTipSettingName))) {
				// check if quicktip for organizations
				$hasQtOrgSetting = QuickQuery('select 1 from setting where name = ? and organizationid is not null', $db, array($quickTipSettingName));
				if (!$hasQtOrgSetting) {
					// get all organizations and create a setting for each
					$organizations = QuickQueryList("select id, parentorganizationid from organization where not deleted", true, $db);
					foreach ($organizations as $id => $parentorganizationid) {
						// root org default to disable, all other orgs enabled
						if ($parentorganizationid)
							$v = "1";
						else
							$v = "0";
						QuickUpdate("insert into setting (name, value, organizationid) values (?, ?, ?)", $db, array($quickTipSettingName, $v, $id));
					}
				}
			}
			Query("COMMIT", $db);
	}
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);

	return true;
}

?>
