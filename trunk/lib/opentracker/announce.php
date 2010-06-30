<?php
require_once 'config.php';
require_once 'functions.php';
$compact = (isset($_GET['compact'])) ? rtrim(strip_tags($_GET['compact'])) : 0;
$nopeerid = (isset($_GET['no_peer_id'])) ? rtrim(strip_tags($_GET['no_peer_id'])) : 0;
$downloaded = (isset($_GET['downloaded'])) ? rtrim(addslashes(strip_tags($_GET['downloaded']))) : null;
$event = (isset($_GET['event'])) ? rtrim(addslashes(strip_tags($_GET['event']))) : null;
$infohash = (isset($_GET['info_hash'])) ? rtrim(strip_tags($_GET['info_hash'])) : null;
$ipv4 = (isset($_GET['ipv4'])) ? ipcheck(rtrim(strip_tags($_GET['ipv4'])),4) : null;
$ipv6 = (isset($_GET['ipv6'])) ? ipcheck(rtrim(strip_tags($_GET['ipv6'])),6) : null;
$requestip = explode(';',ipdetermine($_SERVER['REMOTE_ADDR']));
$left = (isset($_GET['left'])) ? rtrim(addslashes(strip_tags($_GET['left']))) : null;
$numwant = (isset($_GET['numwant'])) ? rtrim(addslashes(strip_tags($_GET['numwant']))) : ANNOUNCE_RETURN;
$peerid = (isset($_GET['peer_id'])) ? rtrim(addslashes(strip_tags($_GET['peer_id']))) : null;
$port = (isset($_GET['port'])) ? rtrim(addslashes(strip_tags($_GET['port']))) : null;
$port6 = $port;
$uploaded = (isset($_GET['uploaded'])) ? rtrim(addslashes(strip_tags($_GET['uploaded']))) : null;
$timestamp = time();
header("Content-Type: text/plain");
try
{
	if (is_null($infohash) || is_null($port) || !is_numeric($port) || is_null($peerid) || is_null($uploaded) || !is_numeric($uploaded) || is_null($downloaded) || !is_numeric($downloaded) || is_null($left) || !is_numeric($left) || (!is_null($event) && ($event != "started") && ($event != "completed") && ($event != "stopped")))
	{
		throw new Exception("invalid request");
	}
	$client = explode('-',$peerid);
	if (count($banned_clients_wildcards) > 0)
	{
		$wildcard_client = preg_replace('/[[:^digit:]]/',"",$client[0]);
		foreach ($banned_clients_wildcards as $banned_client => $return_client)
		{
			if (preg_match("/$wildcard_client/i",$banned_client))
			{
				throw new Exception("this client is banned from the tracker (".$return_client.")!");
			}
		}
	}
	if (!array_key_exists($client[1],$banned_clients_versions))
	{
		$sha1infohash = strtoupper(bin2hex($infohash));
		if ($requestip[1] == 4) #here we need to make sure we only insert ipv4 into the ip row, ipv6 into the ipv6 table
		{
			$realip = $requestip[0];
			$iptype = 4;
		}
		elseif ($requestip[1] == 6)
		{
			#we need to make sure that both the ip and port columns in mysql contain only ipv4 values, ipv6 and port6 are for ipv6 related things
			if (!is_null($ipv4))
			{
				$explodeipv4 = explode(':',$ipv4);
				if (!is_null($explodeipv4[1]))
				{
					$realip = $explodeipv4[0];
					$port = $explodeipv4[1];
				}
				else
				{
					$realip = $explodeipv4[0];
				}
			}
			else
			{
				$realip = $explodeipv4[0];
			}
			$iptype = 6;
		}
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
				throw new Exception("standard announces not allowed! use either no_peer_id or compact!");
			}
		}
		elseif (ANNOUNCE_TYPE == 2)
		{
			if ($compact == 0)
			{
				throw new Exception("tracker requires compact announce");
			}
		}
		$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
		if (!$db->query("select id from announce limit 1") || !$db->query("select id from history limit 1"))
		{
			$db->query($announce);
			$db->query($history);
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
							throw new Exception('enforcing announce interval, ignoring request!');
						}
					}
				}
			}
			$update = $db->query("update announce set downloaded = '$downloaded', event = '$event', expire = '$expire', port = '$port', port6 = '$port6', remain = '$left', timestamp = '$timestamp', uploaded = '$uploaded' where hash = '$sha1infohash' and ip = '$realip' limit 1");
			if (!$update)
			{
				throw new Exception('could not update the database!');
			}
		}
		else
		{
			$insert = $db->query("insert into announce (downloaded,event,expire,hash,ip,ipv6,peerid,port,port6,remain,uploaded,timestamp) values ($downloaded,'$event',$expire,'$sha1infohash','$realip','$ipv6','$peerid',$port,$port6,$left,$uploaded,$timestamp)");
			if (!$insert)
			{
				throw new Exception('could not insert into database!');
			}
		}
		$newhash = $db->query("select * from history where hash = '$sha1infohash' limit 1");
		if ($event != "checked" && $left > 0 || $event != "seeding" && $left == 0)
		{
			if ($newhash->num_rows > 0)
			{
				switch ($event)
				{
					case 'completed':
						if ($left == 0)
						{
							$update1 = $db->query("update history set complete = complete + 1, downloaded = downloaded + 1, incomplete = incomplete - 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
						}
					break;
					case 'started':
						if ($left > 0)
						{
							$update1 = $db->query("update history set incomplete = incomplete + 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
						}
						elseif ($left == 0)
						{
							$update1 = $db->query("update history set complete = complete + 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
						}
					break;
					case 'stopped':
						if ($left > 0)
						{
							$update1 = $db->query("update history set incomplete = incomplete - 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
						}
						elseif ($left == 0)
						{
							$update1 = $db->query("update history set complete = complete - 1, expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
						}
					break;
				}
				if (!$update1)
				{
					throw new Exception('could not update the database!');
				}
			}
			else
			{
				switch ($event)
				{
					case 'completed':
						if ($left == 0)
						{
							$insert1 = $db->query("insert into history (expire,hash,complete,downloaded,timestamp) values ($expire,'$sha1infohash',1,1,$timestamp)");
						}
					break;
					case 'started':
						if ($left > 0)
						{
							$insert1 = $db->query("insert into history (expire,hash,incomplete,timestamp) values ($expire,'$sha1infohash',1,$timestamp)");
						}
						elseif ($left == 0)
						{
							$insert1 = $db->query("insert into history (expire,hash,complete,timestamp) values ($expire,'$sha1infohash',1,$timestamp)");
						}
					break;
					case 'stopped':
						if ($left > 0)
						{
							$insert1 = $db->query("insert into history (expire,hash,timestamp) values ($expire,'$sha1infohash',$timestamp)");
						}
						elseif ($left == 0)
						{
							$insert1 = $db->query("insert into history (expire,hash,timestamp) values ($expire,'$sha1infohash',$timestamp)");
						}
					break;
				}
				if (!$insert1)
				{
					throw new Exception('could not insert into database!');
				}
			}
		}
		else
		{
			if ($newhash->num_rows > 0)
			{
				switch ($event)
				{
					case 'seeding':
						if ($left == 0)
						{
							$update1 = $db->query("update history set expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
						}
					break;
					case 'checked':
						if ($left > 0)
						{
							$update1 = $db->query("update history set expire = $expire where match (hash) against ('\"$sha1infohash\"' IN BOOLEAN MODE) limit 1");
						}
					break;
				}
				if (!$update1)
				{
					throw new Exception('could not update the database!');
				}
			}
			else
			{
				switch ($event)
				{
					case 'seeding':
						if ($left == 0)
						{
							$insert1 = $db->query("insert into history (expire,hash,complete,downloaded,timestamp) values ($expire,'$sha1infohash',1,1,$timestamp)");
						}
					break;
					case 'checked':
						if ($left > 0)
						{
							$insert1 = $db->query("insert into history (expire,hash,incomplete,timestamp) values ($expire,'$sha1infohash',1,$timestamp)");
						}
					break;
				}
				if (!$insert1)
				{
					throw new Exception('could not insert into database!');
				}
			}
		}
		$peersquery = "select * from announce where hash = '$sha1infohash' and expire > $timestamp order by rand() limit $numwant";
		if ($result = $db->query($peersquery))
		{
			#if ($compact == 1 && is_null($ipv6)) #eh, lets not break things further shall we?
			if ($compact == 1)
			{
				$peers = null;
				$peers6 = null; #this is here so we do not get an "undefined variable" error - sooner or later i'll try the expiermental ipv6 compact mentioned by some people
				while ($line = $result->fetch_object())
				{
					$peers .= pack('Nn',$line->ip,$line->port);
				}
			}
			elseif ($nopeerid == 1)
			{
				$peers = array();
				$peers6 = array();
				while ($line = $result->fetch_object())
				{
					if (!is_null($ipv6))
					{
						if (!is_null($line->ipv6))
						{
							$peers6[] = array('ip'=>$line->ipv6,'port'=>(int)$line->port6);
						}
					}
					$peers[] = array('ip'=>$line->ip,'port'=>(int)$line->port);
				}
			}
			else
			{
				$peers = array();
				$peers6 = array();
				while ($line = $result->fetch_object())
				{
					if (!is_null($ipv6))
					{
						if (!is_null($line->ipv6))
						{
							$peers6[] = array('ip'=>$line->ipv6,'port'=>(int)$line->port6,'peer id'=>stripslashes($line->peerid));
						}
					}
					$peers[] = array('ip'=>$line->ip,'port'=>(int)$line->port,'peer id'=>stripslashes($line->peerid));
				}
			}
			$db->query("optimize table announce");
			$db->query("optimize table history");
			$db->close();
			$announce = new bencode();
			if ($iptype == 6 || !is_null($ipv6))
			{
				$announce = $announce->set_data(array('interval'=>(int)(ANNOUNCE_INTERVAL*60),'peers'=>$peers,'peers6'=>$peers6));
			}
			elseif ($iptype == 4 || is_null($ipv6))
			{
				$announce = $announce->set_data(array('interval'=>(int)(ANNOUNCE_INTERVAL*60),'peers'=>$peers));
			}
			die($announce);
		}
	}
	else
	{
		throw new Exception("this client is banned from the tracker (".$banned_clients[$client[1]].")!");
	}
}
catch(Exception $e)
{
	errorexit($e->getMessage());
}
?>