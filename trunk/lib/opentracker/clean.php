<?php
#licensed under the new bsd license
require_once 'config.php';
try
{
	if ($_SERVER['REMOTE_ADDR'] == CLEAN_IP)
	{
		$timestamp = time();
		$db = new mysqli(MYSQLSERVER,MYSQLNAME,MYSQLPASSWORD,MYSQLBASE);
		$deadswarm = $db->query("select * from announce where expire < $timestamp");
		if ($deadswarm->num_rows > 0)
		{
			while ($line = $deadswarm->fetch_object())
			{
				$hash = $line->hash;
				$id = $line->id;
				$left = $line->remain;
				if ($left != 0)
				{
					$db->query("update history set incomplete = incomplete - 1 where match (hash) against ('\"$hash\"' IN BOOLEAN MODE) limit 1");
				}
				else
				{
					$db->query("update history set complete = complete - 1 where match (hash) against ('\"$hash\"' IN BOOLEAN MODE) limit 1");
				}
				$db->query("delete from announce where id = $id limit 1");
			}
		}
		$db->query("delete from history where expire < $timestamp");
		$db->query("optimize table announce");
		$db->query("optimize table history");
		$db->close();
	}
}
catch(Exception $e)
{
	die($e);
}
?>