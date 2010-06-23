<?php
#return each keys length by adding to the filesize (global var located in process.php)
function add_length($value, $key)
{
	global $filesize;
	if ($key == 'length')
	{
		$filesize += $value;
	}
}

#convert our announce and infohash into a scrape url
function ann2scr($str,$str2,$type)
{
	$annslash = strrpos($str,"/");
	if ($annslash)
	{
		$annpart = substr($str,$annslash);
		if (strpos($annpart,"announce"))
		{
			if ($type == 1)
			{
				return substr($str,0,$annslash)."/".str_replace($annpart,"announce","scrape")."?info_hash=".urlencode(hex2bin($str2));
			}
			else
			{
				return substr($str,0,$annslash)."/".str_replace($annpart,"announce","scrape");
			}
		}
	}
}

#updated base32 function, relicensed under the PHP license as noted here (and copyright 2001 the PHP group): http://cvs.moodle.org/moodle/lib/base32.php?revision=1.5
function base32_encode($string)
{
	$base32_array = array
	(
		'00000' => 0x61,
		'00001' => 0x62,
		'00010' => 0x63,
		'00011' => 0x64,
		'00100' => 0x65,
		'00101' => 0x66,
		'00110' => 0x67,
		'00111' => 0x68,
		'01000' => 0x69,
		'01001' => 0x6a,
		'01010' => 0x6b,
		'01011' => 0x6c,
		'01100' => 0x6d,
		'01101' => 0x6e,
		'01110' => 0x6f,
		'01111' => 0x70,
		'10000' => 0x71,
		'10001' => 0x72,
		'10010' => 0x73,
		'10011' => 0x74,
		'10100' => 0x75,
		'10101' => 0x76,
		'10110' => 0x77,
		'10111' => 0x78,
		'11000' => 0x79,
		'11001' => 0x7a,
		'11010' => 0x32,
		'11011' => 0x33,
		'11100' => 0x34,
		'11101' => 0x35,
		'11110' => 0x36,
		'11111' => 0x37,
	);
	$output = null;
	$compute = null;
	for ($i = 0; $i < strlen($string); $i++)
	{
		$compute .= str_pad(decbin(ord(substr($string,$i,1))),8,'0',STR_PAD_LEFT);
	}
	if ((strlen($compute) % 5) != 0)
	{
		$compute = str_pad($compute,strlen($compute)+(5-(strlen($compute)%5)),'0',STR_PAD_RIGHT);
	}
	$explode = explode("\n",rtrim(chunk_split($compute,5,"\n")));
	foreach ($explode as $piece)
	{
		$output .= chr($base32_array[$piece]);
	}
	return strtoupper($output);
}

#check to see if our folders are chmodded properly
function check_permissions($directory)
{
	if (strtoupper(substr(PHP_OS,0,3)) != 'WIN')
	{
		if (substr(sprintf('%o',fileperms($directory)),-4) != '0777')
		{
			die('Looks like <em>'.$directory.'</em> needs its <code>chmod</code> changed to <strong><u>0777</u></strong>.<br />In order for Duga-3 to function properly, we need to be able to read and write to both the <strong>LIBROOT</strong> and <strong>WEBROOT</strong> folders.<br /><code>chmod</code> ONLY the folders themselves, <strong><u>DO NOT SET ANY DUGA-3 FILES TO 0777</u></strong>.<br /><br /><a href="'.$_SERVER["REQUEST_URI"].'">Retry</a>...');
		}
	}
}

#this will fetch a given url and write the reponse to a given file
function curl_fetch($url,$file,$referrer,$proxyrequest,$errorcode)
{
	$fetch_start_close = time() + CURLTIMEOUT;
	do
	{
		if ($proxyrequest > 0)
		{
			$url = PROXYURLPREFIX.''.$url.''.PROXYURLSUFFIX;
		}
		$writefile = fopen($file,"w");
		while (!flock($writefile,LOCK_EX))
		{
			usleep(round(rand(0,100)*1000));
		}
		$curlhandle = curl_init($url);
		if (SPOOFREFERRER == 1 && $referrer != 0 || $referrer != "none")
		{
			$get_options = array
			(
				CURLOPT_CONNECTTIMEOUT => CURLWAITTIMEOUT,
				CURLOPT_ENCODING => "",
				CURLOPT_FILE => $writefile,
				CURLOPT_HEADER => false,
				CURLOPT_REFERER => $plugins[$referrer]['PLUGINURL'],
				CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3",
			);
		}
		else
		{
			$get_options = array
			(
				CURLOPT_CONNECTTIMEOUT => CURLWAITTIMEOUT,
				CURLOPT_ENCODING => "",
				CURLOPT_FILE => $writefile,
				CURLOPT_HEADER => false,
				CURLOPT_USERAGENT => "Duga-3",
			);
		}
		curl_setopt_array($curlhandle,$get_options);
		curl_exec($curlhandle);
		flock($writefile,LOCK_UN);
		fclose($writefile);
		if ($errorcode == 1)
		{
			$curlerrorcode = curl_getinfo($curlhandle,CURLINFO_HTTP_CODE);
		}
		curl_close($curlhandle);
		if (time() == $fetch_start_close || time() > $fetch_start_close)
		{
			break;
		}
		else
		{
			if (DEBUGGING == 1)
			{
				print "<pre>";
				print print_r(curl_error($curlhandle));
				print "<br />";
				print print_r(curl_getinfo($curlhandle));
				print "</pre>";
			}
			if ($errorcode == 1)
			{
				return $curlerrorcode;
			}
		}
	}
	while (0);
}

