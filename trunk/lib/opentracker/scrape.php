<?php
#licensed under the new bsd license
require_once 'config.php';
require_once 'functions.php';
$compact = (isset($_GET['compact'])) ? rtrim(strip_tags($_GET['compact'])) : 0;
$infohash = (isset($_GET['info_hash'])) ? rtrim(strip_tags($_GET['info_hash'])) : null;
$timestamp = time();
header('Content-Type: text/plain;');
if (FULLSCRAPE == 2)
{
	ob_start("ob_gzhandler");
}
try
{
	$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
	if (!$db->query("select id from announce limit 1") || !$db->query("select id from history limit 1"))
	{
		$db->query($announce);
		$db->query($history);
	}
	if (is_null($infohash))
	{
		if (FULLSCRAPE == 0)
		{
			throw new Exception("fullscrapes are disabled on this tracker");
		}
		$hashes = array();
		$query = "select hash from history where expire > $timestamp";
		if ($result = $db->query($query))
		{
			while ($line = $result->fetch_object())
			{
				$hashes[] = hex2bin($line->hash);
			}
		}
	}
	else
	{
		parse_str(str_replace('info_hash=','info_hash[]=',$_SERVER['QUERY_STRING']),$requests);
		foreach ($requests['info_hash'] as $hash)
		{
			if (!is_null($hash) || $hash != " ")
			{
				$hashes = array();
				if (strlen($infohash) != 40)
				{
					$sha1hash = strtoupper(bin2hex($hash));
				}
				else
				{
					$sha1hash = strtoupper($infohash);
				}
				$hashexists = $db->query("select * from history where match (hash) against ('\"$sha1hash\"' IN BOOLEAN MODE) limit 1");
				if ($hashexists->num_rows > 0)
				{
					$hashes[] = hex2bin($sha1hash);
				}
				else
				{
					throw new Exception("invalid info_hash(s) specified");
				}
			}
		}
	}
	$files = array();
	foreach ($hashes as $hash)
	{
		$hash1 = strtoupper(bin2hex($hash));
		$hashcheck = "select complete,incomplete,downloaded from history where match (hash) against ('\"$hash1\"' IN BOOLEAN MODE) limit 1";
		if ($rows = $db->prepare($hashcheck))
		{
			$rows->execute();
			$rows->bind_result($complete,$incomplete,$downloaded);
			while ($rows->fetch())
			{
				$seeds = $complete;
				$leechs = $incomplete;
				$snags = $downloaded;
				if (COMPACT_SCRAPE == 1 && $compact == 1)
				{
					$files[$hash] = pack('n3',$seeds,$leechs,$snags);
				}
				else
				{
					$files[$hash] = array('complete'=>(int)$seeds,'incomplete'=>(int)$leechs,'downloaded'=>(int)$snags);
				}
			}
			$rows->close();
		}
	}
	$db->query("optimize table announce");
	$db->query("optimize table history");
	$db->close();
	$scrape = new bencode();
	if (COMPACT_SCRAPE != 1)
	{
		$scrape = $scrape->set_data(array('files'=>$files,'flags'=>array('min_request_interval'=>(int)(ANNOUNCE_INTERVAL*60)+(SCRAPE_INTERVAL*60))));
	}
	else
	{
		$scrape = $scrape->set_data(array('files'=>$files,'flags'=>array('compact_scrape'=>(int)1,'min_request_interval'=>(int)(ANNOUNCE_INTERVAL*60)+(SCRAPE_INTERVAL*60))));
	}
	die($scrape);
}
catch(Exception $e)
{
	errorexit($e->getMessage());
}
if (FULLSCRAPE == 2)
{
	ob_end_flush();
}
?>