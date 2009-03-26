<?
/*
phpMyORM - Simple Object Relational Mapping for PHP and MySQL

Copyright (C) 2004-2005  Reliance Communications, Inc.

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Please send any questions or comments to
Ben Hencke <bhencke@schoolmessenger.com>

*/
class DBRelationMap {
	var $_objectclass; //class of child objects
	var $_linkidvar; //the field/var that we are linking on (external key)
	var $_parentid; //parent's id
	var $_tablename; //child table (to get ids from)

	var $children; //set to reference of array to store children


	function DBRelationMap ($class, &$childrenarray, $linkidvar, &$parentid) {
		$this->_objectclass = $class;
		$this->_linkidvar = $linkidvar;
		$this->children = &$childrenarray; //reference the array
		$this->_parentid = &$parentid; //reference the parent id var
		$proto = new $this->_objectclass();
		$this->_tablename = $proto->_tablename;

		if ($this->_parentid) {
			$this->refresh();
		}
	}

	function getIDs () {
		$ids = array();
		foreach ($this->children as $child) {
			$ids[] = $child->id;
		}

		return $ids;
	}

	function add (&$child) {

		if (strtolower(get_class($child)) != strtolower($this->_objectclass)) {
			return false;
		}
		$linkidvar = $this->_linkidvar;
		$child->$linkidvar = $this->_parentid;
		$child->update(NULL,true);
		$this->children[] = &$child;
		return true;
	}

	function update () {
		global $_dbcon;
		
		//parent's id must not be 0
		if (!$this->_parentid)
			return false;
		//update or create the objects we have

		//first get a list of the current objects in the db
		$query = "select id from " . $this->_tablename
				." where `" . $this->_linkidvar . "`='" . mysqli_real_escape_string($_dbcon, $this->_parentid) . "'";
		$dblist = QuickQueryList($query);
		$newlist = $this->getIDs($this->children);
		if ($dblist)
			$this->destroychildren( array_diff($dblist, $newlist));

		//use $this->children[index], otherwise we work only on a copy
		//foreach makes copies, not references
		foreach (array_keys($this->children) as $index) {
			$linkidvar = $this->_linkidvar;
			$this->children[$index]->$linkidvar = $this->_parentid;

			if (!$this->children[$index]->update(NULL,true)) {
				//failed to update
				//echo "failed to update ". get_class($this->children[$index]) . "<br>";
			}
		}

		return true;
	}

	function refresh () {
		global $_dbcon;
		
		//parent's id must not be 0
		if (!$this->_parentid)
			return false;

		//first get a list of the current objects in the db
		$query = "select id from " . $this->_tablename
				." where `" . $this->_linkidvar . "`='" . mysqli_real_escape_string($_dbcon, $this->_parentid) . "'";
		$dblist = QuickQueryList($query);

		//check to see if we need to remove some children from the array
		//and update the others currenting in the array
		foreach (array_keys($this->children) as $index) {
			if (!in_array($this->children[$index]->id, $dblist)) {
				unset($this->children[$index]);
			} else {
				$this->children[$index]->refresh(NULL,true);
			}
		}

		//now see which ones need to be created
		$currentlist = $this->getIDs($this->children);
		if ($dblist)
			$addlist = array_diff($dblist, $currentlist);
		else
			$addlist = $currentlist;

		foreach ($addlist as $id) {
			$newchild = new $this->_objectclass($id);
			$linkidvar = $this->_linkidvar;
			$newchild->$linkidvar = $this->_parentid;

			$this->children[] = $newchild;
		}

		return true;
	}

	//deletes from an array of ids
	//(used for cleaning the db)
	function destroychildren ($idarray) {
		foreach ($idarray as $id) {
			$query = "delete from " . $this->_tablename
				." where id=?";
			QuickUpdate($query, false, array($id));
		}
	}

	function destroy () {

		foreach (array_keys($this->children) as $index) {
			$this->children[$index]->destroy(true);
			unset($this->children[$index]);
		}
	}
}

?>