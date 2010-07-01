<?php
require_once 'lib/crawler/config.php';
require_once 'lib/crawler/functions.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
"http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
	<head profile="http://www.microformats.org/wiki/hcard-profile">
		<title>Дуга-3 // search test</title>
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
		<h1>Дуга-3 // search test</h1>
		<div id="output">
<?php
try #batter up
{
	$search = (isset($_POST['search'])) ? addslashes(strip_tags($_POST['search'])) : null;
	$searchin = (isset($_POST['searchin'])) ? $_POST['searchin'] : null;
	$torrent = (isset($_GET['tid'])) ? addslashes(strip_tags($_GET['tid'])) : null;
	$query = null;
	$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
	if (is_null($torrent) && strlen($searchin) > 3 && strlen($search) > 3)
	{
		switch ($searchin)
		{
			case 'name':
				if (strlen($search) > 3)
				{
					$query = $db->query("select * from processed where match (name) against ('$search') limit 30");
				}
			break;
			default:
				if (strlen($search) > 3)
				{
					$query = $db->query("select * from processed where match (hash) against ('\"$search\"' IN BOOLEAN MODE) limit 1");
				}
			break;
		}
	}
	elseif (!is_null($torrent) && is_null($searchin) && is_null($search))
	{
		$query = $db->query("select * from processed where id = '$torrent' limit 1");
	}
	else
	{
		$stats = $db->query("select count(id) as torrents, sum(leechs) as uno, sum(seeds) as dos, sum(snags) as tres from processed");
		if ($stats->num_rows > 0)
		{
			while ($line = $stats->fetch_object())
			{
				if ($line->torrents == null)
				{
					$torrents = 0;
				}
				else
				{
					$torrents = $line->torrents;
				}
				if ($line->uno == null)
				{
					$seeds = 0;
				}
				else
				{
					$seeds = $line->uno;
				}
				if ($line->dos == null)
				{
					$leechs = 0;
				}
				else
				{
					$leechs = $line->dos;
				}
				if ($line->tres == null)
				{
					$downloads = 0;
				}
				else
				{
					$downloads = $line->tres;
				}
			}
		}
		else
		{
			$torrents = 0;
			$seeds = 0;
			$leechs = 0;
			$downloads = 0;
		}
		print "<p>currently watching over <strong>".count($plugins)."</strong> sites, while searching through <strong>$torrents</strong> torrents with <strong>$seeds</strong> seeds, <strong>$leechs</strong> leechers, and <strong>$downloads</strong> downloads.</p>";
		print "<p>";
		print "<form action=index.php method=post>";
		print "<input style=width:300px; name=search type=text /> ";
		print "<select name=searchin>";
		print "<option value=hash>hash</option>";
		print "<option value=name>name</option>";
		print "</select> ";
		print "<input value=submit type=submit />";
		print "</form>";
		print "</p>";
		
	}
	if (!is_null($query))
	{
		if ($query->num_rows > 0)
		{
			if ($query->num_rows > 1)
			{
				while ($line = $query->fetch_object())
				{
					print "<ul>";
					print "<li>name: <a href=./?site=none&execute=search&tid=".$line->id.">".$line->name."</a></li>";
					print "<li>hash: ".$line->hash."</li>";
					print "<li>size: ".format_bytes($line->size)."</li>";
					print "<li>seeds: ".$line->seeds."</li>";
					print "<li>leechs: ".$line->leechs."</li>";
					print "<li>snags: ".$line->snags."</li>";
					print "</ul>";
				}
				print "<p><a href=index.php>reset</a></p>";
			}
			else
			{
				while ($line = $query->fetch_object())
				{
					$files = unserialize(stripslashes(bzdecompress($line->files,true))); #decompress, and unserialize (convert back to PHP array)
					$trackers = unserialize(stripslashes(bzdecompress($line->trackers,true)));
					$urls = explode(";",stripslashes(bzdecompress($line->urls,true)));
					$sites = explode(";",$line->site);
					print "<ul>";
					print "<li>name: ".$line->name."</li>";
					print "<li>hash: ".substr(chunk_split($line->hash,5,"-"),0,-1)."</li>";
					print "<li>size: ".format_bytes($line->size)."</li>";
					print "<li>created: ".date("m/d/Y \@ H:i:s",$line->created)." (updated: ".date("m/d/Y \@ H:i:s",$line->timestamp).")</li>";
					if ($line->client != 0)
					{
						print "<li>created with: ".$line->client."</li>";
					}
					print "<li>seeds: ".$line->seeds."</li>";
					print "<li>leechs: ".$line->leechs."</li>";
					print "<li>snags: ".$line->snags."</li>";
					print "<li>private: ".$line->private."</li>";
					print "<li>tracker: ".$line->tracker."</li>";
					if (is_array($trackers) && count($trackers) > 0)
					{
						print "<li>trackers</li>";
						print "<ul>";
						foreach ($trackers as $key => $tracker_tier)
						{
							foreach ($tracker_tier as $key => $tracker_announce)
							{
								print "<li>$tracker_announce</li>";
							}
						}
						print "</ul>";
					}
					if (count($files) > 0)
					{
						print "<li>files</li>";
						print "<ul>";
						foreach ($files as $key => $directory)
						{
							$cur_file = implode("/",$directory['path']);
							$cur_file_size = $directory['length'];
							print "<li>$cur_file (".format_bytes($cur_file_size).")</li>";
						}
						print "</ul>";
					}
					print "<li>piece length: ".format_bytes($line->piecelength)."</li>";
					print "<li>seen on (in order)</li>";
					print "<ul>";
					for ($i = 0; $i < sizeof($urls); ++$i)
					{
						print "<li>#$i <strong>".$sites[$i]."</strong>: ".$urls[$i]."</li>";
					}
					print "</ul>";
					print "<li>magnet: ".magnet_link($line->hash,$line->tracker)."</li>";
					print "</ul>";
				}
				print "<p><a href=index.php>reset</a></p>";
			}
		}
		else
		{
			print "<p>nothing found!</p>";
			print "<p><a href=index.php>reset</a></p>";
		}
	}
	$db->close();
}
catch(Exception $e) #struck out
{
	echo '<p>something went horribly wrong!</p>';
	echo '<p>'.$e->getMessage().'</p>';
}
?>
		</div>
		<br />
	</body>
</html>