<?

class EasyCall {
	private $specialtask;

	function EasyCall($specialtask) {
		$this->specialtask = $specialtask;
	}

	public function startCall() {
		$this->specialtask->setData("progress", "Calling");
		$this->specialtask->update();
	}

	public function endCall() {
		$this->specialtask->status = "done";
		$this->specialtask->setData("progress", "Call Ended");
		$this->specialtask->setData("error", "callended");
		$this->specialtask->update();
	}

	public function hasExtension() {
		return trim($this->specialtask->getData('phoneextension')) !== '';
	}

	public function extensionDigits() {
		return str_split($this->specialtask->getData('phoneextension'));
	}

	public function phone() {
		return $this->specialtask->getData('phonenumber');
	}

	public function callerid() {
		return $this->specialtask->getData('callerid');
	}

}
?>