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
		
		$values = $this->getValueArray(false, $specificfields);		
		for ($i=0; $i<count($values)-1; $i++) {
			$query .= "?,";
		}
		$query .= "?)";
				
		$this->_lastsql = $query;
		if ($result = Query($query, false, $values)) {
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

		return $this->id;
	}

	function refresh ($specificfields = NULL, $refreshchildren = false) {
		$isrefreshed = false;

		if (!isset($this->id))
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
			foreach ($specificfields as $name) {
					$list[] = "`$name`=?";
			}
			$values = $this->getValueArray(false, $specificfields);
			$values[] = $this->id;

			//put them into an update list
			$query .= implode(",", $list);
			$query .= " where id=?";
			$this->_lastsql = $query;
			if ($result = Query($query, false, $values)) {
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

	//returns values (excluding id or not) optonally escaped and quoted
	function getValueArray ($prepfordb = true, $specificfields = NULL, $includeid = false) {
		$fieldlist = ($specificfields == NULL) ? $this->_fieldlist : $specificfields;

		if ($includeid) {
			$fieldlist = array_merge(array("id"),$fieldlist);
		}

		$values = array();
		foreach ($fieldlist as $name) {
			//check add this name if we have no specificfields
			//or it is in the specific fields

			if ($prepfordb) {
				if ($this->_allownulls && $this->$name === NULL)
					$values[] = "NULL";
				else
					$values[] = "'" . DBSafe($this->$name) . "'";
			} else {
				$values[] = $this->$name;
			}

		}

		return $values;
	}

	function getValueList ($specificfields = NULL) {
		return implode(",", $this->getValueArray(true, $specificfields));
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

function generateFieldList ($includeid = false, $fieldlist = NULL, $alias = false) {
	if ($includeid) {
		$list = array("id");
		$list = array_merge($list,$fieldlist);
	} else {
		$list = $fieldlist;
	}
	if ($alias) {
		return "`$alias`.`" . implode("`,`$alias`.`", $list) . "`";
	} else {
		return "`" . implode("`,`", $list) . "`";
	}
}


//query = from ... where ...
function DBFindMany ($classname, $query, $alias = false, $args = false, $dbconnect = false) {
	return _DBFindPDO(true, $classname, $query, $alias, $args, $dbconnect);
}

function DBFind ($classname, $query, $alias = false, $args = false, $dbconnect = false) {
	return _DBFindPDO(false, $classname, $query, $alias, $args, $dbconnect);
}

function _DBFindPDO($isMany, $classname, $query, $alias=false, $args=false, $dbconnect = false) {
	//make a dummy object of this to get the field list
	$dummy = new $classname();

	$many = array();

	$query = "select " . generateFieldList(true,$dummy->_fieldlist,$alias) ." ". $query;
	if ($result = Query($query, $dbconnect, $args)) {
		while ($row = DBGetRow($result)) {
			$newobj = new $classname();

			$newobj->id = $row[0];

			foreach ($dummy->_fieldlist as $index => $field) {
				if ($dummy->_allownulls && $row[$index+1] === NULL)
					$newobj->$field = NULL;
				else
					$newobj->$field = ($row[$index+1]);
			}

			$many[$newobj->id] = $newobj;
			if (!$isMany) break;
		}
	}
	if ($isMany) {
		return $many;
	} else {
		if (count($many) == 0)
			return false;
		// else return first row object
		return $many[$newobj->id];
	}
}


?>