<?php 

require_once("init.php");

class Group
{
	static protected $table_name = "groups";
	static protected $extra_table = "group_info"; 

	public $users = array();
	public $id;
	public $group_name;
	public $group_picture;
	public $num_users;
	public $timestamp;
	public $c_id;
	public $admins = array();

	protected static function find_by_sql($sql="")
	{
		global $database;
		$objects = array();
		$result_set = $database->query($sql);
		while($row = $database->fetch_assoc($result_set))
		{
			$objects[] = static::instantiate($row);
		}
		return $objects;
	}

	protected static function instantiate($row)
	{
		$object_name = get_called_class();
		$object = new $object_name;

		foreach($row as $attribute=>$value)
		{
			if($object->has_attribute($attribute))
			{
				$object->$attribute = $value;
			}
		}

		$object->users = $object->fill_in_users();
		$object->admins = $object->find_admins_for_group();

		return $object;
	}

	private function has_attribute($attribute)
	{
		$object_vars = get_object_vars($this);
		return array_key_exists($attribute,$object_vars);
	}

	private function fill_in_users()
	{
		global $database;
		$users = array();
		$sql = "SELECT * FROM " . static::$table_name . " WHERE group_id = '{$this->id}'";
		$result_set = $database->query($sql);
		if(is_object($result_set))
		{
			while($row = $database->fetch_assoc($result_set))
			{
				$user_id = $database->escape_value($row['user_id']);
				$users[$user_id] = User::find_by_id($user_id);
			}
		}
		return $users;
	}

	private function find_admins_for_group()
	{
		global $database;
		$admins = array();
		$sql = "SELECT user_id,is_admin FROM " . static::$table_name . " WHERE (group_id= '{$this->id}' AND is_admin = 1)";
		$result_set = $database->query($sql);
		if(is_object($result_set))
		{
			while($row = $database->fetch_assoc($result_set))
			{
				$admins[] = $row['user_id'];
			}
		} 
	}

	public static function find_by_id($id = 0)
	{
		global $database;
		$id = $database->escape_value($id);
		$sql = "SELECT g.group_id AS id,i.group_name,i.group_picture,i.num_users,i.timestamp,i.c_id FROM " . static::$table_name . " AS g ";
		$sql .= "INNER JOIN " . static::$extra_table . " AS i ON g.group_id = i.id WHERE g.group_id = {$id} LIMIT 1";
		$result = static::find_by_sql($sql);
		return empty($result) ? false : $result[0];
	}

	public static function find_all_groups_for_user($id = 0)
	{
		global $database;
		$sql = "SELECT g.group_id AS id,i.group_name,i.group_picture,i.num_users,i.timestamp,i.c_id FROM " . static::$table_name . " AS g ";
		$sql .= "INNER JOIN " . static::$extra_table . " AS i ON g.group_id = i.id WHERE g.user_id = {$id} ORDER BY i.timestamp DESC";
		return static::find_by_sql($sql);
	}

	public function create()
	{
		global $database;
		$timestamp = strftime("%Y-%m-%d %H:%M:%S",time());
		$sql = "INSERT INTO " . static::$extra_table . " (group_name,group_picture,num_users,timestamp) ";
		$sql .= "VALUES ('{$this->group_name}',";
		if($this->group_picture !== null)
		{
			$sql .= "'{$this->group_picture}',";
		}
		else
		{
			$sql .= "NULL,";
		}
		$sql .= "'0','{$timestamp}')";

		$database->query($sql);
		if($database->affected_rows() == 1)
		{
			$this->id = $database->last_id();
			$sql = "INSERT INTO active_chats (from_id,to_id,timestamp) VALUES ('{$this->id}',NULL,'{$timestamp}')";
			$database->query($sql);
			if($database->affected_rows() == 1)
			{
				$this->c_id = $database->last_id();
				$query = "UPDATE " . static::$extra_table . " SET c_id = '{$this->c_id}' WHERE id = '{$this->id}' LIMIT 1";
				$database->query($query);
				if($database->affected_rows() == 1)
				{
					return true;
				}
			}
		}
		return false;
	}

