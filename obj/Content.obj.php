<?

class Content extends DBMappedObject {

	var $contenttype;
	var $data;

	function Content ($id = NULL) {
		$this->_tablename = "content";
		$this->_fieldlist = array("contenttype", "data");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function newFromUpload($path) {
		$content = new Content();
		//TODO use mime magic to detect the mime type
		$content->contenttype = "audio/wav";
		$content->data = base64_encode(file_get_contents($path));
		return $content;
	}
}

?>