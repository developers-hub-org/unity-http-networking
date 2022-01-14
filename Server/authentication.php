<?php

	/*
		Verification Code Types:
		1: Email Verification
		2: Phone Verification
		3: Change Password With Email
		4: Change Password With Phone

		Note: Need online host to send email. Localhost can not send email.
		Note: Need sms service API to send text message.
	*/

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
			$lower_username = strtolower($username);
			$query = "SELECT * FROM accounts WHERE LOWER(username) = '$lower_username'";
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
	
	function get_ser_data_per_page($connection, $path, $page, $per_page, $sort, $desc_asc, $block_check_id, $response)
	{
		$response["error"] = "NO_USER";
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
			$users = array();
			$id_list = array();
			while(true)
			{
				if ($result = mysqli_store_result($connection))
				{
					while ($row = mysqli_fetch_assoc($result)) 
					{
						$id_list[] = $row['id'];
						unset($row["password"]);
						array_push($users, $row);
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
			if(count($users) > 0)
			{
				$id_array = implode(',', $id_list);
				$period = CONNECTION_CHECK_PERIOD + 5;
				$sorted = false;
				$query = "SELECT account_id FROM sessions WHERE account_id IN (" . $id_array . ") AND activity >= CURRENT_TIMESTAMP - INTERVAL " . $period . " SECOND ORDER BY account_id ASC";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{				
					usort($users, 'compare_array_id');
					$sorted = true;
					$j = 0;
					while($row = mysqli_fetch_assoc($result))
					{	
						for ($i = $j; $i < count($users); $i++)
						{
							if ($users[$i]['id'] === $row["account_id"]) 
							{
								$users[$i]["is_online"] = 1;
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
							usort($users, 'compare_array_id');
							$sorted = true;
						}
						$j = 0;
						while($row = mysqli_fetch_assoc($result))
						{	
							for ($i = $j; $i < count($users); $i++)
							{
								if ($users[$i]['id'] === $row["blocked_id"]) 
								{
									$users[$i]["blocked_by_you"] = 1;
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
							usort($users, 'compare_array_id');
							$sorted = true;
						}
						$j = 0;
						while($row = mysqli_fetch_assoc($result))
						{	
							for ($i = $j; $i < count($users); $i++)
							{
								if ($users[$i]['id'] === $row["blocker_id"]) 
								{
									$users[$i]["blocked_you"] = 1;
									$j = $i + 1;
									break;
								}
							}
						}
					}
				}
				$response["successful"] = true;
				$response["users"] = $users;
				$response["error"] = "";
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
				$response["per_page_count"] = $per_page;
				$response["current_page"] = $page;
			}
		}
		return $response;
	}
	
	function send_email_verification_code($connection, $username, $id, $path, $response)
	{
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
				$query = "SELECT id FROM accounts WHERE email = '$email' AND is_email_verified > 0 AND id <> $id";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					$response["error"] = "EMAIL_VERIFIED_BY_ANOTHER_USER";
				}
				else
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
					if(file_exists($path . "/templates/verifIcation_code_template.html"))
					{
						$code = random_int(100000, 999999);
						$company = COMPANY_NAME;
						$company_email = COMPANY_EMAIL;
						$logo = COMPANY_LOGO;
						$time = EMAIL_VERIFICATION_CODE_EXPIRE_TIME;
						$html =  file_get_contents($path . "/templates/verifIcation_code_template.html");

						$html = str_replace("[company_logo_url]", $logo, $html);
						$html = str_replace("[company_name]", $company, $html);
						$html = str_replace("[user_name]", $email, $html);
						$html = str_replace("[verification_code]", $code, $html);
						$html = str_replace("[remained_minutes]", ceil($time / 60), $html);
						$html = str_replace("[copyright_footer]", "Copyright © " . date("Y") . " " . $company, $html);
						$html = str_replace("[email_description]", "You can use this code to validate your email address. This code is confidential and you should not share it with anybody.", $html);
						
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
			}
			else
			{
				$response["error"] = "VALID_EMAIL_NOT_SET";
			}
		}
		return $response;
	}

	function send_phone_verification_code($connection, $username, $id, $path, $response)
	{
		$response["successful"] = false;
		$response["error"] = "USER_NOT_EXIST";
		$query = "";
		if($username != null)
		{
			$query = "SELECT id, phone_number, phone_country, is_phone_verified FROM accounts WHERE username = '$username'";
		}
		else if($id != null)
		{
			$query = "SELECT id, phone_number, phone_country, is_phone_verified FROM accounts WHERE id = $id";
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
			$phone = "+1";
			$country = "us";
			while($row = mysqli_fetch_assoc($result))
			{
				if($row["is_phone_verified"] > 0)
				{
					$is_verified = true;
				}
				$id = $row["id"];
				$phone = $row["phone_number"];
				$country = $row["phone_country"];
			}
			$response["id"] = $id;
			if($is_verified)
			{
				$response["error"] = "ALREADY_VERIFIED";
				return $response;
			}
			if(is_phone_valid($phone, $country))
			{
				$query = "SELECT id FROM accounts WHERE phone_number = '$phone' AND phone_country = $country AND is_phone_verified > 0 AND id <> $id";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					$response["error"] = "PHONE_VERIFIED_BY_ANOTHER_USER";
				}
				else
				{
					// REMINDER: Phone code type = 2
					$query = "SELECT TIMESTAMPDIFF(SECOND, create_time, expire_time) AS remained FROM verification_codes WHERE account_id = $id AND type = 2 AND is_used = 0 AND expire_time > CURRENT_TIMESTAMP";
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
					$code = random_int(100000, 999999);
					$company = COMPANY_NAME;
					$time = PHONE_VERIFICATION_CODE_EXPIRE_TIME;
					$text = $code . " is your " . $company . " verification code. It will expires in " . ceil($time / 60) . " minutes.";
					if(send_sms($phone, $text))
					{
						$query = "INSERT INTO verification_codes (code, type, account_id, expire_time) VALUES('$code', 2, $id, CURRENT_TIMESTAMP + INTERVAL $time SECOND)";
						mysqli_query($connection, $query);
						$response["successful"] = true;
						$response["remained"] = $time;
					}
					else
					{
						$response["error"] = "ERROR_SEND_SMS";
					}
				}
			}
			else
			{
				$response["error"] = "VALID_PHONE_NOT_SET";
			}
		}
		else
		{
			$response["error"] = "USER_NOT_EXISTS";
		}
		return $response;
	}
	
	function verify_email($connection, $username, $id, $code, $response)
	{
		$response["successful"] = false;
		$query = "";
		if($username != null)
		{
			$query = "SELECT id, is_email_verified FROM accounts WHERE username = '$username'";
		}
		else if($id != null)
		{
			$query = "SELECT id, is_email_verified FROM accounts WHERE id = $id";
		}
		else
		{
			return $response;
		}
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) == 1)
		{
			$is_verified = false;
			while($row = mysqli_fetch_assoc($result))
			{
				if($row["is_email_verified"] > 0)
				{
					$is_verified = true;
				}
				$id = $row["id"];
			}
			if($is_verified)
			{
				$response["error"] = "ALREADY_VERIFIED";
			}
			else
			{
				$query = "SELECT id FROM verification_codes WHERE account_id = $id AND is_used = 0 AND code = $code AND type = 1 AND expire_time > CURRENT_TIMESTAMP";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					$query = "UPDATE verification_codes SET is_used = 1 WHERE account_id = $id AND is_used = 0 AND type = 1 AND expire_time > CURRENT_TIMESTAMP";
					mysqli_query($connection, $query);
					$query = "UPDATE accounts SET is_email_verified = 1 WHERE id = $id";
					mysqli_query($connection, $query);
					$response["successful"] = true;
				}
				else
				{
					$response["error"] = "CODE_NOT_VALID";
				}
			}
		}
		else
		{
			$response["error"] = "USER_NOT_EXIST";
		}
		return $response;
	}
	
	function verify_phone($connection, $username, $id, $code, $response)
	{
		$response["successful"] = false;
		$query = "";
		if($username != null)
		{
			$query = "SELECT id, is_phone_verified FROM accounts WHERE username = '$username'";
		}
		else if($id != null)
		{
			$query = "SELECT id, is_phone_verified FROM accounts WHERE id = $id";
		}
		else
		{
			return $response;
		}
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) == 1)
		{
			$is_verified = false;
			while($row = mysqli_fetch_assoc($result))
			{
				if($row["is_phone_verified"] > 0)
				{
					$is_verified = true;
				}
				$id = $row["id"];
			}
			if($is_verified)
			{
				$response["error"] = "ALREADY_VERIFIED";
			}
			else
			{
				$query = "SELECT id FROM verification_codes WHERE account_id = $id AND is_used = 0 AND code = $code AND type = 2 AND expire_time > CURRENT_TIMESTAMP";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					$query = "UPDATE verification_codes SET is_used = 1 WHERE account_id = $id AND is_used = 0 AND type = 2 AND expire_time > CURRENT_TIMESTAMP";
					mysqli_query($connection, $query);
					$query = "UPDATE accounts SET is_phone_verified = 1 WHERE id = $id";
					mysqli_query($connection, $query);
					$response["successful"] = true;
				}
				else
				{
					$response["error"] = "CODE_NOT_VALID";
				}
			}
		}
		else
		{
			$response["error"] = "USER_NOT_EXIST";
		}
		return $response;
	}
	
	function change_password($connection, $path, $version, $id, $username, $password, $session, $old_password, $new_password, $code, $email, $phone, $response)
	{
		$successful = false;
		$response["error"] = "USER_NOT_EXISTS";
		if($old_password != null && ($id != null || $username != null))
		{
			if($username != null)
			{
				$query = "SELECT id, username, password FROM accounts WHERE username = '$username'";
			}
			else
			{
				$query = "SELECT id, username, password FROM accounts WHERE id = $id";
			}
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) == 1)
			{
				$password = "";
				$username = "";
				while($row = mysqli_fetch_assoc($result))
				{
					$password = $row["password"];
					$username = $row["username"];
					$id = $row["id"];
				}
				if($password == $old_password)
				{
					$successful = true;
					$response["username"] = $username;
				}
				else
				{
					$response["error"] = "WRONG_OLD_PASSWORD";
				}
			}
		}
		else if($code != null && ($email != null || $phone != null))
		{
			if($email != null)
			{
				$query = "SELECT id, account_id FROM verification_codes WHERE code = '$code' AND is_used = 0 AND type = 3 AND expire_time > CURRENT_TIMESTAMP";
			}
			else
			{
				$query = "SELECT id, account_id FROM verification_codes WHERE code = '$code' AND is_used = 0 AND type = 4 AND expire_time > CURRENT_TIMESTAMP";
			}
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				$code_id = 0;
				$account_id = 0;
				while($row = mysqli_fetch_assoc($result))
				{
					$code_id = $row["id"];
					$id = $row["account_id"];
				}
				if($email != null)
				{
					$query = "SELECT username, email, is_email_verified FROM accounts WHERE id = $id";
					$result = mysqli_query($connection, $query);
					if($result && mysqli_num_rows($result) == 1)
					{
						$user_email = "#";
						$is_email_verified = false;
						$username = "";
						while($row = mysqli_fetch_assoc($result))
						{
							$user_email = $row["email"];
							$username = $row["username"];
							$is_email_verified = ($row["is_email_verified"] > 0);
						}
						if($user_email == $email && $is_email_verified)
						{
							$query = "UPDATE verification_codes SET is_used = 1 WHERE id = $code_id";
							mysqli_query($connection, $query);
							$successful = true;
							$response["username"] = $username;
						}
						else
						{
							$response["error"] = "EMAIL_NOT_VALID";
						}
					}
				}
				else
				{
					$query = "SELECT username, phone_number, is_phone_verified FROM accounts WHERE id = $id";
					$result = mysqli_query($connection, $query);
					if($result && mysqli_num_rows($result) == 1)
					{
						$user_phone = "#";
						$is_phone_verified = false;
						$username = "";
						while($row = mysqli_fetch_assoc($result))
						{
							$user_phone = $row["phone_number"];
							$username = $row["username"];
							$is_phone_verified = ($row["is_phone_verified"] > 0);
						}
						if($user_phone == $phone && $is_phone_verified)
						{
							$query = "UPDATE verification_codes SET is_used = 1 WHERE id = $code_id";
							mysqli_query($connection, $query);
							$successful = true;
							$response["username"] = $username;
						}
						else
						{
							$response["error"] = "PHONE_NOT_VALID";
						}
					}
				}
			}
			else
			{
				$response["error"] = "CODE_NOT_VALID";
			}
		}
		else if ($password != null && $session != null && $username != null)
		{
			$auth = authenticate($connection, $path, $username, $password, $session, false, $version, false, false, null, null, null, null, null, null);
			if($auth["valid"] == true)
			{
				$id = $auth["account_id"];
				$query = "SELECT username FROM accounts WHERE id = $id AND is_password_set <= 0";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) == 1)
				{
					while($row = mysqli_fetch_assoc($result))
					{
						$username = $row["username"];
					}
					$successful = true;
				}
				else
				{
					$response["error"] = "PASSWORD_ALREADY_SET_FOR_FIRST_TIME";
				}
			}
			else
			{
				$response["error"] = $auth["error"];
			}
		}
		if($successful)
		{
			$query = "UPDATE accounts SET password = '$new_password', is_password_set = 1 WHERE id = $id";
			mysqli_query($connection, $query);
			$response["successful"] = true;
			$response["new_password"] = $new_password;
		}
		return $response;
	}
	
	function send_password_recovery_code($connection, $path, $email, $phone, $country, $response)
	{
		$response["error"] = "USER_NOT_EXISTS";
		$response["successful"] = false;
		if($email != null)
		{
			$query = "SELECT id, username FROM accounts WHERE email = '$email' AND is_email_verified > 0";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) == 1)
			{
				$id = 0;
				$username = "";
				while($row = mysqli_fetch_assoc($result))
				{
					$username = $row["username"];
					$id = $row["id"];
				}
				$query = "SELECT TIMESTAMPDIFF(SECOND, create_time, expire_time) AS remained FROM verification_codes WHERE account_id = $id AND type = 3 AND is_used = 0 AND expire_time > CURRENT_TIMESTAMP";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					while($row = mysqli_fetch_assoc($result))
					{
						$response["error"] = "ALREADY_SENT";
						$response["remained"] = $row["remained"];
					}
				}
				else
				{
					if(file_exists($path . "/templates/verifIcation_code_template.html"))
					{
						$code = random_int(100000, 999999);
						$company = COMPANY_NAME;
						$company_email = COMPANY_EMAIL;
						$logo = COMPANY_LOGO;
						$time = PASSWORD_RECOVERY_EMAIL_CODE_EXPIRE_TIME;
						$html =  file_get_contents($path . "/templates/verifIcation_code_template.html");
						
						$html = str_replace("[company_logo_url]", $logo, $html);
						$html = str_replace("[company_name]", $company, $html);
						$html = str_replace("[user_name]", $username, $html);
						$html = str_replace("[verification_code]", $code, $html);
						$html = str_replace("[remained_minutes]", ceil($time / 60), $html);
						$html = str_replace("[copyright_footer]", "Copyright © " . date("Y") . " " . $company, $html);
						$html = str_replace("[email_description]", "You can use this code to recover your account. This code is confidential and you should not share it with anybody.", $html);

						if(send_email($company_email, $company, $email, "Password Recovery Code", $html))
						{
							$query = "INSERT INTO verification_codes (code, type, account_id, expire_time) VALUES('$code', 3, $id, CURRENT_TIMESTAMP + INTERVAL $time SECOND)";
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
			}
		}
		else if($phone != null && $country != null)
		{
			$query = "SELECT id, username FROM accounts WHERE phone_number = '$phone' AND phone_country = '$country' AND is_phone_verified > 0";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) == 1)
			{
				$id = 0;
				$username = "";
				while($row = mysqli_fetch_assoc($result))
				{
					$username = $row["username"];
					$id = $row["id"];
				}
				$query = "SELECT TIMESTAMPDIFF(SECOND, create_time, expire_time) AS remained FROM verification_codes WHERE account_id = $id AND type = 4 AND is_used = 0 AND expire_time > CURRENT_TIMESTAMP";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					while($row = mysqli_fetch_assoc($result))
					{
						$response["error"] = "ALREADY_SENT";
						$response["remained"] = $row["remained"];
					}
				}
				else
				{
					$code = random_int(100000, 999999);
					$company = COMPANY_NAME;
					$time = PASSWORD_RECOVERY_PHONE_CODE_EXPIRE_TIME;
					$text = $code . " is your " . $company . " account password recovery code.  Your username is " . $username . " and this code will expire in " . ceil($time / 60) . " minutes.";
					if(send_sms($phone, $text))
					{
						$query = "INSERT INTO verification_codes (code, type, account_id, expire_time) VALUES('$code', 4, $id, CURRENT_TIMESTAMP + INTERVAL $time SECOND)";
						mysqli_query($connection, $query);
						$response["successful"] = true;
						$response["remained"] = $time;
					}
					else
					{
						$response["error"] = "ERROR_SEND_SMS";
					}
				}
			}
		}
		return $response;
	}

	function change_email($connection, $path, $username, $password, $session, $version, $email, $response)
	{
		$auth = authenticate($connection, $path, $username, $password, $session, false, $version, false, false, null, null, null, null, null, null);
		if($auth["valid"] == true)
		{
			$id = $auth["account_id"];
			$query = "SELECT id FROM accounts WHERE email = '$email' AND is_email_verified > 0 AND id <> $id";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				$response["error"] = "EMAIL_TAKEN";
			}
			else
			{
				$query = "UPDATE accounts SET email = '$email', is_email_verified = 0 WHERE id = $id AND email <> $email";
				mysqli_query($connection, $query);
				$query = "UPDATE verification_codes SET expire_time = CURRENT_TIMESTAMP WHERE account_id = $id AND is_used = 0 AND type = 1 AND expire_time > CURRENT_TIMESTAMP";
				mysqli_query($connection, $query);
				$response["successful"] = true;
			}
		}
		else
		{
			$response["error"] = $auth["error"];
		}
		return $response;
	}
	
	function change_phone($connection, $path, $username, $password, $session, $version, $phone, $country, $response)
	{
		$auth = authenticate($connection, $path, $username, $password, $session, false, $version, false, false, null, null, null, null, null, null);
		if($auth["valid"] == true)
		{
			$id = $auth["account_id"];
			$query = "SELECT id FROM accounts WHERE phone_number = '$phone' AND phone_country = $country AND is_phone_verified > 0 AND id <> $id";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				$response["error"] = "PHONE_NUMBER_TAKEN";
			}
			else
			{
				$query = "UPDATE accounts SET phone_number = '$phone', is_phone_verified = 0, phone_country = $country WHERE id = $id AND (phone_number <> $phone || phone_country <> $country)";
				mysqli_query($connection, $query);
				$query = "UPDATE verification_codes SET expire_time = CURRENT_TIMESTAMP WHERE account_id = $id AND is_used = 0 AND type = 2 AND expire_time > CURRENT_TIMESTAMP";
				mysqli_query($connection, $query);
				$response["successful"] = true;
			}
		}
		else
		{
			$response["error"] = $auth["error"];
		}
		return $response;
	}
	
	function authenticate($connection, $path, $username, $password, $session, $register, $version, $create_session_if_not_exists, $only_register, $email, $phone, $country, $firstname, $lastname, $birthday)
	{
		include_once ($path . "/control.php");
		$response = array();
		$response["valid"] = false;
		$response["error"] = "";
		$response["session_id"] = 0;
		$response["account_id"] = 0;
		$lower_username = strtolower($username);
		$query = "SELECT id, password, blocked FROM accounts WHERE LOWER(username) = '$lower_username'";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) == 1)
		{
			if($only_register)
			{
				$response["error"] = "USERNAME_TAKEN";
			}
			else
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
				$query_keys = "";
				$query_values = "";
				if($email != null)
				{
					$query_keys = $query_keys . ", email";
					$query_values = $query_values . ", '$email'";
				}
				if($phone != null)
				{
					$query_keys = $query_keys . ", phone_number";
					$query_values = $query_values . ", '$phone'";
				}
				if($country != null)
				{
					$query_keys = $query_keys . ", phone_country";
					$query_values = $query_values . ", '$country'";
				}
				if($firstname != null)
				{
					$query_keys = $query_keys . ", firstname";
					$query_values = $query_values . ", '$firstname'";
				}
				if($lastname != null)
				{
					$query_keys = $query_keys . ", lastname";
					$query_values = $query_values . ", '$lastname'";
				}
				if($birthday != null && is_datetime_valid($birthday))
				{
					$query_keys = $query_keys . ", birthday, is_birthday_set";
					$query_values = $query_values . ", '$birthday', 1";
				}
				if($only_register)
				{
					$query_keys = $query_keys . ", is_password_set";
					$query_values = $query_values . ", 1";
				}
				$query = "INSERT INTO accounts(username, password" . (strlen($query_keys) > 0 ? " " . $query_keys : "") . ") VALUES('$username','$password'" . (strlen($query_values) > 0 ? " " . $query_values : "") . ")";
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
