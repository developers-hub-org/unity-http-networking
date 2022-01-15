<?php

	function getConnection($path)
	{
		if(file_exists($path . "/config.php"))
		{
			include_once ($path . "/config.php");
			$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			if(mysqli_connect_errno())
			{
				return null;
			}
			else
			{
			    mysqli_set_charset($connection, "utf8");
			    return $connection;
			}
		}
		else
		{
			return null;
		}
	}
	
?>