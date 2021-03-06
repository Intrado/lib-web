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


class DBMappedObject {

	private $_isCreated = false;
	var $id; //always has an id

	var $_tablename = ''; //name of our table
	var $_fieldlist = array(); //list of our fields (excluding id)
	var $_childobjects = array(); //list of the child objects in a one-to-one
	var $_childclasses = array(); //classes of the child objects
	var $_relations = array(); //list of DBRelationMap objects for each one-to-many
	var $_allownulls = false; //insert/update/read DB NULL values as php NULL values, otherwise as blank string
	var $_lastsql = ""; //for debug

	function DBMappedObject ($id = NULL, $refreshchildren = false) {
		if ($this->_tablename == '')
			$this->_tablename = get_class($this);

		$this->id = $id;
		$this->refresh();

		//now create children
		foreach ($this->_childobjects as $index => $name) {
			$classname = $this->_childclasses[$index];
			$nameid = $name . "id";
			if ($refreshchildren && in_array($nameid, $this->_fieldlist)) {
				$this->$name = new $classname($this->$nameid);
			} else {
				$this->$name = new $classname();
			}
		}

		//create links
		foreach (array_keys($this->_relations) as $index) {
			$this->_relations[$index]->refresh();
		}
	}

	function isCreated() {
		return($this->_isCreated);
	}

	function create ($specificfields = NULL, $createchildren = false) {
		global $_dbcon;
		
		if ($specificfields == NULL) {
			$specificfields = $this->_fieldlist;
		}

		//create children first
		if ($createchildren) {
			foreach ($this->_childobjects as $name) {
				//create this child
				if ($this->$name->create(NULL,true)) {
					//should we set our id of it?
					if (in_array($name."id", $this->_fieldlist)) {
						$nameid = $name . "id";
						$this->$nameid = $this->$name->id;
						if (!in_array($name."id", $specificfields)) {
							$specificfields[] = $name."id";
						}
					}
				}
			}
		}


		$query = "insert into " . $this->_tablename . " ("
				. $this->getFieldList(false, $specificfields) . ") "
				."values (";
		$i = 0;
		$vals = array();
		foreach ($specificfields as $name) {
			$i++;
			if ($this->$name === NULL) {
				if ($this->_allownulls) {
					$query .= "NULL";
				} else {
					$query .= "''";
				}
			} else {
				$query .= "?";
				$vals[] = $this->$name;
			}
			if ($i == count($specificfields)) {
				$query .= ")";
			} else {
				$query .= ",";
			}
		}
				
		$this->_lastsql = $query;
		if ($result = Query($query, false, $vals)) {
			$this->id = $_dbcon->lastInsertId();
		} else {
			return false;
		}

		if ($createchildren) {
			//update links
			foreach (array_keys($this->_relations) as $index) {
				$this->_relations[$index]->update();
			}
		}

		if ($this->id) {
			$this->_isCreated = true;
		}

		return $this->id;
	}

	function refresh ($specificfields = NULL, $refreshchildren = false) {
		$isrefreshed = false;

		if (! isset($this->id))
			return false;

		$query = "select " . $this->getFieldList(false, $specificfields)
				." from " . $this->_tablename
				." where id=?";
		$this->_lastsql = $query;
		if ($result = Query($query, false, array($this->id))) {
			if ($row = DBGetRow($result)) {
				foreach ($this->_fieldlist as $index => $name) {
					if ($this->_allownulls && $row[$index] === NULL)
						$this->$name = NULL;
					else
						$this->$name = ($row[$index]);
				}
				$isrefreshed = true;
				$this->_isCreated = true;
			}
			$result = null;
		}

		//refresh children
		if ($refreshchildren) {
			foreach ($this->_childobjects as $name) {
				//should we update its id?
				if (in_array($name."id", $this->_fieldlist)) {
					$nameid = $name . "id";
					$this->$name->id = $this->$nameid;
				}
				//refresh this child
				$this->$name->refresh(NULL, true);
			}

			//refresh links
			foreach (array_keys($this->_relations) as $index) {
				$this->_relations[$index]->refresh();
			}
		}

		return $isrefreshed;
	}

	function update ($specificfields = NULL, $updatechildren = false) {
		$isupdated = false;

		if ($specificfields == NULL) {
			$specificfields = $this->_fieldlist;
		}

		//update children
		if ($updatechildren) {
			foreach ($this->_childobjects as $name) {
				//update this child
				$childupdated = $this->$name->update(NULL, true);
				//if this child was updated
				//check to see if we are keeping track of its id
				if ($childupdated && in_array($name."id", $this->_fieldlist)) {
					//then update our id of it
					$nameid = $name . "id";
					$this->$nameid = $this->$name->id;
					//should we add this to the list of fields to update?
					if (!in_array($name."id", $specificfields)) {
						$specificfields[] = $name."id";
					}
				}
			}
		}

		//does this object already exist?
		if (isset($this->id)) {
			$query = "update " . $this->_tablename . " set ";

			//make an array of name=value pairs
			$list = array();
			$vals = array();
			foreach ($specificfields as $name) {
				if ($this->$name === NULL) {
					if ($this->_allownulls) {
						$list[] = "`$name`=NULL";
					} else {
						$list[] = "`$name`=''";
					}
				} else {
					$vals[] = $this->$name;
					$list[] = "`$name`=?";
				}
			}
			$vals[] = $this->id;

			//put them into an update list
			$query .= implode(",", $list);
			$query .= " where id=?";
			$this->_lastsql = $query;
			if ($result = Query($query, false, $vals)) {
				if ($result->rowCount())
					$isupdated = true;
			}
		} else {
			//then we should create the object in the db and update the id field
			if ($this->create($specificfields, false))
				$isupdated = true;
		}

		if ($updatechildren) {
			//update links
			foreach (array_keys($this->_relations) as $index) {
				$this->_relations[$index]->update();
			}
		}

		return $isupdated;
	}

	function destroy ($destroychildren = false) {

		if ($destroychildren) {
			foreach ($this->_childobjects as $name) {
				//update this child
				$childupdated = $this->$name->destroy(NULL, true);
			}

			//destroy links
			foreach (array_keys($this->_relations) as $index) {
				$this->_relations[$index]->destroy();
			}
		}

		if (isset($this->id)) {
			$query = "delete from " . $this->_tablename
					." where id=?";
			$this->_lastsql = $query;
			Query($query, false, array($this->id));
			$this->id = 0;
		}
	}
	
	function getFieldList ($includeid = false, $specificfields = NULL, $alias = false) {
		$fieldlist = ($specificfields == NULL) ? $this->_fieldlist : $specificfields;
		return generateFieldList($includeid, $fieldlist, $alias);
	}

	//must override this function
	function getLinkedChildren ($link) {
		return array();
	}
}

?>
