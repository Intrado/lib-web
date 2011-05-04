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
		$this->id = $import->id;
		$this->name = $import->name;
		$this->description = $import->description;
		$this->notes = $import->notes;
		$this->uploadkey = $import->uploadkey;
		$this->type = $import->datatype;
		$this->method = $import->updatemethod;
		$this->status = $import->status;
		$this->lastrun = $import->lastrun;
		$this->filedate = $import->datamodifiedtime;
		if ($import->datamodifiedtime)
			$this->filesize = QuickQuery("select length(data) from import where id = ?", null, array($import->id)) + 0;
		else
			$this->filesize = 0;
		$this->automatic = ($import->type == "automatic") ? true : false;
		$this->skipheaderlines = $import->skipheaderlines;
	}
}

?>