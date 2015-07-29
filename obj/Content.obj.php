<?

class Content extends DBMappedObject {

	var $contenttype;
	var $data;
	var $height;
	var $width;
	var $originalcontentid;

	function Content ($id = NULL) {
		$this->_tablename = 'content';
		$this->_fieldlist = array('contenttype', 'data', 'height', 'width', 'originalcontentid');
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
