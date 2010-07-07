<?php
$count = (isset($_GET['count'])) ? $_GET['count'] : 0;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
"http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
	<head profile="http://www.microformats.org/wiki/hcard-profile">
		<title>Дуга-3 // <?php print EXECUTE; ?></title>
		<meta http-equiv="cache-control" content="no-cache" />
		<meta name="robots" content="noindex, nofollow" />
<?php
$refreshpage1 = array('process','cache','update','update2');
$refreshpage2 = array('queue');
if ($_SERVER["SERVER_PORT"] == '443')
{
	$prefix = "https://";
}
else
{
	$prefix = "http://";
}
if (in_array(EXECUTE,$refreshpage1))
{
	if (REFRESHRATE > 0)
	{
?>
		<meta http-equiv="refresh" content="<?php print REFRESHRATE; ?>;<?php print $prefix; print URI; print $_SERVER['PHP_SELF']; ?>?site=<?php print SITECRAWL; ?>&execute=<?php print EXECUTE; ?>" />
<?php
	}
}
elseif (in_array(EXECUTE,$refreshpage2))
{
	if (REFRESHRATE > 0)
	{
?>
		<meta http-equiv="refresh" content="<?php print REFRESHRATE; ?>;<?php print $prefix; print URI; print $_SERVER['PHP_SELF']; ?>?site=none&execute=process" />
<?php
	}
}
?>
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
		<h1>Дуга-3 // <?php print EXECUTE; ?></h1>
		<div id="output">