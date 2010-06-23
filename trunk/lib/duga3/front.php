<?php
$time_start = microtime(true);
define('EXECUTE','index');
require_once("functions.php");
check_permissions(WEBROOT);
check_permissions(LIBROOT);
#our processed mysql table
$processed = "
CREATE TABLE IF NOT EXISTS `processed`
(
	`id` int(30) NOT NULL AUTO_INCREMENT,
	`timestamp` int(14) DEFAULT NULL,
	`name` text,
	`tracker` text,
	`trackers` longblob,
	`seeds` int(20) NOT NULL,
	`leechs` int(20) NOT NULL,
	`snags` int(20) NOT NULL,
	`private` text,
	`client` text,
	`comment` text,
	`created` text,
	`encoding` text,
	`modified` text,
	`locale` text,
	`size` text,
	`status` int(11) DEFAULT 0,
	`files` longblob,
	`pieces` longblob,
	`piecelength` text,
	`hash` text,
	`hash2` text,
	`site` text,
	`url` text,
	`urls` longblob,
	`torrage` text,
	`attempts` int(11) DEFAULT 0,
	`cached` text,
	PRIMARY KEY (`id`),
	FULLTEXT created(created),
	FULLTEXT hash(hash),
	FULLTEXT hash2(hash2),
	FULLTEXT name(name),
	FULLTEXT tracker(tracker),
	FULLTEXT site(site),
	FULLTEXT url(url)
) ENGINE=".MYSQLENGINE." AUTO_INCREMENT=1 DEFAULT CHARSET=".MYSQLCHARSET.";
";

#trackers table
$trackers = "
CREATE TABLE IF NOT EXISTS `trackers`
(
	`id` int(30) NOT NULL AUTO_INCREMENT,
	`announce` text NOT NULL,
	`timestamp` int(14) DEFAULT NULL,
	`torrents` int(20) DEFAULT 1,
	`processed` longblob,
	PRIMARY KEY (`id`),
	FULLTEXT announce(announce)
) ENGINE=".MYSQLENGINE." AUTO_INCREMENT=1 DEFAULT CHARSET=".MYSQLCHARSET.";
";
require_once("header.php");
try #batter up
{
	$cachefolder = LIBROOT.'/'.CACHEFOLDER;
	$torrentfolder = WEBROOT.'/'.FOLDER;
	$symlinkfolder = $torrentfolder.'/'.SYMLINKFOLDER;
	if (!is_dir($cachefolder))
	{
		mkdir($cachefolder,0777);
		chmod($cachefolder,0777);
	}
	if (COPYTORRENT == 1 && !is_dir($torrentfolder))
	{
		mkdir($torrentfolder,0777);
		chmod($torrentfolder,0777);
		if (SYMLINKTORRENT == 1 && !is_dir($symlinkfolder))
		{
			mkdir($symlinkfolder,0777);
			chmod($symlinkfolder,0777);
		}
	}
	if (!extension_loaded('mysqli'))
	{
?>
		<h3>are you sure you have the <em>mysql<u>i</u></em> extension enabled?</h3>
		before you refresh this page, you should know that this was the result of php saying "no, that extension isnt loaded" (<code>!extension_loaded('mysqli')</code>).
		<br />
		<br />
		<a href="index.php">retry</a>...
<?php
	}
	elseif (!extension_loaded('dom'))
	{
?>
		<h3>are you sure you have the <em>dom</em> extension enabled?</h3>
		before you refresh this page, you should know that this was the result of php saying "no, that extension isnt loaded" (<code>!extension_loaded('dom')</code>).
		<br />
		<br />
		<a href="index.php">retry</a>...
<?php
	}
	elseif (!extension_loaded('bz2'))
	{
?>
		<h3>are you sure you have the <em>bz2</em> extension enabled?</h3>
		before you refresh this page, you should know that this was the result of php saying "no, that extension isnt loaded" (<code>!extension_loaded('bz2')</code>).
		<br />
		<br />
		<a href="index.php">retry</a>...
<?php
	}
	elseif (!extension_loaded('curl'))
	{
?>
		<h3>are you sure you have the <em>curl</em> extension enabled?</h3>
		before you refresh this page, you should know that this was the result of php saying "no, that extension isnt loaded" (<code>!extension_loaded('curl')</code>).
		<br />
		<br />
		<a href="index.php">retry</a>...
<?php
	}
	else
	{
		$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE); #open a new connection to the database
		if (!$db->query("select id from processed limit 1") || !$db->query("select id from trackers limit 1")) #if we havent installed yet
		{
			if ($db->query($processed) === true && $db->query($trackers) === true) #then install it
			{
?>
		<h3>installed successfully</h3>
		<a href="index.php">continue</a>...
<?php
			}
			else #looks like somethings still not enabled
			{
?>
		<h3>looks like theres a database connection problem</h3>
		<br />
		before you refresh this page, you should know that this was the result of an sql query not getting executed.
		<br />
		<a href="index.php">try again</a>...
<?php
			}
		}
		else #otherwise, we are installed, JUST AS PLANNED...
		{
?>
		<span id="right">
			<a href="./?site=none&execute=logout">logout</a>
		</span>
		<h3>all sites</h3>
		<ul>
			<li><a href="./?site=none&execute=process">process</a></li>
			<li><a href="./?site=none&execute=update">scrape</a></li>
			<li><a href="./?site=none&execute=update2">fullscrape</a></li>
			<li><a href="./?site=none&execute=update3">publicbt fullscrape</a></li>
			<li><a href="./?site=none&execute=stats">stats</a></li>
<?php
			if (COPYTORRENT == 1)
			{
?>
			<li><a href="./?site=none&execute=export">feed</a></li>
			<li><a href="./?site=none&execute=cache">cache</a></li>
<?php
			}
?>
			<li><a href="./?site=none&execute=smoke">smoke</a></li>
			<li><a href="./?site=none&execute=nuke">nuke</a></li>
		</ul>
<?php
			foreach ($plugins as $sitename) #take each plugin and print some stuff you can do with each
			{
?>
		<h3><em><?php echo $sitename['PLUGINNAME']; ?></em></h3>
		<ul>
			<li><a href="./?site=<?php echo $sitename['PLUGINNAME']; ?>&execute=queue">queue</a></li>
			<li><a href="./?site=<?php echo $sitename['PLUGINNAME']; ?>&execute=update">scrape</a></li>
			<li><a href="./?site=<?php echo $sitename['PLUGINNAME']; ?>&execute=stats">stats</a></li>
<?php
				if (COPYTORRENT == 1)
				{
?>
			<li><a href="./?site=<?php echo $sitename['PLUGINNAME']; ?>&execute=export">feed</a></li>
			<li><a href="./?site=<?php echo $sitename['PLUGINNAME']; ?>&execute=cache">cache</a></li>
<?php
				}
?>
		</ul>
<?php
			}
		}
		$db->close(); #close connection to database
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