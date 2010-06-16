<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
"http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
	<head profile="http://www.microformats.org/wiki/hcard-profile">
		<title>Дуга-3 // <?php echo EXECUTE; ?></title>
		<meta http-equiv="cache-control" content="no-cache" />
		<meta name="robots" content="noindex, nofollow" />
<?php
$refreshpage = array('queue','process','cache','update','update2');
if (in_array(EXECUTE,$refreshpage))
{
	if (REFRESHRATE > 0)
	{
?>
		<meta http-equiv="refresh" content="<?php echo REFRESHRATE; ?>;http://<?php echo URI; echo $_SERVER['PHP_SELF']; ?>?site=<?php echo SITECRAWL; ?>&execute=<?php echo EXECUTE; ?>" />
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
		<h1>Дуга-3 // <?php echo EXECUTE; ?></h1>
		<div id="output">