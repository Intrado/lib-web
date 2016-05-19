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

		// SMK note: for some reason the default constructor resets this to
		// the default value unless we set it here AFTER calling it... (???)
		$this->_allownulls = true;
	}
}

?>
