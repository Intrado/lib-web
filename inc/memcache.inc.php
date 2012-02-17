<?

// MemcachePool (global $mcache) methods from original (per README)
		
//	bool connect(string host, int tcp_port = 11211, int udp_port = 0, bool persistent = true, int weight = 1, int timeout = 1, int retry_interval = 15)
//	bool addServer(string host, int tcp_port = 11211, int udp_port = 0, bool persistent = true, int weight = 1, int timeout = 1, int retry_interval = 15, bool status = true)
//	bool setServerParams(string host, int tcp_port = 11211, int timeout = 1, int retry_interval = 15, bool status = true)
	
	/**
	 * Supports fetching flags and CAS values
	 */
//	mixed get(mixed key, mixed &flags = null, mixed &cas = null)
	
	/**
	 * Supports multi-set, for example
	 *  $memcache->set(array('key1' => 'val1', 'key2' => 'val1'), null, 0, 60)
	 */
//	bool add(mixed key, mixed var = null, int flag = 0, int exptime = 0)
//	bool set(mixed key, mixed var = null, int flag = 0, int exptime = 0)
//	bool replace(mixed key, mixed var = null, int flag = 0, int exptime = 0)
	
	/**
	 * Compare-and-Swap, uses the CAS param from MemcachePool::get() 
	 */
//	bool cas(mixed key, mixed var = null, int flag = 0, int exptime = 0, int cas = 0)
	
	/**
	 * Prepends/appends a value to an existing one
	 */
//	bool append(mixed key, mixed var = null, int flag = 0, int exptime = 0)
//	bool prepend(mixed key, mixed var = null, int flag = 0, int exptime = 0)
	
	/**
	 * Supports multi-key operations, for example
	 *  $memcache->delete(array('key1', 'key2'))
	 */
//	bool delete(mixed key, int exptime = 0)

	/**
	 * Supports multi-key operations, for example
	 *  $memcache->increment(array('key1', 'key2'), 1, 0, 0)
	 *
	 * The new defval (default value) and exptime (expiration time) are used
	 * if the key doesn't already exist. They must be supplied (even if 0) for
	 * this to be enabled.
	 *
	 * Returns an integer with the new value if key is a string
	 * Returns an array of integers if the key is an array
	 */
//	mixed increment(mixed key, int value = 1, int defval = 0, int exptime = 0)
//	mixed decrement(mixed key, int value = 1, int defval = 0, int exptime = 0)
	
	/**
	 * Assigns a pool-specific failure callback which will be called when 
	 * a request fails. May be null in order to disable callbacks. The callback
	 * receive arguments like
	 *
	 *  function mycallback($host, $tcp_port, $udp_port, $error, $errnum)
	 *
	 * Where $host and $error are strings or null, the other params are integers.
	 */
//	bool setFailureCallback(function callback)
	
	/**
	 * Locates the server a given would be hashed to
	 * 
	 * Returns a string "hostname:port" on success
	 * Returns false on failure such as invalid key
	 */
//	string findServer(string key)


/**
 * Tries to get a bit of data from the cache. If item is not found, then generate the data and save.
 * If memcache is not available, just acts as a wrapper and returns data from the callback.
 * 
 * @param exptime the TTL for the cache item
 * @param expect a map of key-value pairs that act as a precondition of accepting cache data. 
 * 				When cache data is saved, it is wrapped with this additional metadata. When cache data is retrieved, 
 * 				it is compared agaist this data, and if there is a mismatch, it is invalidated and regenerated. 
 * 				This is especially useful for checking the modified field of an object before relying on a cached calculation result. 
 * @param key the key to use in the cache.
 * 	<ul> 
 * 				<li>If key is specified, it must be <= 250 chars and conform to memcache key restrictions (no spaces, no control characters)</li>
 * 				<li>Keys must be unique across customers, or collisions will occur (read: data corruption)</li>
 * 				<li>If key is null, callback, customerurl, and args are converted to strings and used instead.</li>
 * 				<li>If this is the case, args are converted to strings using http_build_query.</li>
 * 				<li>If generated key becomes too large, is it truncated and replaced with a hash.</li>
 * </ul>
 * @param callback a callback function to generate the data
 * @param ... all remaining aguments are passed to callback
 * 
 * @return returns the results from the callback (possibly from prior invocations).
 */
function gen2cache ($exptime, $expect = null, $key = null, $callback /*, arg1, arg2 */) {
	global $mcache;
	$args = func_get_args();
	$args = array_splice($args, 4); //just get callback args
	
	//if memcache is not available, just proxy to the callback
	if (!isset($mcache))
		return call_user_func_array($callback, $args);
	
	if ($key === null)
		$key = callback2cachekey($callback, $args);

	if (($data = $mcache->get($key)) !== false) {
		
		//see if this is wrapped with expect
		if ($expect != null || is_array($data) && isset($data['gen2cache_expect'])) {
			//we could have used flags, but meh
			if (is_array($data) && isset($data['gen2cache_expect'])) {
				if ($expect == $data['gen2cache_expect'])
					return $data["data"]; //data is good, unwrap
			} else {
				//invalid, expect was passed but entry is missing wrapped data
				error_log_helper("gen2cache expected data to contain wrapped data, but was missing. possibly key overlap between calls using expect and not");
			}
			//if we haven't returned by now, data is invalid.
			$mcache->delete($key);
			//continue, and generate data again
		} else {
			//unwrapped data, just return it
			return $data;
		}
	}
		
	//otherwise generate and put

	$data = call_user_func_array($callback, $args);
	
	//see if we need to wrap for expect
	if ($expect != null) {
		$cachedata = array("gen2cache_expect" => $expect, "data" => $data);
	} else {
		$cachedata = $data;
	}
	
	$mcache->set($key, $cachedata, 0, $exptime);
	
	return $data;
}


