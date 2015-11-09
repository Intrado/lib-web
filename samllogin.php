<?
$isindexpage = true;
require_once("inc/common.inc.php");
$custname = getCustomerName($CUSTOMERURL);
$scheme = getCustomerData($CUSTOMERURL);
if($_SERVER['REQUEST_METHOD'] === 'GET' && !$scheme['_hasSAML']) {
    redirect("index.php");
}
$samlURL = isset($scheme['_samlIdPEntityId']) ? $scheme['_samlIdPEntityId'] : "";
$samlIdPMetadataURL = isset($scheme['_samlIdPMetadataURL']) ? $scheme['_samlIdPMetadataURL']: "";
$samlhost = $SETTINGS['saml']['host'];?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link href='css/samllogin.css' rel='stylesheet'>
    <style>
    header {
    	height: 50%;
    }
    #bottom{
		top: 50%;
	}
    </style>
</head>
<body>

<header>
    <h3 id="title"><?= $custname?></h3>
    <div class="hidden-xs">
    <img  src="img/logo.png" />
    <br/>
    <br/>
    <h1>Welcome Back!</h1>
    <br/>
        </div>

<form id="samlForm" action="<?=$samlhost."/saml/login"?>" method="get">
	<input type="hidden" name="idp" value="<?=$samlURL?>">
	<input type="hidden" name="disco" value="true">
	<input type="hidden" name="idpMetadataURL" value="<?=$samlIdPMetadataURL ?>">
	<input type="hidden" name="customerURL" value="<?=$CUSTOMERURL ?>">
</form>
</header>
<section id="bottom">
	<button id="loginButton" class="btn btn-lg btn-primary btn-block" type="submit" onclick="loginSAML();">Log in</button>
   <a href="index.php?forceLocal=true">Login with local credentials</a>

</section>
<footer >
    Service and support <a href="mailto:<?=$scheme['_supportemail']?>"><?=$scheme['_supportemail']?></a>&nbsp;|&nbsp;<?="(" . substr($scheme['_supportphone'],0,3) . ") " . substr($scheme['_supportphone'],3,3) . "-" . substr($scheme['_supportphone'],6,4);?>
</footer>
<script type="text/javascript">
	function loginSAML(){
		document.getElementById("samlForm").submit();
	}
</script>
</body>
</html>