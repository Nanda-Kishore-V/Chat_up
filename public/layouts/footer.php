</div>
<div id="footer"><?php date_default_timezone_set("Asia/Calcutta"); echo "&copy; Copyright " . strftime("%Y",time()); ?></div>
</body>
</html> 
<?php 
	if(isset($database))
	{
		$database->close_connection();
	}
?>