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
	if (KEEP_HISTORY == 0)
	{
		$db->query("delete from announce where expire < $timestamp");
	}
	if ($db->query("select * from announce") === false)
	{
		$db->query($announce);
	}
	if (is_null($infohash))
	{
		if (FULLSCRAPE == 0)
		{
			errorexit("fullscrapes are disabled on this tracker");
		}
		$hashes = array();
		$query = "select hash from announce where expire > $timestamp";
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
				$hashexists = $db->query("select * from announce where hash = '$sha1hash' and expire > $timestamp limit 1");
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
		$complete = "select count(*) from announce where hash = '$hash' and remain = 0 and expire > $timestamp";
		$incomplete = "select count(*) from announce where hash = '$hash' and remain > 0 and expire > $timestamp";
		if (KEEP_HISTORY == 1)
		{
			$downloaded = "select count(distinct ip,port) from announce where hash = '$hash' and remain = 0";
			if ($line3 = $db->prepare($downloaded))
			{
				$line3->execute();
				$line3->store_result();
				$snags = intval($line3->num_rows(),0);
				$line3->close();
			}
		}
		else
		{
			$snags = 0;
		}
		if ($line = $db->prepare($complete))
		{
			$line->execute();
			$line->store_result();
			$seeds = intval($line->num_rows(),0);
			$line->close();
		}
		if ($line2 = $db->prepare($incomplete))
		{
			$line2->execute();
			$line2->store_result();
			$leechs = intval($line2->num_rows(),0);
			$line2->close();
		}
		$files[$hash] = array('complete'=>$seeds,'incomplete'=>$leechs,'downloaded'=>(int)$snags);
	}
	die(bencode(array('files'=>$files,'flags'=>array('min_request_interval'=>(int)(ANNOUNCE_INTERVAL*60)+(SCRAPE_INTERVAL*60)))));
	$db->query("optimize table announce");
	$db->close();
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
