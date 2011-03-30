<?
class AspAdminQuery extends DBMappedObject{

	var $name = "";
	var $notes = "";
	var $query = "";
	var $numargs = "";
	var $options = "";
	var $optionarray = false;

	function AspAdminQuery($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "aspadminquery";
		$this->_fieldlist = array("name","notes", "query", "numargs", "options");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	// returns true if the specified option name is attached to this query
	function getOption($name) {
		if ($this->optionarray === false)
			$this->optionarray = explode(",", $this->options);
		
		return in_array($name, $this->optionarray);
	}
	
	// set an option
	function setOption($name) {
		// is it already set? (this also intializes the optionarray if it hasn't been used yet)
		if (!$this->getOption($name))
			$this->optionarray[] = $name;
		
		$this->options = (implode(",", $this->optionarray));
	}
	
	// un-set an option
	function unsetOption($name) {
		// is it already set? (this also intializes the optionarray if it hasn't been used yet)
		if ($this->getOption($name)) {
			foreach ($this->optionarray as $index => $value) {
				if ($name == $value) {
					unset($this->optionarray[$index]);
					break;
				}
			}
		
			$this->options = (implode(",", $this->optionarray));
		}
	}
}

?>