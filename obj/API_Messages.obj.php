<?

class API_Messages{

	var $id;
	var $name;
	var $description;

	function API_Messages($id, $name, $description = ""){
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
	}

}




?>