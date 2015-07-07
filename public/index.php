<?php require_once("../includes/init.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http="author" content="Nanda Kishore">
	<meta http="description" content="This is a webpage to chat with people.">
	<title>Chat up</title>
	<link href="stylesheets/test.css" rel="stylesheet">
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<script src="javascripts/main.js"></script>
</head>
<body>
	<div id="main" class="main">
		<div id="header" class="header">
		</div>
	</div>
	<div id="body_pane" class="body_pane"> <!-- header ends -->
<?php
if(!$session->is_logged_in())
{
	redirect_to("login.php");
}
?>
<?php
if(isset($_GET['msg']) && in_array($_GET['msg'], array("message","group")))
{
	$type = $_GET['msg'];
}
else
{
	$type = "message";
}
if($type === "message")
{
	if(isset($_GET['c_id']))
	{
		$c_id = $_GET['c_id'];
		$database->escape_value($c_id);
		$sql = "SELECT * FROM active_chats WHERE id = {$c_id} LIMIT 1";
		$result = $database->query($sql);
		$row = $database->fetch_assoc($result);
		if($row['from_id'] == $session->user_id || $row['to_id'] == $session->user_id)
		{
			$c_id = $database->escape_value($_GET['c_id']);
		}
		else
		{
			$c_id = 0;
		}
		$database->free_result($result);
	}
	else
	{
		$c_id = 0;
	}

	if(isset($_POST['send_message']))
	{
		if($c_id != 0)
		{
			$c_id = $database->escape_value($_POST['c_id']);
			$object = Conversation::find_by_id($c_id);
			if($object->from_id === $session->user_id || $object->to_id === $session->user_id)
			{
				$message_to_send = new Message();
				$message_to_send->c_id = $c_id;
				$message_to_send->message = $database->escape_value($_POST['message']);
				$message_to_send->from_id = $session->user_id;
				if($object->from_id === $session->user_id)
				{
					$message_to_send->to_id = $object->to_id;
				}
				elseif($object->to_id === $session->user_id)
				{
					$message_to_send->to_id = $object->from_id;
				}
				$message_to_send->timestamp = strftime("%Y-%m-%d %H:%M:%S",time());
				$message_to_send->image = $database->escape_value(NULL);
				if($message_to_send->create())
				{
					redirect_to("index.php?msg=message&c_id=" . $c_id);
				}
				else
				{
					echo "Message creation failed";
				}
			}
		}
	}

	if(isset($_POST['search']))
	{
		$search_item = $database->escape_value($_POST['search_item']);
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
						$c_id = $row['id'];
						redirect_to("index.php?msg=message&c_id={$c_id}");
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
								$search .= "<a href=\"index.php?msg=message&c_id={$c_id}\">";
								if($object->profile_picture !== NULL || $object->profile_picture != "")
								{
									$search .= "<img src=\"$object->profile_picture\">";
								}
								else
								{
									$search .= "<img src=\"images/user.svg\">";
								}
								$search .= $object->find_suitable_info($session->user_id);
								$search .= "</a>";
								$search .= "</div>";
							}
							else
							{
								$object = User::find_by_id($user_id);
								$search .= "<div class=\"user_search conversation\"><a href=\"create_conv.php?to={$user_id}\">";
								if($object->profile_picture !== NULL || $object->profile_picture != "")
								{
									$search .= "<img src=\"$object->profile_picture\">";
								}
								else
								{
									$search .= "<img src=\"images/user.svg\">";
								}
								$search .= $object->find_suitable_info($session->user_id);
								$search .= "<br>Click here to start a conversation.</a>";
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
				$sql = "SELECT * FROM active_chats WHERE (from_id = {$session->user_id}  AND to_id = {$object->id})";
				$sql .= " OR (from_id = {$object->id} AND to_id = {$session->user_id} ) LIMIT 1";
				$result_set = $database->query($sql);
				$row = $database->fetch_assoc($result_set);
				if(!empty($row))
				{
					$c_id = $row['id'];
					redirect_to("index.php?msg=message&c_id={$c_id}");
				}
				else
				{
					if($object->id !== $session->user_id)
					{
						$search = "";
						$search .= "<div class=\"user_search conversation\"><a href=\"create_conv.php?to={$object->id}\">";
						if($object->profile_picture !== NULL || $object->profile_picture != "")
						{
							$search .= "<img src=\"$object->profile_picture\">";
						}
						else
						{
							$search .= "<img src=\"images/user.svg\">";
						}
						$search .= $object->find_suitable_info($session->user_id);
						$search .= "<br>Click here to start a conversation.</a>";
						$search .= "</div>";
				 	}
				 	elseif($object->id === $session->user_id)
				 	{
				 		$search = "<div class=\"user_search conversation\">Are you searching for yourself ?</div>";
				 	}
				}
				$database->free_result($result_set);
			}
			else
			{
				$search = "<div class=\"user_search conversation\">No such user found. Invite to Chat Up.</div>";
			}
		}
	}
}
if($type === "group")
{
	if(isset($_GET['group_id']))
	{
		$group_id = $_GET['group_id'];
		$object = Group::find_by_id($group_id);
		if(is_object($object) && array_key_exists($session->user_id,$object->users))
		{
			$group_id = $database->escape_value($_GET['group_id']);
			$c_id = Group::find_c_id_for_group($group_id);
		}
		else
		{
			$group_id = 0;
			$c_id = 0;
		}
	}
	else
	{
		$group_id = 0;
		$c_id = 0;
	}
	/*if(isset($_GET['c_id']) && ($c_id == 0 || $group_id == 0)){$c_id = $_GET['c_id'];$object = Group::find_group_for_c_id($c_id);if(is_object($object) && array_key_exists($session->user_id,$object->users)){$c_id = $database->escape_value($_GET['c_id']);$group_id = Group::find_group_for_c_id($c_id);}else{$c_id = 0;$group_id = 0;}}else{$c_id = 0;$group_id = 0;}*/
	if(isset($_POST['send_message']))
	{
		$c_id = $database->escape_value($_POST['c_id']);
		if($c_id != 0)
		{
			$object = Conversation::find_by_id($c_id);
			$group_id = Group::find_group_id_for_c_id($c_id);
			if($object->from_id === $group_id)
			{
				$message_to_send = new Message();
				$message_to_send->c_id = $c_id;
				$message_to_send->message = $database->escape_value($_POST['message']);
				$message_to_send->from_id = $session->user_id;
				$message_to_send->to_id = NULL;
				$message_to_send->timestamp = strftime("%Y-%m-%d %H:%M:%S",time());
				$message_to_send->image = $database->escape_value(NULL);
				if($message_to_send->create())
				{
					Group::change_timestamp($group_id);
					redirect_to("index.php?msg=group&group_id={$group_id}&c_id=" . $c_id);
				}
				else
				{
					echo "Message creation failed";
				}
			}
		}
	}

	if(isset($_POST['search']))
	{
		$objects = Group::find_by_group_name($_POST['search_item']);
		$search = "";
		if(is_array($objects) && !empty($objects))
		{
			foreach($objects as $key=>$object)
			{
				if(Group::verify_user($session->user_id,$object->id))
				{
					$search .= "<div class=\"group_search conversation\">";
					if($key == 0)
					{
						$search .= "<a href=\"index.php?msg=group\">&laquo;Back</a><br><a href=\"index.php?msg=group&group_id={$object->id}&c_id={$object->c_id}\">";
					}
					if($object->group_picture !== NULL || $object->group_picture != "")
					{
						$search .= "<img src=\"$object->group_picture\">";
					}
					else
					{
						$search .= "<img src=\"images/group.svg\">";
					}
					$search .= $object->group_name;
					$search .= "</a></div>";
				}
			}
		}
		else
		{
			$search .= "<div class=\"group_search conversation\">";
			$search .= "<a href=\"index.php?msg=group\">&laquo;Back</a><br><a href=\"index.php?msg=group\">";
			$search .= "No such group found. Create a new group.";
			$search .= "</a></div>";
		}
	}
}

