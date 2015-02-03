<?
class RenderedRecipient {
	var $recipientPersonId;
	var $targetPersonId;
	
	function RenderedRecipient($recipientId, $targetId) {
		$this->recipientPersonId = $recipientId;
		$this->targetPersonId = $targetId;
	}
}
?>