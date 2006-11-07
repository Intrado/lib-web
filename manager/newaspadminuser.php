<?
$isasplogin = 1;
include_once("common.inc.php");
include_once("../inc/db.mysql.inc.php");

if(isset($_POST["submit"])){
	$login = DBSafe(get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login']);
	$password = password(get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password']);
	$firstname = DBSafe(get_magic_quotes_gpc() ? stripslashes($_POST['firstname']) : $_POST['firstname']);
	$lastname = DBSafe(get_magic_quotes_gpc() ? stripslashes($_POST['lastname']) : $_POST['lastname']);
	$email = DBSafe(get_magic_quotes_gpc() ? stripslashes($_POST['email']) : $_POST['email']);

	if (QuickQuery("SELECT COUNT(*) FROM aspadminuser WHERE login=$login")) {
		error('Login already used');
	} else {
		$query = "INSERT INTO aspadminuser (login, password, firstname, lastname, email) VALUES
			('$login', ' $password', '$firstname', '$lastname', '$email')";

		QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);
	}
}
?>
<form method="post">
	<p>Login: <input type="text" name="login" /><p>
	<p>Password: <input type="password" name="password" /><p>
	<p>First Name: <input type="text" name="firstname" /><p>
	<p>Last Name: <input type="text" name="lastname" /><p>
	<p>Email: <input type="text" name="email" /><p>
	<p><input type="submit" name="submit"/><p>
</form>