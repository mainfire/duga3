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

define('MYSQLNAME',''); #mysql user name
define('MYSQLPASSWORD',''); #mysql user password
define('MYSQLSERVER',''); #mysql server
define('MYSQLBASE',''); #mysql database
define('MYSQLENGINE','MyISAM'); #mysql engine type, MyISAM should be fine
define('MYSQLCHARSET','utf8'); #utf8 is highly recommended, latin1 if utf8 is not possible

define('ANNOUNCE_TYPE','1'); #announce time: 0 to force no_peer_id, 1 for normal, 2 for compact
define('ANNOUNCE_INTERVAL','30'); #the amount of time to wait between announces
define('ANNOUNCE_ENFORCE','0'); #enforce the above announce interval by not responding to clients who announce too early / often
define('ANNOUNCE_EXPIRE','30'); #amount of time (in minutes) before the peer is considered "expired" (as in, they have no announced in this amount of time)
define('ANNOUNCE_RETURN','100'); #our default amount of peers to return on an announce request (if not specified, will be overridden by client)

define('COMPACT_SCRAPE','1'); #enable the new "compact" scrape method, though unsupported, its there
define('FULLSCRAPE','2'); #fullscrape type: 0 for false (force to ask for specific infohashes), 1 for true, 2 for true with gzip
define('SCRAPE_INTERVAL','60'); #value (in minutes) that is added onto the above ANNOUNCE_INTERVAL, you should make this double of the original value

define('LISTLOCATION','C:\web\blacklist.txt'); #location of the below list type file, this is 100% compatible with erdgeists opentracker software (example: /usr/home/kipz/blacklist or C:\web\blacklist.txt)
define('LISTTYPE','blacklist'); #blacklist (allow all torrents except the listed hashes), whitelist (block all torrents except the listed hashes), anything else will disable both

define('CLEAN_IP','127.0.0.1'); #the ip that is allowed to run the swarm cleaner (this should be set to cron locally on the same machine)

$banned_clients = array #blocks clients based on the first six characters of the specified peer_id ('peerid' => 'human readable client version')
(
	#'UT2020' => 'uTorrent 2.0.2', #in example, this would block ONLY utorrent 2.0.2
);

$banned_clients_wildcards = array #instead of using the above, you could use this to do a wildcard on all versions of a given torrent client ('peerid****' => 'human readable client')
(
	#'UT' => 'uTorrent', #in example, this would block all versions of uTorrent from connecting
); #a quick final note is to not use numbers at all: XBT would block all XBT clients, SB would block all of shad0ws clients, you get the idea.
?>