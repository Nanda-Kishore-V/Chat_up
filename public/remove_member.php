<?php require_once("../includes/init.php"); ?>
<?php
if(!$session->is_logged_in())
{
	redirect_to("login.php");
}
?>
<?php 

if(isset($_GET['id']) && $_GET['group_id'])
{
	$group_id = $database->escape_value($_GET['group_id']);
	$user_id = $database->escape_value($_GET['id']);
	if(Group::is_admin($group_id,$session->user_id) || $session->user_id === $_GET['id'])
	{
		
		$group = Group::find_by_id($group_id);
		if($group->remove_member($user_id))
		{
			$session->message("Member removed.");
			redirect_to("index.php?msg=group&group_id={$group_id}");
		}
		else
		{
			$session->message("Member removal failed.");
			redirect_to("index.php?msg=group&group_id={$group_id}");
		}
	}
}

?>
<?php 
	if(isset($database))
	{
		$database->close_connection();
	}
?>
