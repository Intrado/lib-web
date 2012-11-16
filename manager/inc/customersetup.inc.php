<?


function createnewcustomer($shardid) {
	global $_dbcon;
	//choose shard info based on selection
	
	$shardinfo = QuickQueryRow("select id, dbhost, dbusername, dbpassword from shard where id = ?", true,false,array($shardid));
	
	$csShortcodeGroupId = QuickQuery("select id from shortcodegroup where product = 'cs' and isdefault = 1");
	$taiShortcodeGroupId = QuickQuery("select id from shortcodegroup where product = 'tai' and isdefault = 1");
	
	$shardid = $shardinfo['id'];
	$shardhost = $shardinfo['dbhost'];
	$sharduser = $shardinfo['dbusername'];
	$shardpass = $shardinfo['dbpassword'];
	$dbpassword = genpassword();
	$limitedpassword = genpassword();
	QuickUpdate("insert into customer (urlcomponent, shardid, dbpassword, limitedpassword, shortcodegroupid, taishortcodegroupid)
																values (?, ?, ?, ?, ?, ?)", false, array('', $shardid, $dbpassword, $limitedpassword, $csShortcodeGroupId, $taiShortcodeGroupId) )
	or dieWithError("failed to insert customer into auth server", $_dbcon);
		
	$customerid = $_dbcon->lastInsertId();
	$custdbname = "c_$customerid";
	$limitedusername = "c_".$customerid."_limited";
	QuickUpdate("update customer set dbusername = '" . $custdbname . "', limitedusername = '" . $limitedusername . "' where id = '" . $customerid . "'");
		
	$custdb = DBConnect($shardhost, $sharduser, $shardpass, "aspshard");
	QuickUpdate("create database $custdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",$custdb)
	or dieWithError("Failed to create new DB ".$custdbname, $custdb);
	$custdb->query("use ".$custdbname)
	or dieWithError("Failed to connect to DB ".$custdbname, $custdb);
		
	// customer db user
	$grantedhost = '%';
	if (isset($SETTINGS['feature']['should_grant_local']) && $SETTINGS['feature']['should_grant_local']) {
		$grantedhost = 'localhost';
	}
	QuickUpdate("drop user '$custdbname'@'$grantedhost'", $custdb); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
	QuickUpdate("create user '$custdbname'@'$grantedhost' identified by '$dbpassword'", $custdb);
	QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $custdbname . * to '$custdbname'@'$grantedhost'", $custdb);
		
	// create customer tables
	$tablequeries = explode("$$$",file_get_contents("../db/customer.sql"));
	$tablequeries = array_merge($tablequeries, explode("$$$",file_get_contents("../db/createtriggers.sql")));
	foreach ($tablequeries as $tablequery) {
		if (trim($tablequery)) {
			$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
			Query($tablequery,$custdb)
			or dieWithError("Failed to execute statement \n$tablequery\n\nfor $custdbname", $custdb);
		}
	}
		
	// subscriber db user
	createLimitedUser($limitedusername, $limitedpassword, $custdbname, $custdb, $grantedhost);
		
	// 'schoolmessenger' user
	createSMUserProfile($custdb, $custdbname);
		
	$query = "INSERT INTO `fieldmap` (`fieldnum`, `name`, `options`) VALUES
											('f01', 'First Name', 'searchable,text,firstname,subscribe,dynamic'),
											('f02', 'Last Name', 'searchable,text,lastname,subscribe,dynamic'),
											('f03', 'Language', 'searchable,multisearch,language,subscribe,static')";
	QuickUpdate($query, $custdb) or dieWithError("SQL:" . $query, $custdb);
	
	$query = "INSERT INTO `language` (`name`,`code`) VALUES
														('English','en'),
														('Spanish','es')";
	QuickUpdate($query, $custdb) or dieWithError("SQL:" . $query, $custdb);
		
	// Setup all commsuite specific Triggers and Values
	// TODO in the future this should only be done if commsuite is enabled
	cs_setup($customerid, $custdb);
	
		
	// Set Session to make the save button stay on the page
	$_SESSION['customerid']= $customerid;
	
	return $custdb;
}



// Setup Commsuite product for customer.
// first time, one time, setup procedure
// IMPORTANT!!! $_dbcon must be shard user to create tables, must "use c_X" database prior to call
function cs_setup($customerid,$custdb) {
	global $_dbcon;
	
	// Create all commsuite specific
	$tablequeries = explode("$$$",file_get_contents("../db/targetedmessages.sql"));
	foreach ($tablequeries as $tablequery) {
		if (trim($tablequery)) {
			$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
			Query($tablequery,$custdb)
			or dieWithError("Failed to execute statement \n$tablequery\n\nfor $custdbname", $custdb);
		}
	}

	$query = "INSERT INTO `notificationtype` (`name`, `systempriority`, `info`, `deleted`, `type`) VALUES
											('Emergency', 1, 'Emergencies Only', 0, 'job'),
											('Attendance', 2, 'Attendance', 0, 'job'),
											('General', 3, 'General Announcements', 0, 'job'),
											('Survey', 3, 'Surveys', 0, 'survey')";

	QuickUpdate($query, $custdb) or dieWithError(" SQL:" . $query, $custdb);

	$query = "INSERT INTO `jobtypepref` (`jobtypeid`,`type`,`sequence`,`enabled`) VALUES
											(1,'phone',0,1),
											(1,'email',0,1),
											(1,'sms',0,1),
											(2,'phone',0,1),
											(2,'email',0,1),
											(2,'sms',0,1),
											(3,'phone',0,1),
											(3,'email',0,1),
											(3,'sms',0,1),
											(4,'phone',0,1),
											(4,'email',0,1),
											(4,'sms',0,0)";

	QuickUpdate($query, $custdb) or dieWithError(" SQL:" . $query, $custdb);

	// Login Picture
	QuickUpdate("INSERT INTO content (contenttype, data) values
											('image/gif', '" . base64_encode(file_get_contents("mimg/classroom_girl.jpg")) . "')",$custdb);
	$loginpicturecontentid = $custdb->lastInsertId();

	$query = "INSERT INTO `setting` (`name`, `value`) VALUES
											('_loginpicturecontentid', '" . $loginpicturecontentid . "')";
	QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);

				// Subscriber Login Picture
	QuickUpdate("INSERT INTO content (contenttype, data) values
											('image/gif', '" . base64_encode(file_get_contents("mimg/header_highered3.gif")) . "')",$custdb);
				$subscriberloginpicturecontentid = $custdb->lastInsertId();

	$query = "INSERT INTO `setting` (`name`, `value`) VALUES
											('_subscriberloginpicturecontentid', '" . $subscriberloginpicturecontentid . "')";
	QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);

	// Classroom Message Category
	$query = "INSERT INTO `targetedmessagecategory` (`id`, `name`, `deleted`, `image`) VALUES
											(1, 'Default', 0, 'blue dot')";
				QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);

	// set global to customer db, restore after this section
	global $_dbcon;
	$savedbcon = $_dbcon;
	$_dbcon = $custdb;

	// Default Email Templates
	if (!createDefaultTemplates())
		return false;

	// restore global db connection
	$_dbcon = $savedbcon;
}

