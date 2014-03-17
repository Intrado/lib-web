<?

use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;

/**
 *
 */
class MessageLinkModel {

	public $client;
	public $protocol;
	public $transport;
	public $attributes;

	/**
	 * @param $protocol
	 * @param $transport
	 */
	public function __construct($protocol, $transport) {
		if (isset($protocol)) {
			$this->protocol = $protocol;
		}

		if (isset($transport)) {
			$this->transport = $transport;
		}
	}

	/**
	 *
	 */
	public function initialize() {
		if (isset($this->protocol) && isset($this->transport)) {
			$attempts = 0;
			// get a new MessageLinkClient, considered the "model"
			// and open the transport connection
			$this->client = new MessageLinkClient($this->protocol);

			while (true) {
				try {
					$this->transport->open();
					break;
				} catch (TException $tx) {
					// MessageLinkClient instantiation was unsuccessfull
					$attempts++;
					error_log("getInfo: Exception Connection to AppServer (" . $tx->getMessage() . ")");
					$this->transport->close();

					// if $attempts > 2, show error message, else instantiate MessageLinkClient again
					if ($attempts > 2) {
						error_log("getInfo: Failed 3 times to get content from appserver");
						break;
					}
				}
			}
		}
	}

	/**
	 * @param $code
	 */
	public function fetchRequestCodeData($code) {
		if (isset($this->client)) {
			try {
				$this->attributes = $this->client->getInfo($code);
			} catch (MessageLinkCodeNotFoundException $e) {
				error_log("Unable to find the messagelinkcode: " . urlencode($code));
			}
			$this->transport->close();
		}
	}

	/**
	 * @return object model attributes from getInfo(code) response
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @param string $attr
	 * @return mixed
	 */
	public function get($attr) {
		return $this->attributes->$attr;
	}

	/**
	 * @param array $attributes
	 */
	public function setAttributes($attributes) {
		if (is_array($attributes) && count($attributes) > 0) {
			foreach($attributes as $attr => $value) {
				$this->attributes->$attr = $value;
			}
		}
	}
}

?>