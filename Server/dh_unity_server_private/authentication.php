<?php

	function get_user_data_by_id($connection, $path, $id, $block_check_id)
	{
		return get_user_data($connection, $path, $id, null, $block_check_id);
	}

	function get_user_data_by_username($connection, $path, $username, $block_check_id)
	{
		return get_user_data($connection, $path, null, $username, $block_check_id);
	}
	
	function get_user_data($connection, $path, $id, $username, $block_check_id)
	{
		$query = "";
		if($username != null)
		{
			$query = "SELECT * FROM accounts WHERE username = '$username'";
		}
		else if($id != null)
		{
			$query = "SELECT * FROM accounts WHERE id = $id";
		}
		else
		{
			return null;
		}
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) == 1)
		{
			$response = mysqli_fetch_assoc($result);
			unset($response["password"]);
			include_once ($path . "/control.php");
			$period = CONNECTION_CHECK_PERIOD + 5;
			$query = "SELECT id FROM sessions WHERE account_id = " . $response["id"] . " AND activity >= CURRENT_TIMESTAMP - INTERVAL " . $period . " SECOND";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				$response["is_online"] = 1;
			}
			if($block_check_id > 0)
			{
				$query = "SELECT blocker_id, blocked_id FROM users_blacklist WHERE is_active > 0 AND ((blocker_id = " . $response["id"] . " AND blocked_id = $block_check_id) OR (blocker_id = $block_check_id AND blocked_id = " . $response["id"] . "))";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					while($row = mysqli_fetch_assoc($result))
					{
						if($row["blocker_id"] == $block_check_id)
						{
							$response["blocked_by_you"] = 1;
						}
						else
						{
							$response["blocked_you"] = 1;
						}
					}
				}
			}
			return $response;
		}
		return null;
	}
	
	function get_ser_data_per_page($connection, $path, $page, $per_page, $sort, $desc_asc, $block_check_id)
	{
		include_once ($path . "/control.php");
		if($page <= 0)
		{
			$page = 1;
		}
		$sort_query = "";
		if($desc_asc != 0 && $sort != null && $sort != "")
		{
			$result = mysqli_query($connection, "SHOW COLUMNS FROM accounts LIKE '$sort'");
			if(mysqli_num_rows($result) > 0)
			{
				$sort_query = ($desc_asc > 0) ? " ORDER BY $sort ASC" : " ORDER BY $sort DESC";
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
		$columns = array();
		$query = "show columns from accounts;";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			while($row = mysqli_fetch_assoc($result))
			{
				if($row["Field"] != "password")
				{
					array_push($columns, $row["Field"]);
				}
			}
		}
		$column_query = "";
		if(count($columns) > 0)
		{
			$column_query = implode(", ", $columns);
			$column_query = ", " . $column_query;
		}
		$query = "SET @row_number = 0; SELECT * FROM (SELECT (@row_number:=@row_number + 1) AS rank " . $column_query . " FROM accounts" . $sort_query . ") as users LIMIT " . (($page - 1) * $per_page) . ", " . $per_page;
		if (mysqli_multi_query($connection, $query)) 
		{
			$response = array();
			$id_list = array();
			while(true)
			{
				if ($result = mysqli_store_result($connection))
				{
					while ($row = mysqli_fetch_assoc($result)) 
					{
						$id_list[] = $row['id'];
						unset($row["password"]);
						array_push($response, $row);
					}
					mysqli_free_result($result);
				}
				if (mysqli_more_results($connection))
				{
					mysqli_next_result($connection);
				} 
				else
				{
					break;
				}
			}
			if(count($response) > 0)
			{
				$id_array = implode(',', $id_list);
				$period = CONNECTION_CHECK_PERIOD + 5;
				$sorted = false;
				$query = "SELECT account_id FROM sessions WHERE account_id IN (" . $id_array . ") AND activity >= CURRENT_TIMESTAMP - INTERVAL " . $period . " SECOND ORDER BY account_id ASC";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{				
					usort($response, 'compare_array_id');
					$sorted = true;
					$j = 0;
					while($row = mysqli_fetch_assoc($result))
					{	
						for ($i = $j; $i < count($response); $i++)
						{
							if ($response[$i]['id'] === $row["account_id"]) 
							{
								$response[$i]["is_online"] = 1;
								$j = $i + 1;
								break;
							}
						}
					}
				}
				if($block_check_id > 0)
				{
					$query = "SELECT blocked_id FROM users_blacklist WHERE is_active > 0 AND blocker_id = " . $block_check_id . " AND blocked_id IN (" . $id_array . ") ORDER BY blocked_id ASC";
					$result = mysqli_query($connection, $query);
					if($result && mysqli_num_rows($result) > 0)
					{				
						if($sorted == false)
						{
							usort($response, 'compare_array_id');
							$sorted = true;
						}
						$j = 0;
						while($row = mysqli_fetch_assoc($result))
						{	
							for ($i = $j; $i < count($response); $i++)
							{
								if ($response[$i]['id'] === $row["blocked_id"]) 
								{
									$response[$i]["blocked_by_you"] = 1;
									$j = $i + 1;
									break;
								}
							}
						}
					}
					$query = "SELECT blocker_id FROM users_blacklist WHERE is_active > 0 AND blocked_id = " . $block_check_id . " AND blocker_id IN (" . $id_array . ") ORDER BY blocker_id ASC";
					$result = mysqli_query($connection, $query);
					if($result && mysqli_num_rows($result) > 0)
					{				
						if($sorted == false)
						{
							usort($response, 'compare_array_id');
							$sorted = true;
						}
						$j = 0;
						while($row = mysqli_fetch_assoc($result))
						{	
							for ($i = $j; $i < count($response); $i++)
							{
								if ($response[$i]['id'] === $row["blocker_id"]) 
								{
									$response[$i]["blocked_you"] = 1;
									$j = $i + 1;
									break;
								}
							}
						}
					}
				}
				return $response;
			}
		}
		return null;
	}
	
	function send_email_verification_code($connection, $username, $id, $path)
	{
		$response = array();
		$response["successful"] = false;
		$response["error"] = "USER_NOT_EXIST";
		$query = "";
		if($username != null)
		{
			$query = "SELECT id, email, is_email_verified FROM accounts WHERE username = '$username'";
		}
		else if($id != null)
		{
			$query = "SELECT id, email, is_email_verified FROM accounts WHERE id = $id";
		}
		else
		{
			return $response;
		}
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) == 1)
		{
			$id = 0;
			$is_verified = false;
			$email = "example@domain.com";
			while($row = mysqli_fetch_assoc($result))
			{
				if($row["is_email_verified"] > 0)
				{
					$is_verified = true;
				}
				$id = $row["id"];
				$email = $row["email"];
			}
			$response["id"] = $id;
			if($is_verified)
			{
				$response["error"] = "ALREADY_VERIFIED";
				return $response;
			}
			if(is_email_valid($email))
			{
				// REMINDER: Email code type = 1
				$query = "SELECT TIMESTAMPDIFF(SECOND, create_time, expire_time) AS remained FROM verification_codes WHERE account_id = $id AND type = 1 AND is_used = 0 AND expire_time > CURRENT_TIMESTAMP";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					while($row = mysqli_fetch_assoc($result))
					{
						$response["error"] = "ALREADY_SENT";
						$response["remained"] = $row["remained"];
						return $response;
					}
				}
				if(file_exists($path . "/templates/email_verifIcation_code_template.html"))
				{
					$code = random_int(100000, 999999);
					$company = COMPANY_NAME;
					$company_email = COMPANY_EMAIL;
					$logo = COMPANY_LOGO;
					$time = EMAIL_VERIFICATION_CODE_EXPIRE_TIME;
					$html =  file_get_contents($path . "/templates/email_verifIcation_code_template.html");

					$html = str_replace("[company_logo_url]", $logo, $html);
					$html = str_replace("[company_name]", $company, $html);
					$html = str_replace("[user_email_address]", $email, $html);
					$html = str_replace("[verification_code]", $code, $html);
					$html = str_replace("[remained_minutes]", ceil($time / 60), $html);
					$html = str_replace("[copyright_footer]", date("Y") . " " . $company, $html);

					if(send_email($company_email, $company, $email, "Email Verification Code", $html))
					{
						$query = "INSERT INTO verification_codes (code, type, account_id, expire_time) VALUES('$code', 1, $id, CURRENT_TIMESTAMP + INTERVAL $time SECOND)";
						mysqli_query($connection, $query);
						$response["successful"] = true;
						$response["remained"] = $time;
					}
					else
					{
						$response["error"] = "ERROR_SEND_EMAIL";
					}
				}
				else
				{
					$response["error"] = "NO_TEMPLATE";
				}
			}
			else
			{
				$response["error"] = "VALID_EMAIL_NOT_SET";
			}
		}
		return $response;
	}

	function change_password($username, $old_password, $new_password)
	{

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
	
	function compare_array_id($array1, $array2)
	{
		return strnatcmp($array1['id'], $array2['id']);
	}
	
?>
