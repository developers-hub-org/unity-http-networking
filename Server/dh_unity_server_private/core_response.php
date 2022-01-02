<?php

    function get_core_response($connection, $json, $path)
    {

		// -> Server Checks
		include_once ($path . "/control.php");
		// TODO : Check blocked ip here
		// <-

		switch($json->request)
		{
			case 987650: // Authenticate User
				$query = "SELECT id, password, blocked FROM accounts WHERE username = '$json->username'";
			    $result = mysqli_query($connection, $query);
			    if($result && mysqli_num_rows($result) == 1)
				{
					$password = "";
					$blocked = 1;
					$account_id = 0;
                    while($row = mysqli_fetch_assoc($result))
					{
						$password = $row['password'];
						$blocked = $row['blocked'];
						$account_id = $row['id'];
					}
                    if($password == $json->password)
					{

						// Check if the account has been suspended
						if($blocked > 0)
						{
							return 'BLOCKED';
						}

						// Check if there is other devices are online using this account
						$ip = get_user_ip();
						if(ALLOW_MULTIPLE_ONLINE_SESSIONS == false)
						{
							$period = CONNECTION_CHECK_PERIOD + 5;
							$query = "SELECT id FROM sessions WHERE username = '$json->username' AND ip <> '$ip' AND activity >= CURRENT_TIMESTAMP - INTERVAL $period SECOND";
							$result = mysqli_query($connection, $query);
							if($result && mysqli_num_rows($result) > 0)
							{
								return 'ANOTHER_SESSION_IS_ONLINE';
							}
						}

						// If there is a session for this ip then use it otherwise create a new session
						$query = "SELECT id FROM sessions WHERE username = '$json->username' AND ip = '$ip'";
						$result = mysqli_query($connection, $query);
						if($result && mysqli_num_rows($result) > 0)
						{
							// Use an existing session
							$id = 0;
							while($row = mysqli_fetch_assoc($result))
							{
								$id = $row['id'];
							}
							$query = "UPDATE sessions SET session = '$json->session', version = '$json->version' WHERE id = $id";
							mysqli_query($connection, $query);
						}
						else
						{
							// Create a new session
							$query = "INSERT INTO sessions (account_id, username, session, ip, version) VALUES($account_id , '$json->username', '$json->session', '$ip', '$json->version')";
							mysqli_query($connection, $query);
						}
						$auth = array();
						$auth["message"] = 'SUCCESSFUL';
						$auth["sync_period"] = CONNECTION_CHECK_PERIOD;
                    	return $auth;
					}
					else
					{
						return 'WRONG_CREDENTIALS';
					}
				}
				else if($result && mysqli_num_rows($result) == 0)
				{
                    if($json->register)
					{
						// Check to see if maximum number of accounts for this ip is reached
						$ip = get_user_ip();
						$query = "SELECT COUNT(DISTINCT username) AS count FROM sessions WHERE ip = '$ip'";
						$result = mysqli_query($connection, $query);
						$count = 0;
						if($result && mysqli_num_rows($result) > 0)
						{
							while($row = mysqli_fetch_assoc($result))
							{
								$count = $row['count'];
							}
						}
						if($count > MAX_ACCOUNTS_PER_IP)
						{
							return 'ERROR_IP_MAX_USER_REACHED';
						}
						
						// Check to see if maximum number of accounts in total is reached
						$query = "SELECT COUNT(DISTINCT username) AS count FROM accounts";
						$result = mysqli_query($connection, $query);
						$count = 0;
						if($result && mysqli_num_rows($result) > 0)
						{
							while($row = mysqli_fetch_assoc($result))
							{
								$count = $row['count'];
							}
						}
						if($count > MAX_USERS)
						{
							return 'ERROR_MAX_USER_REACHED';
						}

						// Create a new account
						$query = "INSERT INTO accounts(username, password, score) VALUES('$json->username','$json->password', 0)";
						mysqli_query($connection, $query);
						$query = "INSERT INTO sessions (username, session, ip, version) VALUES('$json->username', '$json->session', '$ip', '$json->version')";
						mysqli_query($connection, $query);
						$auth = array();
						$auth["message"] = 'SUCCESSFUL';
						$auth["sync_period"] = CONNECTION_CHECK_PERIOD;
                    	return $auth;
					}
				    else
					{
						return 'WRONG_CREDENTIALS';
					}
				}
				else
				{
				    return 'FATAL_ERROR_MULTIPLE_USERNAME';
				}
				break;
			case 987651: // Check User Connection
				$query = "SELECT id, password, blocked FROM accounts WHERE username = '$json->username'";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) == 1)
				{
					$password = "";
					$blocked = 1;
					$account_id = 0;
					while($row = mysqli_fetch_assoc($result))
					{
						$password = $row['password'];
						$blocked = $row['blocked'];
						$account_id = $row['id'];
					}
					if($password == $json->password)
					{

						// Check if the account has been suspended
						if($blocked > 0)
						{
							return 'BLOCKED';
						}

						// Check if there is a session for this user
						$ip = get_user_ip();
						$query = "SELECT id FROM sessions WHERE username = '$json->username' AND ip = '$ip' AND session = '$json->session'";
						$result = mysqli_query($connection, $query);
						if($result && mysqli_num_rows($result) > 0)
						{
							$id = 0;
							while($row = mysqli_fetch_assoc($result))
							{
								$id = $row['id'];
							}
							$query = "UPDATE sessions SET activity = CURRENT_TIMESTAMP WHERE id = $id";
							mysqli_query($connection, $query);
							return 'SUCCESSFUL';
						}
						else
						{
							return 'WRONG_SESSION';
						}
					}
					else
					{
						return 'WRONG_CREDENTIALS';
					}
				}
				else
				{
					return 'WRONG_CREDENTIALS';
				}
				break;
			case 987652:
				
				break;
		}
		return 'NULL';
    }
	
?>