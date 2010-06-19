<?php
$time_start = microtime(true);
require_once("bdecode.php");
require_once("functions.php");
check_permissions(LIBROOT.'/'.CACHEFOLDER); #check folder permissions
if (COPYTORRENT == 1)
{
	check_permissions(WEBROOT.'/'.FOLDER); #check folder permissions
}
if (SYMLINKTORRENT == 1)
{
	check_permissions(WEBROOT.'/'.FOLDER.'/'.SYMLINKFOLDER); #check folder permissions
}
require_once("header.php");
if(SITECRAWL != "none" && !array_key_exists(SITECRAWL,$plugins))
{
	die('<p>no site? no plugin? <a href="'.$SERVER['URI'].'">retry</a> or <a href=index.php>return</a>...</p>');
	require_once("footer.php");
}
try #batter up
{
	$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE); #connect to database
	if (SITECRAWL != "none") #if our plugin is not "none"
	{
		$query = $db->query("select * from processed where site = '".SITECRAWL."' order by timestamp asc limit ".UPDATEMAX); #take select that plugins torrents
		#$query = $db->query("select * from processed where match (site) against (".SITECRAWL.") order by timestamp asc limit ".UPDATEMAX);
	}
	else #otherwise
	{
		$query = $db->query("select * from processed order by timestamp asc limit ".UPDATEMAX); #just select the oldest timestamps
	}
	if ($query->num_rows > 0) #if we have more than 0 rows to work with
	{
		while($line = $query->fetch_object()) #make our query into a fetchable object
		{
			$timestamp = time(); #get our timestamp
			$db->query("update processed set timestamp = '$timestamp' where id = '".$line->id."' limit 1");
			$infohash = $line->hash;
			$attempts = $line->attempts;
			$cacheprefix = $line->id.'-'.$infohash.'-'.$timestamp;
			$tempscrape = LIBROOT.'/'.CACHEFOLDER.'/'.$cacheprefix.'.scrape'; #cached scrape
			$announce_list = unserialize(stripslashes(bzdecompress($line->trackers,true)));
			$proxyrequest = $plugins[$line->site]['PLUGINPROXYSCRAPE'];
			$break1 = 0; #break out of the tiers loop
			$break2 = 0; #break out of the tiers trackers loop
			foreach ($announce_list as $tracker_tier)
			{
				if ($break1 != 0)
				{
					break; #break if set to 1
				}
				foreach ($tracker_tier as $tracker_announce)
				{
					if ($break2 != 0)
					{
						break; #break if set to 1
					}
					else
					{
						if (!preg_match("/dht:\/\//i",$tracker_announce))
						{
							$scrapeurl = ann2scr($tracker_announce,$infohash,1); #clean up the url for usage
							if (extension_loaded('http') && CURLMETHOD == 2)
							{
								pecl_http_fetch($scrapeurl,$tempscrape,0,$proxyrequest,0);
							}
							else
							{
								curl_fetch($scrapeurl,$tempscrape,0,$proxyrequest,0);
							}
							sleep(SLEEPER);
							if (file_exists($tempscrape) && filesize($tempscrape) != 0) #if we find a tracker that responds with usable content
							{
								$db->query("update trackers set timestamp = '$timestamp' where announce = '".$tracker_announce."' limit 1");
								$break1 = 1; #break out of the overall tier loop
								$break2 = 1; #break out of the current tier loop
							}
						}
					}
				}
			}
			if (!file_exists($tempscrape) || filesize($tempscrape) == 0) #if the tracker responds with unusable content (used by multitracker scraping as well)
			{
				if ($attempts == MAXSCRAPEATTEMPTS || $attempts > MAXSCRAPEATTEMPTS)
				{
					foreach ($announce_list as $tracker_tier) #before we delete this torrent, lets completely unset it from any arrays in the trackers table
					{
						foreach ($tracker_tier as $tracker_announce)
						{
							$announcequery = $db->query("select * from trackers where announce = '$tracker_announce' limit 1");
							if ($announcequery->num_rows > 0)
							{
								while ($line1 = $announcequery->fetch_object())
								{
									$processed = explode(";",stripslashes(bzdecompress($line1->processed,true))); #make an array
									$newtorrents = $line1->torrents - 1; #since we are deleting this torrent, lets subtract 1 torrent from this tracker
									$flipped_processed = array_flip($processed); #flip the above array around for easier handling
									unset($flipped_processed[$torrent]); #unset the VALUE (due to the above flip, the value is now the key, and vice versa) from the array
									$processed = implode(";",array_values(array_flip($flipped_processed))); #flip the array back to the original state, reset the array, and implode back into the string we use in the database
									$flipped_processed = null; #free this from memory, we no longer need it
									$newprocessed = addslashes(bzcompress($processed,BZIP2COMPRESSION,BZIP2WORKFACTOR)); #compress our imploded array for reinsertion back into the database
									$processed = null; #free this from memory, we no longer need it
									$db->query("update trackers set torrents = '$newtorrents', processed = '$newprocessed' where announce = '$tracker_announce' limit 1");
								}
							}
						}
					}
					if ($db->query('delete from processed where id = '.$line->id.' limit 1') === true) #delete it from the database
					{
						$file = WEBROOT."/".FOLDER."/".torrent_location($infohash)."/$infohash.torrent"; #get the location
						if (COPYTORRENT == 1 && file_exists($file)) #if we have the torrent
						{
							unlink($file); #and delete it
							$symlinktorrent = WEBROOT."/".FOLDER."/".SYMLINKFOLDER."/$infohash.torrent";
							if (SYMLINKTORRENT == 1 && file_exists($symlinktorrent))
							{
								unlink($symlinktorrent);
							}
						}
						print "<p>deleting <strong>$infohash</strong>, no more attempts allowed (attempt #".($attempts+1).")...</p>";
					}
				}
				else
				{
					if ($db->query("update processed set attempts = attempts + 1 where id = '".$line->id."' limit 1"))
					{
						print "<p>skipping <strong>$infohash</strong>, couldnt fetch scrape (attempt #".($attempts+1).")...</p>";
					}
				}
			}
			else #otherwise, it looks like the tracker responded properly
			{
				$scrapereader = new bdecode($tempscrape); #parse our scrape contents
				$rawdata = $scrapereader->read_next(); #begin decode
				$seeds = decode_scrape($rawdata,"seeds",$infohash);
				$leechs = decode_scrape($rawdata,"leechs",$infohash);
				$snags = decode_scrape($rawdata,"snags",$infohash);
				if (DEBUGGING == 1)
				{
					print "<pre>";
					print print_r($rawdata['files']);
					print "</pre>";
				}
				if ($db->query("update processed set attempts = '0', status = '1', leechs = '$leechs', seeds = '$seeds', snags = '$snags' where id = '".$line->id."' limit 1") === true) #insert this into the database
				{
					print "<p>scraped and updated <strong>$infohash</strong> (leechers: $leechs; seeders: $seeds; downloaded: $snags)...</p>"; #heads up
				}
			}
			if (file_exists($tempscrape))
			{
				unlink($tempscrape); #delete cached scrape
			}
		}
		print "<p><a href=?site=".SITECRAWL."&execute=update>continue</a>, or <a href=index.php>finish</a>.</p>";
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