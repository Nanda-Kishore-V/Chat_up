<?php require_once("../includes/init.php"); ?>
<?php
if($session->is_logged_in())
{
	redirect_to("index.php?msg=message");
}
?>
<?php

if(isset($_POST['submit']))
{
	$mail_id = $_POST['mail_id'];
	$password = $_POST['password'];
	$mail_id = $database->escape_value($mail_id);
	$password = $database->escape_value($password);
	$user = User::authenticate($mail_id,$password);
	if(is_object($user))
	{
		$session->log_in($user);
		redirect_to("index.php?msg=message");
	}
	else
	{
		$message = "Username/Password is incorrect.";
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http="author" content="Nanda Kishore">
	<meta http="description" content="This is a webpage with details about Chat up.">
	<title>Chat up</title>
	<link href="stylesheets/homepage.css" rel="stylesheet">
</head>
<body>
	<div id="main" class="main">
		<div id="header" class="header">
		</div>
	</div>
	<div id="body_pane" class="body_pane">
		<div class="form_container">
			<h2>SIGN IN</h2>
			<form action="login.php" method="post" class="form">
				<?php echo "<div class=\"errors\">{$message}</div>"; ?>
				<ul>
					<li>
						<label for="email_id">Email-id:</label>
						<input type="text" class="field-medium" value="<?php if(isset($mail_id)) { echo $mail_id; } ?>" id="mail_id" name="mail_id">
					</li>
					<li>
						<label for="password">Password:</label>
						<input type="password" value="" id="password" name="password" class="field-medium">
					</li>
					<li>
						<input type="submit" name="submit" id="submit" value="Sign In">
					</li>
					<li>
						<a href="signup.php">Not a registered member ? Sign up.</a>
					</li>
				</ul>
			</form>
		</div>
	</div>
</body>
</html>


