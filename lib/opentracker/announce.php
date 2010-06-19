<?php
require_once 'config.php';
require_once 'functions.php';
$compact = (isset($_GET['compact'])) ? 1 : 0;
$nopeerid = (isset($_GET['no_peer_id'])) ? 1 : 0;
$downloaded = (isset($_GET['downloaded'])) ? rtrim(addslashes(strip_tags($_GET['downloaded']))) : null;
$event = (isset($_GET['event'])) ? rtrim(addslashes(strip_tags($_GET['event']))) : null;
$infohash = (isset($_GET['info_hash'])) ? rtrim(strip_tags($_GET['info_hash'])) : null;
#$ip = (isset($_GET['ipv4'])) ? rtrim(strip_tags($_GET['ipv4'])) : null;
$ipv6 = (isset($_GET['ipv6'])) ? rtrim(strip_tags($_GET['ipv6'])) : null;
$realip = ipv4check($_SERVER['REMOTE_ADDR']);
$left = (isset($_GET['left'])) ? rtrim(addslashes(strip_tags($_GET['left']))) : null;
$numwant = (isset($_GET['numwant'])) ? rtrim(addslashes(strip_tags($_GET['numwant']))) : ANNOUNCE_RETURN;
$peerid = (isset($_GET['peer_id'])) ? rtrim(addslashes(strip_tags($_GET['peer_id']))) : null;
$port = (isset($_GET['port'])) ? rtrim(addslashes(strip_tags($_GET['port']))) : null;
$uploaded = (isset($_GET['uploaded'])) ? rtrim(addslashes(strip_tags($_GET['uploaded']))) : null;
$timestamp = time();
header("Content-Type: text/plain");
try
{
	if (is_null($infohash) || is_null($port) || !is_numeric($port) || is_null($peerid) || is_null($uploaded) || !is_numeric($uploaded) || is_null($downloaded) || !is_numeric($downloaded) || is_null($left) || !is_numeric($left) || (!is_null($event) && ($event != "started") && ($event != "completed") && ($event != "stopped")))
	{
		errorexit("invalid request");
	}
	$sha1infohash = strtoupper(bin2hex($infohash));
	if (is_null($event) && $left > 0)
	{
		$event = "checked";
	}
	elseif (is_null($event) && $left == 0)
	{
		$event = "seeding";
	}
	if (LISTTYPE == "blacklist")
	{
		announce_list($sha1infohash,1);
	}
	elseif (LISTTYPE == "whitelist")
	{
		announce_list($sha1infohash,2);
	}
	if (!is_null($numwant) && !is_int($numwant))
	{
		$numwant = ANNOUNCE_RETURN;
	}
	if (is_int($numwant) && $numwant >= 250)
	{
		$numwant = 250;
	}
	if (ANNOUNCE_TYPE == 0)
	{
		if ($compact == 0 && $nopeerid == 0)
		{
			errorexit("standard announces not allowed! use either no_peer_id or compact!");
		}
	}
	elseif (ANNOUNCE_TYPE == 2)
	{
		if ($compact == 0)
		{
			errorexit('tracker requires compact announce');
		}
	}
	$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
	if (!$db->query("select * from announce") || !$db->query("select * from history"))
	{
		$db->query($announce);
		$db->query($history);
	}
	$db->query("delete from announce where expire < $timestamp");
	$db->query("delete from history where expire < $timestamp");
	$ratio = (ANNOUNCE_INTERVAL*60) + (ANNOUNCE_EXPIRE*60);
	if (!is_null($event) && ($event == 'stopped'))
	{
		$expire = time() - $ratio - 5;
	}
	else
	{
		$expire = time() + $ratio;
	}
	$newip = $db->query("select * from announce where ip = '$realip' and hash = '$sha1infohash' limit 1");
	if ($newip->num_rows > 0)
	{
		if (ANNOUNCE_ENFORCE == 1 && $event != "completed" && $event != "started" && $event != "stopped")
		{
			while ($line = $newip->fetch_object())
			{
				if ($line->event == "started" || $event == "checked")
				{
					$oldstamp = time() - ($line->timestamp-60);
					if ($oldstamp <= (ANNOUNCE_INTERVAL*60))
					{
						errorexit('enforcing announce interval, ignoring request!');
					}
				}
			}
		}
		$update = $db->query("update announce set downloaded = '$downloaded', event = '$event', expire = '$expire', port = '$port', remain = '$left', timestamp = '$timestamp', uploaded = '$uploaded' where hash = '$sha1infohash' and ip = '$realip' limit 1");
		if (!$update)
		{
			errorexit('could not update the database!');
		}
	}
	else
	{
		$insert = $db->query("insert into announce (downloaded,event,expire,hash,ip,ipv6,remain,peerid,uploaded,timestamp) values ($downloaded,'$event',$expire,'$sha1infohash','$realip','$ipv6',$left,'$peerid',$uploaded,$timestamp)");
		if (!$insert)
		{
			errorexit('could not insert into database!');
		}
	}
	$newhash = $db->query("select * from history where hash = '$sha1infohash' limit 1");
	if ($newhash->num_rows > 0)
	{
		if ($event == "started" && $left == 0)
		{
			$update1 = $db->query("update history set complete = complete + 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
		}
		elseif ($event == "completed" && $left == 0)
		{
			$update1 = $db->query("update history set complete = complete + 1, downloaded = downloaded + 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
		}
		elseif ($event == "stopped" && $left == 0)
		{
			$update1 = $db->query("update history set complete = complete - 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
		}
		elseif ($event == "started" && $left > 0)
		{
			$update1 = $db->query("update history set incomplete = incomplete + 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
		}
		elseif ($event == "stopped" && $left > 0)
		{
			$update1 = $db->query("update history set incomplete = incomplete - 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
		}
		if (!$update1)
		{
			errorexit('could not update the database!');
		}
	}
	else
	{
		if ($event == "started" && $left == 0)
		{
			$insert = $db->query("insert into history (expire,hash,complete,timestamp) values ($expire,'$sha1infohash',1,$timestamp)");
		}
		elseif ($event == "completed" && $left == 0)
		{
			$insert = $db->query("insert into history (expire,hash,complete,downloaded,timestamp) values ($expire,'$sha1infohash',1,1,$timestamp)");
		}
		elseif ($event == "stopped" && $left == 0 || $left > 0)
		{
			$insert = $db->query("insert into history (expire,hash,timestamp) values ($expire,'$sha1infohash',$timestamp)");
		}
		elseif ($event == "started" && $left > 0)
		{
			$insert = $db->query("insert into history (expire,hash,incomplete,timestamp) values ($expire,'$sha1infohash',1,$timestamp)");
		}
		if (!$insert)
		{
			errorexit('could not insert into database!');
		}
	}
	$peersquery = "select * from announce where hash = '$sha1infohash' and expire > $timestamp order by rand() limit $numwant";
	if ($result = $db->query($peersquery))
	{
		#if ($compact == 1 && is_null($ipv6)) #eh, lets not break things further shall we?
		if ($compact == 1)
		{
			$peers = null;
			while ($line = $result->fetch_object())
			{
				$peers .= pack('Nn',$line->ip,$line->port);
			}
		}
		elseif ($nopeerid == 1)
		{
			$peers = array();
			while ($line = $result->fetch_object())
			{
				#if (!is_null($ipv6))
				#{
					#if (!is_null$line->ipv6))
					#{
						#$peers[] = array('ip'=>$line->ipv6,'port'=>(int)$line->port);
					#}
				#}
				#else
				#{
					$peers[] = array('ip'=>$line->ip,'port'=>(int)$line->port);
				#}
			}
		}
		else
		{
			$peers = array();
			while ($line = $result->fetch_object())
			{
				#if (!is_null($ipv6))
				#{
					#if (!is_null$line->ipv6))
					#{
						#$peers[] = array('ip'=>$line->ipv6,'port'=>(int)$line->port,'peer id'=>stripslashes($line->peerid));
					#}
				#}
				#else
				#{
					$peers[] = array('ip'=>$line->ip,'port'=>(int)$line->port,'peer id'=>stripslashes($line->peerid));
				#}
			}
		}
		#if (!is_null($ipv6))
		#{
			#die(bencode(array('interval'=>(int)(ANNOUNCE_INTERVAL*60),'peers6'=>$peers)));
		#}
		#else
		#{
			die(bencode(array('interval'=>(int)(ANNOUNCE_INTERVAL*60),'peers'=>$peers)));
		#}
	}
	$db->query("optimize table announce");
	$db->query("optimize table history");
	$db->close();
}
catch(Exception $e)
{
	errorexit("tracker is down!");
}
?>
