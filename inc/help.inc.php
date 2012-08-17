<?
function addHelpSection() {
?>
	<div class="help">
	<h3>Need Help?</h3>
    <p>Visit the <a href="#" onclick="popup('help/index.php',950,500);">help section</a>,
        send an <a href="mailto:<?=$_SESSION['_supportemail']?>">email to support</a>,
        or call (<?=substr($_SESSION['_supportphone'],0,3) . ") " . substr($_SESSION['_supportphone'],3,3) . "-" . substr($_SESSION['_supportphone'],6,4);?>.
        Also be sure to <a href="mailto:feedback@schoolmessenger.com">give us feedback</a> about the new version.</p>
    </div>
<?
}
?>