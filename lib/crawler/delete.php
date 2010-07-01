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
try
{
	$torrent = (isset($_GET['tid'])) ? addslashes(strip_tags($_GET['tid'])) : null;
	if (!is_null($torrent))
	{
		$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
		$result = $db->query("select * from processed where id = '$torrent' limit 1");
		if ($result->num_rows > 0) #if we have more than 0 rows to work with
		{
			while($line = $result->fetch_object()) #make our query into a fetchable object
			{
				$trackers = unserialize(stripslashes(bzdecompress($line->trackers,true)));
				foreach ($trackers as $tracker_tier)
				{
					foreach ($tracker_tier as $announce)
					{
						$announcequery = $db->query("select * from trackers where announce = '$announce' limit 1");
						if ($announcequery->num_rows > 0)
						{
							while ($line1 = $announcequery->fetch_object())
							{
								$processed = explode(";",stripslashes(bzdecompress($line1->processed,true)));
								$newtorrents = $line1->torrents - 1;
								$flipped_processed = array_flip($processed);
								unset($flipped_processed[$torrent]);
								$processed = implode(";",array_values(array_flip($flipped_processed)));
								$flipped_processed = null;
								$newprocessed = addslashes(bzcompress($processed,BZIP2COMPRESSION,BZIP2WORKFACTOR));
								$processed = null;
								$db->query("update trackers set torrents = '$newtorrents', processed = '$newprocessed' where announce = '$announce' limit 1");
							}
						}
					}
				}
				$torrentlocation = WEBROOT."/".FOLDER."/".torrent_location($line->hash)."/".$line->hash.".torrent";
				if (COPYTORRENT == 1 && file_exists($torrentlocation))
				{
					unlink($torrentlocation);
					$symlinklocation = WEBROOT."/".FOLDER."/".SYMLINKFOLDER."/".$line->hash.".torrent";
					if (SYMLINKTORRENT == 1 && file_exists($symlinklocation))
					{
						unlink($symlinklocation);
					}
				}
			}
			if ($db->query("delete from processed where id = '$torrent' limit 1") === true)
			{
				print "<p>completely flushed #<strong>$torrent</strong> from the database!</p>";
			}
		}
		else
		{
			print "<p>#<strong>$torrent</strong> does not seem to exist in the database!</p>";
		}
		$db->query("optimize table processed");
		$db->query("optimize table trackers");
		$db->close();
	}
	print "<p><a href=index.php>return</a>...</p>";
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