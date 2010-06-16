<?php
$time_start = microtime(true);
require_once("functions.php");
require_once("header.php");
if(SITECRAWL != "none" && !array_key_exists(SITECRAWL,$plugins))
{
	die('<p>no site? no plugin? <a href="'.$SERVER['URI'].'">retry</a> or <a href=index.php>return</a>...</p>');
	require_once("footer.php");
}
try #batter up
{
	if (COPYTORRENT == 1)
	{
		$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE); #open connection to database
		if (SITECRAWL != "none")
		{
			$exportfile = WEBROOT.'/duga3-'.SITECRAWL.'.xml'; #export file
			$exportfile2 = WEBROOT.'/duga3-'.SITECRAWL.'-trackers.xml';
			$result =  $db->query("select * from processed where site = ".SITECRAWL." order by timestamp desc limit ".EXPORTMAX); #query database
		}
		else
		{
			$exportfile = WEBROOT.'/duga3.xml'; #export file
			$exportfile2 = WEBROOT.'/duga3-trackers.xml';
			$result =  $db->query("select * from processed order by timestamp desc limit ".EXPORTMAX); #query database
		}
		$result2 =  $db->query("select * from trackers order by torrents desc");
		$writexml = fopen($exportfile,"w"); #start the writing process
		$exportheader = '<?xml version="1.0" encoding="UTF-8"?>
<torrents type="array">';
		$exportfooter ='</torrents>';
		fwrite($writexml, $exportheader); #write the header to file
		if ($result->num_rows > 0) #carry on if we can
		{
			while ($line = $result->fetch_object()) #take each entry and interpret
			{
				$xmlurl = $line->torrage; #torrage location
				$xmlname = $line->name; #torrent name
				$xmlname = str_replace('&','&amp;',$xmlname);
				$xmlname = str_replace('<','&lt;',$xmlname);
				$xmlname = str_replace("'",'&apos;',$xmlname);
				$xmlname = str_replace('>','&gt;',$xmlname);
				$exportitem = '
	<torrent>
		<title>'.$xmlname.'</title>
		<created>'.$line->created.'</created>
		<crawled>'.$line->timestamp.'</crawled>
		<tracker>'.$line->tracker.'</tracker>
		<seeds>'.$line->seeds.'</seeds>
		<leechs>'.$line->leechs.'</leechs>
		<downloads>'.$line->snags.'</downloads>
		<infohash>'.strtoupper($line->hash).'</infohash>
		<torrent_location>'.$xmlurl.'</torrent_location>
		<torrent_metasize>'.$line->size.'</torrent_metasize>
	</torrent>';
				fwrite($writexml, $exportitem); #write the node
			}
		}
		fwrite($writexml, $exportfooter);
		fclose($writexml); #close
		$writexml2 = fopen($exportfile2,"w"); #start the writing process
		$exportheader2 = '<?xml version="1.0" encoding="UTF-8"?>
<trackers type="array">';
		$exportfooter2 ='</trackers>';
		fwrite($writexml2, $exportheader2); #write the header to file
		if ($result2->num_rows > 0) #carry on if we can
		{
			while ($line2 = $result2->fetch_object()) #take each entry and interpret
			{
				$tracker = $line2->announce;
				$tracker = str_replace('&','&amp;',$tracker);
				$tracker = str_replace('<','&lt;',$tracker);
				$tracker = str_replace("'",'&apos;',$tracker);
				$tracker = str_replace('>','&gt;',$tracker);
				$exportitem2 = '
	<tracker>
		<announce>'.$tracker.'</announce>
		<lastseen>'.$line2->timestamp.'</lastseen>
		<torrents>'.$line2->torrents.'</torrents>
	</tracker>';
				fwrite($writexml2, $exportitem2); #write the node
			}
		}
		fwrite($writexml2, $exportfooter2);
		fclose($writexml2); #close		
		print "<p>wrote 2 new xml files in the FEEDSROOT folder, <a href=index.php>finish</a>.</p>"; #done
		$db->close(); #close database
	}
	else
	{
		print "<p><strong>COPYTORRENT</strong> needs to be set to 1 for this to work, <a href=index.php>finish</a>.</p>"; #done
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