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
define('ANNOUNCE_ENFORCE','1'); #enforce the above announce interval by not responding to clients who announce too early / often
define('ANNOUNCE_EXPIRE','30'); #amount of time (in minutes) before the peer is considered "expired" (as in, they have no announced in this amount of time)
define('ANNOUNCE_RETURN','100'); #our default amount of peers to return on an announce request (if not specified, will be overridden by client)

define('FULLSCRAPE','2'); #fullscrape type: 0 for false (force to ask for specific infohashes), 1 for true, 2 for true with gzip
define('SCRAPE_INTERVAL','60'); #value (in minutes) that is added onto the above ANNOUNCE_INTERVAL, you should make this double of the original value

define('LISTTYPE','blacklist'); #blacklist (allow all torrents except the listed hashes), whitelist (block all torrents except the listed hashes), anything else will disable both

define('KEEP_HISTORY','1'); #this is a function that is completely optional and doesnt directly affect the peer swarm in any way, but will adversely affect the scrape values:
#if disabled, this will return the "downloads" scrape value as 0 for all torrents with faster processing (one less mysql query that involves more counting)
#if enabled, it will show the amount of downloads as it should, at the cost of extra cpu power with a multitude of torrents (keeps a full history of all peer instances)

$infohash_list = array #specify (uppercase) infohash strings to white / blacklist
(
);
?>