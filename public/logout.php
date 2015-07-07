<?php

require_once("../includes/init.php");
$session->log_out();
redirect_to("login.php");

?>