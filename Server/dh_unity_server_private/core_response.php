<?php

    function get_core_response($connection, $json, $path)
    {
		
		include_once ($path . "/control.php");
		$ip = get_user_ip();
		$response = array();
		$response["successful"] = false;
		$response["now_datetime"] = get_current_datetime($connection);
		
		if(is_in_blacklist($connection, $ip))
		{
			$response["error"] = "BLACKLIST";
			return $response;
		}
		
		switch($json->request)
		{
			case 987650: // Connect To Server
				$response["sync_period"] = CONNECTION_CHECK_PERIOD;
				$response["successful"] = true;
				break;
			case 987651: // Sync Connection
				if(isset($json->extentions_requests))
				{
					$auth = authenticate($connection, $path, $json->username, $json->password, $json->session, false, $json->version, false);
					if($auth["valid"] == true)
					{
						$requests_response = array();
						foreach($json->extentions_requests as $key => $value) 
						{
							// Return type ($task) must be an array
							switch($value)
							{
								case 987701: // UPDATE_USER_ACTIVITY
									$task = array();
									$id = $auth["session_id"];
									$query = "UPDATE sessions SET activity = CURRENT_TIMESTAMP WHERE id = $id";
									mysqli_query($connection, $query);
									// $task["successful"] = true;
									$requests_response["987701"] = $task;
									break;
								case 987750: // GET_UNREAD_MESSAGES_COUNT
									$task = array();
									$task["unread_messages"] = get_unread_messages_count($connection, $auth["account_id"]);
									// $task["successful"] = true;
									$requests_response["987750"] = $task;
									break;
								case 987751: // GET_UNDELIVERED_MESSAGES
										$undelivered_messages = get_undelivered_messages($connection, $auth["account_id"], MAX_MESSAGE_PER_PACKAGE, true);
										if($undelivered_messages != null)
										{	
											$task = array();
											$task["undelivered_messages"] = $undelivered_messages;
											// $task["successful"] = true;
											$requests_response["987751"] = $task;
										}
										break;
							}
						}
						if(count($requests_response) > 0)
						{
							$response["requests_response"] = $requests_response;
						}
						$response["successful"] = true;
					}
					else
					{
						$response["error"] = $auth["error"];
					}
				}
				break;
			case 987650:
				
				break;
			case 987700: // AUTHENTICATE_USER
				$create_session_if_not_exists = false;
				$register = false;
				if(isset($json->create_session))
				{
					$create_session_if_not_exists = $json->create_session;
				}
				if(isset($json->register_user))
				{
					$register = $json->register_user;
				}
				$auth = authenticate($connection, $path, $json->username, $json->password, $json->session, $register, $json->version, $create_session_if_not_exists);
				if($auth["valid"] == true)
				{
					$response["successful"] = true;
					$response["account_id"] = $auth["account_id"];
					$response["session_id"] = $auth["session_id"];
				}
				else
				{
					$response["error"] = $auth["error"];
				}
				break;
			case 987702: // GET_USER_DATA
				$auth = authenticate($connection, $path, $json->username, $json->password, $json->session, false, $json->version, false);
				if($auth["valid"] == true)
				{
					$user = get_ser_data($connection, $json->get_username);
					if($user != null)
					{
						$response["successful"] = true;
						$response["user"] = $user;
					}
					else
					{
						$response["error"] = "USER_NOT_EXISTS";
					}
				}
				else
				{
					$response["error"] = $auth["error"];
				}
				break;
			case 987703: // GET_USERS_DATA_PER_PAGE
				$auth = authenticate($connection, $path, $json->username, $json->password, $json->session, false, $json->version, false);
				if($auth["valid"] == true)
				{
					$users = get_ser_data_per_page($connection, $path, $json->users_page, $json->users_per_page, $json->users_sort, $json->users_desc_asc);
					if($users != null)
					{
						$response["users"] = $users;
					}
					else
					{
						$response["error"] = "NO_USER";
					}
					$response["successful"] = true;
				}
				else
				{
					$response["error"] = $auth["error"];
				}
				break;
		}
		return $response;
    }
	
	function process_sync_request($id, $connection, $json, $path)
	{
		include_once ($path . "/control.php");
		
	}
	
	function get_core_response_by_id($id, $connection, $json, $path)
	{
		include_once ($path . "/control.php");
		
	}
	
	function get_ser_data($connection, $username)
	{
		$query = "SELECT * FROM accounts WHERE username = '$username'";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) == 1)
		{
			$response = mysqli_fetch_assoc($result);
			unset($response["password"]);
			return $response;
		}
		return null;
	}
	
	function get_ser_data_per_page($connection, $path, $page, $per_page, $sort, $desc_asc)
	{
		include_once ($path . "/control.php");
		if($page <= 0)
		{
			$page = 1;
		}
		$sort_query = "";
		if($sort != null && $sort != "")
		{
			$result = mysqli_query($connection, "SHOW COLUMNS FROM accounts LIKE '$sort'");
			if(mysqli_num_rows($result) > 0)
			{
				if($desc_asc != 0)
				{
					$sort_query = ($desc_asc > 0) ? " ORDER BY $sort ASC" : " ORDER BY $sort DESC";
				}
				else
				{
					$sort_query = " ORDER BY $sort";
				}
			}
		}
		if($per_page <= 0)
		{
			$per_page = 10;
		}
		if($per_page > MAX_RETURN_ACCOUNTS_PER_PAGE)
		{
			$per_page = MAX_RETURN_ACCOUNTS_PER_PAGE;
		}
		$query = "SET @row_number = 0; SELECT (@row_number:=@row_number + 1) AS num, * FROM accounts";
		// $query = "SELECT * FROM accounts" . $sort_query . " LIMIT " . ($page - 1) . ", " . $per_page;
		// $result = mysqli_query($connection, $query);
		$result = mysqli_multi_query($connection, $query);
		$response = array();
		do 
		{
			// Store first result set
			if ($result = mysqli_store_result($con)) 
			{
				while ($row = mysqli_fetch_row($result)) {
				printf("%s\n", $row[0]);
				}
				mysqli_free_result($result);
			}
				// if there are more result-sets, the print a divider
				if (mysqli_more_results($con)) {
				printf("-------------\n");
			}
			//Prepare next result set
		} 
		while (mysqli_next_result($con));
		
		
		// if $response  lenghht > 0 return
		
		if($result && mysqli_more_results($connection)
		{
			
			
			
			
			
			
			
			/*
			$response = array();
			while($row = mysqli_fetch_assoc($result))
			{
				unset($row["password"]);
				array_push($response, $row);
			}
			return $response;*/
		}
		return null;
	}
	
	function authenticate($connection, $path, $username, $password, $session, $register, $version, $create_session_if_not_exists)
	{
		include_once ($path . "/control.php");
		$response = array();
		$response["valid"] = false;
		$response["error"] = "";
		$response["session_id"] = 0;
		$response["account_id"] = 0;
		$query = "SELECT id, password, blocked FROM accounts WHERE username = '$username'";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) == 1)
		{
			$pass = "";
			$blocked = 1;
			$account_id = 0;
			while($row = mysqli_fetch_assoc($result))
			{
				$pass = $row['password'];
				$blocked = $row['blocked'];
				$account_id = $row['id'];
			}
			$response["account_id"] = $account_id;
			if($password == $pass)
			{
				// Check if the account has been suspended
				if($blocked > 0)
				{
					$response["error"] = "BLOCKED";
				}
				else
				{
					if($create_session_if_not_exists)
					{
						// Check if there is other devices are online using this account
						$ip = get_user_ip();
						if(ALLOW_MULTIPLE_ONLINE_SESSIONS == false)
						{
							$period = CONNECTION_CHECK_PERIOD + 5;
							$query = "SELECT id FROM sessions WHERE account_id = $account_id AND ip <> '$ip' AND activity >= CURRENT_TIMESTAMP - INTERVAL $period SECOND";
							$result = mysqli_query($connection, $query);
							if($result && mysqli_num_rows($result) > 0)
							{
								$response["error"] = "ANOTHER_SESSION_IS_ONLINE";
								return $response;
							}
						}
						
						// If there is a session for this ip then use it otherwise create a new session
						$query = "SELECT id FROM sessions WHERE account_id = $account_id AND ip = '$ip'";
						$result = mysqli_query($connection, $query);
						if($result && mysqli_num_rows($result) > 0)
						{
							// Use an existing session
							$session_id = 0;
							while($row = mysqli_fetch_assoc($result))
							{
								$session_id = $row['id'];
							}
							$response["session_id"] = $session_id;
							$query = "UPDATE sessions SET username = '$username', session = '$session', version = '$version' WHERE id = $session_id";
							mysqli_query($connection, $query);
						}
						else
						{
							// Create a new session
							$query = "INSERT INTO sessions (account_id, username, session, ip, version) VALUES($account_id , '$username', '$session', '$ip', '$version')";
							mysqli_query($connection, $query);
							$session_id = mysqli_insert_id($connection);
							$response["session_id"] = $session_id;
						}
						$response["valid"] = true;
					}
					else
					{
						// Check if there is a session for this user
						$ip = get_user_ip();
						$query = "SELECT id FROM sessions WHERE account_id = $account_id AND ip = '$ip' AND session = '$session'";
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
		else if($result && mysqli_num_rows($result) == 0)
		{
			if($register)
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
					$response["error"] = 'ERROR_IP_MAX_USER_REACHED';
					return $response;
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
					$response["error"] = 'ERROR_MAX_USER_REACHED';
					return $response;
				}

				// Create a new account
				$query = "INSERT INTO accounts(username, password) VALUES('$username','$password')";
				$result = mysqli_query($connection, $query);
				$account_id = mysqli_insert_id($connection);
				$query = "INSERT INTO sessions (account_id, username, session, ip, version) VALUES($account_id, '$username', '$session', '$ip', '$version')";
				mysqli_query($connection, $query);
				$session_id = mysqli_insert_id($connection);
				$response["session_id"] = $session_id;
				$response["account_id"] = $account_id;
				$response["valid"] = true;
			}
			else
			{
				$response["error"] = 'WRONG_CREDENTIALS';
			}
		}
		else
		{
			$response["error"] = "FATAL_ERROR_MULTIPLE_USERNAME";
		}
        return $response;
	}
	
	function get_unread_messages_count($connection, $account_id)
	{
		$count = 0;
		$query = "SELECT COUNT(id) AS count FROM messages WHERE receiver_id = $account_id AND seen = 0";
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
		$query = "SELECT messages.id, messages.sender_id, messages.receiver_type, messages.encryption_key, messages.message_text, messages.send_time, accounts.username FROM messages INNER JOIN accounts ON messages.sender_id = accounts.id WHERE messages.receiver_id = $account_id AND messages.delivered = 0 ORDER BY messages.send_time DESC LIMIT $max";
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
				$query = "UPDATE messages SET delivered = 1 WHERE receiver_id = $account_id AND delivered = 0 ORDER BY send_time DESC LIMIT $max";
				mysqli_query($connection, $query);
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
	
	function get_current_datetime($connection)
	{
		$result = mysqli_query($connection, "SELECT CURRENT_TIMESTAMP() AS now");
		if($result)
		{
			while($row = mysqli_fetch_assoc($result))
			{
				return $row['now'];
			}
		}
		return date("Y-m-d H:i:s", time());
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
