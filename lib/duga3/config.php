<?php
###############################################################
################DO NOT MODIFY BELOW HERE#######################
###############################################################
#keeps this file from being loaded by a browser
if (preg_match("/config.php/i",$_SERVER['PHP_SELF'])) 
{
	die();
}
###############################################################
################DO NOT MODIFY ABOVE HERE#######################
###############################################################

define('URI','kipz.irc.su'); #you need to change this to your hostname, ie some.web.site.com

###############################################################
################DO NOT MODIFY BELOW HERE#######################
###############################################################
#attempt to stop remote inclusion exploitation
if ($_SERVER['SERVER_NAME'] != URI)
{
	die();
}
###############################################################
################DO NOT MODIFY ABOVE HERE#######################
###############################################################

$admin_users = array # user => password (allows multiple users)
(
	'some_user' => 'some_password',
	#'another_user' => 'another_password',
);

define('MYSQLNAME',''); #mysql user name
define('MYSQLPASSWORD',''); #mysql user password
define('MYSQLSERVER',''); #mysql server
define('MYSQLBASE',''); #mysql database
define('MYSQLENGINE','MyISAM'); #mysql engine type, MyISAM should be fine
define('MYSQLCHARSET','utf8'); #utf8 is highly recommended, latin1 if utf8 is not possible

define('FOLDER','tor'); #torrent folder (resides in the below WEBROOT folder); example: tor
define('SYMLINKFOLDER','get'); #symlinks folder (resides in the above FOLDER folder); example: get (SYMLINKTORRENT must be set to 1, see define's note)
define('CACHEFOLDER','cache'); #cache folder (resides in the below LIBROOT folder); example: cache
define('WEBROOT','/home/kipz/public_html/'); #bot root; example: /var/www/crawler/bot or C:/www/crawler/bot
define('LIBROOT','/home/kipz/public_html/lib/duga3'); #folder in lib; example: /var/www/crawler/lib/duga3 or C:/www/crawler/lib/duga3

define('CACHEMAX','50'); #max entires to send to torrage in one run
define('EXPORTMAX','100'); #max entires to export
define('PROCESSMAX','50'); #max entires to process
define('QUEUEMAX','1000'); #max entires to queue
define('UPDATEMIN','1000'); #minimum amount of torrents a tracker needs to qualify for a fullscrape
define('UPDATEMAX','50'); #max entries to update (single scrapes, not fullscrapes)

define('SHUFFLEQUEUED','1'); #shuffle the queued array so we dont ask for so many torrents at once from a single site

define('CURLMETHOD','1'); #1 for default barebones cURL (faster, more reliable, less extendable), 2 fir the pecl http of cURL (slower, less reliable, no built in write function, more extendable)
define('COPYTORRENT','0'); #make a copy of the torrent within the FOLDER directory (ie: example.com/tor/83/E1/83E1246H...torrent). NOTE: you need this enabled to cache torrents
#Windows users: for the below function to work right, you must not be on any versions of Windows below Vista
define('SYMLINKTORRENT','0'); #due to the above folders layout, you may want to symlink these torrents within a get/ folder in the FOLDER directory (ie: example.com/tor/get/83E1246H... => example.com/tor/83/E1/83E1246H...torrent)
define('TORRAGE','http://torrage.com'); #the site we want to cache our torrents to, must be a torrage based site (torcache.com is a known alternative)

define('EXECTIMEOUT','595'); #when should we kill the scripts execution if it doesnt respond fast enough
define('CURLTIMEOUT','2'); #max amount of time it takes for curl begin execution
define('CURLWAITTIMEOUT','3'); #max number of seconds to wait for initial response connection, leave this low too
define('SLEEPER','0'); #time (in seconds) to wait after processing a url (of any kind)

define('SPOOFREFERRER','0'); #does some spoofing by resetting the referrer to the plugins xml url, and the user-agent to firefox on windows 7
define('MAXREDIRECTS','5'); #max "depth" of redirects; duga3 will not follow any more than X redirect urls (useful for keeping php under control)
define('REFRESHRATE','15'); #amount of time to take before automatically refreshing certain pages like queueing, processing, updating, etc, after X amount of seconds (set to 0 to disable)
define('MEMORYLIMIT','2048'); #attempt to raise the memory limit without setting it in php.ini, this value represents megabytes

