<?php require_once("../includes/init.php"); ?>
<?php
if(!$session->is_logged_in())
{
	redirect_to("login.php");
}
?>
<?php 
if(isset($_GET['user_id']) && isset($_GET['group_id']))
{
	$group = new Group();
	$group->id = $database->escape_value($_GET['group_id']);
	$user_id = $database->escape_value($_GET['user_id']);
	if($group->add_group_member($user_id)) 
	{
		$session->message("1");
		redirect_to("create_group.php?page=2");
	}
	else
	{
		$session->message("2");
		redirect_to("create_group.php?page=2");
	}
}