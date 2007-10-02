<?
$ppNotLoggedIn = 1;

if(isset($_POST['submit'])){
	sendNewPassword($_POST['email']);
}

?>

<form method="POST" action="forgotpassword.php">
	<table>
		<tr>
			<td>Email:</td>
			<td><input type="text" name="email"></td>
		</tr>
	</table>
	<input type="submit" name="submit" value="Submit">
</form>

<br><a href="index.php">Return to Parent Portal Login</a>