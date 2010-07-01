<?php
#licensed under the new bsd license
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
	`port6` int(5) NOT NULL,
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
			errorexit("invalid torrent: blacklisted from tracker");
		}
	}
	elseif ($type == 2)
	{
		if (!in_array($hash,$infohashes))
		{
			errorexit("invalid torrent: not whitelisted on tracker");
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

#this will format the bytes displayed in the footer
function format_bytes($bytes)
{
	$precision = 2;
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision).' '.$units[$pow];
}

#convert our sha1 infohash back intos binary format
#licensed under the php license, since this was found on php.net
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
	#this is far from completed, we NEED to do some sort of endpoint check at least once. the problem with this statement is that theres about a million ways you could do this:
	# - do we return a special flag in the announce that asks the peer to send a request (on the opposite procotol from this request) with a special event specified to verify the endpoint?
	# - do we add a flag to the database that says "this is a valid checked ipv6 endpoint" after we check the peer on their initial announce? (note: this could lead to numerous potential DDoS vulnerabilities)
	# - do we do nothing (like now) and just assume the client is a valid endpoint and has all NAT and firewall settings configured the right way
	if ($type == 4)
	{
		$explode = explode(':',$ip); #we only want the ip to check against
		if (count($explode) > 1)
		{
			$ipv4 = $explode[0];
			$port = $explode[1];
		}
		else
		{
			$ipv4 = $explode[0];
			$port = null;
		}
		if (!filter_var($ipv4,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
		{
			errorexit("invalid request (bad ip)");
		}
		else
		{
			return $ipv4.':'.$port;
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