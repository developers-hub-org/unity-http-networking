<?php

    function get_core_response($connection, $json, $path)
    {
		include_once ($path . "/control.php");
		switch($json->request)
		{
			case 987650: // Authenticate User
				$ip = get_user_ip();
				if(is_in_blacklist($connection, $ip))
				{
					return "BLACKLIST";
				}
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
							$query = "SELECT id FROM sessions WHERE account_id = $account_id AND ip <> '$ip' AND activity >= CURRENT_TIMESTAMP - INTERVAL $period SECOND";
							$result = mysqli_query($connection, $query);
							if($result && mysqli_num_rows($result) > 0)
							{
								return 'ANOTHER_SESSION_IS_ONLINE';
							}
						}

						// If there is a session for this ip then use it otherwise create a new session
						$query = "SELECT id FROM sessions WHERE account_id = $account_id AND ip = '$ip'";
						$result = mysqli_query($connection, $query);
						if($result && mysqli_num_rows($result) > 0)
						{
							// Use an existing session
							$id = 0;
							while($row = mysqli_fetch_assoc($result))
							{
								$id = $row['id'];
							}
							$query = "UPDATE sessions SET username = '$json->username', session = '$json->session', version = '$json->version' WHERE id = $id";
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
						$result = mysqli_query($connection, $query);
						$account_id = mysqli_insert_id($connection);
						$query = "INSERT INTO sessions (account_id, username, session, ip, version) VALUES($account_id, '$json->username', '$json->session', '$ip', '$json->version')";
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
			case 987651: // Sync User Connection
				$auth = authenticate($connection, $json->username, $json->password, $json->session);
				if($auth["valid"] == true)
				{
					$sync_data = array();
					$sync_data["message"] = 'SUCCESSFUL';
					$sync_data["unread_messages"] = get_unread_messages_count($connection, $auth["account_id"]);
					$undelivered_messages = get_undelivered_messages($connection, $auth["account_id"], MAX_MESSAGE_PER_PACKAGE, true);
					if($undelivered_messages != null)
					{
						array_push($sync_data, $undelivered_messages);
					}
					
					
					
					
					return $sync_data;
				}
				else
				{
					return $auth["error"];
				}
				break;
			case 987652:

				
				
				
				
				
				break;
			case 987653:
				
				break;
			case 987654:
				
				break;
			case 987655:
				
				break;
			case 987656:
				
				break;
			case 987657:
				
				break;
			case 987658:
				
				break;
			case 987659:
				
				break;
			case 987660:
				
				break;
			case 987661:
				
				break;
			case 987662:
				
				break;
		}
		return 'NULL';
    }
	
	function authenticate($connection, $username, $password, $session)
	{
		$response = array();
		$response["valid"] = false;
		$response["error"] = "NULL";
		$response["session_id"] = 0;
		$response["account_id"] = 0;
		$query = "SELECT id, password, blocked FROM accounts WHERE username = '$username'";
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
			$response["account_id"] = $account_id;
			if($password == $json->password)
			{
				// Check if the account has been suspended
				if($blocked > 0)
				{
					$response["error"] = "BLOCKED";
				}
				else
				{
					$ip = get_user_ip();
					if(is_in_blacklist($connection, $ip))
					{
						$response["error"] = "BLACKLIST";
					}
					else
					{
						// Check if there is a session for this user
						$query = "SELECT id FROM sessions WHERE account_id = $account_id AND ip = '$ip' AND session = '$json->session'";
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
							$response["valid"] = true;
							$response["session_id"] = $id;
						}
						else
						{
							$response["error"] = "WRONG_SESSION";
						}
					}
				}
			}
			else
			{
				$response["error"] = "WRONG_CREDENTIALS";
			}
		}
		else
		{
			$response["error"] = "WRONG_CREDENTIALS";
		}
        return $response;
	}
	
	function get_unread_messages_count($connection, $account_id)
	{
		$count = 0;
		$query = "SELECT COUNT(id) AS count FROM messages WHERE receiver_id = $auth[\"account_id\"] AND seen = 0";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			while($row = mysqli_fetch_assoc($result))
			{
				$count = $row['count'];
			}
		}
		return $count;
	}
	
	function get_undelivered_messages($connection, $account_id, $max, $mark_as_delivered)
	{
		// $query = "SELECT id, sender_id, receiver_type, encryption_key, message_text FROM messages WHERE receiver_id = $auth[\"account_id\"] AND delivered = 0 ORDER BY send_time DESC LIMIT $max";
		$query = "SELECT messages.id, messages.sender_id, messages.receiver_type, messages.encryption_key, messages.message_text, accounts.username FROM messages INNER JOIN accounts ON messages.sender_id = accounts.id WHERE messages.receiver_id = $auth[\"account_id\"] AND messages.delivered = 0 ORDER BY messages.send_time DESC LIMIT $max";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			$undelivered_messages = array();
			while($row = mysqli_fetch_assoc($result))
			{
				array_push($undelivered_messages, $row);
			}
			if($mark_as_delivered)
			{
				$query = "UPDATE messages SET delivered = 1 WHERE receiver_id = $auth[\"account_id\"] AND delivered = 0 ORDER BY send_time DESC LIMIT $max";
				mysqli_num_rows($result);
			}
			return $undelivered_messages;
		}
		return null;
	}
	
	function is_in_blacklist($connection, $ip)
	{
		$query = "SELECT id FROM blacklist WHERE ip = '$ip'";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			return true;
		}
		return false;
	}
	
?>
