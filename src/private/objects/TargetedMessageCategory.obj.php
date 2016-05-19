<?

class TargetedMessageCategory extends DBMappedObject {
	var $name;
	var $image;
	var $deleted = 0;

	function TargetedMessageCategory ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "targetedmessagecategory";
		$this->_fieldlist = array(
			"name",
			"image",
			"deleted"
		);
		
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

$classroomcategoryicons = array(
	"gold star" => "award_star_gold_2",
	"lightning" => "lightning",
	"information" => "information",
	"red dot" => "diagona/16/151",
	"green dot" => "diagona/16/152",
	"blue dot" => "diagona/16/153",
	"yellow dot" => "diagona/16/154",
	"pink dot" => "diagona/16/155",
	"orange dot" => "diagona/16/156",
	"purple dot" => "diagona/16/157",
	"black dot" => "diagona/16/158",
	"gray dot" => "diagona/16/159",
);

?>
