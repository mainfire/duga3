<?php
$time_start = microtime(true);
require_once("bdecode.php");
require_once("functions.php");
check_permissions(LIBROOT.'/'.CACHEFOLDER);
require_once("header.php");
try
{
	$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
	$result = $db->query("select * from trackers where announce = 'http://tracker.publicbt.com/announce' limit 1");
	if ($result->num_rows > 0)
	{
		$db->query("update trackers set timestamp = '$timestamp' where id = '".$line->id."' limit 1");
		$tempscrape = LIBROOT.'/'.CACHEFOLDER.'/'.time().'.bz2';
		$unzippedscrape = LIBROOT.'/'.CACHEFOLDER.'/all.txt';
		$bzippedfullscrape = 'http://publicbt.com/all.txt.bz2';
		if (extension_loaded('http') && CURLMETHOD == 2)
		{
			pecl_http_fetch($bzippedfullscrape,$tempscrape,0,0,0);
		}
		else
		{
			curl_fetch($bzippedfullscrape,$tempscrape,0,0,0);
		}
		if (!file_exists($tempscrape) || filesize($tempscrape) == 0)
		{
			print "<p>couldnt fetch scrape from <strong>$bzippedfullscrape</strong>...</p>";
		}
		else
		{
			$scrapehandle = @bzopen($tempscrape,"r");
			$scrapedata = null;
			if ($scrapehandle)
			{
				while (!feof($scrapehandle))
				{
					$scrapebuffer = bzread($scrapehandle,SCRAPEREAD);
					$scrapedata .= $scrapebuffer;
				}
			}
			bzclose($scrapehandle);
			$bzippedarray = explode('\n',$scrapedata);
			$scrapedata = null; 
			foreach ($bzippedarray as $scrapeinfo)
			{
				$timestamp = time();
				$scrapeinfoarray = explode(':',$scrapeinfo);
				$infohash = strtoupper(bin2hex(urldecode($scrapeinfoarray[0])));
				$seeds = $scrapeinfoarray[1];
				$leechs = $scrapeinfoarray[2];
				$snags = 0;
				$selecthash = $db->query("select * from processed where hash = '$infohash' limit 1");
				if ($selecthash->num_rows > 0)
				{
					if ($db->query("update processed set attempts = '0', status = '1', leechs = '$leechs', seeds = '$seeds', snags = '$snags', timestamp = '$timestamp' where hash = '$infohash' limit 1") === true)
					{
						print "<p>scraped and updated <strong>$infohash</strong> (seeders: $seeds; leechers: $leechs; downloaded: $snags)...</p>";
					}
				}
				unset($torrent); #this will unset each torrent as we update its information, reducing the size of the array (and perhaps some associated overhead)
			}
		}
		if (file_exists($tempscrape))
		{
			if (file_exists($unzippedscrape))
			{
				unlink($unzippedscrape);
			}
			unlink($tempscrape);
		}
		print "<p><a href=?site=none&execute=update3>continue</a>, or <a href=index.php>finish</a>.</p>";
	}
	else
	{
		print "<p>you need to <a href=./>queue</a>, then process a few torrents first.</p>";
	}
	$db->query("optimize table processed");
	$db->query("optimize table trackers");
	$db->close();
}
catch(Exception $e)
{
	echo '<p>something went horribly wrong!</p>';
	echo '<p>'.$e->getMessage().'</p>';
}
$time_end = microtime(true);
$time = $time_end - $time_start;
require_once("footer.php");
?>