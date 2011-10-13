<?

function log_memcache_error($host, $port) {
	error_log_helper("Problem connecting to memcache on $host:$port");	
}

function init_memcache() {
	global $mcache, $SETTINGS;
	
	if (!isset($SETTINGS['memcache']) || !isset($SETTINGS['memcache']['memcached_url']))
		return;
	
	$mcache = new MemcachePool();
	
	foreach ($SETTINGS['memcache']['memcached_url'] as $memcacheurl) {
		//parse out the host (or url), port, and options
		//memcached_url[]="tcp://127.0.0.1:11211?persistent=1"

		$urlbits = parse_url($memcacheurl);
		
		if (isset($urlbits['query']))
			$options = sane_parsestr($urlbits['query']);
		else
			$options = array();
		
		$mcache->addServer(
			$urlbits['host'],  //host ie tcp://127.0.0.1 or unix:///path/to/memcached.sock
			isset($urlbits['port']) ? $urlbits['port'] : 11211, //port, 0 for unix socket
			isset($urlbits['udp_port']) && $urlbits['udp_port'] ? $urlbits['udp_port'] : 0, //udp_port, non zero indicates udp
			isset($options['persistent']) ? $options['persistent'] : true, //persistent
			isset($options['weight']) ? $options['weight'] : 1, //weight
			isset($options['timeout']) ? $options['timeout'] : 1.0, //timeout (float)
			isset($options['retry_interval']) ? $options['retry_interval'] : 15, //retry_interval
			isset($options['status']) ? $options['status'] : true //status (is server enabled)
		);
	}
}

init_memcache();



?>