	public function update_group_info()
	{
		global $database;
		$sql = "UPDATE " . static::$extra_table . " SET ";
		$sql .= " group_name = '{$this->group_name}',";
		if($this->group_picture !== NULL || trim($this->group_picture) !== "")
		{
			$sql .= " group_picture = '{$this->group_picture}',";
		}
		else
		{
			$sql .= " group_picture = NULL,";
		}
		$timestamp = strftime("%Y-%m-%d %H:%M:%S",time());
		$sql .= " timestamp = '{$timestamp}'";
		$sql .= " WHERE id = {$this->id}";
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

	public function add_group_member($user_id = 0)
	{
		global $database;
		$flag = 0;
		foreach($this->users as $u_id => $user)
		{
			if($u_id === $user_id)
			{
				$flag = 1;
			}
		}
		if($flag !== 1)
		{
			$sql = "SELECT first_name FROM users WHERE id = {$user_id} LIMIT 1";
			$result = $database->query($sql);
			if($result->num_rows != 1)
			{
				return false;
			}
			$sql = "INSERT INTO " .static::$table_name . " (group_id,user_id) VALUES ('{$this->id}','{$user_id}')";
			$database->query($sql);
			if($database->affected_rows() == 1)
			{
				$num_users = $this->num_users;
				$num_users++;
				$database->escape_value($num_users);
				$query = "UPDATE " . static::$extra_table . " SET num_users = {$num_users} WHERE id={$this->id} LIMIT 1";
				$database->query($query);
				if($database->affected_rows() == 1)
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
	}

	public function make_admin($user_id = 0)
	{
		global $database;
		$sql = "UPDATE " . static::$table_name . " SET is_admin = 1 WHERE (group_id = {$this->id} AND user_id = {$user_id}) LIMIT 1";
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

	public function remove_admin($user_id = 0)
	{
		global $database;
		$sql =  "UPDATE " . static::$table_name . " SET is_admin = 0 WHERE (group_id = {$this->id} AND user_id = {$user_id}) LIMIT 1";
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

	public function remove_member($user_id = 0)
	{
		global $database;
		if(Group::is_admin($this->id,$user_id))
		{
			$sql = "UPDATE " . static::$table_name . " SET is_admin = 1 WHERE (user_id != 1 AND group_id = {$this->id}) LIMIT 1";
			$database->query($sql);
		}
		$sql = "DELETE FROM " .static::$table_name . " WHERE (group_id = {$this->id} AND user_id = {$user_id}) LIMIT 1";
		$database->query($sql);
		if($database->affected_rows() == 1)
		{
			$num_users = $this->num_users;
			$num_users--;
			$database->escape_value($num_users);
			$query = "UPDATE " . static::$extra_table . " SET num_users = {$num_users} WHERE id={$this->id} LIMIT 1";
			$database->query($query);
			if($database->affected_rows() == 1)
			{
				if($num_users === 0)
				{
					$this->delete();
				}
				else
				{
					return true;
				}
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

	public function delete()
	{
		global $database;
		$sql = "DELETE FROM " . static::$extra_table . " WHERE id = '{$this->id}' LIMIT 1";
		$database->query($sql);
		if($database->affected_rows() == 1)
		{
			$sql = "DELETE FROM " . static::$table_name . " WHERE group_id = '{$this->id}'";
			$database->query($sql);
			if($database->affected_rows() >= 0)
			{
				$sql = "DELETE FROM active_chats WHERE id = {$this->c_id} LIMIT 1";
				$database->query($sql);
				if($database->affected_rows() == 1)
				{
					$sql = "DELETE FROM messages WHERE c_id = {$this->c_id}";
					$database->query($sql);
					if($database->affected_rows() >= 0)
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

	public static function find_c_id_for_group($id = 0)
	{
		global $database;
		$sql = "SELECT c_id FROM " . static::$extra_table . " WHERE id = {$id} LIMIT 1";
		$result = $database->query($sql);
		if($result->num_rows == 1)
		{
			$row = $database->fetch_assoc($result);
			return $row['c_id'];
		}
		else
		{
			return null;
		}
	}

	public static function find_group_for_c_id($c_id = 0)
	{
		global $database;
		$sql = "SELECT id FROM " . static::$extra_table . " WHERE c_id = {$c_id} LIMIT 1";
		$result = $database->query($sql);
		if($result->num_rows == 1)
		{
			$row = $database->fetch_assoc($result);
			$id = $database->escape_value($row['id']);
			return static::find_by_id($id);
		}
		else
		{
			return NULL;
		}
	}


	public static function find_group_id_for_c_id($c_id = 0)
	{
		global $database;
		$sql = "SELECT id FROM " . static::$extra_table . " WHERE c_id = {$c_id} LIMIT 1";
		$result = $database->query($sql);
		if($result->num_rows == 1)
		{
			$row = $database->fetch_assoc($result);
			$id = $database->escape_value($row['id']);
			return $id;
		}
		else
		{
			return null;
		}
	}

	public static function find_all_users($id = 0)
	{
		if($id == 0)
		{
			return null;
		}
		global $database;
		$users = array();
		$sql = "SELECT * FROM " . static::$table_name . " WHERE group_id = '{$id}'";
		$result_set = $database->query($sql);
		if(is_object($result_set))
		{
			while($row = $database->fetch_assoc($result_set))
			{
				$user_id = $database->escape_value($row['user_id']);
				$users[$user_id] = User::find_by_id($user_id);
			}
		}
		return $users;
	}

	public static function find_by_group_name($group_name = "")
	{
		global $database;
		$database->escape_value($group_name);
		if(trim($group_name) === "")
		{
			return false;
		}
		$sql = "SELECT * FROM " . static::$extra_table . " WHERE group_name LIKE '%{$group_name}%'";
		return static::find_by_sql($sql);
	}

	public static function verify_user($user_id = 0,$group_id = 0)
	{
		global $database;
		$sql = "SELECT group_id FROM " . static::$table_name . " WHERE user_id = '{$user_id}'";
		$result = $database->query($sql);
		if(!empty($result))
		{
			while($row = $database->fetch_assoc($result))
			{
				if($row['group_id'] === $group_id )
				{
					return true;
				}
			}
		}
		return false;
	}

	public static function change_timestamp($group_id = 0)
	{
		if($group_id == 0)
		{
			return false;
		}
		global $database;
		$timestamp = strftime("%Y-%m-%d %H:%M:%S",time());
		$sql = "UPDATE " . static::$extra_table . " SET timestamp = '{$timestamp}' WHERE id={$group_id} LIMIT 1";
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

	public static function is_admin($group_id = 0,$user_id = 0)
	{
		global $database;
		$group_id = $database->escape_value($group_id);
		$user_id = $database->escape_value($user_id);
		$sql = "SELECT is_admin FROM " . static::$table_name . " WHERE (group_id = '{$group_id}' AND user_id = '{$user_id}') LIMIT 1";
		$result = $database->query($sql);
		$row = $database->fetch_assoc($result);
		if($row['is_admin'] == 1)
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