<?php
require_once 'config.php';
require_once 'functions.php';
$compact = (isset($_GET['compact'])) ? 1 : 0;
$nopeerid = (isset($_GET['no_peer_id'])) ? 1 : 0;
$downloaded = (isset($_GET['downloaded'])) ? rtrim(addslashes(strip_tags($_GET['downloaded']))) : null;
$event = (isset($_GET['event'])) ? rtrim(addslashes(strip_tags($_GET['event']))) : null;
$infohash = (isset($_GET['info_hash'])) ? rtrim(strip_tags($_GET['info_hash'])) : null;
$ip = (isset($_GET['ip'])) ? ipcheck(rtrim(strip_tags($_GET['ip']))) : null;
$realip = ipcheck($_SERVER['REMOTE_ADDR']);
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
	if (is_null($event))
	{
		$event = "checkin";
	}
	$sha1infohash = strtoupper(bin2hex($infohash));
	if (LISTTYPE == "blacklist")
	{
		if (in_array($sha1infohash,$infohash_list))
		{
			errorexit("invalid / blacklisted torrent");
		}
	}
	elseif (LISTTYPE == "whitelist")
	{
		if (!in_array($sha1infohash,$infohash_list))
		{
			errorexit("invalid / non-whitelisted torrent");
		}
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
	if ($db->query("select * from announce") === false)
	{
		$db->query($announce);
	}
	if (KEEP_HISTORY == 0)
	{
		$db->query("delete from announce where expire < $timestamp");
	}
	$ratio = (ANNOUNCE_INTERVAL*60) + (ANNOUNCE_EXPIRE*60);
	if (!is_null($event) && ($event == 'stopped'))
	{
		$expire = time() - $ratio - 5;
	}
	else
	{
		$expire = time() + $ratio;
	}
	$newbie = $db->query("select * from announce where ip = '$realip' and hash = '$sha1infohash' limit 1");
	if ($newbie->num_rows > 0)
	{
		if (ANNOUNCE_ENFORCE == 1 && $event != "completed" && $event != "started" && $event != "stopped")
		{
			while ($line = $newbie->fetch_object())
			{
				if ($line->event == "started" || $event == "checkin")
				{
					$oldstamp = time() - ($line->timestamp-60);
					if ($oldstamp <= (ANNOUNCE_INTERVAL*60))
					{
						errorexit('enforcing announce interval, ignoring request!');
					}
				}
			}
		}
		$update = $db->query("update announce set downloaded = '$downloaded', expire = '$expire', event = '$event', port = '$port', remain = '$left', timestamp = '$timestamp', uploaded = '$uploaded' where hash = '$sha1infohash' and ip = '$realip' limit 1");
		if (!$update)
		{
			errorexit('could not update the database!');
		}
	}
	else
	{
		$insert = $db->query("insert into announce (downloaded,event,expire,hash,ip,remain,peerid,uploaded,timestamp) values ($downloaded,'$event',$expire,'$sha1infohash','$realip',$left,'$peerid',$uploaded,$timestamp)");
		if (!$insert)
		{
			errorexit('could not insert into database!');
		}
	}
	$peers = "select * from announce where hash = '$sha1infohash' and expire > $timestamp order by rand() limit $numwant";
	if ($result = $db->query($peers))
	{
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
				$peers[] = array('ip'=>$line->ip,'port'=>(int)$line->port);
			}
		}
		else
		{
			$peers = array();
			while ($line = $result->fetch_object())
			{
				$peers[] = array('ip'=>$line->ip,'port'=>(int)$line->port,'peer id'=>stripslashes($line->peerid));
			}
		}
		die(bencode(array('interval'=>(int)(ANNOUNCE_INTERVAL*60),'peers'=>$peers)));
	}
	$db->query("optimize table announce");
	$db->close();
}
catch(Exception $e)
{
	errorexit("tracker is down!");
}
?>
