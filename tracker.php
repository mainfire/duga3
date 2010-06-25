<?php
require_once "lib/opentracker/config.php";
require_once "lib/opentracker/functions.php";
$torrent = (isset($_GET['torrent'])) ? $_GET['torrent'] : null;
try
{
	$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
	if (!$db->query("select id from announce limit 1") || !$db->query("select id from history limit 1"))
	{
		$db->query($announce);
		$db->query($history);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
"http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
	<head profile="http://www.microformats.org/wiki/hcard-profile">
		<title>open tracker</title>
		<meta http-equiv="cache-control" content="no-cache" />
		<meta name="robots" content="noindex, nofollow" />
		<style type="text/css">
		a
		{
			color: orange;
			text-decoration: none;
		}
		a:visited
		{
			color: rgb(230,230,230);
			text-decoration: underline;
		}
		a:hover
		{
			color: rgb(180,180,180);
			text-decoration: none;
		}
		body
		{
			font-family: Georgia, serif;
			color: rgb(210,210,210);
			background: rgb(30,30,30);
		}
		center
		{
			font-size: small;
		}
		#bold
		{
			font-weight: bold;
		}
		#emphasis
		{
			font-style: italic;
		}
		h1
		{
			text-align: center;
			color: rgb(165,165,165);
		}
		h2,h3
		{
			color: rgb(150,150,150);
		}
		#output
		{
			-moz-border-radius: 20px;
			-webkit-border-radius: 20px;
			border: 2px solid orange;
			padding: 10px 20px 10px 20px;
			margin: 10px;
		}
		#right
		{
			margin-top: 20px;
			float: right;
			clear: right;
		}
		</style>
	</head>
	<body>
		<h1>open tracker</h1>
		<div id="output">
<?php
	$timestamp = time();
	if (!is_null($torrent))
	{
		if (strlen($torrent) == 40)
		{
			$query1 = $db->query("select * from history where match (hash) against ('\"$torrent\"' IN BOOLEAN MODE) limit 1");
			if ($query1->num_rows > 0)
			{
?>
		<h3><?php print $torrent; ?></h3>
<?php
				$query2 = $db->query("select * from announce where match (hash) against ('\"$torrent\"' IN BOOLEAN MODE) order by timestamp desc limit 200");
				if ($query2->num_rows > 0)
				{
					$query3 = $db->query("select sum(incomplete) as leechs, sum(complete) as seeds, sum(downloaded) as snags from history where match (hash) against ('\"$torrent\"' IN BOOLEAN MODE) limit 1");
					while ($line = $query3->fetch_object())
					{
						$seeds = intval($line->seeds,0);
						$leechs = intval($line->leechs,0);
						$snags = intval($line->snags,0);
?>
			<h3>swarm totals</h3>
			<ul>
				<li id="enable">seeders: <?php echo $seeds; ?></li>
				<li id="enable">leechers: <?php echo $leechs; ?></li>
				<li id="enable">downloads: <?php echo $snags; ?></li>
			</ul>
<?php
					}
?>
			<h3>swarm information</h3>
			<ul>
<?php
					while ($line = $query2->fetch_object())
					{
						$uploaded = format_bytes($line->uploaded);
						$downloaded = format_bytes($line->downloaded);
						$event = $line->event;
						$left = $line->remain;
						$peerid = explode('-',stripslashes($line->peerid));
						$xes = null;
						for ($i = 0; $i < strlen($peerid[2]); ++$i)
						{
							$xes .= "x";
						}
?>
				<li><?php print $peerid[1].'-'.$xes;?>: <em><?php print $event; ?></em> with <?php print $downloaded; ?> downloaded and <?php print $uploaded; ?> uploaded (<?php print $left; ?> pieces left)</li>
<?php
					}
?>
			</ul>
<?php
				}
				else
				{
?>
		it seems that this torrent has become stale (no peers) and will expire shortly
		<br />
		<br />
<?php
				}
			}
			else
			{
?>
		<h3>invalid torrent</h3>
		the infohash <?php print $torrent; ?> does not exist
		<br />
		<br />
<?php
			}
		}
		else
		{
?>
		<h3>invalid torrent infohash</h3>
		torrent infohash must exist on the tracker already, and must be 40 bytes long (consisting of the alphanumerics A-F/0-9)
		<br />
		<br />
<?php
		}
?>
		<a href="?">return</a>...
<?php
	}
	else
	{
		$blacklisted = count(explode('\n',file_get_contents("lib/opentracker/blacklist.txt")));
		$db->query("delete from announce where expire < $timestamp");
		$db->query("delete from history where expire < $timestamp");
		$query1 = $db->query("select count(id) as torrents, sum(incomplete) as leechs, sum(complete) as seeds, sum(downloaded) as snags from history");
		$query2 = $db->query("select * from announce order by timestamp desc limit 25");
		if ($query1->num_rows > 0)
		{
			while ($line = $query1->fetch_object())
			{
				$torrents = intval($line->torrents,0);
				$seeds = intval($line->seeds,0);
				$leechs = intval($line->leechs,0);
				$snags = intval($line->snags,0);
?>
			<h3>tracker totals</h3>
			<ul>
				<li id="enable">blacklisted: <?php echo $blacklisted; ?></li>
				<li id="enable">torrents: <?php echo $torrents; ?></li>
				<li id="enable">seeders: <?php echo $seeds; ?></li>
				<li id="enable">leechers: <?php echo $leechs; ?></li>
				<li id="enable">downloads: <?php echo $snags; ?></li>
			</ul>
<?php
			}
		}
		if ($query2->num_rows > 0)
		{
?>
		<h3>last 25 announce actions</h3>
		<ul>
<?php
			while ($line = $query2->fetch_object())
			{
				$event = $line->event;
				$hash = $line->hash;
				$peerid = explode('-',stripslashes($line->peerid));
				$timestamp = date("m/d/Y \@ H:\x:\x",$line->timestamp);
				$xes = null;
				for ($i = 0; $i < strlen($peerid[2]); ++$i)
				{
					$xes .= "x";
				}
?>
			<li id="enable"><em><?php print $peerid[1].'-'.$xes;?></em> <?php print $event; ?> <strong><a href="?torrent=<?php print $hash; ?>"><?php print $hash; ?></a></strong> on <?php print $timestamp; ?></li>
<?php
			}
?>
		</ul>
<?php
		}
		else
		{
?>
		<h3>nothing happening</h3>
		no clients or torrents to show
<?php
		}
	}
	$db->close();
}
catch(Exception $e)
{
	errorexit("tracker is down!");
}
?>
		</div>
	</body>
</html>