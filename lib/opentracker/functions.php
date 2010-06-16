<?php
#our announce mysql table, do not modify this!
$announce = "
CREATE TABLE IF NOT EXISTS `announce`
(
	`id` int(30) NOT NULL AUTO_INCREMENT,
	`hash` text NOT NULL,
	`ip` text NOT NULL,
	`port` int(5) NOT NULL,
	`peerid` char(20) binary NOT NULL,
	`event` text NOT NULL,
	`uploaded` bigint(20) unsigned NOT NULL default '0',
	`downloaded` bigint(20) unsigned NOT NULL default '0',
	`remain` bigint(20) unsigned NOT NULL default '0',
	`timestamp` text NOT NULL,
	`expire` text NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=".MYSQLENGINE." AUTO_INCREMENT=1 DEFAULT CHARSET=".MYSQLCHARSET.";
";

#bencode function by flippy
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

#check to see if our folders are chmodded properly
function check_permissions($directory)
{
	if (strtoupper(substr(PHP_OS,0,3)) != 'WIN')
	{
		if (substr(sprintf('%o',fileperms($directory)),-4) != '0777')
		{
			die('Looks like <em>'.$directory.'</em> needs its <code>chmod</code> changed to <strong><u>0777</u></strong>.<br />In order for Duga-3 to function properly, we need to be able to read and write to both the <strong>LIBROOT</strong> and <strong>WEBROOT</strong> folders.<br /><code>chmod</code> ONLY the folders themselves, <strong><u>DO NOT SET ANY DUGA-3 FILES TO 0777</u></strong>.<br /><br /><a href="'.$_SERVER["REQUEST_URI"].'">Retry</a>...');
		}
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
	$newip = ip2long($ip);
	$newip2 = ip2long(gethostbyname($ip));
	if ($newip == false || $newip == -1)
	{
		if ($newip2 == false || $newip2 == -1)
		{
			errorexit("invalid request (bad ip)");
		}
	}
	else
	{
		return $ip;
	}
}

#warning message (unused so far, only suppoted by "vuze")
function warningexit($reason)
{
	die(bencode(array("warning message" => $reason)));
}
?>