#this will post a given array to a given url and write the response to a given file
function curl_post($url,$file,$array)
{
	$writefile = fopen($file,"w");
	while (!flock($writefile,LOCK_EX))
	{
		usleep(round(rand(0,100)*1000));
	}
	$curlhandle = curl_init($url);
	curl_setopt($curlhandle,CURLOPT_MAXREDIRS,MAXREDIRECTS);
	curl_setopt($curlhandle,CURLOPT_FOLLOWLOCATION,1);
	curl_setopt($curlhandle,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($curlhandle,CURLOPT_POST,1);
	curl_setopt($curlhandle,CURLOPT_POSTFIELDS,$array);
	curl_setopt($curlhandle,CURLOPT_USERAGENT,'Duga-3');
	curl_setopt($curlhandle,CURLOPT_TIMEOUT,CURLTIMEOUT);
	curl_setopt($curlhandle,CURLOPT_CONNECTTIMEOUT,CURLWAITTIMEOUT);
	curl_setopt($curlhandle,CURLOPT_FILE,$writefile);
	curl_setopt($curlhandle,CURLOPT_ENCODING,"");
	curl_exec($curlhandle);
	if (DEBUGGING == 1)
	{
		print "<pre>";
		print print_r(curl_error($curlhandle));
		print "<br />";
		print print_r(curl_getinfo($curlhandle));
		print "</pre>";
	}
	flock($writefile,LOCK_UN);
	fclose($writefile);
	curl_close($curlhandle);
}

#decode and return certain values from a single scrape
function decode_scrape($rawdata,$return,$infohash)
{
	if ($rawdata != false)
	{
		$infohash2 = hex2bin($infohash);
		switch ($return)
		{
			case 'seeds':
				$str = (isset($rawdata['files'][$infohash2]['complete'])) ? $rawdata['files'][$infohash2]['complete'] : "0";
				return $str;
			break;
			case 'leechs':
				$str = (isset($rawdata['files'][$infohash2]['incomplete'])) ? $rawdata['files'][$infohash2]['incomplete'] : "0";
				return $str;
			break;
			case 'snags':
				$str = (isset($rawdata['files'][$infohash2]['downloaded'])) ? $rawdata['files'][$infohash2]['downloaded'] : "0";
				return $str;
			break;
		}
	}
	else
	{
		switch ($return)
		{
			case 'seeds':
				return "0";
			break;
			case 'leechs':
				return "0";
			break;
			case 'snags':
				return "0";
			break;
		}
	}
}

#this will format the bytes displayed in the footer
function format_bytes($bytes)
{
	$precision = 2;
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision).' '.$units[$pow];
}

#sometimes we need to convert our sha1 version of an infohash back into its binary format, so we use this function
function hex2bin($str)
{
	$bin = "";
	$i = 0;
	do
	{
		$bin .= chr(hexdec($str{$i}.$str{($i + 1)}));
		$i += 2;
	}
	while ($i < strlen($str));
	return $bin;
}

#return a (Vuze) comaptible magnet link
function magnet_link($string,$tracker)
{
	$infohash = base32_encode(pack("H*",$string));
	return "magnet:?xt=urn:btih:$infohash&tr=$tracker";
}

#assigns a string to a number, and uses mt_rand to decide on a string to use
function nextstring($num)
{
	switch($num)
	{
		case "1":
			return "A";
		break;
		case "2":
			return "B";
		break;
		case "3":
			return "C";
		break;
		case "4":
			return "D";
		break;
		case "5":
			return "E";
		break;
		case "6":
			return "F";
		break;
		case "7":
			return "0";
		break;
		case "8":
			return "1";
		break;
		case "9":
			return "2";
		break;
		case "10":
			return "3";
		break;
		case "11":
			return "4";
		break;
		case "12":
			return "5";
		break;
		case "13":
			return "6";
		break;
		case "14":
			return "7";
		break;
		case "15":
			return "8";
		break;
		case "16":
			return "9";
		break;
	}
}

