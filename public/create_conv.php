<?php require_once("../includes/init.php"); ?>
<?php
if(!$session->is_logged_in())
{
	redirect_to("login.php");
}
?>
<?php

if(isset($_GET['to']))
{
	$to = $database->escape_value($_GET['to']);
	$timestamp = strftime("%Y-%m-%d %H:%M:%S",time());
	$sql = "SELECT * FROM active_chats WHERE (from_id = {$session->user_id}  AND to_id = {$to})";
	$sql .= " OR (from_id = {$to} AND to_id = {$session->user_id} ) LIMIT 1";
	$result_set = $database->query($sql);
	$row = $database->fetch_assoc($result_set);
	if(!empty($row))
	{
		$c_id = $row['id'];
		redirect_to("index.php?c_id={$c_id}");
	}
	else
	{
		$conversation = new Conversation();
		$conversation->user_id = $conversation->from_id = $session->user_id;
		$conversation->to_id = $to;
		$conversation->timestamp = $timestamp;
		if($conversation->create())
		{
			redirect_to("index.php?msg=message&c_id={$conversation->c_id}");
		}
		else
		{
			redirect_to("index.php?msg=message");
		}
	}
}

?>