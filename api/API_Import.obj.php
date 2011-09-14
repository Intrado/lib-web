<?

class API_Import {
	var $id;
	var $name;
	var $description;
	var $notes;
	var $uploadkey;
	var $type;
	var $method;
	var $status;
	var $lastrun;
	var $filedate;
	var $filesize;
	var $automatic;
	var $skipheaderlines;
	
	// param import must be Import DBMO
	function API_Import($import) {
		if ($import == null)
			return; // empty object
		$this->id = $import->id + 0;
		$this->name = $import->name;
		$this->description = $import->description;
		$this->notes = $import->notes;
		$this->uploadkey = $import->uploadkey;
		$this->type = $import->datatype;
		$this->method = $import->updatemethod;
		$this->status = $import->status;
		$this->lastrun = $import->lastrun;
		$this->filedate = $import->datamodifiedtime;
		$this->filesize = $import->datalength;
		$this->automatic = ($import->type == "automatic") ? true : false;
		$this->skipheaderlines = $import->skipheaderlines + 0;
	}
}

?>