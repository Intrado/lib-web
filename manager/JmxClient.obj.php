<?
/* Serve Jmx read/write/execute requests
 * Uses Jolokia servlet running in jetty on target
 * 
 * - Nickolas Heckman
 */

class JmxClient {
	var $jolokiaUrl;
	
	function JmxClient($jettyUrl) {
		$this->jolokiaUrl = "$jettyUrl/jolokia";
	}
	
	function read($mbean, $attribute = false) {
		$response = $this->_request($mbean, 'read', $attribute);
		if ($response)
			return $response['value'];
		else
			return false;
	}
	
	function exec($mbean, $operation, $arguments = array()) {
		return $this->_request($mbean, 'exec', false, $operation, $arguments);
	}
	
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
		$ctx = stream_context_create(array('http' => $http));
		$fp = @fopen($this->jolokiaUrl, 'rb', false, $ctx);
		if (!$fp)
			throw new Exception("Problem with {$this->jolokiaUrl}, $php_errormsg");
		$response = @stream_get_contents($fp);
		if ($response === false)
			throw new Exception("Problem reading data from {$this->jolokiaUrl}, $php_errormsg");
		
		return json_decode($response, true);
		
	}
}