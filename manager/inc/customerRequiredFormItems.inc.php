<?

$formdata["enabled"] = array(
		"label" => _L('Enabled'),
		"fieldhelp" => "Unchecking this box will disable this customer!  All repeating jobs will be stopped.  All scheduled jobs must be canceled manually.",
		"value" => isset($custinfo)?$custinfo["enabled"]:"",
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => $helpstepnum
);

$formdata["softdisable"] = array(
		"label" => _L('Soft Disable'),
		"fieldhelp" => "This box is a soft disable for this customer.",
		"value" => isset($custinfo)?$custinfo["softdisable"]:"",
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => $helpstepnum
);

//Unable to change shard on this form
if (!$customerid) {
	$formdata["shard"] = array(
			"label" => _L('Shard'),
			"value" => "",
			"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($shards))
			),
			"control" => array("SelectMenu", "values" => array("" =>_L("-- Select a Shard --")) + $shards),
			"helpstep" => $helpstepnum
	);
}

$formdata["dmmethod"] = array(
		"label" => _L('DM Method'),
		"value" => $settings['_dmmethod'],
		"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($dmmethod))
		),
		"control" => array("SelectMenu", "values" => array("" =>_L("-- Select a Method --")) + $dmmethod),
		"helpstep" => $helpstepnum
);
$formdata["timezone"] = array(
		"label" => _L('Time zone'),
		"value" => $settings['timezone'],
		"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => $timezones)
		),
		"control" => array("SelectMenu", "values" => array_merge(array("" =>_L("-- Select a Timezone --")),array_combine($timezones,$timezones))),
		"helpstep" => $helpstepnum
);

$formdata["displayname"] = array(
		"label" => _L('Display Name'),
		"value" => $settings['displayname'],
		"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 3,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => $helpstepnum
);

