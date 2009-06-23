<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<script type='text/javascript' src='../script/prototype.js'></script>
</head>
<body>
<table style='width:900px; margin: 0 auto'>
<tr>
<td valign=top>
<h1 style='margin:0;padding:0;font-size:16px;font-weight:normal;'>
<span id='manualTab' style='margin-left:10px; background:rgb(180,220,240);padding:5px;padding-bottom:0'>Manual Testcases</span>
<span id='automatedTab' style = 'margin-left:10px; background:rgb(180,240,220);padding:5px;padding-bottom:0'>Automated Testcases (ASSERT FALSE)</span>
</h1>
<div id='requestDiv' style='padding:10px;border:solid 1px rgb(150,150,150)'>
<div id='automatedTestcasesContainer'></div>
<div id='manualTestcasesConstainer'></div>
GET
<br/>
<input id='dataGET' type='text' style='width:350px'/>
<div>
	<a href="#" id='clearLink' style='float:right'>Clear</a>
	POST
	<br style='clear:both'/>
	<textarea id='dataPOST' style='width:510px; height:550px'>
	</textarea>
</div>
<div>
<button id='runautomatedButton' style='float:right'>Run/Cancel All Automated Tests</button>
<button id='sendButton'>Send Request</button>
<br style='clear:both'/>
</div>
</div>
</td>
<td valign=top style='background: gray;'>
	<textarea id='result' style='background: rgb(50,70,80); color: rgb(220,220,220); font-family: monospace; font-size:10px; white-space:nowrap; width:600px; overflow:auto; height:700px'>
	</textarea>
	<input id='raw' style='background:black;color:white;width:600px' type='text'/>
</td>
</tr>
</table>