#this will fetch a given url and write the reponse to a given file
function pecl_http_fetch($url,$file,$referrer,$proxyrequest,$errorcode)
{
	$fetch_start_close = time() + CURLTIMEOUT;
	do
	{
		if ($proxyrequest > 0)
		{
			$url = PROXYURLPREFIX.''.$url.''.PROXYURLSUFFIX;
		}
		if (SPOOFREFERRER == 1 && $referrer != 0 || $referrer != "none")
		{
			$get_options = array
			(
				compress => 1,
				connecttimeout => CURLWAITTIMEOUT,
				referer => $plugins[$referrer]['PLUGINURL'],
				useragent => "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3",
			);
		}
		else
		{
			$get_options = array
			(
				compress => 1,
				connecttimeout => CURLWAITTIMEOUT,
				useragent => "Duga-3",
			);
		}
		$writefile = fopen($file,"w");
		while (!flock($writefile,LOCK_EX))
		{
			usleep(round(rand(0,100)*1000));
		}
		$request = new HttpRequest($url,HTTP_METH_GET,$get_options);
		try
		{
			$request->send(); #dont remove this from within the try { brackets }, this is to prevent exceptions from killing execution (404's and the like are handled elsewhere)
		}
		catch (HttpException $e)
		{
		}
		fwrite($writefile,$request->getResponseBody());
		flock($writefile,LOCK_UN);
		fclose($writefile);
		if ($errorcode == 1)
		{
			$requesterrorcode = $request->getResponseCode();
		}
		if (time() == $fetch_start_close || time() > $fetch_start_close)
		{
			break;
		}
		else
		{
			if ($errorcode == 1)
			{
				return $requesterrorcode;
			}
		}
	}
	while (0);
}

#TODO
function queue_check($file,$string)
{
	if (!file_exists($file) || filesize($file) == 0)
	{
		$initial = 0;
	}
	else
	{
		$initial = unserialize(file_get_contents($file));
	}
	if ($initial == 0 || !is_array($initial))
	{
		return 0;
	}
	else
	{
		if (isset($initial[$string]))
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
}

#TODO
function queue_array_save($file,$array,$mode)
{
	if (!file_exists($file) || filesize($file) == 0)
	{
		$initial = 0;
	}
	else
	{
		$initial = unserialize(file_get_contents($file));
	}
	$serialize = fopen($file,"w");
	while (!flock($serialize,LOCK_EX))
	{
		usleep(round(rand(0,100)*1000));
	}
	if ($initial == 0 || !is_array($initial))
	{
		$newarray = $array;
	}
	else
	{
		if ($mode == 1)
		{
			$difference = array_diff($initial,$array);
			$newarray = array_merge($difference,$array);
			if (SHUFFLEQUEUED == 1)
			{
				shuffle_with_keys($newarray);
			}
		}
		elseif ($mode == 2)
		{
			if (isset($initial[$array]))
			{
				unset($initial[$array]);
			}
			$newarray = $initial;
		}
	}
	fwrite($serialize,serialize($newarray));
	flock($serialize,LOCK_UN);
	fclose($serialize);
	if ($mode == 2)
	{
		return $size;
	}
}

function shuffle_with_keys(&$array)
{
	$aux = array();
	$keys = array_keys($array);
	srand((float)microtime()*10000); #seed the below shuffle
	shuffle($keys);
	foreach($keys as $key)
	{
		$aux[$key] = $array[$key];
		unset($array[$key]);
	}
	$array = $aux;
}

#recursively delete directories
function recursive_rmdir($dir)
{
	$files = glob($dir.'*',GLOB_MARK);
	foreach($files as $file)
	{
		if(is_dir($file))
		{
			recursive_rmdir($file);
		}
		else
		{
			unlink($file);
		}
	}
	if (is_dir($dir))
	{
		rmdir($dir);
	}
}

#this _will_ correct any "stupid" urls that torrent sites tend to use (ie: using spaces instead of +)
function RFC3986url($string)
{
	$entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
	$replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
	return str_replace($entities, $replacements, urlencode($string));
}

#for sanity, I made the script able to do directories somewhat based on the torrents infohash, to lessen the sheer amount of torrents residing in a single directory
#the trick works like this: send a string through this function (ie: asdfjklqwertyuiop), and it will be returned as as/df
function torrent_location($str)
{
	$directory1 = substr($str,0,2);
	$directory2 = substr($str,2,2);
	return $directory1.'/'.$directory2;
}
?>