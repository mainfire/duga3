<?php
#code originally written by Greg Poole (m4dm4n@gmail.com)
define('MAX_INTEGER_LENGTH', 12);
class bdecode
{
	var	$data, $pointer = 0, $data_length = null;
	function bdecode($filename = null)
	{
		if(is_null($filename))
		{
			return;
		}
		if(($data = @file_get_contents($filename)) === false)
		{
			trigger_error("Could not create bdecode for {$filename}: failed to read file", E_USER_WARNING);
			return;
		}
		$this->set_data($data);
	}
	function set_data($data)
	{
		$this->data_length = strlen($data);
		$this->data = $data;
	}
	function read_next()
	{
		if(is_null($this->data_length))
		{
			$this->data_length = strlen($this->data);
		}
		while($this->pointer < $this->data_length)
		{
			switch($this->data[$this->pointer++])
			{
				case 'l':
					return $this->read_list();
				case 'd':
					return $this->read_dictionary();
				case 'i':
					return $this->next_integer();
				default:
					$this->pointer--;
					return $this->next_string();
			}
		}
	}
	function read_dictionary()
	{
		$dictionary = array();
		while($this->data[$this->pointer] != 'e')
		{
			$key = $this->next_string();
			if($key !== false)
			{
				if($key == "info")
				{
					$info_start = $this->pointer;
				}
				$dictionary[$key] = $this->read_next();
				if($key == "info")
				{
					$dictionary['info_hash'] = strtoupper(sha1(substr($this->data, $info_start, $this->pointer - $info_start)));
					unset($info_start);
				}
			}
			else
			{
				return false;
			}
		}
		$this->pointer++;
		return $dictionary;
	}
	function read_list()
	{
		$list = array();
		while($this->data[$this->pointer] != 'e')
		{
			$next = $this->read_next();
			if($next === false)
			{
				return false;
			}
			$list[] = $next;
		}
		$this->pointer++;
		return $list;
	}
	function next_string()
	{
		$colon = strpos($this->data, ":", $this->pointer);
		if($colon === false || ($colon - $this->pointer) > MAX_INTEGER_LENGTH)
		{
			return false;
		}
		$length = substr($this->data, $this->pointer, $colon - $this->pointer);
		$this->pointer = $colon + 1;
		$str = substr($this->data, $this->pointer, $length);
		$this->pointer += $length;
		return $str;
	}
	function next_integer()
	{
		$end = strpos($this->data, "e", $this->pointer);	
		if($end === false || ($end - $this->pointer) > MAX_INTEGER_LENGTH)
		{
			return false;
		}
		$int = intval(substr($this->data, $this->pointer, $end - $this->pointer));
		$this->pointer = $end + 1;
		return $int;
	}
}
?>
