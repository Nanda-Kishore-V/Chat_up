<?php require_once("layouts/header.php"); ?>
<?php require_once("../includes/init.php"); ?>
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
	}
	else
	{
		$c_id = 0;
	}

	if(isset($_POST['send_message']))
	{
		if($c_id == 0)
		{
			redirect_to("index.php");
		}
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

	if(isset($_POST['search']))
	{
		$object = User::find_by_mail_id($_POST['mail_id']);
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
					$search .= "<div class=\"user_search conv\"><a href=\"create_conv.php?to={$object->id}\">";
					if($object->profile_picture !== NULL || $object->profile_picture != "")
					{
						$search .= "<img src=\"$object->profile_picture\">";
					}
					else
					{
						$search .= "<img src=\"images/nopic.jpeg\">";
					}
					$search .= $object->full_name();
					$search .= "<br>Click here to start a conversation.</a>";
					$search .= "</div>";
			 	}
			 	elseif($object->id === $session->user_id)
			 	{
			 		$search = "<div class=\"user_search conv\">Are you searching for yourself ?</div>";
			 	}
			}
		}
		else
		{
			$search = "<div class=\"user_search conv\">No such user found. Invite to Chat Up.</div>";
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
		}
		else
		{
			$group_id = 0;
		}
	}
	else
	{
		$group_id = 0;
	}
	if(isset($_GET['c_id']))
	{
		$c_id = $_GET['c_id'];
		$object = Group::find_group_for_c_id($c_id);
		if(is_object($object) && array_key_exists($session->user_id,$object->users))
		{
			$c_id = $database->escape_value($_GET['c_id']);
		}
		else
		{
			$c_id = 0;
		}
	}
	else
	{
		$c_id = 0;
	}
}

?>
<div id="active_chats">
	<span>
		<form class="form search" method="post" action="index.php">
			<input type="text" name="mail_id" class="field-long" placeholder="<?php if($type==="message") {echo "Email-id";} else {echo "Group-id";} ?>">
			<input type="submit" value="Search" name="search">
		</form>
	</span>
	<div class="tabs">
		<a class="tab tab1" href="index.php?msg=message">Chats</a>
		<a class="tab tab2" href="index.php?msg=group">Groups</a>
	</div>
	<?php 
	if($type === "message")
	{
		if(isset($search))
		{
			echo $search;
		}
		$conversation->user_id = $user_id = $session->user_id;
		$objects = $conversation->find_all_conv_by_user();
		$output = "";
		foreach($objects as $object)
		{
			$output .= "<div class=\"conv";
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
				$output .= "<img src=\"images/nopic.jpeg\">";
			}
			else
			{
				$output .= "<img src=\"" . $object->profile_picture . "\">";
			} 
			$output .= $object->first_name . " " . $object->last_name . "</a>";
			$output .= "</div>";
		}
		echo $output;
	} 
	if($type === "group")
	{
		if(isset($search))
		{
			echo $search;
		}
		$groups = Group::find_all_groups_for_user($session->user_id);
		$output = "";
		foreach($groups as $group)
		{
			$output .= "<div class=\"conv";
			if($group_id == $group->id)
			{
				$output .= " selected";
			}
			$output .= "\">";
			$output .= "<a href=\"index.php?msg=group&group_id={$group->id}&c_id={$group->c_id}\">";
			if($group->group_picture === NULL || trim($group->group_picture) === "")
			{
				$output .= "<img src=\"images/nopic.jpeg\">";
			}
			else
			{
				$output .= "<img src=\"" . $group->group_picture . "\">";
			} 
			$output .= $group->group_name . "</a>";
			$output .= "</div>";
		}
		echo $output;
	}
	?>
</div>
<div id="messages">
	<?php 
	if($type === "message")
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
			}
			$output .= $object->from_id;
			if($object->message === null)
			{
				$output .= "<img src=\"" . $object->image . "\">";
			}
			else
			{
				$output .= "<p>" . wordwrap($object->message,70) . "</p>";
			}
			$timestamp = strtotime($object->timestamp);
			$output .= "<p class=\"time\">" . strftime("%a %b,%d %Y %I:%M %p",$timestamp) . "</p>";
			$output .= "</div>";
			if($object->to_id == $session->user_id && $object->is_read == 0)
			{
				$object->mark_read();
			}
		}
		echo $output;
	}
	elseif($type === "group")	
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
			}
			if($object->message === null)
			{
				$output .= "<img src=\"" . $object->image . "\">";
			}
			else
			{
				$output .= "<p>" . wordwrap($object->message,70) . "</p>";
			}
			$timestamp = strtotime($object->timestamp);
			$output .= "<p class=\"time\">" . strftime("%a %b,%d %Y %I:%M %p",$timestamp) . "</p>";
			$output .= "</div>";
		}
		echo $output;
	} 
	?>
</div>
<div class="send_message <?php if($c_id==0) {echo "no_display";} ?>" id="send_message">
	<form class="send message" method="post" action="index.php?c_id=<?php echo $c_id; ?>">
		<span><input type="text" name="message" class="field-long" placeholder="Chat Up"><input type="submit" value="Send" name="send_message"></span>
	</form>
</div>

<?php require_once("layouts/footer.php"); ?>