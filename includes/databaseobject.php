<?php

class DatabaseObject
{
	static protected $table_name;
	static protected $db_fields = array();

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

	public function find_all()
	{
		$sql = "SELECT * FROM " . static::$table_name ;
		return static::find_by_sql($sql);
	}

	public static function find_by_id($id = 0)
	{
		global $database;
		$sql = "SELECT * FROM " . static::$table_name . " WHERE id = " . $database->escape_value($id) . " LIMIT 1";
		$result_array = static::find_by_sql($sql);
		return empty($result_array) ? false : $result_array[0];  
	}

	public function create()
	{
		global $database;
		$attributes = $this->escaped_attributes();
		$sql = "INSERT INTO ". static::$table_name. " ( ";
		$sql .= join(",",array_keys($attributes));
		$sql .= " ) VALUES ( '";
		$sql .= join("', '", array_values($attributes));
		$sql .= "')";
		
		if($database->query($sql))
		{
			$this->id = $database->last_id();
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

	public static function count_all()
	{
		global $database;
		$sql = "SELECT COUNT(*) FROM " . static::$table_name;
		$result_array = $database->query($sql);
		$result = $database->fetch_array($result_array);
		return $result[0];
	}

	protected function escaped_attributes()
	{
		global $database;
		$attributes = array();
		foreach(static::$db_fields as $field)
		{
			if(property_exists($this, $field))
			{
				$attributes[$field] = $database->escape_value($this->$field);
			}
		}
		return $attributes;
	}


}

?>