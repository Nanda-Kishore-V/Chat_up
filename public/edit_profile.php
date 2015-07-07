<?php require_once("../includes/init.php"); ?>
<?php
if(!$session->is_logged_in())
{
	redirect_to("login.php");
}
?>
<?php
$user = User::find_by_id($session->user_id);
?>
<?php

if(isset($_POST['submit']))
{
	if(trim($_POST['first_name']) == "" || trim($_POST['last_name']) == "")
	{
		$errors['name'] = "Enter a valid name.";
	}
	if(trim($_POST['status']) == "")
	{
		$errors['status'] = "Status cant be empty.";
	}
	
	if(empty($errors))
	{
		$user->first_name = $database->escape_value($_POST['first_name']);
		$user->last_name = $database->escape_value($_POST['last_name']);
		$user->status = $database->escape_value($_POST['status']);
		if($user->update())
		{
			$message = "Profile edited successfully.";
		}
		else
		{
			$message = "Profile editing failed";
		}
	}
}

if(isset($_POST['submit_picture']))
{
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
	if($_FILES['profile_picture'] || !empty($_FILES['profile_picture']) || is_array($_FILES['profile_picture']))
	{
		if(!in_array($_FILES['profile_picture']['type'],$image_types) && $_FILES['profile_picture']['size'] < 0 && $_FILES['profile_picture']['size'] > 512000)
		{
			$errors['profile_picture'] = "Upload a valid group picture.";
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
		$timestamp = strftime("%Y-%m-%d-%H-%M-%S",time());
		$target_path = "images/" . basename($timestamp) . basename(str_replace(" ", "_", $user->first_name)) . basename(substr($_FILES['profile_picture']['name'],-10,10));
		if($_FILES['profile_picture']['size'])
		{
			if(file_exists($target_path))
			{
				$errors[] = "The filename already exists.Try again with another file name.";
			}
			if(move_uploaded_file($_FILES['profile_picture']['tmp_name'],$target_path))
			{
				$profile_picture = $database->escape_value($target_path);
			}
			else
			{
				$errors['upload_error'] = "The group picture upload failed.";
			}
		}
		else
		{
			$profile_picture = NULL;
		}
		if(isset($profile_picture))
		{
			$user->profile_picture = $profile_picture;
		}
		else
		{
			$user->profile_picture = NULL;
		}
		if($user->update())
		{
			$message = "Profile picture changed successfully.";
		}
		else
		{
			$message = "Profile picture change failed.";
		}
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
	<script src="javascripts/edit.js"></script>
</head>
<body>
	<div id="main" class="main">
		<div id="header" class="header">
		</div>
	</div>
	<div id="body_pane" class="body_pane">
		<div id="profile_container" class="profile_container">
		<?php
			$user = User::find_by_id($session->user_id); 
		?>
			<span><a href="index.php?msg=message"><img src="images/arrow_back.svg"></a>&nbsp;&nbsp;&nbsp;Edit Profile</span>
			<div id="profile_photo" class="profile_photo">
				<img src="<?php if($user->profile_picture !== NULL ) {echo $user->profile_picture;} else {echo "images/user.svg";}?>">
				<form class="form picture" action="edit_profile.php" method="post" enctype="multipart/form-data">
					<ul>
						<li>
							<label for="last_name">Profile Picture</label>
							<input type="file" name="profile_picture" class="field-file" id="last_name">
						</li>
						<li>
							<input type="submit" name="submit_picture" value="Change">
						</li>
					</ul>
				</form>
			</div>
			<div class="status"><h2>Status: </h2><?php echo $user->status; ?></div>
		</div>
		<div id="right_pane" class="right_pane">
			<div id="user_info" class="user_info">
				<?php if(isset($message)) echo "<span class=\"success_message\">$message</span>"; ?>
			</div>
			<div id="edit_profile" class="edit_profile">
				<form class="form edit" action="edit_profile.php" method="post" enctype="multipart/form-data">
					<h2>Edit Your Profile</h2>
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
							<label for="first_name">Name</label>
							<input type="text" name="first_name" value="<?php echo $user->first_name; ?>" class="field-short" id="first_name">
							<input type="text" name="last_name" value="<?php echo $user->last_name; ?>" class="field-short" id="last_name">
						</li>
						<li>
							<label for="status">Status</label>
							<input type="text" name="status" value="<?php echo $user->status; ?>" class="field-long" id="status">
						</li>
						<li>
							<label>
							<input type="submit" value="Edit" name="submit" id="submit">
						</li>
					</ul>
				</form>
			</div>
		</div>
	</div>
</body>
</html>