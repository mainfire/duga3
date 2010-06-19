<?php
#our announce mysql table, do not modify this!
$announce = "
CREATE TABLE IF NOT EXISTS `announce`
(
	`id` int(30) NOT NULL AUTO_INCREMENT,
	`hash` char(40) NOT NULL,
	`ip` char(16) NOT NULL,
	`ipv6` char(50) NOT NULL,
	`port` int(5) NOT NULL,
	`peerid` char(20) binary NOT NULL,
	`event` char(15) NOT NULL,
	`uploaded` bigint(20) unsigned NOT NULL default '0',
	`downloaded` bigint(20) unsigned NOT NULL default '0',
	`remain` bigint(20) unsigned NOT NULL default '0',
	`timestamp` int(14) NOT NULL,
	`expire` int(14) NOT NULL,
	FULLTEXT hash(hash),
	FULLTEXT ip(ip),
	FULLTEXT ipv6(ipv6),
	PRIMARY KEY (`id`)
) ENGINE=".MYSQLENGINE." AUTO_INCREMENT=1 DEFAULT CHARSET=".MYSQLCHARSET.";
";

#our history mysql table, do not modify this!
$history = "
CREATE TABLE IF NOT EXISTS `history`
(
	`id` int(30) NOT NULL AUTO_INCREMENT,
	`hash` char(40) NOT NULL,
	`complete` int(8) NOT NULL default '0',
	`incomplete` int(8) NOT NULL default '0',
	`downloaded` int(8) NOT NULL default '0',
	`timestamp` int(14) NOT NULL,
	`expire` int(14) NOT NULL,
	FULLTEXT hash(hash),
	PRIMARY KEY (`id`)
) ENGINE=".MYSQLENGINE." AUTO_INCREMENT=1 DEFAULT CHARSET=".MYSQLCHARSET.";
";

#determines what to do with (or without) our list
function announce_list($hash,$type)
{
	if (!file_exists(LISTLOCATION) || filesize(LISTLOCATION) == 0)
	{
		file_put_contents(LISTLOCATION,null,LOCK_EX);
		$infohashes = array();
	}
	else
	{
		$list = file_get_contents(LISTLOCATION);
		$infohashes = explode("\n",$list);
	}
	if ($type == 1)
	{
		if (in_array($hash,$infohashes))
		{
			errorexit("invalid / blacklisted torrent");
		}
	}
	elseif ($type == 2)
	{
		if (!in_array($hash,$infohashes))
		{
			errorexit("invalid / non-whitelisted torrent");
		}
	}
}

#bencode function by whitsoft
function bencode($var)
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
					$ret .= bencode($key).bencode($val);
				}
				return $ret.'e';
			}
			else
			{
				$ret = 'l';
				foreach ($var as $val)
				{
					$ret .= bencode($val);
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

#bencode an error response (tracker only)
function errorexit($reason)
{
	die(bencode(array("failure reason" => $reason)));
}

#convert our sha1 infohash back intos binary format
function hex2bin($str)
{
	$bin = "";
	$i = 0;
	do
	{
		$bin .= chr(hexdec($str{$i}.$str{($i + 1)}));
		$i += 2;
	}
	while ($i < strlen($str));
	return $bin;
}

#validate our ip address
function ipcheck($ip)
{
	$ipv4 = explode('.',$ip);
	$ipv6 = explode(':',$ip);
	if (count($ipv4) > 0)
	{
		if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
		{
			errorexit("invalid request (bad ip)");
		}
		else
		{
			return "4";
		}
	}
	elseif (count($ipv6) > 0)
	{
		if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
		{
			errorexit("invalid request (bad ip)");
		}
		else
		{
			return "6";
		}
	}
	else
	{
		errorexit("invalid request (bad ip)");
	}
}

#warning message (unused so far, only suppoted by "vuze")
function warningexit($reason)
{
	die(bencode(array("warning message" => $reason)));
}
?>