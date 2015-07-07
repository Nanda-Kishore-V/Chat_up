<?php require_once("../includes/init.php"); ?>
<?php
if(!$session->is_logged_in())
{
	redirect_to("login.php");
}
?>
<?php
$page = 1;
if(isset($_GET['update']) && $_GET['update'] == 1 && isset($_GET['group_id']))
{
	global $page;
	$page = 2;
	$group_id = $database->escape_value($_GET['group_id']);
	$group = Group::find_by_id($group_id);
	$group_name = $group->group_name;
}
if(isset($_GET['user_id']) && isset($_GET['group_id']))
{
	global $page;
	$group_id = $database->escape_value($_GET['group_id']); //check if he is the admin
	if(Group::is_admin($group_id,$session->user_id))
	{
		$group = Group::find_by_id($group_id);
		$user_id = $database->escape_value($_GET['user_id']);
		if($group->add_group_member($user_id)) 
		{
			$page = 2;
			$message = "Member added successfull.";
		}
		else
		{
			$page = 2;
			$message = "Try again.";
		}
	}
}
if(isset($_POST['next_page']))
{
	global $page;
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
	$group_name = $database->escape_value($_POST['group_name']);
	if(!$_FILES['group_picture'] || empty($_FILES['group_picture']) || !is_array($_FILES['group_picture']))
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
	else
	{
		$group_picture = NULL;
	}
	if(empty($errors))
	{
		$group = new Group();
		$group->group_name = $group_name;
		$target_path = "images/groups/" . basename(str_replace(" ", "_", $group_name)) . basename(substr($_FILES['group_picture']['name'],-10,10));
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
		$group->group_picture = $group_picture;
		if($group->create())
		{
			$group_id = $group->id;
			$page = 2;
			$group->add_group_member($session->user_id);
			$group->make_admin($session->user_id);
		}
		else
		{
			$errors['retry'] = "Group creation failed.";
		}
	}
}
if(isset($_POST['group_id']))
{
	$group_id = $_POST['group_id'];
}
if(isset($_POST['search_submit']))
{
	global $page;
	$page = 2;
	$search_item = $database->escape_value($_POST['search_item']);
	$group_id = $database->escape_value($_POST['group_id']);
	if(!preg_match("/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/",$search_item))
	{
		$objects = Contact::find_by_name($session->user_id,$search_item);
		if(is_array($objects) && !empty($objects))
		{
			if(count($objects) == 1)
			{
				reset($objects);
				$user_id = key($objects);
				$sql = "SELECT * FROM active_chats WHERE (from_id = {$session->user_id}  AND to_id = {$user_id})";
				$sql .= " OR (from_id = {$user_id} AND to_id = {$session->user_id} ) LIMIT 1";
				$result_set = $database->query($sql);
				$row = $database->fetch_assoc($result_set);
				if(!empty($row))
				{
					$search = "";
					$search .= "<div class=\"user_search conversation\"><a href=\"create_group.php?user_id={$user_id}&group_id={$group_id}\">";
					$object = User::find_by_id($user_id);
					if($object->profile_picture !== NULL || $object->profile_picture != "")
					{
						$search .= "<img src=\"$object->profile_picture\">";
					}
					else
					{
						$search .= "<img src=\"images/user.svg\">";
					}
					$search .= $object->find_suitable_info($session->user_id);
					$search .= "<br><img src=\"images/add.svg\" alt=\"$object->first_name\" class=\"add\"></a>";
					$search .= "</div>";
				}
			}
			else
			{	
				$search = "";
				foreach($objects as $user_id=>$name)
				{
					if($user_id !== $session->user_id)
					{	
						$sql = "SELECT * FROM active_chats WHERE (from_id = {$session->user_id}  AND to_id = {$user_id})";
						$sql .= " OR (from_id = {$user_id} AND to_id = {$session->user_id} ) LIMIT 1";
						$result_set = $database->query($sql);
						$row = $database->fetch_assoc($result_set);
						if(!empty($row))
						{
							$c_id = $row['id'];
							$object = User::find_by_id($user_id);
							$search .= "<div class=\"user_search conversation\">";
							$search .= "<a href=\"create_group.php?user_id={$user_id}&group_id={$group_id}\">";
							if($object->profile_picture !== NULL || $object->profile_picture != "")
							{
								$search .= "<img src=\"$object->profile_picture\">";
							}
							else
							{
								$search .= "<img src=\"images/user.svg\">";
							}
							$search .= $object->find_suitable_info($session->user_id);
							$search .= "<br><img src=\"images/add.svg\" alt=\"$object->first_name\" class=\"add\"></a>";
							$search .= "</div>";
						}
						else
						{
							$object = User::find_by_id($user_id);
							$search .= "<div class=\"user_search conversation\"><a href=\"create_group.php?user_id={$user_id}&group_id={$group_id}\">";
							if($object->profile_picture !== NULL || $object->profile_picture != "")
							{
								$search .= "<img src=\"$object->profile_picture\">";
							}
							else
							{
								$search .= "<img src=\"images/user.svg\">";
							}
							$search .= $object->find_suitable_info($session->user_id);
							$search .= "<br><img src=\"images/add.svg\" alt=\"$object->first_name\" class=\"add\"></a>";
							$search .= "</div>";
						}
				 	}
				 	elseif($user_id === $session->user_id)
				 	{
				 		$search = "<div class=\"user_search conversation\">Are you searching for yourself ?</div>";
				 	}
				}
			}
		}
		else
		{
			$search = "<div class=\"user_search conversation\"><a href=\"create_contact.php\">No such user in your contact. Add to contacts.</a></div>";
		}
	}
	else
	{
		$object = User::find_by_mail_id($search_item);
		if(is_object($object))
		{
			$flag = 0;
			$group = Group::find_by_id($group_id);
			foreach($group->users as $user_id=>$user)
			{
				if($object->id === $user->id)
				{
					$search = "<div class=\"user_search conversation\">The user is already added.</div>";
					$flag = 1;
					break;
				}
			}
			if($object->id !== $session->user_id && $flag !== 1)
			{

				$search = "";
				$search .= "<div class=\"user_search conversation\"><a href=\"create_group.php?user_id={$object->id}&group_id={$group_id}\">";
				if($object->profile_picture !== NULL || $object->profile_picture != "")
				{
					$search .= "<img src=\"$object->profile_picture\">";
				}
				else
				{
					$search .= "<img src=\"images/user.svg\">";
				}
				$search .= $object->find_suitable_info($session->user_id);
				$search .= "<br><img src=\"images/add.svg\" alt=\"$object->first_name\" class=\"add\"></a>";
				$search .= "</div>";
		 	}
		 	elseif($object->id === $session->user_id && $flag !==1)
		 	{
		 		$search = "<div class=\"user_search conversation\">Are you searching for yourself ?</div>";
		 	}
		}
		else
		{
			$search = "<div class=\"user_search conversation\">No such user found. Invite to Chat Up.</div>";
		}
		$page = 2;
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http="author" content="Nanda Kishore">
	<meta http="description" content="This is a webpage to create groups.">
	<title>Chat up</title>
	<link href="stylesheets/create_group.css" rel="stylesheet">
</head>
<body>
	<div id="main" class="main">
		<div id="header" class="header">
		</div>
	<div id="body_pane" class="body_pane">
		<div id="left_pane" class="left_pane">
			<div id="self_info" class="self_info">
				<span class="heading"> Create Group </span>
				<span class="logout"><a href="logout.php" title="Logout">Log out</a></span>
				<span class="back"><a href="index.php" title="Go back to chats">&laquo;Back</a></span>
			</div>
			<div id="display_image" class="display_image">
				<img src="images/group.svg">
			</div>
		</div>
		<div id="right_pane" class="right_pane">
			<div id="user_info" class="user_info">
				<?php if(isset($group_name)) {echo "<span class=\"info\">{$group_name}</span>";}?>
			</div>
			<div id="form_container" class="form_container">
				<?php
				if($page === 1)
				{
				?>
				<form class="form" action="create_group.php" method="post" enctype="multipart/form-data">
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
							<label>Group Name&nbsp;&nbsp;
							<input type="text" name="group_name" class="field-medium" value="<?php if(isset($group_name)) {echo $group_name;} ?>">
							</label>
						</li>
						<li>
							<label>Group Picture
							<input type="file" name="group_picture" class="field-medium">
							</label>
						</li>
						<li>
							<input type="submit" name="next_page" value="Next">
						</li>
					</ul>
				</form>
				<?php
				}
				?>
				<?php 
				if($page === 2)
				{
					if(isset($message))
					{
						echo "<div class=\"message\">{$message}<a href=\"index.php?msg=group\"><button class=\"finish_button\">Done</button></a></div>";
					}
				?>
				<form class="form add_member" action="create_group.php" method="post">
					<ul>
						<input type="hidden" name="group_id" value="<?php if(isset($group_id)) {echo $group_id;} else {redirect_to("http://www.google.co.in");} ?>">
						<li>
							<label id="search_icon"><img src="images/search.svg" alt="submit">
								<input type="submit" name="search_submit" value="" id="search_submit">
							</label>
						</li>
						<li>
							<label for="search">Search and Add</label>
							<input type="text" id="search" value="" name="search_item" class="field-long">
						</li>
					</ul>
				</form>
				<div class="active_chats">
					<?php if(isset($search)) {echo $search;} ?>
				</div>
				<div class="selected_members" id="selected_members">
					<h2> Members added </h2>
					<?php
					if(isset($group_id))
					{
						$output = "";
						$group = Group::find_by_id($group_id);
						if(!empty($group->users))
						{
							foreach($group->users as $user_id=>$user)
							{
								$output .= "<div class=\"conversation\">";
								if($group->group_picture !== NULL)
								{
									$output .= "<img src=\"$user->profile_picture\" alt=\"" . $user->find_suitable_info($session->user_id) . "\">";
								}
								else
								{
									$output .= "<img src=\"images/user.svg\" alt=\"" . $user->find_suitable_info($session->user_id) . "\">";
								}
								$output .= "<span>" . $user->find_suitable_info($session->user_id) . "</span>";
								$output .= "<span class=\"remove\"><a href=\"remove_member.php?id={$user->id}\"><img src=\"images/remove.svg\"></a></span>";
								$output .= "</div>";
							}
						}
						else
						{
							$output .= "<div class=\"conversation\">No members to show.</div>";
						}
						echo $output;
					}
					else
					{
					}
					?>
				</div>
				<?php
				}
				?>
			</div>
		</div>
	</div>
</body>
</html>
