<?php
require_once("../includes/init.php");

$contact->user_id = 1;
if($contact->add_contact(8,"Dad"))
{
	echo "Yes";
} 
else
{
	echo "No";
}

?>