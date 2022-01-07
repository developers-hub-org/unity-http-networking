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
					$requests_response = array();
					foreach($json->extentions_requests as $key => $value) 
					{
						// Return type ($task) must be an array
						switch($value)
						{
							case 987701: // UPDATE_USER_ACTIVITY
								include_once ($path . "/authentication.php");
								$auth = authenticate($connection, $path, $json->username, $json->password, $json->session, false, $json->version, false);
								if($auth["valid"] == true)
								{
									$task = array();
									$id = $auth["session_id"];
									$query = "UPDATE sessions SET activity = CURRENT_TIMESTAMP WHERE id = $id";
									mysqli_query($connection, $query);
									// $task["successful"] = true;
									$requests_response["987701"] = $task;
								}
								else
								{
									$response["error"] = $auth["error"];
								}
								break;
							case 987750: // GET_UNREAD_MESSAGES_COUNT
								include_once ($path . "/authentication.php");
								$auth = authenticate($connection, $path, $json->username, $json->password, $json->session, false, $json->version, false);
								if($auth["valid"] == true)
								{
									include_once ($path . "/messaging.php");
									$task = array();
									$task["unread_messages"] = get_unread_messages_count($connection, $auth["account_id"]);
									// $task["successful"] = true;
									$requests_response["987750"] = $task;
								}
								else
								{
									$response["error"] = $auth["error"];
								}
								break;
							case 987751: // GET_UNDELIVERED_MESSAGES
								include_once ($path . "/authentication.php");
								$auth = authenticate($connection, $path, $json->username, $json->password, $json->session, false, $json->version, false);
								if($auth["valid"] == true)
								{
									include_once ($path . "/messaging.php");
									$undelivered_messages = get_undelivered_messages($connection, $auth["account_id"], MAX_MESSAGE_PER_PACKAGE, true);
									if($undelivered_messages != null)
									{	
										$task = array();
										$task["undelivered_messages"] = $undelivered_messages;
										// $task["successful"] = true;
										$requests_response["987751"] = $task;
									}
								}
								else
								{
									$response["error"] = $auth["error"];
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
				break;
			case 987650:
				
				break;
			case 987700: // AUTHENTICATE_USER
				include_once ($path . "/authentication.php");
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
				include_once ($path . "/authentication.php");
				$your_id = -1;
				if(isset($json->your_id))
				{
					$your_id = $json->your_id;
				}
				$user = null;
				if(isset($json->get_username))
				{
					$user = get_user_data_by_username($connection, $path, $json->get_username, $your_id);
				}
				else if(isset($json->get_id))
				{
					$user = get_user_data_by_id($connection, $path, $json->get_id, $your_id);
				}
				else
				{
					$response["error"] = "USER_NOT_EXISTS";
				}
				if($user != null)
				{
					$response["successful"] = true;
					$response["user"] = $user;
				}
				else
				{
					$response["error"] = "USER_NOT_EXISTS";
				}
				break;
			case 987703: // GET_USERS_DATA_PER_PAGE
				include_once ($path . "/authentication.php");
				$your_id = -1;
				if(isset($json->your_id))
				{
					$your_id = $json->your_id;
				}
				$users = get_ser_data_per_page($connection, $path, $json->users_page, $json->users_per_page, $json->users_sort, $json->users_desc_asc, $your_id);
				if($users != null)
				{
					$response["users"] = $users;
					$response["successful"] = true;
					$result = mysqli_query($connection, "SELECT COUNT(id) AS count FROM accounts");
					$count = 0;
					if($result && mysqli_num_rows($result) > 0)
					{
						while($row = mysqli_fetch_assoc($result))
						{
							$count = $row['count'];
						}
					}
					$response["total_users_count"] = $count;
				}
				else
				{
					$response["error"] = "NO_USER";
				}
				break;
		}
		return $response;
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
