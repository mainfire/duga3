<?php
#becode function originally by whitsoft, converted into class format
#released into the public domain
class bencode
{
	var $data;
	function bencode($filename = null)
	{
		if(is_null($filename))
		{
			return;
		}
		if(($data = @file_get_contents($filename)) === false)
		{
			trigger_error("Could not create bencode class for {$filename}: failed to read file", E_USER_WARNING);
			return;
		}
		$this->set_data(unserialize($data));
	}
	function set_data($var)
	{
		if (is_int($var))
		{
			return 'i'.$var.'e';
		}
		elseif (is_array($var))
		{
			if (count($var) == 0)
			{
				return 'de';
			}
			else
			{
				$assoc = false;
				foreach ($var as $key => $val)
				{
					if (!is_int($key))
					{
						$assoc = true;
						break;
					}
				}
				if ($assoc)
				{
					ksort($var, SORT_REGULAR);
					$ret = 'd';
					foreach ($var as $key => $val)
					{
						$ret .= $this->set_data($key).$this->set_data($val);
					}
					return $ret.'e';
				}
				else
				{
					$ret = 'l';
					foreach ($var as $val)
					{
						$ret .= $this->set_data($val);
					}
					return $ret.'e';
				}
			}
		}
		else
		{
			return strlen($var).':'.$var;
		}
	}
}
?>