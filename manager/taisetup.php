<?
require_once("../obj/Person.obj.php");
require_once("../obj/User.obj.php");
require_once("../obj/Organization.obj.php");


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
				($schoolmessengeruser->accessid, 'tai_canmanageactivationcodes', 1)";
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
	$rootorgkey = "Customer"; // be paranoid and loop until unique orgkey found, may already exist
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

