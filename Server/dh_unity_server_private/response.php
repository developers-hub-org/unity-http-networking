<?php

    function get_response($connection, $json, $path)
    {

		// -> Server Checks (Do Not Remove)
		include_once ($path . "/control.php");
		// TODO : Check blocked ip here
		// <-

		switch($json->request)
		{
			case 0:
				
				// ------ This is a test: Create table --------
				$query = "CREATE TABLE IF NOT EXISTS accounts(id int(11) AUTO_INCREMENT, username varchar(255) NOT NULL, password varchar(255) NOT NULL, score int(11) DEFAULT 0, blocked int(11) DEFAULT 0, PRIMARY KEY (id))";
				mysqli_query($connection, $query);
				$query = "CREATE TABLE IF NOT EXISTS sessions(id int(11) AUTO_INCREMENT, account_id int(11), username varchar(255) NOT NULL, session varchar(50) NOT NULL, ip varchar(50) NOT NULL, version varchar(255) NOT NULL, activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id))";
			    mysqli_query($connection, $query);
			    return 'TABLE_CREATED';
				//---------------------------------------------
				
				break;
			case 1:
			    
				// ----- This is a test: Create account -------
				$query = "SELECT * FROM accounts WHERE username = '$json->username'";
			    $result = mysqli_query($connection, $query);
			    if($result && mysqli_num_rows($result) > 0)
				{
                    return 'USERNAME_EXISTS';
				}
				else
				{
				    $query = "INSERT INTO accounts(username, password, score) VALUES('$json->username','$json->password', 0)";
					mysqli_query($connection, $query);
					return 'ACCOUNT_CREATED';
				}
				//---------------------------------------------
				
				break;
			case 2:
			    
				// ---- This is a test: Get account info ------
				$query = "SELECT * FROM accounts WHERE username = '$json->username'";
			    $result = mysqli_query($connection, $query);
			    if($result && mysqli_num_rows($result) == 1)
				{
                    $info = array();
                    while($row = mysqli_fetch_assoc($result))
					{
					    $info["username"] = $row['username'];
					    $info["id"] = $row['id'];
					    $info["score"] = $row['score'];
					}
                    return $info;
				}
				else
				{
				    return 'USERNAME_NOT_EXIST';
				}
				//---------------------------------------------
				
				break;
			// Do not remove anythig below this line
			case 987650:
			case 987651:
				include_once ($path . "/core_response.php");
				return get_core_response($connection, $json, $path);
		}
		return 'NULL';
    }

	function get_user_ip()
	{
		if(isset($_SERVER["HTTP_CF_CONNECTING_IP"]))
		{
			$_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			$_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
		}
		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];
		if(filter_var($client, FILTER_VALIDATE_IP))
		{
			$ip = $client;
		}
		else if(filter_var($forward, FILTER_VALIDATE_IP))
		{
			$ip = $forward;
		}
		else
		{
			$ip = $remote;
		}
		return $ip;
	}
	
?>
