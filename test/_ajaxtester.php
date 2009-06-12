<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<script type='text/javascript' src='../script/prototype.js'></script>
</head>
<body>
<table style='width:800px; margin: 0 auto'>
<tr>
<td valign=top style='background: rgb(230,240,250)'>
Test Cases
<br/>
<div id='testcasesContainer'></div>
<br/>
GET
<br/>
<input id='dataGET' type='text' style='width:350px'/>
<div>
	POST
	<br/>
	<a href="#" id='clearLink'>Clear</a>
	<br/>
	<textarea id='dataPOST' style='width:400px; height:550px'>
	</textarea>
</div>
<button id='sendButton'>Send Request</button>
</td>
<td valign=top style='background: gray;'>
	<textarea id='result' style='background: rgb(50,70,80); color: rgb(220,220,220); font-family: monospace; font-size:16px; width:500px; height:700px'>
	</textarea>
	<input id='raw' style='background:black;color:white;width:500px' type='text'/>
</td>
</tr>
</table>

<script type='text/javascript'>
var testcaseData = {};
testcaseData['ajax.php'] = '';
testcaseData['ajax.php?type'] = '';
testcaseData['ajax.php?type='] = '';
testcaseData['ajax.php?type=sdjfkladjf'] = '';
testcaseData['ajax.php?type=$_SESSION'] = '';
testcaseData['ajax.php?type=lists'] = '';
testcaseData['ajax.php?type=Message'] = '';
testcaseData['ajax.php?type=Message&id'] = '';
testcaseData['ajax.php?type=Message&id='] = '';
testcaseData['ajax.php?type=Message&id=;;;'] = '';
testcaseData['ajax.php?type=Message&id=$_SESSION'] = '';
testcaseData['ajax.php?type=Message&id=1'] = '';
testcaseData['ajax.php?type=MessagePart'] = '';
testcaseData['ajax.php?type=MessagePart&id'] = '';
testcaseData['ajax.php?type=MessagePart&id='] = '';
testcaseData['ajax.php?type=MessagePart&id=;;;'] = '';
testcaseData['ajax.php?type=MessagePart&id=$_SESSION'] = '';
testcaseData['ajax.php?type=MessagePart&id=1'] = '';
testcaseData['ajax.php?type=persondatavalues'] = '';
testcaseData['ajax.php?type=persondatavalues&fieldnum'] = '';
testcaseData['ajax.php?type=persondatavalues&fieldnum='] = '';
testcaseData['ajax.php?type=persondatavalues&fieldnum=;;;'] = '';
testcaseData['ajax.php?type=persondatavalues&fieldnum=$_SESSION'] = '';
testcaseData['ajax.php?type=persondatavalues&fieldnum=f01'] = '';
testcaseData['ajax.php?type=persondatavalues&fieldnum=f06'] = '';
testcaseData['ajax.php?type=fieldvalues'] = '';
testcaseData['ajax.php?type=fieldvalues*'] = 'fields=';
testcaseData['ajax.php?type=fieldvalues**'] = 'fields=notAnArray';
testcaseData['ajax.php?type=fieldvalues***'] = 'fields=$_SESSION';
testcaseData['ajax.php?type=fieldvalues****'] = 'fields=' + [].toJSON();
testcaseData['ajax.php?type=fieldvalues*****'] = 'fields=' + ['z01','z02','z01'].toJSON();
testcaseData['ajax.php?type=fieldvalues******'] = 'fields=' + ['f01','f02','g01'].toJSON();

var testcaseSelectbox = new Element('select');
testcaseSelectbox.insert(new Element('option', {'value':''}).insert('-- Choose a Test Case --'));
for (var url in testcaseData) {
	testcaseSelectbox.insert(new Element('option', {'value':url}).insert(url));
}
$('testcasesContainer').update(testcaseSelectbox);

testcaseSelectbox.observe('change', function(event) {
	$('dataPOST').value = '';
	if (testcaseData[testcaseSelectbox.getValue()]) {
		$('dataPOST').value = testcaseData[testcaseSelectbox.getValue()];
	}
	$('dataGET').value = testcaseSelectbox.getValue().replace(/\*/g, '');
});

$('clearLink').observe('click', function(event) {
	event.stop();
	$('dataPOST').value = '';
});
$('sendButton').observe('click', function() {
	if (!$('dataGET').getValue()) {
		alert('Specify GET field');
		return;
	}

	var url= $('dataGET').getValue();

	var postBody = null;
	if ($('dataPOST').getValue())
		postBody = $('dataPOST').getValue();

	$('result').value = '--- Loading ---';
	new Ajax.Request('../' + url, {
		'method': 'post',
		'postBody': postBody,
		onSuccess: function(transport) {
			$('result').value = transport.responseText;
			$('result').value = $('result').value.replace(/:/g, ":\t");
			$('result').value = $('result').value.replace(/,/g, ",\n");
			$('result').value = $('result').value.replace(/:\t\[/g, ":\n\[\n");
			$('result').value = $('result').value.replace(/:\t\{/g, ":\n\{\n");
			$('result').value = $('result').value.replace(/\],/g, "\n\],\n");
			$('result').value = $('result').value.replace(/\},/g, "\n\},\n");
			$('raw').value = transport.responseText;
		}
	});
});
</script>
</body>
</html>