?>
<div id="profile_container" class="profile_container">
<?php
	$user = User::find_by_id($session->user_id); 
?>
	<span><img id="back_from_profile" src="images/arrow_back.svg">&nbsp;&nbsp;&nbsp;Profile &amp; Status</span>
	<div id="profile_photo" class="profile_photo">
		<img src="<?php if($user->profile_picture !== NULL ) {echo $user->profile_picture;} else {echo "images/user.svg";}?>">
	</div>
	<div class="status"><h2>Status: </h2><?php echo $user->status; ?></div>
</div>
<div id="contacts_container" class="contacts_container">
	<div id="contacts_header" class="contacts_header"><img id="back_from_contacts" src="images/arrow_back.svg"><h2>Contacts</h2></div>
	<div id="search_from_contacts" class="search">
		<div class="box">
			<form class="form search" method="post" action="index.php?msg=<?php echo $type; ?>">
				<input type="text" name="search_item" placeholder="<?php if($type==="message") {echo "Email-id or Contact name";} else {echo "Group-id";} ?>">
				<label>
					<img src="images/search.svg">
					<input type="submit" value="Search" name="search">
				</label>
			</form>
		</div>
	</div>
	<a href="add_contact.php">
	<div class="add_contacts"><span>Add contacts</span><img src="images/add.svg" alt="add"></div>
	</a>
	<div id="contact_members" class="contact_members">
	<?php 
		$contacts = Contact::find_contacts_for_user($session->user_id);
		$output = "";
		$letter = "";
		foreach ($contacts as $user_id => $contact) 
		{
			$user = User::find_by_id($user_id);
			$name = $user->find_suitable_info($session->user_id);
			$sql = "SELECT * FROM active_chats WHERE (from_id = {$session->user_id}  AND to_id = {$user_id})";
			$sql .= " OR (from_id = {$user_id} AND to_id = {$session->user_id} ) LIMIT 1";
			$result_set = $database->query($sql);
			$row = $database->fetch_assoc($result_set);
			if(!empty($row))
			{
				$link = "index.php?msg=message&c_id={$row['id']}";
			}
			else
			{
				$link = "create_conv.php?to={$user_id}";
			}
			if(strcasecmp($letter,substr($name,0,1)))
			{
				$output .= "<div class=\"letter\">".ucwords(substr($name,0,1))."</div>";
			}
			$output .= "<a href=\"$link\"><div class=\"members\">";
			if($user->profile_picture !== NULL)
			{
				$output .= "<img src=\"{$user->profile_picture}\" alt=\"{$name}\">";
			}
			else
			{
				$output .= "<img src=\"images/user.svg\" alt=\"{$name}\">";
			}
			$output .= "<span class=\"member_name\">$name</span>";
			$output .= "<span class=\"member_status\">$user->status</span>";
			$output .= "</div></a>"; 
			$letter = substr($name,0,1);
		}
		echo $output;
	?>
	</div>
