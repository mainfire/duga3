<?php
#i'd like to refer to this file as the switchboard file rather than the request file
#licensed under the new bsd license
require_once("config.php");
ini_set("max_execution_time",EXECTIMEOUT);
ini_set("memory_limit",MEMORYLIMIT."M");
if (extension_loaded('http'))
{
	ini_set("http.force_exit",0);
}
error_reporting(DEBUGGINGERRORS);
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']))
{
	header('WWW-Authenticate: Basic realm="duga-3"');
	header('HTTP/1.0 401 Unauthorized');
	die();
}
else
{
	$username = (isset($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER'] : false;
	$password = (isset($_SERVER['PHP_AUTH_PW'])) ? $_SERVER['PHP_AUTH_PW'] : false;
	if (!array_key_exists($username,$admin_users))
	{
		print "<html><head><title>Forbidden 1</title></head><body><h2>Forbidden 1</h2></body></html>";
		die();
	}
	else
	{
		if ($password != $admin_users[$username])
		{
			print "<html><head><title>Forbidden 2</title></head><body><h2>Forbidden 2</h2></body></html>";
			die();
		}
		else
		{
			header('X-Powered-By: Duga-3 '.VERSION);
			$execute = (isset($_GET['execute'])) ? $_GET['execute'] : false;
			$sitecrawl = (isset($_GET['site'])) ? $_GET['site'] : false;
			if ($sitecrawl != false && $execute != false)
			{
				define('EXECUTE',$execute);
				define('SITECRAWL',$sitecrawl);
				switch($execute)
				{
					case 'cache':
						require_once 'torrage.php';
					break;
					case 'delete':
						require_once 'delete.php';
					break;
					case 'export':
						require_once 'export.php';
					break;
					case 'logout':
						unset($_SERVER['PHP_AUTH_USER']);
						unset($_SERVER['PHP_AUTH_PW']);
						require_once "header.php";
						print "<h2>You are almost logged out</h2>";
						print "<p>Due to the implementation of the current authentication system, you will need to close your browser to completely logout.</p>";
						print "</div></body></html>";
						die();
					break;
					case 'nuke':
						require_once 'clean.php';
					break;
					case 'process':
						require_once 'process.php';
					break;
					case 'queue':
						require_once 'queue.php';
					break;
					case 'smoke':
						require_once 'clean.php';
					break;
					case 'stats':
						require_once 'stats.php';
					break;
					case 'update':
						require_once 'update.php';
					break;
					case 'update2':
						require_once 'update2.php';
					break;
					case 'update3':
						require_once 'update3.php';
					break;
					case 'upload':
						require_once 'upload.php';
					break;
					default:
						require_once 'front.php';
					break;
				}
				die();
			}
			else 
			{
				require_once 'front.php';
				die();
			}
		}
	}
}
?>