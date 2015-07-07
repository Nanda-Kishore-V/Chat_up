<?php

require_once("init.php");
class Conversation
{
	public static $table_name = "active_chats";
	public static $db_fields = array("from_id","to_id","timestamp","id","first_name","last_name","profile_picture");
	public $user_id;
	public $c_id;
	public $from_id;
	public $to_id;
	public $timestamp;
	public $id;
	public $first_name;
	public $last_name;
	public $profile_picture; 

	public function find_all_conv_by_user()
	{
		global $database;
		$sql = "SELECT " . static::$table_name . ".id AS c_id,from_id,to_id,timestamp,u.id,u.first_name,u.last_name,u.profile_picture FROM " .     static::$table_name ;
		$sql .= " INNER JOIN users AS u WHERE (from_id = " . $this->user_id . " OR to_id = " . $this->user_id .") ";
		$sql .= " AND (CASE WHEN from_id = " . $this->user_id . " THEN u.id = to_id ELSE u.id = from_id END) ORDER BY timestamp DESC";

		return static::find_by_sql($sql);
	}

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

	public static function find_by_id($id = 0)
	{
		global $database;
		$id = $database->escape_value($id);
		$sql = "SELECT * FROM " . static::$table_name . " WHERE id = {$id} LIMIT 1";
		$objects = static::find_by_sql($sql);
		return empty($objects) ? false : $objects[0];
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

	public function create()
	{
		global $database;
		$sql = "SELECT " . static::$table_name . ".id AS c_id,from_id,to_id,timestamp,u.id,u.first_name,u.last_name,u.profile_picture FROM " . static::$table_name ;
		$sql .= " INNER JOIN users AS u ";
		$sql .= " ON (CASE WHEN from_id = " . $this->user_id . " THEN u.id = to_id ELSE u.id = from_id END)";
		$sql .= " WHERE (from_id = " . $this->from_id . " AND to_id = " . $this->to_id . ") ";
		$sql .= " OR (from_id = " . $this->to_id . " AND to_id = " . $this->from_id . ") ";

		$objects = static::find_by_sql($sql);
		if(is_array($objects) && !empty($objects))
		{
			return $objects;
		}
		else
		{
			$sql = "INSERT INTO ". static::$table_name;
			$sql .= " (from_id,to_id,timestamp)";
			$sql .= " VALUES ( ";
			$sql .= " '{$this->from_id}','{$this->to_id}','{$this->timestamp}' ";
			$sql .= " )";
			
			if($database->query($sql))
			{
				$this->c_id = $database->last_id();
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	public function update()
	{
		global $database;
		$sql = "UPDATE " . static::$table_name . " SET ";
		$sql .= "from_id = '{$this->from_id}', ";
		$sql .= "to_id = '{$this->to_id}', ";
		$sql .= "timestamp = '{$this->timestamp}' ";
		$sql .= " WHERE id=". $database->escape_value($this->c_id);

		$result = $database->query($sql);
		if($database->affected_rows() == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function delete()
	{
		global $database;
		$sql = "DELETE FROM " . static::$table_name . " WHERE id = " . $database->escape_value($this->c_id) . " LIMIT 1";
		$result = $database->query($sql);
		if($database->affected_rows() == 1)
		{
			$sql = "DELETE FROM messages WHERE c_id = {$this->c_id}";
			$database->query($sql);
			if($database->affected_rows() >= 1)
			{
				return true;
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

	public function save()
	{
		if(!isset($this->c_id))
		{
			return $this->create();
		}
		else
		{
			return $this->update();
		}
	}
}

$conversation = new Conversation();

?>