define('BZIP2COMPRESSION','9'); #the compession ratio "quality", the higher (max: 9) the number the more compression used, the less (min: 1) the number the less compression used
define('BZIP2WORKFACTOR','50'); #numeric value betwen 0 and 250, controls the compression phase on large repetitve tasks

define('SCRAPEREAD','50000'); #the default "per line", or single amount of characters to read in a given buffer, in a fullscrape
define('MAXSCRAPEATTEMPTS','5'); #we will give a given torrents five chances to be scraped, not during crawling, but when it is rescraped
define('ANNOUNCE_RESET','http://tracker.openbittorrent.com/announce'); #this is important, the tracker specified here is what replaces any (former) tpb trackers
$announce_array_reset = array #same as above, this will reset the announce list reguardless of whats in there already
(
	"http://tracker.publicbt.com/announce",
	"http://tracker.openbittorrent.com/announce",
	"udp://tracker.publicbt.com:80/announce",
	"udp://tracker.openbittorrent.com:80/announce",
	"http://tracker.ilibr.org:6969/announce",
	"http://announce.opensharing.ru:2710/announce",
	"http://www.thepeerhub.com/announce.php",
	"http://denis.stalker.h3q.com:6969/announce",
	"http://free.btr.kz:8888/announce",
	"http://tracker.bitreactor.to:2710/announce",
	"http://kubanmedia.org:2710/announce",
);
$announce_blacklist = array #specify announce urls that will be replaced with ANNOUNCE_RESET
(
	"http://tracker.thepiratebay.org/announce",
	"http://tracker.thepiratebay.org:80/announce",
	"http://open.tracker.thepiratebay.org/announce",
	"http://open.tracker.thepiratebay.org:80/announce",
	"http://a.tracker.thepiratebay.org/announce",
	"http://a.tracker.thepiratebay.org:80/announce",
	"http://tpb.tracker.thepiratebay.org/announce",
	"http://tpb.tracker.thepiratebay.org:80/announce",
	"http://vip.tracker.thepiratebay.org/announce",
	"http://vip.tracker.thepiratebay.org:80/announce",
	"http://vip.tracker.prq.to:80/announce",
	"http://vip.tracker.prq.to/announce",
	"http://red.tracker.prq.to:80/announce",
	"http://red.tracker.prq.to/announce",
	"http://tracker.prq.to/announce",
	"http://tracker.prq.to:80/announce",
	"udp://tracker.thepiratebay.org/announce",
	"udp://tracker.thepiratebay.org:80/announce",
	"udp://open.tracker.thepiratebay.org/announce",
	"udp://open.tracker.thepiratebay.org:80/announce",
	"udp://a.tracker.thepiratebay.org/announce",
	"udp://a.tracker.thepiratebay.org:80/announce",
	"udp://tpb.tracker.thepiratebay.org/announce",
	"udp://tpb.tracker.thepiratebay.org:80/announce",
	"udp://vip.tracker.thepiratebay.org/announce",
	"udp://vip.tracker.thepiratebay.org:80/announce",
	"udp://vip.tracker.prq.to:80/announce",
	"udp://vip.tracker.prq.to/announce",
	"udp://red.tracker.prq.to:80/announce",
	"udp://red.tracker.prq.to/announce",
	"udp://tracker.prq.to/announce",
	"udp://tracker.prq.to:80/announce",
);

