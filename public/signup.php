<?php require_once("../includes/init.php"); ?>
<?php

if(isset($_POST['submit']))
{
	$mail_id = $_POST['mail_id'];
	$first_name = $_POST['first_name'];
	$last_name = $_POST['last_name'];
	$upload_errors = array(
	UPLOAD_ERR_OK => "No errors.",
	UPLOAD_ERR_INI_SIZE => "Larger than upload_max_filesize.",
	UPLOAD_ERR_FORM_SIZE => "Larger than form MAX_FILE_SIZE.",
	UPLOAD_ERR_PARTIAL => "Partial upload.",
	UPLOAD_ERR_NO_FILE => "No file.",
	UPLOAD_ERR_NO_TMP_DIR => "No temporary directory.",
	UPLOAD_ERR_CANT_WRITE => "Cant write to disk.",
	UPLOAD_ERR_EXTENSION => "file upload stopped by extension."
	);
	$image_types = array("image/gif","image/jpeg","image/pjpeg","image/png");
	$errors = array();
	if(!preg_match("/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/",$_POST['mail_id']))
	{
		$errors['mail_id'] = "The mail-id is invalid.";
	}
	if(!preg_match("/[a-zA-Z \.]+/", $_POST['first_name']))
	{
		$errors['first_name'] = "Enter your name.";
	}
	if(!preg_match("/[a-zA-Z \.]+/", $_POST['last_name']))
	{
		$errors['last_name'] = "Enter your name.";
	}
	if(!preg_match("/((?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%]).{6,20})/",$_POST['password']))
	{
		$errors['password'] = "Your password must contain atleast one number, one lowercase, one uppercase and a special char. It must be atleast 6 chars long.";
	}
	if($_POST['password'] !== $_POST['re_password'])
	{
		$errors['password'] = "Your passwords don't match.";
	}
	if(!$_FILES['profile_picture'] || empty($_FILES['profile_picture']) || !is_array($_FILES['profile_picture']))
	{
		if(!in_array($_FILES['profile_picture']['type'],$image_types) && $_FILES['profile_picture']['size'] < 0 && $_FILES['profile_picture']['size'] > 512000)
		{
			$errors['profile_picture'] = "Upload a valid profile picture.";
		}
		elseif($_FILES['profile_picture']['error'] !== 0)
		{
			if(isset($errors['profile_picture']))
			{
				$errors['profile_picture'] .= $upload_errors[$_FILES['profile_picture']['error']];
			}
			else
			{				
				$errors['profile_picture'] = $upload_errors[$_FILES['profile_picture']['error']];
			}
		}
	}
	if(empty($errors))
	{
		$user = new User;
		$mail_id = $_POST['mail_id'];
		$user->mail_id = $database->escape_value($mail_id);
		$first_name = $_POST['first_name'];
		$user->first_name = $database->escape_value($first_name);
		$last_name = $_POST['last_name'];
		$user->last_name = $database->escape_value($last_name);
		$password = $_POST['password'];
		$password = $database->escape_value($password);
		$hashed_password = User::generate_hash($password);
		$user->password = $hashed_password;
		$timestamp = strftime("%Y-%m-%d-%H-%M-%S",time());
		$target_path = "images/" . basename($timestamp) . basename($mail_id) . basename(substr($_FILES['profile_picture']['name'],-10,10));
		if($_FILES['profile_picture']['size'])
		{
			if(file_exists($target_path))
			{
				$errors[] = "The filename already exists.";
			}
			if(move_uploaded_file($_FILES['profile_picture']['tmp_name'],$target_path))
			{
				$user->profile_picture = $database->escape_value($target_path);
			}
			else
			{
				$errors['upload_error'] = "The profile picture upload failed, possibly due to incorrect permission on the target folder.";
			}
		}
		else
		{
			$user->profile_picture = null;
		}
		if($user->save())
		{
			$session->log_in($user);
			redirect_to("index.php?msg=message&id=" . $user->id);
		}
		else
		{
			$error['server_error'] = "Sorry try again later.";
		}
	}
}

?>
<?php /*require_once("layouts/header.php");*/ ?>
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
	
<form action="signup.php" method="post" enctype="multipart/form-data" class="form_long">
	<h2>SIGN UP</h2>
	<?php  
		if(isset($errors) && !empty($errors))
		{
			$output = "";
			foreach($errors as $error=>$reason)
			{
				$output .= ucwords(str_replace("_", " ", $error)) . ": " . $reason ."<br>";
			}
			echo "<div class=\"errors\">" . nl2br(wordwrap($output,150)) . "</div>";
		}
	?>
	<ul>
		<li>
			<label for="mail_id">Email-id: </label>
			<input type="text" placeholder="Email-id" value="<?php if(isset($mail_id)) { echo $mail_id; } ?>" name="mail_id" id="mail_id" class="field-long">
		</li>
		<li>
			<label for="first_name">Name: </label>
			<input type="text" value="<?php if(isset($first_name)) { echo $first_name; } ?>" name="first_name" id="first_name" class="field-short" placeholder="First Name">
			<input type="text" value="<?php if(isset($last_name)) { echo $last_name; } ?>" name="last_name" id="last_name" class="field-short" placeholder="Last Name">
		</li>
		<li>
			<label for="password">Password: </label>
			<input type="password" placeholder="Password" value="" name="password" id="password" class="field-long">
		</li>
		<li>
			<label for="re_password">Re-enter Password: </label>
			<input type="password" value="" placeholder="Re-enter Password" name="re_password" id="re_password" class="field-long">
		</li>
		<li>
			<label for="profile_picture">Profile Picture: </label>
			<input type="file" name="profile_picture" id="profile_picture">
		</li>
		<li>
			<input type="submit" name="submit" id="submit" value="Sign up">
			<a href="login.php">Already registered ? Sign in.</a>
		</li>
	</ul>
</form>
</div>
<?php /*require_once("layouts/footer.php");*/ ?>
	</div>
</body>
</html>
