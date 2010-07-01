<?php
#licensed under the new bsd license
$time_start = microtime(true);
require_once("functions.php");
check_permissions(LIBROOT.'/'.CACHEFOLDER);
if (COPYTORRENT == 1)
{
	check_permissions(WEBROOT.'/'.FOLDER);
}
require_once("header.php");
if(!array_key_exists(SITECRAWL,$plugins))
{
	die('<p>no site? no plugin? <a href="'.$SERVER['URI'].'">retry</a> or <a href=index.php>return</a>...</p>');
	require_once("footer.php");
}
try
{
	$currentplugin = $plugins[SITECRAWL];
	$loopbreak = 0;
	$timestamp = time();
	$serialize = LIBROOT."/".CACHEFOLDER."/db.queue";
	$queuedarray = array();
	$tempxml = LIBROOT."/".CACHEFOLDER."/".$timestamp.".xml";
	$url = $currentplugin['PLUGINURL'];
	$proxyrequest = $currentplugin['PLUGINPROXY'];
	if (extension_loaded('http') && CURLMETHOD == 2)
	{
		pecl_http_fetch($url,$tempxml,0,$proxyrequest,0);
	}
	else
	{
		curl_fetch($url,$tempxml,0,$proxyrequest,0);
	}
	if (!file_exists($tempxml) || filesize($tempxml) == 0)
	{
		print "<p>couldnt fetch the following feed: <strong>$url</strong> - <a href=index.php>return</a>.</p>";
	}
	else
	{
		switch ($currentplugin['PLUGINHACKS'])
		{
			case 1;
				$xml = new DOMDocument();
				$xml->load($tempxml);
			break;
			case 2:
				$stringify = file_get_contents($tempxml);
				$stringify = utf8_encode(html_entity_decode($stringify));
				$xml = new DOMDocument();
				$xml->loadXML($stringify);
			break;
			case 3:
				$stringify = file_get_contents($tempxml);
				$stringify = utf8_encode(html_entity_decode($stringify));
				$xml = new DOMDocument();
				$xml->loadXML($stringify);
			break;
			default:
				$xml = new DOMDocument();
				$xml->load($tempxml);
			break;
		}
		$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
		if ($currentplugin['PLUGINXML'] == 'Atom')
		{
			foreach($xml->getElementsByTagName($currentplugin['PLUGINNODETAG']) as $node) 
			{
				if ($loopbreak == QUEUEMAX)
				{
					break;
				}
				$url1 = RFC3986url($node->getAttribute($currentplugin['PLUGINNODEATTRIBUTE']));
				if ($currentplugin['PLUGINHACKS'] == 1 || $currentplugin['PLUGINHACKS'] == 3)
				{
					eval($currentplugin['PLUGINHACKSEVAL']);
				}
				else
				{
					$finalurl = $url1;
				}
				if (!is_null($finalurl) && filter_var($finalurl,FILTER_VALIDATE_URL))
				{
					$alreadyqueued = queue_check($serialize,$finalurl);
					$query = $db->query("select * from processed where match (url) against ('\"$finalurl\"' IN BOOLEAN MODE) limit 1");
					if ($alreadyqueued > 0)
					{
						print "<p>skipped <strong>$finalurl</strong>, already queued...</p>";
					}
					elseif ($query->num_rows > 0)
					{
						print "<p>skipped <strong>$finalurl</strong>, already processed...</p>";
					}
					else
					{
						$addition = array
						(
							$finalurl => SITECRAWL,
						);
						$queuedarray = array_merge($queuedarray,$addition);
						$loopbreak = $loopbreak + 1;
						print "<p>queued <strong>$finalurl</strong>...</p>";
					}
				}
			}
		}
		elseif ($currentplugin['PLUGINXML'] == 'RSS')
		{
			foreach($xml->getElementsByTagName($currentplugin['PLUGINNODETAG']) as $node)
			{
				if ($loopbreak == QUEUEMAX)
				{
					break;
				}
				$urls = $node->getElementsByTagName($currentplugin['PLUGINNODETAG2']);
				$url1 = RFC3986url($urls->item(0)->nodeValue);
				if ($currentplugin['PLUGINHACKS'] == 1 || $currentplugin['PLUGINHACKS'] == 3)
				{
					eval($currentplugin['PLUGINHACKSEVAL']);
				}
				elseif ($currentplugin['PLUGINTORRAGE'] == 1)
				{
					$finalurl = "http://torrage.com/torrent/".strtoupper($url1);
				}
				else
				{
					$finalurl = $url1;
				}
				if (!is_null($finalurl) && filter_var($finalurl,FILTER_VALIDATE_URL))
				{
					$alreadyqueued = queue_check($serialize,$finalurl);
					$query = $db->query("select * from processed where match (url) against ('\"$finalurl\"' IN BOOLEAN MODE) limit 1");
					if ($alreadyqueued > 0)
					{
						print "<p>skipped <strong>$finalurl</strong>, already queued...</p>";
					}
					elseif ($query->num_rows > 0)
					{
						print "<p>skipped <strong>$finalurl</strong>, already processed...</p>";
					}
					else
					{
						$addition = array
						(
							$finalurl => SITECRAWL,
						);
						$queuedarray = array_merge($queuedarray,$addition);
						$loopbreak = $loopbreak + 1;
						print "<p>queued <strong>$finalurl</strong>...</p>";
					}
				}
			}
		}
		queue_array_save($serialize,$queuedarray,1);
		$db->query("optimize table processed");
		$db->close();
		print "<p><a href=?site=none&execute=process>process</a>, or <a href=index.php>finish</a>....</p>";
	}
	if (file_exists($tempxml))
	{
		unlink($tempxml);
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