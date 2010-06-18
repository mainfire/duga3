<?php
require_once 'config.php';
require_once 'functions.php';
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
	if (!$db->query("select * from announce") || !$db->query("select * from history"))
	{
		$db->query($announce);
		$db->query($history);
	}
	$db->query("delete from announce where expire < $timestamp");
	$db->query("delete from history where expire < $timestamp");
	if (is_null($infohash))
	{
		if (FULLSCRAPE == 0)
		{
			errorexit("fullscrapes are disabled on this tracker");
		}
		$hashes = array();
		$query = "select hash from history";
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
				if (strlen($infohash) == 40)
				{
					$sha1hash = strtoupper($infohash);
				}
				else
				{
					$sha1hash = bin2hex($hash);
				}
				$hashexists = $db->query("select * from history where match (hash) against ('\"$sha1hash\"' IN BOOLEAN MODE) limit 1");
				if ($hashexists->num_rows > 0)
				{
					$hashes[] = hex2bin($sha1hash);
				}
				else
				{
					errorexit("invalid info_hash(s) specified");
				}
			}
		}
	}
	$files = array();
	foreach ($hashes as $hash)
	{
		$hashcheck = "select complete,hash,incomplete,downloaded from history where match (hash) against ('\"".bin2hex($hash)."\"' IN BOOLEAN MODE) limit 1";
		if ($rows = $db->prepare($hashcheck))
		{
			$rows->execute();
			$rows->bind_result($complete,$infohash,$incomplete,$downloaded);
			while ($rows->fetch())
			{
				$seeds = $complete;
				$leechs = $incomplete;
				$snags = $downloaded;
				$files[$hash] = array('complete'=>(int)$seeds,'incomplete'=>(int)$leechs,'downloaded'=>(int)$snags);
			}
			$rows->close();
		}
	}
	$db->query("optimize table announce");
	$db->query("optimize table history");
	$db->close();
	die(bencode(array('files'=>$files,'flags'=>array('min_request_interval'=>(int)(ANNOUNCE_INTERVAL*60)+(SCRAPE_INTERVAL*60)))));
}
catch(Exception $e)
{
	errorexit("tracker is down!");
}
if (FULLSCRAPE == 2)
{
	ob_end_flush();
}
?>