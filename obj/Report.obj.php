<?

/*


#
# Table structure for table `report`
#

CREATE TABLE report (
  ID smallint(6) NOT NULL auto_increment,
  Name varchar(20) NOT NULL default '',
  Path varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;


*/

class Report extends DBMappedObject {
	var $name;
	var $path;
	
	function Report ($id = NULL) {
		$this->_tablename = "report";
		$this->_fieldlist = array("name", "path");
		
		$this->id = $id;
		$this->refresh();
	}
	
	function findByName ($name) {
		if ($id = QuickQuery("select id from report where name='$name'")) {
			$this->id = $id;
			$this->refresh();
		}
	}
}


?>