<?php
require_once("constants.php");

date_default_timezone_set("Asia/Calcutta");

function strip_zeros_from_date($marked_string = "")
{
	$no_zeros = str_replace("*0","",$marked_string);
	$cleaned_string = str_replace("*", "", $no_zeros);
	return $cleaned_string;
}

function redirect_to( $location = null )
{
	if($location != null)
	{
		header("Location: {$location}");
		exit;
	}
}

function output_message($message = "")
{
	if(!empty($message))
	{
		return "<p class=\"message\">{$message}</p>";
	}
	else
	{
		return "";
	}
}

function __autoload($class_name)
{
	$class_name = strtolower($class_name);
	$path = LIB_PATH.DS."{$class_name}.php";
	if(file_exists($path))
	{
		require_once($path);
	}
	else
	{
		die("The file {$class_name}.php could not be found.");
	}
}

function include_layout_template($template="")
{
	include(SITE_ROOT.DS.'public'. DS .'layouts'.DS.$template);
}

function log_action($action,$message="")
{
	date_default_timezone_set("Asia/Calcutta");
	defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
	defined('SITE_ROOT') ? null : define('SITE_ROOT','C:' . DS . DS . 'Apache24' . DS . 'htdocs' . DS . 'Nanda' . DS. 'photo_gallery');

	$file = SITE_ROOT.DS."logs/log.txt";
	if(is_file($file) && is_writable($file))
	{
		if($handle = fopen($file,"at"))
		{
			$content = strftime("%Y-%m-%d %H:%M:%S",time());
			$content .= " | ";
			$content .= ucfirst($action) . ": ";
			$content .= $message . "\n";
			fwrite($handle,$content);
			fclose($handle);
		}
	}
	else
	{
		echo "An error occured";
	}
}

function read_log()
{
	defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
	defined('SITE_ROOT') ? null : define('SITE_ROOT','C:' . DS . DS . 'Apache24' . DS . 'htdocs' . DS . 'Nanda' . DS. 'photo_gallery');

	$file = SITE_ROOT.DS."logs/log.txt";
	if(is_file($file) && is_readable($file))
	{
		if($handle = fopen($file,"r"))
		{
			$text = "<ul class=\"log-entries\">";
			while(!feof($handle))
			{
				$temp = fgets($handle);
				if(trim($temp) == "")
				{

				}
				else
				{
					$text .= "<li>" . $temp . "</li>";
				}
			}
			$text .= "</ul>";
			echo $text;
			fclose($handle);
		}
	}
	else
	{
		echo "An error occured";
	}
}

function clear_log()
{
	global $session;
	date_default_timezone_set("Asia/Calcutta");
	defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
	defined('SITE_ROOT') ? null : define('SITE_ROOT','C:' . DS . DS . 'Apache24' . DS . 'htdocs' . DS . 'Nanda' . DS. 'photo_gallery');

	$file = SITE_ROOT.DS."logs/log.txt";
	if(is_file($file) && is_writable($file))
	{
		if($handle = fopen($file,"wt"))
		{
			$content = strftime("%Y-%m-%d %H:%M:%S",time());
			$content .= " | Log file cleared by User {$session->user_id}\n";
			fwrite($handle,$content);
			fclose($handle);
		}
	}
	else
	{
		echo "An error occured";
	}
}


?>