$formdata["organizationfieldname"] = array(
		"label" => _L("'Organization' Display Name"),
		"value" => $settings['organizationfieldname'],
		"validators" => array(
				array("ValLength","min" => 3,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => $helpstepnum
);

$formdata["urlcomponent"] =	array(
		"label" => _L('URL Path'),
		"value" => $settings['urlcomponent'],
		"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 30),
				array("ValUrlComponent", "customerid" => $customerid, "urlcomponent" => $settings['urlcomponent'])
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => $helpstepnum
);

$formdata["logo"] = array(
		"label" => _L('Logo'),
		"value" => ($customerid && $settings['_logocontentid'] != '')?"Saved":'',
		"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($logos))
		),
		"control" => array("LogoRadioButton", "values" => $logos),
		"helpstep" => $helpstepnum
);
$formdata["logoclickurl"] = array(
		"label" => _L('Logo Click URL'),
		"value" => $settings['_logoclickurl'],
		"validators" => array(
				array("ValRequired"),
				array("ValUrl"),
				array("ValLength","min" => 3,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => $helpstepnum
);
$formdata["productname"] = array(
		"label" => _L('Brand'),
		"value" => $settings['_productname'],
		"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 3,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => $helpstepnum
);

$formdata["supportemail"] = array(
		"label" => _L('Support Email'),
		"value" => $settings['_supportemail'],
		"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 255),
				array("ValEmail")
		),
		"control" => array("TextField","maxlength"=>255,"min"=>3,"size"=>35),
		"helpstep" => $helpstepnum
);
$formdata["supportphone"] = array(
		"label" => _L('Support Phone'),
		"value" => $settings['_supportphone'],
		"validators" => array(
				array("ValRequired"),
				array("ValPhone")
		),
		"control" => array("TextField","size" => 15, "maxlength" => 20),
		"helpstep" => $helpstepnum
);

$formdata["callerid"] = array(
		"label" => _L('Default Caller ID'),
		"value" => $settings['callerid'],
		"validators" => array(
				array("ValRequired"),
				array("ValPhone")
		),
		"control" => array("TextField","size" => 15, "maxlength" => 20),
		"helpstep" => $helpstepnum
);
$formdata["defaultareacode"] = array(
		"label" => _L('Default Area Code'),
		"value" => $settings['defaultareacode'],
		"validators" => array(
				array('ValNumber')
		),
		"control" => array("TextField","size" => 3, "maxlength" => 3),
		"helpstep" => $helpstepnum
);

$formdata["nsid"] = array(
		"label" => _L('NetSuite ID'),
		"value" => isset($custinfo)?$custinfo["nsid"]:"",
		"validators" => array(
				array("ValLength","max" => 50)
		),
		"control" => array("TextField","maxlength"=>50,"size"=>4),
		"helpstep" => $helpstepnum
);

$formdata["notes"] = array(
		"label" => _L('Notes'),
		"value" => isset($custinfo)?$custinfo["notes"]:"",
		"validators" => array(),
		"control" => array("TextArea", "rows" => 3, "cols" => 100),
		"helpstep" => $helpstepnum
);

function saveRequiredFields($custdb,$customerid,$postdata) {
	global $SETTINGS,$defaultlogos;
	
	$query = "update customer set
		urlcomponent = ?,
		enabled=?,
		nsid=?,
		notes=?
		where id = ?";
	
	QuickUpdate($query,false,array(
			$postdata["urlcomponent"],
			$postdata["enabled"]?'1':'0',
			$postdata["nsid"],
			$postdata["notes"],
			$customerid
	));
	
	// notify authserver to refresh the customer cache
	refreshCustomer($customerid);
	
	$shardinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id = ?", true,false,array($customerid));
	$sharddb = DBConnect($shardinfo["dbhost"], $shardinfo["dbusername"], $shardinfo["dbpassword"], "aspshard");
	if(!$sharddb) {
		exit("Connection failed for customer: $customerid, shardhost: {$shardinfo["dbhost"]}");
	}
	
	// if timezone changed (rare occurance, but we must update scheduled jobs and report records on the shard database)
	if ($postdata["timezone"] != getCustomerSystemSetting('timezone', false, true, $custdb)) {
		QuickUpdate("update qjob set timezone=? where customerid=?", $sharddb, array($postdata["timezone"],$customerid));
		QuickUpdate("update qschedule set timezone=? where customerid=?", $sharddb,array($postdata["timezone"],$customerid));
		QuickUpdate("update qreportsubscription set timezone=? where customerid=?", $sharddb,array($postdata["timezone"],$customerid));
	}
	
	if (!$postdata["enabled"]) {
		setCustomerSystemSetting("disablerepeat", "1", $custdb);
		setCustomerSystemSetting("_customerenabled", "0", $custdb);
		// Remove active import alerts but leave the alert rules since they will not trigger for disabled customers
		QuickUpdate("delete from importalert where customerid=?", $sharddb, array($customerid));

	} else if ($postdata["softdisable"]) {
		setCustomerSystemSetting("_customersoftdisable", "0", $custdb);
	} else {
		setCustomerSystemSetting("_customerenabled", "1", $custdb);
		setCustomerSystemSetting("_customersoftdisable", "1", $custdb);
	}

	if(getCustomerSystemSetting('_dmmethod', '', true, $custdb)!='asp' && $postdata["dmmethod"] == 'asp'){
		$aspquery = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'");
		$aspsharddb = DBConnect($aspquery[0], $aspquery[1], $aspquery[2], "aspshard");
		QuickUpdate("delete from specialtaskqueue where customerid = " . $customerid, $aspsharddb);
		QuickUpdate("update qjob set dispatchtype = 'system' where customerid = " . $customerid . " and status = 'active'", $aspsharddb);
	}
	setCustomerSystemSetting('_dmmethod', $postdata["dmmethod"], $custdb);
	setCustomerSystemSetting('timezone', $postdata["timezone"], $custdb);
	setCustomerSystemSetting('displayname', $postdata["displayname"], $custdb);
	setCustomerSystemSetting('organizationfieldname', $postdata['organizationfieldname'], $custdb);
	
	setCustomerSystemSetting('urlcomponent', $postdata["urlcomponent"], $custdb);
	setCustomerSystemSetting('surveyurl', $SETTINGS['feature']['customer_url_prefix'] . "/" . $postdata["urlcomponent"] . "/survey/", $custdb);
	
	// Logo Picture
	$logo = $postdata["logo"];
	if (isset($defaultlogos[$logo])) {
		$logofile = @file_get_contents($defaultlogos[$logo]['filelocation']);
		if($logofile) {
			$query = "INSERT INTO `content` (`contenttype`, `data`) VALUES
			('" . $defaultlogos[$logo]["filetype"] . "', '" . base64_encode($logofile) . "');";
			QuickUpdate($query, $custdb);
			$logocontentid = $custdb->lastInsertId();
			setCustomerSystemSetting('_logocontentid', $logocontentid, $custdb);
		}
	}
	
	setCustomerSystemSetting('_logoclickurl', $postdata["logoclickurl"], $custdb);
	setCustomerSystemSetting('_productname',  $postdata["productname"],$custdb);
	setCustomerSystemSetting('_supportemail', $postdata["supportemail"], $custdb);
	setCustomerSystemSetting('_supportphone', Phone::parse($postdata["supportphone"]), $custdb);
	setCustomerSystemSetting('callerid', Phone::parse($postdata["callerid"]), $custdb);
	setCustomerSystemSetting('defaultareacode', $postdata["defaultareacode"], $custdb);

}


?>