<script type='text/javascript'>
var testcaseData = {};
testcaseData['ASSERT FALSE--ajax.php'] = '';
testcaseData['ASSERT FALSE--ajax.php?type'] = '';
testcaseData['ASSERT FALSE--ajax.php?type='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=sdjfkladjf'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=\0\0'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=$_SESSION'] = '';
testcaseData['ajax.php?type=lists'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Message'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Message&id'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Message&id='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Message&id=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Message&id=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Message&id=$_SESSION'] = '';
testcaseData['ajax.php?type=Message&id=1'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessagePart'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessagePart&id'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessagePart&id='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessagePart&id=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessagePart&id=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessagePart&id=$_SESSION'] = '';
testcaseData['ajax.php?type=MessagePart&id=1'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessageParts'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessageParts&id'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessageParts&id='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessageParts&id=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessageParts&id=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=MessageParts&id=$_SESSION'] = '';
testcaseData['ajax.php?type=MessageParts&id=1'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&messagetype'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&messagetype='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&messagetype=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&messagetype=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&messagetype=$_SESSION'] = '';
testcaseData['ajax.php?type=Messages&messagetype=phone'] = '';
testcaseData['ajax.php?type=Messages&messagetype=email'] = '';
testcaseData['ajax.php?type=Messages&messagetype=sms'] = '';
testcaseData['ajax.php?type=Messages&messagetype=print'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=$_SESSION'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=3'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=3&messagetype'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=3&messagetype='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=3&messagetype=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=3&messagetype=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=3&messagetype=$_SESSION'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid&messagetype=phone'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=&messagetype=phone'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=???\';;;&messagetype=phone'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=%&messagetype=phone'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=Messages&userid=\0\0&messagetype=phone'] = '';
testcaseData['ajax.php?type=Messages&userid=3&messagetype=phone'] = '';
testcaseData['ajax.php?type=Messages&userid=3&messagetype=email'] = '';
testcaseData['ajax.php?type=Messages&userid=3&messagetype=sms'] = '';
testcaseData['ajax.php?type=Messages&userid=3&messagetype=print'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=authorizedmapnames'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messagetype'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messagetype='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messagetype=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messagetype=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messagetype=$_SESSION'] = '';
testcaseData['ajax.php?type=hasmessage&messagetype=phone'] = '';
testcaseData['ajax.php?type=hasmessage&messagetype=email'] = '';
testcaseData['ajax.php?type=hasmessage&messagetype=sms'] = '';
testcaseData['ajax.php?type=hasmessage&messagetype=print'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messageid'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messageid='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messageid=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messageid=%'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=hasmessage&messageid=$_SESSION'] = '';
testcaseData['ajax.php?type=hasmessage&messageid=1'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=listrules'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=listrules&listids'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=listrules&listids='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=listrules&listids=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=listrules&listids=$_SESSION'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=listrules&listids=' + [].toJSON()] = '';
testcaseData['ASSERT FALSE--ajax.php?type=listrules&listids=' + ["???\';;;", "???\';;;"].toJSON()] = '';
testcaseData['ASSERT FALSE--ajax.php?type=listrules&listids=' + ["%", "%"].toJSON()] = '';
testcaseData['ajax.php?type=listrules&listids=' + [1].toJSON()] = '';
testcaseData['ajax.php?type=listrules&listids=' + [1,2,3].toJSON()] = '';
testcaseData['ASSERT FALSE--ajax.php?type=liststats'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=liststats&listids'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=liststats&listids='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=liststats&listids=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=liststats&listids=$_SESSION'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=liststats&listids=' + [].toJSON()] = '';
testcaseData['ASSERT FALSE--ajax.php?type=liststats&listids=' + ["???\';;;", "???\';;;"].toJSON()] = '';
testcaseData['ASSERT FALSE--ajax.php?type=liststats&listids=' + ["%", "%"].toJSON()] = '';
testcaseData['ajax.php?type=liststats&listids=' + [1].toJSON()] = '';
testcaseData['ajax.php?type=liststats&listids=' + [1,2,3].toJSON()] = '';
testcaseData['ajax.php?type=liststats&listids=' + [1,2,3,null].toJSON()] = '';
testcaseData['ajax.php?type=liststats&listids=' + [1,"???\';;;",3,null].toJSON()] = '';
testcaseData['ASSERT FALSE--ajax.php?type=persondatavalues'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=persondatavalues&fieldnum'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=persondatavalues&fieldnum='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=persondatavalues&fieldnum=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=persondatavalues&fieldnum=$_SESSION'] = '';
testcaseData['ajax.php?type=persondatavalues&fieldnum=f01'] = '';
testcaseData['ajax.php?type=persondatavalues&fieldnum=f06'] = '';
testcaseData['ajax.php?type=rulewidgetsettings'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=previewmessage'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=previewmessage&id'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=previewmessage&id='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=previewmessage&id=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=previewmessage&id=$_SESSION'] = '';
testcaseData['ajax.php?type=previewmessage&id=1'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=messagefields'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=messagefields&id'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=messagefields&id='] = '';
testcaseData['ASSERT FALSE--ajax.php?type=messagefields&id=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=messagefields&id=$_SESSION'] = '';
testcaseData['ajax.php?type=messagefields&id=1'] = '';
testcaseData['ASSERT FALSE--ajax.php?type=fieldvalues'] = '';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id'] = '';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id='] = '';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=$_SESSION'] = '';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=\0\0'] = '';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;*'] = '';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;**'] = 'phone';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;***'] = 'phone=';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;****'] = 'phone=???\';;;';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;*****'] = 'phone=$_SESSION';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;******'] = 'phone=\0\0';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;*******'] = 'language';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;********'] = 'language=';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;*********'] = 'language=???\';;;';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;**********'] = 'language=$_SESSION';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;***********'] = 'language=\0\0';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;************'] = 'phone=8316001337&language';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;*************'] = 'phone=8316001337&language=';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=???\';;;****************'] = 'phone=8316001337&language=\0\0';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new*'] = 'phone';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new**'] = 'phone=';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new***'] = 'phone=???\';;;';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new****'] = 'phone=$_SESSION';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new*****'] = 'phone=\0\0';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new******'] = 'language';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new*******'] = 'language=';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new********'] = 'language=???\';;;';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new*********'] = 'language=$_SESSION';
testcaseData['ASSERT FALSE--ajaxeasycall.php?id=new**********'] = 'language=\0\0';
testcaseData['ASSERT FALSE--ajaxeasycall.php*'] = '';
testcaseData['ASSERT FALSE--ajaxeasycall.php**'] = 'phone=8316001337&language';
testcaseData['ASSERT FALSE--ajaxeasycall.php***'] = 'phone=8316001337&language=';
testcaseData['ASSERT FALSE--ajaxeasycall.php****'] = 'phone=???\';;;&language=english';
testcaseData['ASSERT FALSE--ajaxeasycall.php*****'] = 'phone=junk&language=english';
testcaseData['ajaxeasycall.php*phone,language,name sql'] = 'phone=8316001337&language=english&name=???\';;;';
testcaseData['ajaxeasycall.php*phone,language,origin sql'] = 'phone=8316001337&language=english&origin=???\';;;';
testcaseData['ajaxeasycall.php*phone,language,name,origin sql'] = 'phone=8316001337&language=english&name=Ajax Teseter&origin=???\';;;';
testcaseData['ajaxeasycall.php*phone,language,name sql,origin'] = 'phone=8316001337&language=english&name=???\';;;&origin=ajaxtester';
testcaseData['ASSERT FALSE--ajaxeasycall.php*name'] = 'name=Ajax Teseter';
testcaseData['ASSERT FALSE--ajaxeasycall.php*origin=ajaxtester'] = 'origin=ajaxtester';
testcaseData['ASSERT FALSE--ajaxeasycall.php*origin=start'] = 'origin=start';
testcaseData['ASSERT FALSE--ajaxeasycall.php*name,origin'] = 'name=Ajax Tester&origin=start';
testcaseData['ajaxeasycall.php*phone,language'] = 'phone=8316001337&language=english';
testcaseData['ajaxeasycall.php*phone,language,name'] = 'phone=8316001337&language=english&name=Ajax Tester';
testcaseData['ajaxeasycall.php*phone,language,origin'] = 'phone=8316001337&language=english&origin=ajaxtester';
testcaseData['ajaxeasycall.php*phone,language,name,origin'] = 'phone=8316001337&language=english&name=Ajax Tester&origin=ajaxtester';
testcaseData['ajaxeasycall.php?id=1'] = '';
testcaseData['ajaxlistform.php?type=saverules*f01*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}].toJSON();
testcaseData['ajaxlistform.php?type=saverules*f01,f02*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', logical:'and', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php???\';;;'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?type'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?type='] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=asdf'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=???\';;;'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=\0\0'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=$_SESSION'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*Blank postbody*'] = '';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*Bad postbody*'] = '$_SESSION';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*Bad postbody**'] = 'ruledata';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*Bad postbody***'] = 'ruledata=';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*ruledata=sql*'] = 'ruledata=???\';;;';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*ruledata=bad*'] = 'ruledata=$_SESSION';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*ruledata=bad**'] = 'ruledata=\0\0';
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*ruledata=empty array*'] = 'ruledata=' + [].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*ruledata=array of empty objects*'] = 'ruledata=' + [{}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*ruledata=array of empty objects**'] = 'ruledata=' + [{},{},{},{}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*fieldnum sql*'] = 'ruledata=' + [{fieldnum:'???\';;;', type:'text', logical:'and', op:'eq', val:'keeyip'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*text sql*'] = 'ruledata=' + [{fieldnum:'f01', type:'???\';;;', logical:'and', op:'eq', val:'keeyip'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*logical sql*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'???\';;;', op:'eq', val:'keeyip'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*op sql*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'???\';;;', val:'keeyip'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct,f02 type bad*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'multisearch', logical:'and', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct,f02 logical bad*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', logical:'and not', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct,f02 op bad*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', logical:'and', op:'equator', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, next no fieldnum*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {type:'text', logical:'and', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, f02 no type*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', logical:'and', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, f02 no logical*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, f02 no op*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', logical:'and', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, f02 no val*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', logical:'and', op:'eq'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct,f21*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f21', type:'text', logical:'and', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, next fieldnum empty'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'', type:'text', logical:'and', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, next type empty'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'', logical:'and', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, next logical empty'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', logical:'', op:'eq', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, next op empty'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', logical:'and', op:'', val:'chan'}].toJSON();
testcaseData['ASSERT FALSE--ajaxlistform.php?type=saverules*f01 correct, f01 again'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'chan'}].toJSON();
testcaseData['ajaxlistform.php?type=saverules*f01 correct,f02 val empty'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'keeyip'}, {fieldnum:'f02', type:'text', logical:'and', op:'eq', val:''}].toJSON();
testcaseData['ajaxlistform.php?type=saverules*val sql*'] = 'ruledata=' + [{fieldnum:'f01', type:'text', logical:'and', op:'eq', val:'???\';;;'}].toJSON();