/**
 * Tries to get a bit of data from the cache. If item is not found, try to get a lock, then generate the data and save.
 * Other clients should block on the lock until the item is generated, thus only creating the item once.
 * If memcache is not available, just acts as a wrapper and returns data from the callback.
 * 
 * @param exptime the TTL for the cache item
 * @param expect a map of key-value pairs that act as a precondition of accepting cache data. 
 * 				When cache data is saved, it is wrapped with this additional metadata. When cache data is retrieved, 
 * 				it is compared agaist this data, and if there is a mismatch, it is invalidated and regenerated. 
 * 				This is especially useful for checking the modified field of an object before relying on a cached calculation result. 
 * @param key the key to use in the cache.
 * 	<ul> 
 * 				<li>If key is specified, it must be <= 250 chars and conform to memcache key restrictions (no spaces, no control characters)</li>
 * 				<li>Keys must be unique across customers, or collisions will occur (read: data corruption)</li>
 * 				<li>If key is null, callback, customerurl, and args are converted to strings and used instead.</li>
 * 				<li>If this is the case, args are converted to strings using http_build_query.</li>
 * 				<li>If generated key becomes too large, is it truncated and replaced with a hash.</li>
 * </ul>
 * @param callback a callback function to generate the data
 * @param ... all remaining aguments are passed to callback
 * 
 * @return returns the results from the callback (possibly from prior invocations).
 */
function gen2cachelock ($exptime, $expect = null, $key = null, $callback /*, arg1, arg2 */) {
	global $mcache;
	$args = func_get_args();
	$args = array_splice($args, 4); //just get callback args
	
	//if memcache is not available, just proxy to the callback
	if (!isset($mcache))
		return call_user_func_array($callback, $args);
	
	if ($key === null)
		$key = callback2cachekey($callback, $args);
	
	//try to get a lock
	$lock_id = mt_rand();
	$max_lock_time = 120; //2 minutes
	$retry_us = 20000; //20ms
	$starttime = microtime(true);
	while (microtime(true) - $starttime < $max_lock_time) {
		//try a get
		if (($data = $mcache->get($key)) !== false) {
			
			//see if this is wrapped with expect
			if ($expect != null || is_array($data) && isset($data['gen2cache_expect'])) {
				//we could have used flags, but meh
				if (is_array($data) && isset($data['gen2cache_expect'])) {
					if ($expect == $data['gen2cache_expect'])
						return $data["data"]; //data is good, unwrap
				} else {
					//invalid, expect was passed but entry is missing wrapped data
					error_log_helper("gen2cache expected data to contain wrapped data, but was missing. possibly key overlap between calls using expect and not");
				}
				//if we haven't returned by now, data is invalid.
				$mcache->delete($key);
				continue;
			}
			//unwrapped data, just return it
			return $data;
			
			//otherwise try a lock
		} else if ($mcache->add($key.".lock", $lock_id, 0, $max_lock_time)) {
			//if lock accuired, generate and put
			$data = call_user_func_array($callback, $args);
			
			//see if we need to wrap for expect
			if ($expect != null) {
				$cachedata = array("gen2cache_expect" => $expect, "data" => $data);
			} else {
				$cachedata = $data;
			}
			
			$mcache->set($key, $cachedata, 0, $exptime);
			//delete lock
			$mcache->delete($key.".lock");
			
			return $data;
		} else {
			//otherwise sleep and try again
			usleep($retry_us);
		}
	}
	
	return call_user_func_array($callback, $args);; //timed out, just call callback
}

/**
 * Helper function which deletes the cache data created by gen2cache.
 * Safe to use if memcache isn't available.
 */
function gen2cache_invalidate($callback /*, arg1, arg2 */) {
	global $mcache;
	
	if (!isset($mcache))
		return;
	
	//TODO set/check the lock so that race conditions between regenerating data can't occur.
	$args = func_get_args();
	$args = array_splice($args, 1); //just get callback args
	$mcache->delete(callback2cachekey($callback, $args));
}

/**
 * Creates a key suitable for use in memcache. 
 * Tries to retain as much human readable text as possible while guarantying a unique key.
 * Args are converted to strings using http_build_query
 * Doesn't work with create_function() or closures as they can vary at run-time (wouldn't find cached items again).
 */
function callback2cachekey ($callback, $args) {
	global $CUSTOMERURL;
	//handle object method callbacks, ie array('MyClass', 'myCallbackMethod')
	if (is_array($callback))
		$callback = implode("::", $callback);
	
	$key = "MemcachePool_" . $CUSTOMERURL . "_" . $callback . ":" . http_build_query($args, false, "&");
	if (strlen($key) > 200) {
		$hash = hash("sha256", $key);
		$key = substr($key, 0, 133) . "---" . $hash;
	}
	return $key;
}



function init_memcache() {
	global $mcache, $SETTINGS;
	
	if (!class_exists("MemcachePool") || !isset($SETTINGS['memcache']) || !isset($SETTINGS['memcache']['memcached_url']))
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