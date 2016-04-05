<?
const MIN_FRESHNESS = 60;
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");
require_once("dbmo//authserver/DmGroup.obj.php");
include_once("../inc/memcache.inc.php");

if (!$MANAGERUSER->authorized("systemdm")) {
	exit("Not Authorized");
}


function fmt_resources($row, $index) {
	if (!isset($row[$index])) {
		$str = '<div style="float: left; width: 250px; height: 16px; "></div>';
		return $str;
	}
	$dms = $row[$index];
	if (!$dms || count($dms) == 0) {
		$str = '<div style="float: left; width: 250px; height: 16px; "></div>';
		return $str;
	}

	$str = '';
	foreach ($dms as $dm) {
		$data = json_decode($dm['poststatus']);
		$data = $data[0];

		$restotal = $data->restotal;
		$resactout = $data->restotal ? $data->resactout / $restotal * 100 : 0;
		$resactin = $data->restotal ? $data->resactin / $restotal * 100 : 0;

		if ($dm['freshness'] > MIN_FRESHNESS) {
			$str .= '<div style="float: left; width: 220px; height: 16px; background: #FF0000; ">';
		} else {
			$str .= '<div style="float: left; width: 220px; height: 16px; ">';
		}
		$str .= '<div align="right" style="float: right; width: 220px; height: 16px;">';
		$str .= $dm['name'] . '&nbsp;';
		$str .= '<div style="float: right; width: 100px; height: 16px; border: 1px solid black;">';
		$str .= '<div style="float: left; width: ' . $resactout . 'px; height: 15px; background: #00BBFF;"></div>';
		$str .= '<div style="float: left; width: ' . $resactin . 'px; height: 15px; background: #FF00BB;"></div>';
		$str .= '</div></div></div>';
	}
	return $str;
}

$query = "select  dm.name,
			dm.dmuuid,
			'' as poststatus,
			dm.dmgroupid,
			now() - from_unixtime(dm.lastseen/1000) as freshness,
			s_delmech_resource_count.value as delmech_resource_count,
			dmgroup.carrierRateModelParams as carrierRateModelParams,
			dmgroup.carrierRateModelClassname as carrier
			from dm dm
			left join dmgroup on dm.dmgroupid = dmgroup.id
			left join dmsetting s_delmech_resource_count on
					(dm.id = s_delmech_resource_count.dmid
					and s_delmech_resource_count.name = 'delmech_resource_count')
			left join dmsetting s_dm_enabled on
					(dm.id = s_dm_enabled.dmid 
					and s_dm_enabled.name = 'dm_enabled')			
			where dm.type = 'system'
			 and s_dm_enabled.value = '1' and dm.enablestate='active'
			order by carrier, lpad(dm.name,50,' ')";


$result = Query($query);
$data = array();
$restotal = 0;
$resactout = 0;
$resactin = 0;

$memcaches = array();
if (isset($SETTINGS['memcache']) && isset($SETTINGS['memcache']['hosts'])) {
	foreach ((array)$SETTINGS['memcache']['hosts'] as $host) {
		$memcache = new Memcache();
		$memcache->addserver($host);
		$memcaches[] = $memcache;
	}
}

while ($row = DBGetRow($result)) {

	//fake some blank data when the api is unavailable
	$poststatus = "[{\"restotal\":0, \"resactout\": 0, \"resactin\":0}]";
	$cachedpoststatus = false;
	// since we don't have memcached and java is putting things in cache using a different hash
	// method the only way to find the key is to try both memcache servers.  While this is wonky
	// it works so what the heck.  Finally, for voipin's java compresses the value in a way that
	// again prevents memcache() from reading it, so for those we end up falling back to curl.
	// Still overall its WAY faster than using http for all dms.
	foreach ($memcaches as $memache) {
		try {
			$cachedpoststatus = $memache->get("dmpoststatus/" . $row[1]);
		} catch (Exception $e) {
			// nothing
		}
		if ($cachedpoststatus !== false) {
			break;
		}
	}
	if ($cachedpoststatus === false) {
		$managerApi = "https://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/manager/api/2";
		$dmUuid = $row[1];
		$url = "{$managerApi}/deliverymechanisms/{$dmUuid}";
		if (($fh = fopen($url, "r")) !== false) {
			$apidata = stream_get_contents($fh);
			fclose($fh);
			if ($apidata) {
				$dmdata = json_decode($apidata);
				if (isset($dmdata->postStatus)) {
					$poststatus = $dmdata->postStatus;
				}
			}
		}
	} else {
		$poststatus = $cachedpoststatus;
	}

	$dm = array();
	$dm['poststatus'] = $poststatus;
	$dm['name'] = $row[0];
	$dm['freshness'] = $row[4];
	$dm['resources'] = $row[5];

	$col = 0;
	$state = null;
	if ($row[6]) {
		$carrierdata = json_decode($row[6],true);
		if (isset($carrierdata['type'])) {
			$carrier = $row[7] . ($carrierdata['type'] ? " " . ucfirst($carrierdata['type']) : "");
		} else {
			$carrier = $row[7];
		}
		$state = $carrierdata['state'];
	} else if (preg_match('/^va-voipin/',$row[0])) {
		$carrier = "Inbound";
		$state = "va";
	} else if (preg_match('/^voipin/',$row[0])) {
		$carrier = "Inbound";
		$state = "ca";
	}

	if (isset($state)) {
		switch ($state) {
		case 'ca':
			$col = 1;
			break;
		case 'va':
			$col = 2;
			break;
		case 'il':
			$col = 3;
			break;
		}
		$data[$carrier][0] = $carrier;
		$data[$carrier][$col][] = $dm;
	}
}

// Add field titles, leading # means it is sortable leading @ means it is hidden by default
$titles = array(0 => "#Carrier");
$titles[1] = "#SV3";
$titles[2] = "#DC2";
$titles[3] = "#CH1";

$formatters = array(1 => "fmt_resources",
					2 => "fmt_resources",
					3 => "fmt_resources" );

/////////////////////////////
// Display
/////////////////////////////
$TITLE = _L("System&nbsp;DMs&nbsp;Summary");
$PAGE = "dm:systemdmsummary";

include_once("nav.inc.php");

startWindow(_L('System DMs Summary'));

if (count($data)) {
	?>
	<table class="list sortable" id="customer_dm_table">
	<?
		showTable($data, $titles, $formatters);
	?>
	</table>
	<?
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Records Found") . "</div>";
}
endWindow();

include_once("navbottom.inc.php");
?>