$('automatedTestcasesContainer').hide();
$('manualTestcasesConstainer').show();
$('requestDiv').style.background = $('manualTab').style.background;
$('runautomatedButton').hide();

$('manualTab').observe('click', function() {
	$('automatedTestcasesContainer').hide();
	$('manualTestcasesConstainer').show();
	$('requestDiv').style.background = $('manualTab').style.background;
	$('runautomatedButton').hide();
});
$('automatedTab').observe('click', function() {
	$('automatedTestcasesContainer').show();
	$('manualTestcasesConstainer').hide();
	$('requestDiv').style.background = $('automatedTab').style.background;
	$('runautomatedButton').show();
});

var automatedTestcaseSelectbox = new Element('select');
automatedTestcaseSelectbox.observe('change', function(event) {
	$('dataPOST').value = '';
	if (testcaseData[automatedTestcaseSelectbox.getValue()])
		$('dataPOST').value = testcaseData[automatedTestcaseSelectbox.getValue()];
	$('dataGET').value = automatedTestcaseSelectbox.getValue();
	if ($('dataGET').value.indexOf('*') >= 0)
		$('dataGET').value = $('dataGET').value.substring(0, $('dataGET').value.indexOf('*'));
	$('dataGET').value = $('dataGET').value.replace(/ASSERT FALSE--/g, '');
});
var manualTestcaseSelectbox = new Element('select');
manualTestcaseSelectbox.observe('change', function(event) {
	$('dataPOST').value = '';
	if (testcaseData[manualTestcaseSelectbox.getValue()])
		$('dataPOST').value = testcaseData[manualTestcaseSelectbox.getValue()];
	$('dataGET').value = manualTestcaseSelectbox.getValue();
	if ($('dataGET').value.indexOf('*') >= 0)
		$('dataGET').value = $('dataGET').value.substring(0, $('dataGET').value.indexOf('*'));
});