// Setup Talk-About-It product for customer.
// first time, one time, setup procedure
// IMPORTANT!!! $_dbcon must be shard user to create tables, must "use c_X" database prior to call
function tai_setup($customerid) {
	// check if already setup
	if (QuickQuery("select 1 from setting where name = '_dbtaiversion'")) {
		error_log("debug: tai schema already exists, skip setup customerid $customerid");
		return;
	}
	error_log("debug: tai setup customerid $customerid");

	// create customer tables
	$tablequeries = explode("$$$",file_get_contents("../db/taicustomer.sql"));
	foreach ($tablequeries as $tablequery) {
		if (trim($tablequery)) {
			$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
			Query($tablequery)
				or dieWithError("Failed to execute statement \n$tablequery\n\nfor customerid $customerid");
		}
	}

	// get schoolmessenger user
	$query = "from user where login = 'schoolmessenger'";
	$schoolmessengeruser = DBFind("User", $query);
	
	// permissions for schoolmessenger user
	$query = "INSERT INTO permission (accessid, name, value) VALUES
				($schoolmessengeruser->accessid, 'tai_canforwardthread', 1), 
				($schoolmessengeruser->accessid, 'tai_canviewreports', 1), 
				($schoolmessengeruser->accessid, 'tai_canmanagenews', 1), 
				($schoolmessengeruser->accessid, 'tai_cansendanonymously', 1), 
				($schoolmessengeruser->accessid, 'tai_canmanagetopics', 1), 
				($schoolmessengeruser->accessid, 'tai_canbetopicrecipient', 1), 
				($schoolmessengeruser->accessid, 'tai_canusecannedresponses', 1), 
				($schoolmessengeruser->accessid, 'tai_canmanagecannedresponses', 1), 
				($schoolmessengeruser->accessid, 'tai_canrequestidentityreveal', 1), 
				($schoolmessengeruser->accessid, 'tai_canmanagesurveys', 1), 
				($schoolmessengeruser->accessid, 'tai_canmanagelockouts', 1), 
				($schoolmessengeruser->accessid, 'tai_canmanageactivationcodes', 1),
				($schoolmessengeruser->accessid, 'tai_canmodifydisplayname', 1),
				($schoolmessengeruser->accessid, 'tai_canviewunreadmessagereport', 1)";
	QuickUpdate($query)
		or dieWithError(" SQL: " . $query);
	
	// add person for user
	$person = new Person();
	$person->userid = $schoolmessengeruser->id;
	$person->type = "addressbook";
	if (!$person->create()) {
		// TODO verify error handling works
		dieWithError("Failed to create person for schoolmessenger user");
	}
	$schoolmessengeruser->personid = $person->id;
	$schoolmessengeruser->update();

	// create root organization
	$rootorgkey = "District"; // be paranoid and loop until unique orgkey found, may already exist
	$query = "select 1 from organization where orgkey like ?";
	$i = 0;
	while (QuickQuery($query, null, array("%" . $rootorgkey . "%"))) {
		$i++;
		$rootorgkey .= "".$i;
	}
	$org = new Organization();
	$org->orgkey = $rootorgkey;
	$org->create();
	
	// make all existing orgs children of the new root org
	$query = "update organization set parentorganizationid = ? where orgkey not like ?";
	QuickUpdate($query, null, array($org->id, $rootorgkey));
	
	// create role for schoolmessenger user
	$query = "INSERT INTO `role` (`userid`, `accessid`, `organizationid`) VALUES (?, ?, ?)";
	QuickUpdate($query, null, array($schoolmessengeruser->id, $schoolmessengeruser->accessid, $org->id));
}

?>