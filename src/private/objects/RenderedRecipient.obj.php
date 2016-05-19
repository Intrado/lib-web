<?
class RenderedRecipient {
	var $recipientPersonId; // the person receiving the message
	var $targetPersonId; // the person the message is about (used in field inserts)
	
	function RenderedRecipient($recipientId, $targetId) {
		$this->recipientPersonId = $recipientId;
		$this->targetPersonId = $targetId;
	}
}
?>