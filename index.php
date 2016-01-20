<?php
session_start();
require_once dirname( __FILE__ ) .'/functions.php';
if(isset($_GET['logout']) && $_GET['logout'] == 'true')
{
	$_SESSION['sessionhash'] = $_SESSION['user'] = "";
	$host = $_SERVER['HTTP_HOST'];
	$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	header("Location: http://$host$uri/");
}
?>

<html>
<head>
   <title>sslConfig</title>
   <meta charset="utf-8">
   <script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="https://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/sha256.js"></script>
	<script type="text/javascript" src="helper.js"></script>
	<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
   <link rel="stylesheet" type="text/css" href="reset.css">
	<link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
<?php
if(isset($_POST['user']) && isset($_POST['pwhash']) && isset($_POST['login']))
{
	if(!userLogin())
		echo "<div class='error'>Login fehlgeschlagen</div>";
}
if(isset($_SESSION['user']) && isset($_SESSION['sessionhash']) && userLoggedIn($_SESSION['user']) === TRUE)
{
	if($_SESSION['user'] == root)
		require_once dirname( __FILE__ ) .'/rootlogin.php';
}
else
{
   if(isset($_SESSION['user']) && userLoggedIn($_SESSION['user'])=== -1)
		echo "<div class='warning'>Session Timeout</div>";
	$_SESSION['sessionhash'] = $_SESSION['user'] = "";
?>
   <div class="login">
   	<h1>Login</h1>
   	<div class="form">
   		<form action="" method="post" id="loginForm">
   		   <div><label for="user">Username:</label><input type="text" name="user" id="user" /></div>
   			<div><label for="pwhash">Password:</label><input type="password" name="pwhash" id="pwhash" /></div>
   			<div>
   				<input type="submit" name="login" value="Login" id="login" />
            </div>
   			<input type="hidden" name="rand" value="<?php echo generateRandomString(16); ?>" />
   		</form>
   	</div>
   </div>
<?php
}
?>

</body>
</html>
