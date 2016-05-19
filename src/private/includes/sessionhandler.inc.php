<?

//see if we are using memcache for sessions
if (isset($SETTINGS['memcache']['use_memcache_sessions']) && $SETTINGS['memcache']['use_memcache_sessions'] && isset($mcache)) {

	function mcache_sess_open($save_path, $session_name) {
		global $mcache_session_prefix;
		$mcache_session_prefix = "session.$session_name.";
		return true;
	}

	function mcache_sess_close() {
		return true;
	}

	function mcache_sess_read($id) {
		global $SETTINGS, $SESSION_READONLY, $mcache, $mcache_session_prefix, $page_lock_id;

		if ($SESSION_READONLY === true) {
			return $mcache->get($mcache_session_prefix . $id);
		} else {
			$page_lock_id = mt_rand();
			$max_lock_time = $SETTINGS['memcache']['memcache_session_lock_seconds'];
			$retry_us = $SETTINGS['memcache']['memcache_session_lock_retry_us'];
			
			$retry_us = max($retry_us, 1000);
			
			//try to get a lock
			$starttime = microtime(true);
			while (microtime(true) - $starttime < $max_lock_time) {
				if ($mcache->add($mcache_session_prefix . $id .".lock", $page_lock_id, 0, $max_lock_time))
					return $mcache->get($mcache_session_prefix . $id);
				usleep($retry_us);
			}
			$page_lock_id = "no lock aquired";
			
			error_log("Session unable to get a lock for session!");
		}
	}

	function mcache_sess_write($id, $sess_data) {
		global $SETTINGS, $SESSION_READONLY, $mcache, $mcache_session_prefix, $page_lock_id;
		
		if (isset($mcache)) { //ignore attempts to write if objects have been destructed
			if ($SESSION_READONLY === true)
				return true;
			else {
				
				//check to see if lock is still valid
				$lock_id = $mcache->get($mcache_session_prefix . $id .".lock");
				if ($lock_id != $page_lock_id) {
					error_log("memcache session lost lock on write! Expected $page_lock_id got $lock_id");
					return false;
				}
				
				$expiretime = $SETTINGS['memcache']['memcache_session_expire_mins'] * 60;
				$retval = $mcache->set($mcache_session_prefix . $id, $sess_data, 0, $expiretime);
				
				//remove the lock
				$mcache->delete($mcache_session_prefix . $id .".lock", 0);
				
				return $retval;
			}
		}
	}

	function mcache_sess_destroy($id) {
		global $SETTINGS, $SESSION_READONLY, $mcache, $mcache_session_prefix, $page_lock_id;
		
		$mcache->delete($mcache_session_prefix . $id .".lock", 0);
		$mcache->delete($mcache_session_prefix . $id, 0);

		return true;
	}

	function mcache_sess_gc($maxlifetime) {
		return true;
	}
	
	session_set_save_handler("mcache_sess_open", "mcache_sess_close", "mcache_sess_read", "mcache_sess_write", "mcache_sess_destroy", "mcache_sess_gc");
	
	//as of php 5.0.5, objects are destructed before session write handler is called, memcache is unusable then!
	//so the trick is to register session_write_close as a shutdown function. this will in turn call session write handler before object destruction.
	register_shutdown_function('session_write_close'); 

} else {
	//fallback to authserver

	//These functions are session handlers for authserver
	function sess_open($save_path, $session_name) {
		return(true);
	}

	function sess_close() {
		return(true);
	}

	function sess_read($id) {
		global $SESSION_READONLY;
		if ($SESSION_READONLY === true)
		return (string) getSessionDataReadOnly($id);
		else
		return (string) getSessionData($id);
	}

	function sess_write($id, $sess_data) {
		global $SESSION_READONLY;
		if ($SESSION_READONLY == true)
		return true;
		else
		return putSessionData($id, $sess_data);
	}

	function sess_destroy($id) {
		//FIXME this doesn't actually destroy the session!
		return(true);
	}

	function sess_gc($maxlifetime) {
		return true;
	}

	session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
}
?>