#below is our plugin array
$plugins = array
(
	'1337x' => array
	(
		'PLUGINNAME' => '1337x',
		'PLUGINXML' => 'RSS',
		'PLUGINURL' => 'http://1337x.org/rss/cat/all/',
		'PLUGINNODETAG' => 'enclosure',
		'PLUGINNODETAG2' => 'url',
		'PLUGINHACKS' => '1',
		'PLUGINHACKSEVAL' => '$finalurl = $node->getAttribute($currentplugin["PLUGINNODETAG2"]);',
		'PLUGINPROXY' => '0',
		'PLUGINPROXYSCRAPE' => '0',
	),
	'btjunkie' => array
	(
		'PLUGINNAME' => 'btjunkie',
		'PLUGINXML' => 'RSS',
		'PLUGINURL' => 'http://btjunkie.org/rss.xml?c=1',
		'PLUGINNODETAG' => 'item',
		'PLUGINNODETAG2' => 'guid',
		'PLUGINHACKS' => '1',
		'PLUGINHACKSEVAL' => '$url2 = str_replace(SITECRAWL,"dl.btjunkie",$url1); $finalurl = $url2."/download.torrent";',
		'PLUGINPROXY' => '0',
		'PLUGINPROXYSCRAPE' => '0',
	),
	'clearbits' => array
	(
		'PLUGINNAME' => 'clearbits',
		'PLUGINXML' => 'RSS',
		'PLUGINURL' => 'http://www.clearbits.net/rss.xml',
		'PLUGINNODETAG' => 'enclosure',
		'PLUGINNODETAG2' => 'url',
		'PLUGINHACKS' => '1',
		'PLUGINHACKSEVAL' => '$finalurl = $node->getAttribute($currentplugin["PLUGINNODETAG2"]);',
		'PLUGINPROXY' => '0',
		'PLUGINPROXYSCRAPE' => '0',
	),
	'isohunt' => array
	(
		'PLUGINNAME' => 'isohunt',
		'PLUGINXML' => 'Atom',
		'PLUGINURL' => 'http://isohunt.com/js/rss/?iht=',
		'PLUGINNODETAG' => 'enclosure',
		'PLUGINNODEATTRIBUTE' => 'url',
		'PLUGINHACKS' => '0',
		'PLUGINPROXY' => '0',
		'PLUGINPROXYSCRAPE' => '0',
	),
	'mininova' => array
	(
		'PLUGINNAME' => 'mininova',
		'PLUGINXML' => 'Atom',
		'PLUGINURL' => 'http://www.mininova.org/rss.xml',
		'PLUGINNODETAG' => 'enclosure',
		'PLUGINNODEATTRIBUTE' => 'url',
		'PLUGINHACKS' => '0',
		'PLUGINPROXY' => '0',
		'PLUGINPROXYSCRAPE' => '0',
	),
	'thepiratebay' => array
	(
		'PLUGINNAME' => 'thepiratebay',
		'PLUGINXML' => 'Atom',
		'PLUGINURL' => 'http://rss.thepiratebay.org/0',
		'PLUGINNODETAG' => 'enclosure',
		'PLUGINNODEATTRIBUTE' => 'url',
		'PLUGINHACKS' => '0',
		'PLUGINPROXY' => '0',
		'PLUGINPROXYSCRAPE' => '0',
	),
	'torrentbox' => array
	(
		'PLUGINNAME' => 'torrentbox',
		'PLUGINXML' => 'RSS',
		'PLUGINURL' => 'http://torrentbox.com/rssfeed.php',
		'PLUGINNODETAG' => 'item',
		'PLUGINNODETAG2' => 'link',
		'PLUGINHACKS' => '1',
		'PLUGINHACKSEVAL' => '$url2 = str_replace("<![CDATA[","",$url1); $url2 = str_replace("]]>","",$url1); $finalurl = $url2;',
		'PLUGINPROXY' => '0',
		'PLUGINPROXYSCRAPE' => '0',
	),
	'torrentportal' => array
	(
		'PLUGINNAME' => 'torrentportal',
		'PLUGINXML' => 'RSS',
		'PLUGINURL' => 'http://www.torrentportal.com/rss.xml',
		'PLUGINNODETAG' => 'item',
		'PLUGINNODETAG2' => 'link',
		'PLUGINHACKS' => '1',
		'PLUGINHACKSEVAL' => '$finalurl = str_replace("details","download",$url1);',
		'PLUGINPROXY' => '0',
		'PLUGINPROXYSCRAPE' => '0',
	),
);

define('PROXYURLPREFIX','http://www.surfy.nl/index.php?q='); #if we need to proxy a request, we will ask for it through here (prefix)
define('PROXYURLSUFFIX','&hl=c4'); #for example, glype proxies allow you to remove the bar rather than strip it out manually

define('DEBUGGING','0'); #you should leave this set at 0 unless you want the screen spammed with verbose information
define('DEBUGGINGERRORS','E_ALL'); #show php errors, useful when combined with the above (see http://www.php.net/manual/en/function.error-reporting.php#examples)

define('VERSION','06152010'); #NO SOUP FOR YOU
?>