<?
/*

From bbellwfu at gmail dot com on the php forums.
Modified by Ben Hencke

Usage
 Grab some XML data, either from a file, URL, etc. however you want. Assume storage in $strYourXML;

 $objXML = new xml2Array();
 $arrOutput = $objXML->parse($strYourXML);
 print_r($arrOutput); //print it out, or do whatever!

*/
class XmlToArray {

	var $arrOutput = array();
	var $resParser;
	var $strXmlData;

	function parse($strInputXML) {

		$this->resParser = xml_parser_create ();
		xml_set_object($this->resParser,$this);
		xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");

		xml_set_character_data_handler($this->resParser, "tagData");

		$this->strXmlData = xml_parse($this->resParser,$strInputXML );
		if(!$this->strXmlData) {
			error_log(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($this->resParser)),
			xml_get_current_line_number($this->resParser)));
			return false;
		}

		xml_parser_free($this->resParser);

		//xml has only 1 top element, no need to keep an array of a single element
		$this->arrOutput = $this->arrOutput[0];

		return $this->arrOutput;
	}
	function tagOpen($parser, $name, $attrs) {
		$tag=array("name"=>$name,"attrs"=>$attrs);
		array_push($this->arrOutput,$tag);
	}

	function tagData($parser, $tagData) {
		if(trim($tagData) !== "") { //don't skip "0", "false", etc
			if(isset($this->arrOutput[count($this->arrOutput)-1]['txt'])) {
				$this->arrOutput[count($this->arrOutput)-1]['txt'] .= $tagData;
			}
			else {
				$this->arrOutput[count($this->arrOutput)-1]['txt'] = $tagData;
			}
		}
	}

	function tagClosed($parser, $name) {
		$this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
		array_pop($this->arrOutput);
	}
}

function findChild ($element, $name) {
	foreach ($element['children'] as $child) {
		if ($child['name'] == $name)
			return $child;
	}
	return false;
}

function findChildren ($element, $name) {
	$found = array();
	foreach ($element['children'] as $index => $child) {
		if ($child['name'] == $name)
			$found[$index] = $child;
	}
	return count($found) > 0 ? $found : false;
}

?>
