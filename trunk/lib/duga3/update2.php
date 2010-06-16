<?php
$time_start = microtime(true);
require_once("bdecode.php");
require_once("functions.php");
check_permissions(LIBROOT.'/'.CACHEFOLDER); #check folder permissions
require_once("header.php");
try #batter up
{
	$timestamp = time(); #get our timestamp
	$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE); #connect to database
	if (SITECRAWL != "freebsd" || SITECRAWL != "mininova")
	{
		$result = $db->query("select * from trackers where torrents > ".UPDATEMIN." order by timestamp asc limit 1"); #just select the oldest timestamps
	}
	else
	{
		$result = $db->query("select * from trackers where announce = 'http://tracker.mininova.org/announce' limit 1"); #just select the oldest timestamps
	}
	if ($result->num_rows > 0) #if we have more than 0 rows to work with
	{
		while($line = $result->fetch_object()) #make our query into a fetchable object
		{
			$db->query("update trackers set timestamp = '$timestamp' where id = '".$line->id."' limit 1");
			$timestamp = time(); #updated timestamp
			$cacheprefix = $line->id.'-'.$timestamp;
			$tempscrape = LIBROOT.'/'.CACHEFOLDER.'/'.$cacheprefix.'.scrape'; #cached scrape
			$processed = stripslashes(bzdecompress($line->processed,true));
			$array = explode(';',$processed);
			$announce = $line->announce;
			$scrapeurl = ann2scr($announce,0,0); #clean up the url for usage, return as fullscrape method
			if (extension_loaded('http') && CURLMETHOD == 2)
			{
				pecl_http_fetch($scrapeurl,$tempscrape,0,0,0);
			}
			else
			{
				curl_fetch($scrapeurl,$tempscrape,0,0,0);
			}
			if (!file_exists($tempscrape) || filesize($tempscrape) == 0) #if the tracker responds with unusable content (used by multitracker scraping as well)
			{
				print "<p>couldnt fetch scrape from <strong>$scrapeurl</strong>...</p>";
			}
			else
			{
				$scrapehandle = fopen($tempscrape,"r");
				$bencoded = null;
				if ($scrapehandle)
				{
					while (!feof($scrapehandle))
					{
						$scrapebuffer = fgets($scrapehandle,SCRAPEREAD);
						$bencoded .= $scrapebuffer;
					}
				}
				fclose($scrapehandle);
				$scrapereader = new BEncodeReader(); #create a new decode instance
				$scrapereader->setData($bencoded); #set the fullscrape data
				$rawdata = $scrapereader->readNext(); #begin decode
				if ($rawdata != false)
				{
					foreach($array as $torrent)
					{
						$selecthash = $db->query("select hash from processed where id = '$torrent' limit 1");
						while ($line2 = $selecthash->fetch_object())
						{
							$infohash = $line2->hash;
							$infohash2 = hex2bin($infohash); #this is here so we can convert our infohash back over to binary format
							$seeds = decode_scrape($rawdata,"seeds",$infohash);
							$leechs = decode_scrape($rawdata,"leechs",$infohash);
							$snags = decode_scrape($rawdata,"snags",$infohash);
							if (DEBUGGING == 1)
							{
								print "<pre>";
								print print_r($rawdata['files'][$infohash2]);
								print "</pre>";
							}
							if ($db->query("update processed set attempts = '0', status = '1', leechs = '$leechs', seeds = '$seeds', snags = '$snags', timestamp = '$timestamp' where id = '$torrent' limit 1") === true) #insert this into the database
							{
								print "<p>scraped and updated <strong>$infohash</strong> (seeders: $seeds; leechers: $leechs; downloaded: $snags)...</p>"; #heads up
							}
						}
					}
				}
				else
				{
					print "<p>couldnt decode scrape information from <strong>$scrapeurl</strong>...</p>";
				}
			}
			if (file_exists($tempscrape))
			{
				unlink($tempscrape); #delete cached scrape
			}
		}
		print "<p><a href=?site=".SITECRAWL."&execute=update2>continue</a>, or <a href=index.php>finish</a>.</p>";
	}
	else #otherwise, we dont have any torrents to process in the first place
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