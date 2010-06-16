<?php
$time_start = microtime(true);
require_once("functions.php");
require_once("header.php");
if(SITECRAWL != "none" && !array_key_exists(SITECRAWL,$plugins))
{
	die('<p>no site? no plugin? <a href="'.$SERVER['URI'].'">retry</a> or <a href=index.php>return</a>...</p>');
	require_once("footer.php");
}
try
{
	$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
	$serialize = LIBROOT."/".CACHEFOLDER."/db.queue";
	if (!file_exists($serialize) || filesize($serialize) == 0)
	{
		$queued = array();
	}
	else
	{
		$queued = unserialize(file_get_contents($serialize));
	}
	if (SITECRAWL != "none") #if our plugin is not "none"
	{
		$query = "select count(id) as torrents, sum(leechs) as uno, sum(seeds) as dos, sum(snags) as tres from processed where match (site) against ('".SITECRAWL."')";
		$query2 = "select * from processed where cached = '1' and site = '".SITECRAWL."'";
		$query3 = "select * from processed where site = '".SITECRAWL."' order by timestamp asc limit 20";

	}
	else
	{
		$query = "select count(id) as torrents, sum(leechs) as uno, sum(seeds) as dos, sum(snags) as tres from processed";
		$query2 = "select * from processed where cached = '1'";
		$query3 = "select * from processed order by timestamp desc limit 20";
		$query4 = "select * from trackers order by timestamp desc limit 20";
	}
	if ($rows = $db->prepare($query))
	{
		$rows->execute();
		$rows->store_result();
		$num_rows = $rows->num_rows();
		$rows->close();
	}
	if ($rows2 = $db->prepare($query2))
	{
		$rows2->execute();
		$rows2->store_result();
		$num_rows2 = $rows2->num_rows();
		$rows2->close();
	}
	if ($rows3 = $db->prepare($query3))
	{
		$rows3->execute();
		$rows3->store_result();
		$num_rows3 = $rows3->num_rows();
		$rows3->close();
	}
	if ($num_rows3 > 0)
	{
		if ($result = $db->query($query))
		{
			while($line = $result->fetch_object())
			{
				if (SITECRAWL != "none")
				{
					print "<p>totals for ".SITECRAWL.":</p>";
				}
				else
				{
					print "<p>totals:</p>";
				}
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
				print "<ul>";
				print "<li>queued: ".count($queued)."</li>";
				print "<li>torrents: ".$torrents."</li>";
				print "<li>seeds: ".$seeds."</li>";
				print "<li>leechs: ".$leechs."</li>";
				print "<li>downloads: ".$downloads."</li>";
				if (COPYTORRENT == 1)
				{
					print "<li>cached: ".$num_rows3."</li>";
				}
				print "</ul>";
				if ($num_rows3 > 0)
				{
					if (SITECRAWL != "none")
					{
						print "<p>last 20 cached / processed / updated torrents for ".SITECRAWL.":</p>";
					}
					else
					{
						print "<p>last 20 cached / processed / updated torrents:</p>";
					}
					print "<ul>";
					$result2 = $db->query($query3);
					while($line2 = $result2->fetch_object())
					{
						$sites = explode(";",$line2->site);
						$count = sizeof($sites);
						print "<li>($count) <strong>".$line2->hash."</strong>: ".$line2->seeds." - ".$line2->leechs." - ".$line2->snags."</li>";
					}
					print "</ul>";
				}
				if (SITECRAWL == "none")
				{
					if ($rows4 = $db->prepare($query4))
					{
						$rows4->execute();
						$rows4->store_result();
						$num_rows4 = $rows4->num_rows();
						$rows4->close();
					}
					if ($num_rows4 > 0)
					{
						print "<p>last 20 processed / updated trackers:</p>";
						print "<ul>";
						$result4 = $db->query($query4);
						while($line4 = $result4->fetch_object())
						{
							print "<li>(".$line4->torrents.") <strong>".$line4->announce."</strong></li>";
						}
						print "</ul>";
					}
				}
				if (count($queued) > 0)
				{
					print "<p>last 20 processed / updated trackers:</p>";
					print "<ul>";
					$loopbreak = 1;
					foreach ($queued as $url => $plugin)
					{
						if ($loopbreak >= 20)
						{
							break;
						}
						print "<li>($plugin) <strong>$url</strong></li>";
						$loopbreak = $loopbreak + 1;
					}
					print "</ul>";
				}
				print "<p><a href=index.php>return</a> to index.</p>";
			}
		}
	}
	else
	{
		print "<p>nothing processed, <a href=index.php>return</a> to queue some up.</p>";
	}
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