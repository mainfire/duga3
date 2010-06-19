<?php
require_once 'bencode.php';
#our announce mysql table, do not modify this!
$announce = "
CREATE TABLE IF NOT EXISTS `announce`
(
	`id` int(30) NOT NULL AUTO_INCREMENT,
	`hash` char(40) NOT NULL,
	`ip` char(16) NOT NULL,
	`ipv6` char(40) NOT NULL,
	`port` int(5) NOT NULL,
	`peerid` char(40) binary NOT NULL,
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

#bencode an error response (tracker only)
function errorexit($reason)
{
	$error = new bencode();
	$error = $error->set_data(array("failure reason"=>$reason));
	die($error);
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
function ipcheck($ip,$type)
{
	if ($type == 4)
	{
		if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
		{
			errorexit("invalid request (bad ip)");
		}
		else
		{
			return $ip;
		}
	}
	elseif ($type == 6)
	{
		if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
		{
			errorexit("invalid request (bad ip6)");
		}
		else
		{
			return $ip;
		}
	}
}

#determine whether this client is making a request from an ipv4 or ipv6 address
function ipdetermine($ip)
{
	if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
	{
		if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
		{
			errorexit("invalid request (bad ip)");
		}
		else
		{
			return $ip.';6';
		}
	}
	else
	{
		return $ip.';4';
	}
}
?>