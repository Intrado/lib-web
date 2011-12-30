<pre><?


//$SESSION_READONLY = true;

require_once("inc/common.inc.php");

class MyObject {
	var $foo = "foo";
	var $bar = "bar";
}


function generateSomething($value) {
	sleep(1);
	
	$o = new MyObject();
	$o->foo = $value;
	
	return $o;
}


$value = gen2cache(60, null, "generateSomething", 55);

var_dump($value);

?></pre>