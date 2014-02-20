<?

class FeedCategory extends DBMappedObject {
	var $name;
	var $description;
	var $deleted = 0;

	private static $feedTypes = array(
		"rss" => "RSS",
		"desktop" => "Desktop Alerts",
		"push" => "Push Notifications"
	);

	// local cache of mapped types
	private $types = false;

	function FeedCategory ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "feedcategory";
		$this->_fieldlist = array("name", "description", "deleted");
		DBMappedObject::DBMappedObject($id);
	}

	/** Get the types associated with this feed category
	 * @return array of strings
	 */
	public function getTypes() {
		if (!$this->types)
			$this->types = QuickQueryList("select type from feedcategorytype where feedcategoryid = ?", false, false, array($this->id));
		return $this->types;
	}

	/** Replace the current category types with these
	 * @param array $types is a list of types
	 */
	public function updateTypes($types) {
		QuickUpdate("delete from feedcategorytype where feedcategoryid = ?", false, array($this->id));
		if ($types && count($types)) {
			QuickUpdate("insert into feedcategorytype (feedcategoryid, type) values ". repeatWithSeparator("({$this->id}, ?)", ",", count($types)),
				false, $types);
		}
		// reset the local cache
		$this->types = false;
	}

	// returns all allowed feed categories for the current user
	// if jobid is specified, it will include any additional feed categories associated with the job
	static function getAllowedFeedCategories($jobid = false) {
		global $USER;
		if (!$USER->authorize("feedpost"))
			return array();
		
		// get all the feed categories for the current user and those already associated with the job (if specified)
		$args = array();
		// if the user has feed restrictions...
		if (QuickQuery("select 1 from userfeedcategory where userid = ? limit 1", false, array($USER->id))) {
			$args[] = $USER->id;
			$usercategorywhere = "id in (select feedcategoryid from userfeedcategory where userid=?) ";
		
			// the job may already have categories selected, make sure they are displayed as well.
			$jobcategorywhere = "";
			if ($jobid) {
				$jobcategorywhere = " id in (select destination from jobpost where type = 'feed' and jobid = ? and posted) ";
				$args[] = $jobid;
			}
		
			// construct the where clause based off which restrictions (if any) exist
			if ($usercategorywhere && $jobcategorywhere)
				$categorywhere = " ($usercategorywhere or $jobcategorywhere) ";
			else if ($usercategorywhere)
				$categorywhere = $usercategorywhere;
			else
				$categorywhere = $jobcategorywhere;
		} else {
			// user is unrestricted, just show them all categories
			$categorywhere = "1";
		}
		return DBFindMany("FeedCategory", 
			"from feedcategory 
			where
			$categorywhere
			and not deleted
			order by name", false, $args);
	}
	
	static function getFeedDescriptions() {
		return QuickQueryList("select id, description from feedcategory where not deleted", true, false);
	}
	
	static function getAllTypes() {
		return self::$feedTypes;
	}
}

?>
