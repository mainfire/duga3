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
	$serialize = LIBROOT."/".CACHEFOLDER."/db.queue";
	$serializesize = 0;
	if (!file_exists($serialize) || filesize($serialize) == 0)
	{
		if (SITECRAWL != "none")
		{
			print "<p>you need to <a href=?site=".SITECRAWL."&execute=queue>queue</a> up a few torrents first.</p>";
		}
		else
		{
			print "<p>you need to <a href=index.php>queue</a> up a few torrents first.</p>";
		}
	}
	else
	{
		$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
		$loopbreak = 0;
		$queued = unserialize(file_get_contents($serialize));
		if (!is_array($queued) || count($queued) == 0)
		{
			print "<p>nothing to process!</p>";
		}
		else
		{
			if (SITECRAWL != "none")
			{
				$flipped = array_flip($queued);
				foreach ($flipped as $site)
				{
					if ($site != SITECRAWL)
					{
						unset($site);
					}
				}
				$queued = array_flip($flipped);
				$flipped = null;
			}
			foreach ($queued as $url => $pluginsite)
			{
				if ($loopbreak == PROCESSMAX)
				{
					break;
				}
				queue_array_save($serialize,$url,2);
				$timestamp = time();
				$cacheprefix1 = $pluginsite.'-'.$timestamp;
				$temptorrent = LIBROOT.'/'.CACHEFOLDER.'/'.$cacheprefix1.'.torrent';
				$proxyrequest = $plugins[$pluginsite]['PLUGINPROXY'];
				if (extension_loaded('http') && CURLMETHOD == 2)
				{
					pecl_http_fetch($url,$temptorrent,SITECRAWL,$proxyrequest,0);
				}
				else
				{
					curl_fetch($url,$temptorrent,SITECRAWL,$proxyrequest,0);
				}
				sleep(SLEEPER);
				if (!file_exists($temptorrent) || filesize($temptorrent) == 0)
				{
					print "<p>skipping <strong>$url</strong>, couldnt fetch torrent...</p>";
				}
				else
				{
					$torrentreader = new bdecode($temptorrent);
					$torrent = $torrentreader->read_next();
					if($torrent != false)
					{
						$infohash = $torrent['info_hash']; #get the infohash of the torrent
						$announce = (isset($torrent['announce'])) ? $torrent['announce'] : ANNOUNCE_RESET; #get the main announce url
						if (in_array($announce,$announce_blacklist) || strlen($announce) < 15)
						{
							$announce = ANNOUNCE_RESET;
						}
						#$announce = str_replace("udp://","http://",$announce);
						$announce_list = (isset($torrent['announce-list'])) ? $torrent['announce-list'] : array_unshift($announce_array_reset,$announce); #get the announce-list, or fallback
						for ($i = 0; $i < sizeof($announce_list); ++$i)
						{
							$announce_list[$i] = array_diff($announce_list[$i],$announce_blacklist); #filter this tiers entire tracker list against our blacklist (fastest way possible without flipping + unset)
							$announce_list[$i] = array_values(array_filter($announce_list[$i])); #reset this tiers array correctly
						}
						$announce_list = array_values(array_filter($announce_list));
						$announce_list_db = addslashes(bzcompress(serialize($announce_list),BZIP2COMPRESSION,BZIP2WORKFACTOR));
						$name = $torrent['info']['name']; #torrent name
						$client = (isset($torrent['created by'])) ? $torrent['created by'] : 0; #created by what client?
						$comment = (isset($torrent['comment'])) ? addslashes($torrent['comment']) : 0; #get comment if it exists
						$created = (isset($torrent['creation date'])) ? $torrent['creation date'] : 0; #created on what date?
						$locale = (isset($torrent['locale'])) ? $torrent['locale'] : 0; #get the locale data if it exists
						$encoding = (isset($torrent['encoding'])) ? $torrent['encoding'] : 0; #get torrents encoding
						$modified = (isset($torrent['modified-by'])) ? $torrent['modified-by'] : 0; #whether or not this torrent has already been modified
						$filesize = 0; #start our filesize at 0
						array_walk_recursive($torrent,'add_length'); #walk the 'length' bit and add up the size in the end
						$piecelength = $torrent['info']['piece length']; #piece length, in bytes
						$pieces = strtoupper(bin2hex($torrent['info']['pieces'])); #convert our binary pieces into a hex string
						$pieces = substr(chunk_split($pieces,20,"-"),0,-1); #break this up into smaller 20 byte
						$pieces_db = addslashes(bzcompress($pieces,BZIP2COMPRESSION,BZIP2WORKFACTOR)); #and finally, compress our nicely broken up string
						$pieces = null; #free this from memory
						$private = (isset($torrent['info']['private'])) ? 1 : 0; #get the private flag
						$metafiles_reset = array
						(
							'0' => array
							(
								'length' => $filesize,
								'path' => array
								(
									'0' => $name,
								),
							),
						); #create a dummy dictionary list so it can be reused interchangably between multiple and single files with the same code, GENIUS
						$metafiles = (isset($torrent['info']['files'])) ? $torrent['info']['files'] : $metafiles_reset; #get our files list, or reset to the above array
						$files = addslashes(bzcompress(serialize($metafiles),BZIP2COMPRESSION,BZIP2WORKFACTOR));
						$hashcheck = $db->query("select * from processed where match (hash) against ('\"$infohash\"' IN BOOLEAN MODE) limit 1");
						$hashcheck2 = $db->query("select * from processed where match (hash2) against ('\"$infohash\"' IN BOOLEAN MODE) limit 1");
						if ($hashcheck->num_rows > 0) #if it does
						{
							while ($line1 = $hashcheck->fetch_object())
							{
								$oldsites = $line1->site;
								$sitechecks = explode(";",$oldsites);
								if (!in_array($pluginsite,$sitechecks))
								{
									$newsites = $oldsites.";".$pluginsite;
									$oldurls = stripslashes(bzdecompress($line1->urls,true));
									$newurls = addslashes(bzcompress($oldurls.';'.$url,BZIP2COMPRESSION,BZIP2WORKFACTOR));
									$sitecount = count($sitechecks)+1;
									if ($db->query("update processed set urls = '$newurls', site = '$newsites', timestamp = '$timestamp' where hash = '$infohash' limit 1") === true)
									{
										print "<p>updated <strong>$infohash</strong>, seen on <strong>$sitecount</strong> sites...</p>";
									}
								}
								else
								{
									print "<p>skipping <strong>$infohash</strong>, already in database for <em>$pluginsite</em> (found on <em>".$sitechecks[0]."</em>)...</p>";
								}
							}
						}
						elseif ($hashcheck2->num_rows > 0) #if it does
						{
							while ($line1 = $hashcheck2->fetch_object())
							{
								$oldsites = $line1->site;
								$sitechecks = explode(";",$oldsites);
								if (!in_array($pluginsite,$sitechecks))
								{
									$newsites = $oldsites.";".$pluginsite;
									$oldurls = stripslashes(bzdecompress($line1->urls,true));
									$newurls = addslashes(bzcompress($oldurls.';'.$url,BZIP2COMPRESSION,BZIP2WORKFACTOR));
									$sitecount = count($sitechecks)+1;
									if ($db->query("update processed set urls = '$newurls', site = '$newsites', timestamp = '$timestamp' where hash = '$infohash' limit 1") === true)
									{
										print "<p>updated <strong>$infohash</strong>, seen on <strong>$sitecount</strong> sites...</p>";
									}
								}
								else
								{
									print "<p>skipping <strong>$infohash</strong>, already in database for <em>$pluginsite</em>...</p>";
								}
							}
						}
						else #if it doesnt
						{
							if (DEBUGGING == 1)
							{
								print "<pre>";
								print print_r($torrent);
								print "</i></u></s></b></pre>";
							}
							if ($db->query("insert into processed (cached,client,comment,created,encoding,files,hash,locale,modified,name,pieces,piecelength,private,site,size,timestamp,torrage,tracker,trackers,url,urls) values ('0','$client','$comment','$created','$encoding','$files','$infohash','$locale','$modified','$name','$pieces_db','$piecelength','$private','$pluginsite','$filesize','$timestamp','".TORRAGE."/torrent/$infohash.torrent','$announce','$announce_list_db','$url','".addslashes(bzcompress($url,BZIP2COMPRESSION,BZIP2WORKFACTOR))."')") === true) #if we can insert this into the database
							{
								$torrentid = $db->insert_id; #here we get the id of whatever was just inserted into the database
								foreach ($announce_list as $tracker_tier)
								{
									foreach ($tracker_tier as $tracker_announce)
									{
										$announcecheck2 = $db->query("select * from trackers where announce = '$tracker_announce' limit 1");
										#$announcecheck2 = $db->query("select * from trackers where match (announce) against ('$tracker_announce') limit 1");
										if ($announcecheck2->num_rows > 0)
										{
											while ($line2 = $announcecheck2->fetch_object())
											{
												$oldprocessed2 = stripslashes(bzdecompress($line2->processed,true));
												$newprocessed2 = addslashes(bzcompress($oldprocessed2.';'.$torrentid,BZIP2COMPRESSION,BZIP2WORKFACTOR));
											}
											$db->query("update trackers set processed = '$newprocessed2', torrents = torrents + 1, timestamp = '$timestamp' where announce = '$tracker_announce' limit 1");
										}
										else
										{
											$db->query("insert into trackers (announce,timestamp,processed) values ('$tracker_announce','$timestamp','".addslashes(bzcompress($torrentid,BZIP2COMPRESSION,BZIP2WORKFACTOR))."')");
										}
									}
								}
								if (COPYTORRENT == 1 && file_exists($temptorrent)) #if we can copy the torrent in the first place
								{
									$hashdirectory = WEBROOT."/".FOLDER."/".torrent_location($infohash);
									if (!is_dir($hashdirectory))
									{
										mkdir($hashdirectory,0777,true);
										chmod($hashdirectory,0777); #doublecheck, even though the above true statement should be enough
									}
									$finaltorrent = $hashdirectory."/$infohash.torrent"; #final locally cached location
									copy($temptorrent,$finaltorrent); #copy to its final resting place
									if (SYMLINKTORRENT == 1)
									{
										$symlinktorrent = WEBROOT."/".FOLDER."/".SYMLINKFOLDER."/$infohash.torrent";
										symlink($finaltorrent,$symlinktorrent);
									}
								}
								print "<p>processed <strong>$infohash</strong> into database...</p>";
							}
							else
							{
								print "<p>skipping <strong>$infohash</strong>, could not process into database...</p>";
							}
						}
					}
					else
					{
						print "<p>skipping <strong>$url</strong>, could not decode torrent...</p>";
					}
				}
				if (file_exists($temptorrent))
				{
					unlink($temptorrent); #delete cached torrent
				}
				$loopbreak = $loopbreak + 1;
			}
			$serializesize= count(unserialize(file_get_contents($serialize)));
		}
		if (SITECRAWL != "none")
		{
			$rows3 = $db->query("select * from processed where site = '".SITECRAWL."'");
		}
		else
		{
			$rows3 = $db->query('select * from processed');
		}
		if ($serializesize != 0)
		{
			print "<p><a href=?site=none&execute=process>process more</a>, <a href=?site=".SITECRAWL."&execute=scrape>scrape</a>, or <a href=index.php>finish</a> (".$rows3->num_rows." in processed table).</p>";
		}
		else
		{
			print "<p>looks like youre <a href=index.php>finished</a>!</p>";
		}
		$db->query("optimize table processed");
		$db->query("optimize table trackers");
		$db->close();
	}
}
catch(Exception $e)
{
	echo '<p>something went horribly wrong!</p>';
	echo '<p>'.$e->getMessage().'</p>';
}
$time_end = microtime(true);
$time = $time_end - $time_start;
require_once 'footer.php';
?>