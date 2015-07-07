<?php require_once("../includes/init.php"); ?>
<?php
if(!$session->is_logged_in())
{
	redirect_to("login.php");
}
?>
<?php

if(isset($_GET['group_id']))
{
	$group_id = $database->escape_value($_GET['group_id']);
	$group = Group::find_by_id($group_id);
	if(!array_key_exists($session->user_id,$group->users))
	{
		redirect_to("index.php");
	}
}

if(isset($_POST['submit']))
{
	$group_name = $database->escape_value($_POST['group_name']);
	if(trim($group_name) === NULL)
	{
		$errors['group_name'] = "Group name is missing.";
	}
	if(empty($errors))
	{
		$group->group_name = $group_name;
		if($group->update_group_info())
		{
			$message = "Group name updated.";
		}
		else
		{
			$message = "Group updation failed.";
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
	if($_FILES['group_picture'] || !empty($_FILES['group_picture']) || is_array($_FILES['group_picture']))
	{
		if(!in_array($_FILES['group_picture']['type'],$image_types) && $_FILES['group_picture']['size'] < 0 && $_FILES['group_picture']['size'] > 512000)
		{
			$errors['group_picture'] = "Upload a valid group picture.";
		}
		elseif($_FILES['group_picture']['error'] !== 0)
		{
			if(isset($errors['group_picture']))
			{
				$errors['group_picture'] .= $upload_errors[$_FILES['group_picture']['error']];
			}
			else
			{				
				$errors['group_picture'] = $upload_errors[$_FILES['group_picture']['error']];
			}
		}
	}
	if(empty($errors))
	{
		$timestamp = strftime("%Y-%m-%d-%H-%M-%S",time());
		$target_path = "images/groups/" . basename($timestamp) . basename(substr($_FILES['group_picture']['name'],-10,10));
		if($_FILES['group_picture']['size'])
		{
			if(file_exists($target_path))
			{
				$errors[] = "The filename already exists.Try again with another file name.";
			}
			if(move_uploaded_file($_FILES['group_picture']['tmp_name'],$target_path))
			{
				$group_picture = $database->escape_value($target_path);
			}
			else
			{
				$errors['upload_error'] = "The group picture upload failed.";
			}
		}
		else
		{
			$group_picture = NULL;
		}
		if(isset($group_picture))
		{
			$group->group_picture = $group_picture;
		}
		else
		{
			$group->group_picture = NULL;
		}
		if($group->update_group_info())
		{
			$message = "Group picture changed successfully.";
		}
		else
		{
			$message = "Group picture change failed.";
		}
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http="author" content="Nanda Kishore">
	<meta http="description" content="This is a webpage to chat with people.">
	<title>Chat up</title>
	<link href="stylesheets/homepage.css" rel="stylesheet">
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<script src="javascripts/main.js"></script>
</head>
<body>
	<div id="main" class="main">
		<div id="header" class="header">
		</div>
	</div>
	<div id="body_pane" class="body_pane"> <!-- header ends -->
		<div id="profile_container" class="profile_container">
			<span><a href="index.php?msg=group<?php if(isset($group_id)) echo "&group_id={$group_id}"; ?>"><img src="images/arrow_back.svg"></a>&nbsp;&nbsp;&nbsp;Edit Profile</span>
			<div id="profile_photo" class="profile_photo">
				<img src="<?php if($group->group_picture !== NULL ) {echo $group->group_picture;} else {echo "images/group.svg";}?>">
				<form class="form picture" action="edit_group.php?group_id=<?php {echo $group_id;}?>" method="post" enctype="multipart/form-data">
					<ul>
						<li>
							<label for="last_name">Profile Picture</label>
							<input type="file" name="group_picture" class="field-file" id="last_name">
						</li>
						<li>
							<input type="submit" name="submit_picture" value="Change">
						</li>
					</ul>
				</form>
			</div>
		</div>
			<div id="right_pane" class="right_pane">
			<div id="user_info" class="user_info">
				<?php if(isset($message)) echo "<span class=\"success_message\">$message</span>"; ?>
			</div>
			<div id="edit_profile" class="edit_profile">
				<form class="form edit" action="edit_group.php?group_id=<?php {echo $group_id;}?>" method="post" enctype="multipart/form-data">
					<h2>Edit the Group</h2>
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
							<label for="group_name">Group name</label>
							<input type="text" name="group_name" value="<?php echo $group->group_name; ?>" class="field-long" id="group_name">
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
	</div> <!-- footer starts -->
</body>
</html>
<?php 
	if(isset($database))
	{
		$database->close_connection();
	}
?>