</div>
		<div id="left_pane" class="left_pane">
			<div id="self_info" class="self_info">
				<?php
					$me = User::find_by_id($session->user_id);
					if($me->profile_picture !== NULL || trim($me->profile_picture) !== "")
					{
						echo "<img id=\"me\" src=\"$me->profile_picture\" alt=\"self\">";
					}
					else
					{
						echo "<img id=\"me\" src=\"images/user.svg\" alt=\"self\">";
					}
				?>
				
				<span id="options1" class="options"><a title="User options"><img src="images/settings.svg" alt="settings"></a></span>
				<span id="contacts1" class="contacts"><a href="#" title="Contacts"><img src="images/contacts.svg" alt="contacts"></a></span>
				<span class="add"><a href="create_group.php" title="Create group"><img src="images/add_group.svg" alt="add_group"></a></span>
				<span class="logout"><a href="logout.php" title="Logout">Log out</a></span>
			</div>
			<div class="menu" id="menu1">
				<div class="menu_items" id="display_profile"><a href="#">Profile &amp; Status</a></div>
				<div class="menu_items"><a href="edit_profile.php">Edit Profile</a></div>
				<div class="menu_items"><a href="logout.php">Logout</a></div>
			</div>
			<div id="search" class="search">
				<div class="box">
					<form class="form search" method="post" action="index.php?msg=<?php echo $type; ?>">
						<input type="text" name="search_item" placeholder="<?php if($type==="message") {echo "Email-id or Contact name";} else {echo "Group-id";} ?>">
						<label>
							<img src="images/search.svg">
							<input type="submit" value="Search" name="search">
						</label>
					</form>
				</div>
			</div>
			<div id="tabs" class="tabs">
				<div class="tab_1<?php if($type === "message") {echo " selected";}?>">
					<span id="tab_1">
						<a class="tab tab1" href="index.php?msg=message">Chats</a>
					</span>
				</div>
				<div class="tab_2<?php if($type === "group") {echo " selected";}?>">
					<span id="tab_2">
						<a class="tab tab2" href="index.php?msg=group">Groups</a>
					</span>
				</div>
			</div>
			<div id="active_chats" class="active_chats">
				<?php 
				if($type === "message")
				{
					if(isset($search))
					{
						echo $search;
					}
					else
					{
						$conversation->user_id = $user_id = $session->user_id;
						$objects = $conversation->find_all_conv_by_user();
						$output = "";
						foreach($objects as $object)
						{
							$output .= "<div class=\"conversation";
							if($object->c_id == $c_id)
							{
								$output .= " selected\">";
							}
							else
							{
								$output .= "\">";
							}
							$output .= "<a href=\"index.php?msg=message&c_id={$object->c_id}\">";
							if($object->profile_picture === null || trim($object->profile_picture) === "")
							{
								$output .= "<img src=\"images/user.svg\">";
							}
							else
							{
								$output .= "<img src=\"" . $object->profile_picture . "\">";
							} 
							$user = User::find_by_id($object->id);
							$output .= $user->find_suitable_info($session->user_id) . "</a>";
							$output .= "</div>";
						}
						echo $output;
					}
				} 
				if($type === "group")
				{
					if(isset($search))
					{
						echo $search;
					}
					else
					{
						$groups = Group::find_all_groups_for_user($session->user_id);
						$output = "";
						foreach($groups as $group)
						{
							$output .= "<div class=\"conversation";
							if($group_id == $group->id)
							{
								$group_selected = $group;
								$output .= " selected";
							}
							$output .= "\">";
							$output .= "<a href=\"index.php?msg=group&group_id={$group->id}&c_id={$group->c_id}\">";
							if($group->group_picture === NULL || trim($group->group_picture) === "")
							{
								$output .= "<img src=\"images/group.svg\">";
							}
							else
							{
								$output .= "<img src=\"" . $group->group_picture . "\">";
							} 
							$output .= $group->group_name . "</a><br>";
							$output .= "</div>";
						}
						echo $output;
					}
				}
				?>
			</div>
		</div>
		<div id="right_pane" class="right_pane">
			<div id="user_info" class="user_info">
			<?php	
			if($c_id != 0 && $type === "message")
			{
				$sql = "SELECT from_id,to_id FROM active_chats WHERE id = {$c_id} LIMIT 1";
				$result = $database->query($sql);
				if($result->num_rows === 1)
				{
					$row = $database->fetch_assoc($result);
					if($row['from_id'] === $session->user_id)
					{
						$from = User::find_by_id($database->escape_value($row['to_id']));
						if(is_object($from))
						{
							$from_name = $from->find_suitable_info($session->user_id);
							if($from->profile_picture === NULL || trim($from->profile_picture) === "")
							{
								$from_pic = "images/user.svg";
							}
							else
							{
								$from_pic = $from->profile_picture;
							}
						}
						else
						{
							$from_name = "Unkown";
							$from_pic = "images/user.svg";
						}
					}
					elseif($row['to_id'] === $session->user_id)
					{
						$from = User::find_by_id($database->escape_value($row['from_id']));
						if(is_object($from))
						{
							$from_name = $from->find_suitable_info($session->user_id);
							if($from->profile_picture === NULL || trim($from->profile_picture) === "")
							{
								$from_pic = "images/user.svg";
							}
							else
							{
								$from_pic = $from->profile_picture;
							}
						}
						else
						{
							$from_name = "Unkown";
							$from_pic = "images/user.svg";
						}
					}
				}
				$display = "<img src=\"{$from_pic}\" alt=\"{$from_name}\">";
				$display .= "<span class=\"from_name\">" . $from_name . "</span>";
				$display .= "<span class=\"options\">";
				$display .= "<a href=\"#\" title=\"Info\" id=\"options2\"><img src=\"images/settings.svg\" alt=\"settings\"></a>";
				$display .= "<a href=\"#\" title=\"Attach Image\"><img src=\"images/attachment.svg\" alt=\"attachment\"></a>";
				$display .= "</span>";
				echo $display;
				$database->free_result($result);
			}
			if($c_id != 0 && $type === "group" && isset($group_selected))
			{
				if($group_selected->group_picture !== NULL || trim($group_selected->group_picture) !== "")
				{
					$from_pic = $group_selected->group_picture;
				}
				else
				{
					$from_pic = "images/group.svg";
				}
				$from_name = $group_selected->group_name;
				$output = "<img src=\"{$from_pic}\" alt={$from_name}>";
				$output .= "<span class=\"from_name\">" . $from_name . "</span><span class=\"members\">";
				$string = "";
				foreach($group_selected->users as $user_id=>$user)
				{
					$string .= $user->find_suitable_info($session->user_id);
					$string .= ","; 
				}
				$string = substr($string,0,-1);
				$output .= substr($string,0,35);
				if(strlen($string) > 35)
				{
					$output .= "&hellip;";
				}
				$output .= "</span>";
				$output .= "<span class=\"options\">";
				$output .= "<a href=\"#\" title=\"Info\" id=\"options2\"><img src=\"images/settings.svg\" alt=\"settings\"></a>";
				$output .= "<a href=\"#\" title=\"Attach Image\"><img src=\"images/attachment.svg\" alt=\"attachment\"></a>";
				$output .= "</span>";
				echo $output;
			}
			?>
			</div>
			<div id="menu2" class="menu2">
				<?php if($type === "message") { ?>
				<div id="user_image" class="user_image">
					<img class="user_profile_pic" src="
					<?php 
					if(isset($from->profile_picture) && $from->profile_picture !== NULL) 
					{
						echo $from->profile_picture;
					} 
					else 
					{
						echo "images/user.svg";
					} 
					?>">
				</div>
				<div id="user_status" class="user_status">
					<h2>Status</h2>
					<?php if(isset($from)) echo $from->status; ?>
				</div>
				<div id="user_mail" class="user_mail">
					<h2>Mail-Id</h2>
					<span><?php if(isset($from)) echo $from->mail_id; ?></span>
				</div>
				<?php } if($type === "group") { 
					if(isset($group_id) && Group::is_admin($group_id,$session->user_id))
					{
						$is_admin = 1;
					}
					else
					{
						$is_admin = 0;
					}
				?>
				<div id="group_image" class="user_image">
					<img class="user_profile_pic" src="
					<?php 
					if(isset($group_selected) && $group_selected->group_picture !== NULL) 
					{
						echo $group_selected->group_picture;
					} 
					else 
					{
						echo "images/group.svg";
					} ?>">
					<span><a href="edit_group.php?group_id=<?php if(isset($group_selected)) {echo $group_selected->id;} ?>"><img src="images/edit.svg"></a></span>
				</div>
				<div id="group_name_display" class="user_status">
					<h2>Group name</h2><span><a href="edit_group.php?group_id=<?php if(isset($group_selected)) {echo $group_selected->id;} ?>"><img src="images/edit.svg"></a></span>
					<?php if(isset($group_selected)) {echo $group_selected->group_name;} ?>
				</div>
				<div id="group_members_display" class="group_members_display">
					<h2>Group Members</h2>
					<?php if($is_admin == 1) { ?>
					<span><a href="create_group.php?update=1&group_id=<?php if(isset($group_selected)) {echo $group_selected->id;} ?>"><img src="images/add.svg"></a></span>
					<?php } ?> 
					<?php 
					if(isset($message))
					{
						echo $message ."<br>";
					} 
					?>
					<?php
					if(isset($group_selected)) 
					{
						foreach($group_selected->users as $user_id=>$user) 
						{
							echo $user->find_suitable_info($session->user_id);
							if($is_admin == 1 && $session->user_id !== $user->id)
							{
								echo "<a href=\"remove_member.php?id={$user->id}&group_id={$group_selected->id}\"><img class=\"small_image\" src=\"images/remove.svg\"></a>";
							}
							echo "<br><br>";
						}
					} 
					?>
				</div>
				<?php if(isset($group_selected)) { ?>
				<a href="remove_member.php?id=<?php echo $session->user_id; ?>&group_id=<?php echo $group_selected->id; ?>"><button class="exit_group">Exit Group</button></a>
				<?php } ?>
				<?php } ?>
			</div>
			<div id="messages" class="messages">
			<?php 
			if($c_id == 0)
			{
				echo "<div class=\"placeholder_image\"></div>";
			}
			if($type === "message")
			{
				$output = "";
				if($c_id != 0)
				{
					$msg->c_id = $c_id;
					$objects = $msg->find_all_messages_for_conv();
					foreach($objects as $object)
					{
						$output .= "<div class=\"msg";
						if($object->from_id == $session->user_id)
						{
							$output .= " from\">";
						}
						else
						{
							$output .= " to\">";
							$output .= "<span class=\"from_name\">" . $from_name . "</span>";
						}
						if($object->message === null)
						{
							$output .= "<img src=\"" . $object->image . "\">";
						}
						else
						{
							$output .= "<p>" . nl2br(htmlentities(wordwrap($object->message,70))) . "</p>";
						}
						$timestamp = strtotime($object->timestamp);
						$output .= "<p class=\"time\">" . strftime("%a %b,%d %Y %I:%M %p",$timestamp) . "</p>";
						$output .= "</div>";
						if($object->to_id == $session->user_id && $object->is_read == 0)
						{
							$object->mark_read();
						}
					}
					$output .= "<a href=\"#last_message\" id=\"last_message\"><a>";
					echo $output;
				}
			}
			elseif($type === "group" && isset($group_selected))	
			{
				$user_array = array();
				if($c_id != 0)
				{
					$output = "";
					$msg->c_id = $c_id;
					$objects = $msg->find_all_messages_for_conv();
					foreach($objects as $object)
					{
						$output .= "<div class=\"msg";
						if($object->from_id == $session->user_id)
						{
							$output .= " from\">";
						}
						else
						{
							$output .= " to\">";
							$output .= "<span class=\"from_name\">" . $group_selected->users[$object->from_id]->find_suitable_info($session->user_id) . "</span>";
						}
						if($object->message === null)
						{
							$output .= "<img src=\"" . $object->image . "\">";
						}
						else
						{
							$output .= "<p>" . nl2br(htmlentities(wordwrap($object->message,70))) . "</p>";
						}
						$timestamp = strtotime($object->timestamp);
						$output .= "<p class=\"time\">" . strftime("%a %b,%d %Y %I:%M %p",$timestamp) . "</p>";
						$output .= "</div>";
					}
					$output .= "<a href=\"#last_message\" id=\"last_message\"><a>";
					echo $output;
				}
			} 
			?>
			</div>
			<div id="send_message" class="send_message">
				<form class="send message" method="post" action="index.php?msg=<?php echo $type; if($type=="group") {echo "&group_id={$group_id}";} echo "&c_id=" . $c_id ; ?>">
					<span>
						<input type="hidden" name="c_id" value="<?php echo $c_id; ?>">
						<input type="text" name="message" class="field-long" placeholder="Chat Up" autocomplete="off">
						<label>
							<img src="images/send.svg">
							<input type="submit" value="Send" name="send_message">
						</label>
					</span>
				</form>
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
