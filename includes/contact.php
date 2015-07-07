<?php 

require_once("init.php");

class Contact
{
	public static $table_name = "contacts";
	public static $extra_table = "users";
	public static $db_fields = array("user_id","contact","name","first_name","last_name","profile_picture","mail_id");
	public $user_id;
	public $contact;
	public $name;
	public $first_name;
	public $last_name;
	public $profile_picture;
	public $mail_id;

	public static function find_by_sql($sql = "")
	{
		global $database;
		$objects = array();
		$result = $database->query($sql);
		while($record = $database->fetch_assoc($result))
		{
			$objects[] = static::instantiate($record);
		}
		return $objects;
	}

	protected static function instantiate($record)
	{
		$class_name = get_called_class();
		$object = new $class_name;
		
		foreach($record as $attribute=>$value)
		{
			if($object->has_attribute($attribute))
			{
				$object->$attribute = $value;
			}
		}
		return $object;
	}

	protected function has_attribute($attribute)
	{
		$object_vars = get_object_vars($this);
		return array_key_exists($attribute,$object_vars);
	}

	public static function fill_in_contacts($user_id = 0)
	{
		global $database;
		$objects = array();
		$user_id = $database->escape_value($user_id);
		$sql = "SELECT c.user_id,c.contact,c.name,u.first_name,u.last_name,u.profile_picture,u.mail_id,u.status FROM " . static::$table_name . " AS c INNER JOIN " . static::$extra_table . " AS u ";
		$sql .= "ON c.contact = u.id ";
		$sql .= "WHERE user_id='{$user_id}' ORDER BY c.name ASC";
		$result = $database->query($sql);
		while($row = $database->fetch_assoc($result))
		{
			$objects = static::find_by_sql($sql);
		}
		return $objects;
	}

	public static function find_contacts_for_user($user_id = 0)
	{
		global $database;
		$contacts = array();
		$user_id = $database->escape_value($user_id);
		$sql = "SELECT contact,name FROM " . static::$table_name . " WHERE user_id='{$user_id}'";
		$result = $database->query($sql);
		while($row = $database->fetch_assoc($result))
		{
			$contacts[$row['contact']] = $row['name'];
		}
		return $contacts;
	}

	public static function find_by_name($user_id = 0,$name = "")
	{
		global $database;
		$contacts = array();
		$user_id = $database->escape_value($user_id);
		$name = $database->escape_value($name);
		$sql = "SELECT contact,name FROM " . static::$table_name . " WHERE (user_id='{$user_id}' AND name LIKE '%{$name}%')";
		$result = $database->query($sql);
		while($row = $database->fetch_assoc($result))
		{
			$contacts[$row['contact']] = $row['name'];
		}
		return $contacts;
	}

	public function add_contact($contact = 0,$name="")
	{
		global $database;
		$contact = $database->escape_value($contact);
		$name = $database->escape_value($name);
		$sql = "SELECT contact FROM " . static::$table_name . " WHERE (user_id='{$this->user_id}' AND contact='{$contact}') LIMIT 1";
		$result = $database->query($sql);
		if($result->num_rows == 1)
		{
			return "Contact already exists.";
		}
		$sql = "INSERT INTO " . static::$table_name . "(user_id,contact,name) VALUES ('{$this->user_id}','{$contact}','{$name}')";
		$database->query($sql);
		if($database->affected_rows() == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function remove_contact($contact = 0)
	{
		global $database;
		$contact = $database->escape_value($contact);
		$sql = "DELETE FROM " . static::$table_name . " WHERE (user_id='{$this->user_id}' AND contact='{$contact}') LIMIT 1";
		$database->query($sql);
		if($database->affected_rows() == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function update_name($contact = 0,$name="")
	{
		global $database;
		$contact = $database->escape_value($contact);
		$name = $database->escape_value($name);
		$sql = "UPDATE " . static::$table_name . " SET name = '{$name}' WHERE user_id='{$this->user_id}' AND contact = '{$contact}' LIMIT 1";
		$database->query($sql);
		if($database->affected_rows() == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

$contact = new Contact();

?>