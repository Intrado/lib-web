<?
/* Serve Jmx read/execute requests
 * Uses Jolokia servlet running in jetty on target
 * 
 * - Nickolas Heckman
 */

class JmxClient {
	var $jolokiaUrl;
	
	// Initialize the client with the remote host's jetty url. Ex: http://localhost:8086
	function JmxClient($jettyUrl) {
		$this->jolokiaUrl = "$jettyUrl/jolokia";
	}
	
	// read the value of an mbean, or an individual attribute and return it.
	function read($mbean, $attribute = false) {
		return $this->_request($mbean, 'read', $attribute);
	}
	
	// execute an operation, providing an array of the required arguments.
	function exec($mbean, $operation, $arguments = array()) {
		return $this->_request($mbean, 'exec', false, $operation, $arguments);
	}
	
	// handle all request types via http post
	private function _request($mbean, $type, $attribute = false, $operation = false, $arguments = array()) {
		$postdata = array(
				'method' => 'POST',
				'type' => $type,
				'mbean' => $mbean);
		if ($attribute)
			$postdata['attribute'] = $attribute;
		if ($operation)
			$postdata['operation'] = $operation;
		if ($arguments)
			$postdata['arguments'] = $arguments;
		
		$http = array(
				'method' => 'POST',
				'content' => json_encode($postdata));
		
		// turn on error tracking so we can return the errors generated
		ini_set('track_errors', 1);
		
		$ctx = stream_context_create(array('http' => $http));
		$fp = @fopen($this->jolokiaUrl, 'rb', false, $ctx);
		
		$result = array();
		if (!$fp) {
			$result["error"] = $php_errormsg;
		} else {
			$response = @stream_get_contents($fp);
			if ($response === false)
				$result["error"] = $php_errormsg;
		}
		// turn error tracking back off
		ini_set('track_errors', 0);
		
		if (isset($result['error']))
			return $result;
		
		$resdata = json_decode($response, true);
		$result['value'] = $resdata['value'];
		
		return $result;
	}
}