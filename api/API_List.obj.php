<?

class API_List{

	var $id;
	var $name;
	var $description;


	function API_List($id, $name, $description = ""){
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
	}

}


?>