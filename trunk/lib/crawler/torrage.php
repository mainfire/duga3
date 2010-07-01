<?php
#this page has been largely rewritten from where it was before, i'll document it _at_some_point_
#licensed under the new bsd license
$time_start = microtime(true);
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
try
{
	if (COPYTORRENT == 1)
	{
		$torrageurl = TORRAGE."/autoupload.php";
		$cacheprefix = time();
		$torragecheck = LIBROOT.'/'.CACHEFOLDER.'/'.$cacheprefix.'.check';
		if (extension_loaded('http') && CURLMETHOD == 2)
		{
			pecl_http_fetch($torrageurl,$torragecheck,0);
		}
		else
		{
			curl_fetch($torrageurl,$torragecheck,0);
		}
		if (!file_exists($torragecheck) || filesize($torragecheck) == 0)
		{
			print "<p><strong>".TORRAGE."</strong> could not be reached (bad hostname / domain, site offline, etc). <a href=index.php>finish</a>...</p>";
		}
		elseif (file_exists($torragecheck))
		{
			$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
			if (SITECRAWL != "none")
			{
				$query = "select * from processed where site = '".SITECRAWL."' and cached = 0 order by timestamp desc limit ".CACHEMAX;
			}
			else
			{
				$query = "select * from processed where cached = 0 order by timestamp desc limit ".CACHEMAX;
			}
			if ($result = $db->query($query))
			{
				$num_rows = $result->num_rows;
				if ($num_rows > 0)
				{
					while ($line = $result->fetch_objecT())
					{
						$timestamp = time();
						$infohash = $line->hash;
						$cacheprefix = $line->id.'-'.$infohash.'-'.$timestamp;
						$torrentlocation = WEBROOT."/".FOLDER."/".torrent_location($infohash)."/$infohash.torrent";
						$postdata = array();
						$postdata["name"] = "torrent";
						$postdata["torrent"] = "@".$torrentlocation;
						$temphash = LIBROOT.'/'.CACHEFOLDER.'/'.$cacheprefix.'.cache'; #cached response
						if (extension_loaded('http') && CURLMETHOD == 2)
						{
							pecl_http_post($torrageurl,$temphash,$postdata);
						}
						else
						{
							curl_post($torrageurl,$temphash,$postdata);
						}
						if (!file_exists($temphash) || filesize($temphash) == 0)
						{
							if ($db->query('delete from processed where id = '.$line->id.' limit 1') === true) #delete it from the database
							{
								if (COPYTORRENT == 1) #if we have the torrent
								{
									unlink($torrentlocation); #and delete it
									if (SYMLINKTORRENT == 1)
									{
										$symlinktorrent = WEBROOT."/".FOLDER."/".SYMLINKFOLDER."/$infohash.torrent";
										unlink($symlinktorrent);
									}
								}
								print("<p>deleting <strong>$infohash</strong> from database, there is a problem with the file (nil / no response)</p>"); #heads up
							}
						}
						else
						{
							$responsehash = file_get_contents($temphash);
							$order = array("\r\n", "\n", "\r");
							$infohash2 = str_replace($order,"",$responsehash);
							if (strlen($infohash2) == 40 && $infohash2 == $infohash)
							{
								if ($db->query("update processed set timestamp = '$timestamp', cached = '1' where id = '".$line->id."' limit 1") === true)
								{
									print "<p>added <strong>".$infohash."</strong> to the torrage cache...</p>";
								}
							}
							elseif (strlen($infohash2) == 40)
							{
								if ($db->query("update processed set timestamp = '$timestamp', cached = '1', hash2 = '$infohash', hash = '$infohash2' where id = '".$line->id."' limit 1") === true)
								{
									if (COPYTORRENT == 1) #if we have the torrent
									{
										$hashdirectory = WEBROOT."/".FOLDER."/".torrent_location($infohash);
										if (!is_dir($hashdirectory))
										{
											mkdir($hashdirectory,0777,true);
											chmod($hashdirectory,0777); #doublecheck, even though the above true statement should be enough
										}
										$newtorrentlocation = $hashdirectory."/$infohash2.torrent";
										rename($torrentlocation,$newtorrentlocation);
										if (SYMLINKTORRENT == 1)
										{
											$oldsymlinklocation = WEBROOT."/".FOLDER."/".SYMLINKFOLDER."/$infohash1.torrent";
											$newsymlinklocation = WEBROOT."/".FOLDER."/".SYMLINKFOLDER."/$infohash2.torrent";
											unlink($oldsymlinklocation);
											symlink($newtorrentlocation,$newsymlinklocation);
										}
									}
									print "<p>added <strong>".$infohash2."</strong> to the torrage cache (reset from <strong>".$infohash."</strong>)...</p>";
								}
							}
							else
							{
								if ($db->query('delete from processed where id = '.$line->id.' limit 1') === true) #delete it from the database
								{
									if (COPYTORRENT == 1) #if we have the torrent
									{
										unlink($torrentlocation); #and delete it
										if (SYMLINKTORRENT == 1)
										{
											$symlinktorrent = WEBROOT."/".FOLDER."/".SYMLINKFOLDER."/$infohash.torrent";
											unlink($symlinktorrent);
										}
									}
									print("<p>deleting <strong>$infohash</strong> from database, there is a problem with the file (response: $responsehash)</p>"); #heads up
								}
							}
						}
						if (file_exists($temphash))
						{
							unlink($temphash);
						}
					}
				}
			}
			$result->close();
			if (SITECRAWL != "none")
			{
				$rows = $db->query("select * from processed where site = '".SITECRAWL."' where cached = '0'");
			}
			else
			{
				$rows = $db->query('select * from processed where cached = "0"');
			}
			if ($rows->num_rows > 0)
			{
				print "<p><a href=?site=".SITECRAWL."&execute=cache>continue</a>, or <a href=index.php>finish</a>...</p>";
			}
			else
			{
				print "<p>looks like youre <a href=index.php>finished</a>!</p>";
			}
			$db->query("optimize table processed");
			$db->query("optimize table trackers");
			$db->close();
		}
		else
		{
			print "<p>something unexpected happened: <strong>couldnt connect to site</strong>, or <strong>the site isnt torrage compatible!</strong> <a href=index.php>finish</a>...</p>";
		}
		if (file_exists($torragecheck))
		{
			unlink($torragecheck);
		}
	}
	else
	{
		print "<p>you dont seem to have COPYTORRENT set to 1, <a href=index.php>finish</a>...</p>";
	}
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