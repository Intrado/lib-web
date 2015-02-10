<?

class ListGuardianCategory extends DBMappedObject {

	var $listId;
	var $guardianCategoryId;

	function ListGuardianCategory($listId, $guardianCategoryId) {
		$this->listId = $listId;
		$this->guardianCategoryId = $guardianCategoryId;
		$this->_allownulls = false;
		$this->_tablename = "listguardiancategory";
		$this->_fieldlist = array("listId", "guardianCategoryId");
	}

	static function upsertListGuardianCategories($listId, $categories) {
		ListGuardianCategory::deleteListGuardianCategories($listId);
		ListGuardianCategory::insertListGuardianCategories($listId, $categories);
	}

	static function insertListGuardianCategories($listId, $categories) {
		QuickUpdate('BEGIN');
		foreach ($categories as $categoryId) {
			$le = new ListGuardianCategory($listId, $categoryId);
			$le->create();
		}
		QuickUpdate('COMMIT');
	}

	static function deleteListGuardianCategories($listId) {
		QuickUpdate("DELETE FROM listguardiancategory WHERE listId=?", false, array($listId));
	}

	static function getGuardiansForList($listId) {
		$categories = QuickQueryList("select guardianCategoryId from listguardiancategory where listid=$listId");
		return $categories;
	}

}

?>
