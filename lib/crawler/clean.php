<?php
#licensed under the new bsd license
$time_start = microtime(true);
require_once("functions.php");
check_permissions(LIBROOT.'/'.CACHEFOLDER); #check folder permission
if (COPYTORRENT == 1)
{
	check_permissions(WEBROOT.'/'.FOLDER); #check folder permissions
}
if (SYMLINKTORRENT == 1)
{
	check_permissions(WEBROOT.'/'.FOLDER.'/'.SYMLINKFOLDER); #check folder permissions
}
$nuke = (isset($_GET['nuke'])) ? $_GET['nuke'] : false;
require_once("header.php");
if(SITECRAWL != "none" && !array_key_exists(SITECRAWL,$plugins))
{
	die('<p>no site? no plugin? <a href="'.$SERVER['URI'].'">retry</a> or <a href=index.php>return</a>...</p>');
	require_once("footer.php");
}
try #batter up
{
	if (EXECUTE == "nuke")
	{
		$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE); #open connection to database
		if ($nuke != true)
		{
			print "<h3>warning: this will <em>completely</em> delete your entire database!</h3>";
			print "<p>please be sure to make a backup of your data if you plan on reinstalling later!<br />also, please be aware that this process could take anywhere from under a second, to a few hours, depending on how many torrents you may have.<br /><br /><a href=./?site=none&execute=nuke&nuke=true>nuke database</a>, or <a href=index.php>return</a>...</p>";
		}
		else
		{
			$query1 = "drop table processed"; #truncate our process table of all entires
			$query2 = "drop table trackers"; #truncate our trackers table of all entries
			$cachedir = LIBROOT."/".CACHEFOLDER;
			if (is_dir($cachedir))
			{
				recursive_rmdir($cachedir);
			}
			if (COPYTORRENT == 1) #make sure we are copying torrents in the first place
			{
				$torrents = WEBROOT."/".FOLDER;
				if (is_dir($torrents)) #check if this is even a directory
				{
					recursive_rmdir($torrents); #delete the entire directory
				}
			}
			if ($db->query($query1) === true && $db->query($query2) === true) #if we can truncate both our tables
			{
				print "<p>nuked the entire database, <a href=index.php>reinstall</a>.</p>"; #then let me know that you did
			}
		}
		$db->close(); #close connection to database
	}
	elseif (EXECUTE == "smoke")
	{
		$serialize = LIBROOT."/".CACHEFOLDER."/db.queue";
		if (file_exists($serialize) || filesize($serialize) != 9)
		{
			unlink($serialize);
			print "<p>cleared the queued list, <a href=index.php>return</a>.</p>";
		}
	}
}
catch(Exception $e) #struck out
{
	echo '<p>something went horribly wrong!</p>';
	echo '<p>'.$e->getMessage().'</p>';
}
$time_end = microtime(true);
$time = $time_end - $time_start;
require_once("footer.php");
?>