<?
class AspAdminQuery extends DBMappedObject{

	var $name = "";
	var $notes = "";
	var $query = "";
	var $numargs = "";
	var $options = "";
	var $optionarray = false;

	public function AspAdminQuery($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "aspadminquery";
		$this->_fieldlist = array("name","notes", "query", "numargs", "options");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	// returns true if the specified option name is attached to this query
	public function getOption($name) {
		if ($this->optionarray === false) {
			if ($this->options == "")
				$this->optionarray = array();
			else
				$this->optionarray = explode(",", $this->options);
		}
		return in_array($name, $this->optionarray);
	}
	
	// set an option
	public function setOption($name) {
		// is it already set? (this also intializes the optionarray if it hasn't been used yet)
		if (!$this->getOption($name))
			$this->optionarray[] = $name;
		
		$this->options = (implode(",", $this->optionarray));
	}
	
	// un-set an option
	public function unsetOption($name) {
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

	/**
	 * Scan the options for prodfilter=value and return the value
	 *
	 * @return string The value (rval) from the prodfilter option or an empty string if none exists
	 */
	public function getProductFilter() {

		// Does this query have any options set?
		if (strlen($this->options)) {
			$optionarr = explode(',', $this->options);
			if (is_array($optionarr) && count($optionarr)) {

				// Iterate over the options
				foreach ($optionarr as $option) {

					// If this one is a product filter option
					if (preg_match('/prodfilter=(.*)/', $option, $matches)) return($matches[1]);
				}
			}
		}

		return('');
	}

	/**
	 * Sets the prodfilter=value option; performs no checking on validity of value
	 *
	 * @param string $value The name of the product to set the filter to (i.e. 'cs', or 'tai', etc)
	 */
	public function setProductFilter($value) {

		// Get rid of any old one
		$this->unsetProductFilter();

		// Set the new one
		$this->setOption("prodfilter={$value}");
	}

	/**
	 * Finds and eliminates any currently set Product Filter
	 */
	public function unsetProductFilter() {

		// Is there a product filter option set?
		if (strlen($value = $this->getProductFilter())) {

			// Get rid of it
			$this->unsetOption("prodfilter={$value}");
		}
	}
}

?>
