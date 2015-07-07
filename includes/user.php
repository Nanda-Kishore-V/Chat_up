<?php
require_once("init.php");

class User extends DatabaseObject
{
	protected static $table_name = "users"; 
	protected static $extra_table = "contacts";
	protected static $db_fields = array("mail_id","first_name","last_name","password","profile_picture","status");
	public $id;
	public $mail_id;
	public $first_name;
	public $last_name;
	public $password; 
	public $profile_picture;
	public $status;

	public static function authenticate($mail_id = "",$password ="")
	{
		global $database;

		$sql = "SELECT * FROM " . static::$table_name ;
		$sql .= " WHERE mail_id = '{$mail_id}' ";
		$sql .= " LIMIT 1";

		$result_array = static::find_by_sql($sql);
		if(!empty($result_array))
		{
			$existing_hash = $result_array[0]->password;
			if(static::password_check($password,$existing_hash))
			{
				return $result_array[0];
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}		
	}

	public static function find_by_mail_id($mail_id = "")
	{
		global $database;
		$sql = "SELECT * FROM " . static::$table_name . " WHERE mail_id = '" . $database->escape_value($mail_id) . "' LIMIT 1";
		$result_array = static::find_by_sql($sql);
		return empty($result_array) ? false : $result_array[0];  
	}

	public function full_name()
	{
		if(isset($this->first_name) && isset($this->last_name))
		{
			return $this->first_name . " " . $this->last_name;
		}
		else
		{
			return "";
		}
	}

	public function find_suitable_info($user_id = 0)
	{
		$contacts = array();
		$contacts = Contact::find_contacts_for_user($user_id);
		if(array_key_exists($this->id,$contacts))
		{
			return $contacts[$this->id];
		}
		else if($this->id == $user_id)
		{
			return "You";
		}
		else
		{
			if(isset($this->mail_id))
			{
				return $this->mail_id;
			}
			else
			{
				return "";
			}		
		}
	}

	public static function generate_hash($password)
	{
		$blowfish = "$2y$10$";
		$unique = md5(uniqid(mt_rand(),true));
		$correct_chars = base64_encode($unique);
		$exact_chars = str_replace("+", ".", $correct_chars);
		$salt = substr($exact_chars,0,22);
		$format_and_salt = $blowfish . $salt;
		$hash = crypt($password,$format_and_salt);
		return $hash;
	}

	private static function password_check($password="", $existing_hash="")
 	{
 		$hash = crypt($password,$existing_hash);
 		if($hash == $existing_hash)
 		{
 			return true;
 		}
 		else
 		{
 			return false;
 		}
 	}
} 

?>