for (var url in testcaseData) {
	var option = new Element('option', {'value':url}).insert(url);
	if (url.startsWith('ASSERT FALSE--'))
		automatedTestcaseSelectbox.insert(option);
	else
		manualTestcaseSelectbox.insert(option);
}

automatedTestcaseSelectbox.insert({top:new Element('option', {'value':''}).insert('-- Choose from ' + automatedTestcaseSelectbox.options.length + ' automated Test Cases --')});
manualTestcaseSelectbox.insert({top:new Element('option', {'value':''}).insert('-- Choose from ' + manualTestcaseSelectbox.options.length + ' manual Test Cases --')});

$('manualTestcasesConstainer').update(manualTestcaseSelectbox);
$('automatedTestcasesContainer').update(automatedTestcaseSelectbox);
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

var runIndex = 0;
var failCount = 0;
var warningCount = 0;
var testCount = 0;
$('runautomatedButton').observe('click', function() {
	if (runIndex > 0)
		runIndex = -1;
	else {
		runIndex = 1;
		failCount = 0;
		warningCount = 0;
		testCount = 0;
		$('result').value = '';
		$('raw').value = '--- Running ---';
		ajax_assert_false();
	}
});
function ajax_assert_false() {
	if (runIndex <= 0) {
		if (runIndex === 0)
			$('raw').value = "--- Finished ---";
		else
			$('raw').value = "--- Cancelled ---";
		if (failCount > 0)
			$('raw').value += ' ' + failCount + ' Failed';
		else
			$('raw').value += ' None Failed';
		if (warningCount > 0)
			$('raw').value += ', ' + warningCount + ' Warnings';
		else
			$('raw').value += ', No Warnings';
		$('raw').value += '-- ' + testCount + ' Tests';
		return;
	}
	if (automatedTestcaseSelectbox.options.length <= 1) {
		alert('There are no automated testcases to run');
		$('result').value = '';
		$('raw').value = '';
		return;
	}

	testCount++;
	
	var url= automatedTestcaseSelectbox.options[runIndex].value;
	var postBody = testcaseData[url];
	if (url.indexOf('*') >= 0)
		url = url.substring(0, url.indexOf('*'));
	url = url.replace(/ASSERT FALSE--/g, '');
	$('result').value += '            ' + automatedTestcaseSelectbox.options[runIndex].value;
	new Ajax.Request('../' + url, {
		'method': 'post',
		'postBody': postBody,
		onSuccess: function(transport) {
			var data = transport.responseJSON;
			if (data) {
				if (data['error']) {
					$('result').value += "\nWARNING data['error']=" + data['error'] + " \n";
					warningCount++;
				} else {
					$('result').value += "\n!!! ASSERTION FAILED, Got Data !!!\n";
					failCount++;
				}
			}
			else {
				$('result').value += " --- Assertion Success\n";
			}
			if (runIndex > 0)
				runIndex++;
			if (runIndex >= automatedTestcaseSelectbox.options.length)
				runIndex = 0;
			setTimeout('ajax_assert_false()', 10);
		}
	});
}

</script>
</body>
</html>
