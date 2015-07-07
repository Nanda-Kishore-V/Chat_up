<?php

require_once("init.php");

class Message
{
	public static $table_name = "messages";
	public static $db_fields = array("c_id","message","image","is_read","timestamp","from_id","to_id");
	public $user_id;
	public $id;
	public $c_id;
	public $message;
	public $image;
	public $is_read;
	public $timestamp;
	public $from_id;
	public $to_id;

	public function find_all_messages_for_conv()
	{
		global $database;
		$objects = array();
		$sql = "SELECT * FROM " . static::$table_name . " WHERE c_id = " . $this->c_id ;
/*		$sql .= " AND (from_id = " . $session->user_id . " OR to_id = " . $session->user_id . ")";
*/		$sql .= " ORDER BY timestamp" ;
		return static::find_by_sql($sql); 
	}

	public function verify_c_id($c_id=0)
	{
		global $database;
		$database->escape_value($c_id);
		$sql = "SELECT * FROM active_chats WHERE id = {$c_id} LIMIT 1";
		$result = $database->query($sql);
		$row = $database->fetch_assoc($result);
		if($row['from_id'] == $session->user_id || $row['to_id'] == $session->user_id)
		{
			return $c_id;
		}
		else
		{
			return 0;
		}
	}

	protected static function instantiate($record)
	{
		$object_name = get_called_class();
		$object = new $object_name;

		foreach($record as $attribute => $value)
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
		return array_key_exists($attribute, $object_vars);
	}

	public static function find_by_sql($sql="")
	{
		global $database;
		$objects = array();
		$result_set = $database->query($sql);
		while($record = $database->fetch_assoc($result_set)) 
		{
			$objects[] = static::instantiate($record);
		}
		return $objects;
	}

	public static function find_by_id($id = 0)
	{
		global $database;
		$sql = "SELECT * FROM " . static::$table_name . " WHERE id = " . $database->escape_value($id) . " LIMIT 1";
		$result_array = static::find_by_sql($sql);
		return empty($result_array) ? false : $result_array[0];  
	}

	public function mark_read()
	{
		global $database;
		$sql = "UPDATE " . static::$table_name . " SET is_read = '1' WHERE id = " . $this->id;
		$result = $database->query($sql);
		if(!$result)
		{
			return false;
		} 
		else
		{
			return true;
		}
	}

	public function create()
	{
		global $database;
		if($this->message === NULL && $this->message->image === NULL)
		{
			return false;
		}
		if($this->image === null)
		{
			$sql = "INSERT INTO ". static::$table_name;
			$sql .= " (c_id,message,image,is_read,timestamp,from_id,to_id) ";
			$sql .= " VALUES ";
			$sql .=  "( '{$this->c_id}','{$this->message}',NULL,'0','{$this->timestamp}','{$this->from_id}',";
			if($this->to_id === NULL)
			{
				$sql .= "NULL );";
			}
			else
			{
				$sql .= "'{$this->to_id}' )";
			}
		}
		elseif($this->message === null)
		{
			$sql = "INSERT INTO ". static::$table_name;
			$sql .= " (c_id,message,image,is_read,timestamp,from_id,to_id) ";
			$sql .= " VALUES ";
			$sql .=  "( '{$this->c_id}',NULL,'{$this->image}','0','{$this->timestamp}','{$this->from_id}',";
			if($this->to_id === NULL)
			{
				$sql .= "NULL );";
			}
			else
			{
				$sql .= "'{$this->to_id}' )";
			}
		}
		else
		{
			$sql = "INSERT INTO ". static::$table_name;
			$sql .= " (c_id,message,image,is_read,timestamp,from_id,to_id) ";
			$sql .= " VALUES ";
			$sql .=  "( '{$this->c_id}','{$this->message}','{$this->image}','0','{$this->timestamp}','{$this->from_id}',";
			if($this->to_id === NULL)
			{
				$sql .= "NULL );";
			}
			else
			{
				$sql .= "'{$this->to_id}' )";
			}
		}
		if($database->query($sql))
		{
			$this->id = $database->last_id();
			$this->change_timestamp();
			return true;
		}
		else
		{
			return false;
		}
	}

	public function change_timestamp()
	{
		global $database;
		$timestamp = strftime("%Y-%m-%d %H:%M:%S",time());
		$sql = "UPDATE active_chats SET timestamp = '{$timestamp}' WHERE id = '{$this->c_id}' LIMIT 1";

		if($database->query($sql))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function update()
	{
		global $database;
		$attributes = $this->escaped_attributes();
		$attribute_pairs = array();
		foreach($attributes as $key => $value)
		{
			$attribute_pairs[] = "{$key}='{$value}'"; 
		}
		$sql = "UPDATE " . static::$table_name . " SET ";
		$sql .= join(", ", $attribute_pairs);
		$sql .= " WHERE id=". $database->escape_value($this->id);

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
		$sql = "DELETE FROM " . static::$table_name . " WHERE id = " . $database->escape_value($this->id) . "LIMIT 1";
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

	public function save()
	{
		if(!isset($this->id))
		{
			return $this->create();
		}
		else
		{
			return $this->update();
		}
	}

}

$msg = new Message();

?>