<?php require_once("../includes/init.php"); ?>
<?php
if(!$session->is_logged_in())
{
	redirect_to("login.php");
}
?>
<?php 

if(isset($_GET['id']) && isset($_GET['name']))
{
	$id = $database->escape_value($_GET['id']);
	$name = $database->escape_value($_GET['id']);
	$contact->user_id = $session->user_id;
	$contact->add_contact($id,$name);
}

?>
<?php

if(isset($_POST['submit']))
{
	$search_item = $database->escape_value($_POST['search_item']);
	$object = User::find_by_mail_id($search_item);
	if(is_object($object))
	{
		$contacts = Contact::fill_in_contacts($session->user_id);
		foreach($contacts as $contact)
		{
			if($contact->mail_id === $search_item)
			{
				$search = "<div class=\"user_search conversation\">Contact already exists.</div>";
			}
		}
		if(!isset($search))
		{
			if($object->id !== $session->user_id)
			{
				$search = "";
				$search .= "<div class=\"user_search conversation\">";
				if($object->profile_picture !== NULL || $object->profile_picture != "")
				{
					$search .= "<img src=\"$object->profile_picture\">";
				}
				else
				{
					$search .= "<img src=\"images/user.svg\">";
				}
				$search .= $object->find_suitable_info($session->user_id);
				$search .= "<a href=\"add_contact.php?id={$object->id}\"><img src=\"images/add.svg\" alt=\"add\"></a>";
				$search .= "</div>";
		 	}
		 	elseif($object->id === $session->user_id)
		 	{
		 		$search = "<div class=\"user_search conversation\">Are you searching for yourself ?</div>";
		 	}
		}
	}
	else
	{
		$search = "<div class=\"user_search conversation\">No such user found. Invite to Chat Up.</div>";
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
					<span class="heading"> Add Contact </span>
					<span class="logout"><a href="logout.php" title="Logout">Log out</a></span>
					<span class="back"><a href="index.php" title="Go back to chats">&laquo;Back</a></span>
				</div>
				<div id="display_image" class="display_image">
					<img src="images/user.svg">
				</div>
			</div>
			<div id="right_pane" class="right_pane">
				<div id="user_info" class="user_info">
					<?php /*echo "<span class=\"info\"></span>";*/?>
				</div>
					<form class="form add_member" action="add_contact.php" method="post">
					<ul>
						<input type="hidden" name="group_id" value="">
						<li>
							<label id="search_icon"><img src="images/search.svg" alt="submit">
								<input type="submit" name="submit" value="" id="search_submit">
							</label>
						</li>
						<li>
							<label for="search">Search and Add</label>
							<input type="text" id="search" value="" name="search_item" class="field-long">
						</li>
					</ul>
				</form>
				<div class="active_chats">
					<?php
						if(isset($search))
						{
							echo $search;
						}
					?>
				</div>
				<div class="selected_members" id="selected_members">
					<h2> Contacts added: </h2>
				</div>
			</div>
		</div>
	</div>
</body>
</html>