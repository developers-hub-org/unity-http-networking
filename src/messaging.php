<?php
	
	function get_unread_messages_count($connection, $account_id, $response)
	{
		$count = 0;
		$query = "SELECT COUNT(messages.id) AS count FROM messages LEFT JOIN messages_views ON messages.id = messages_views.message_id WHERE messages.receiver_id = $account_id AND messages_views.id IS NULL";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			while($row = mysqli_fetch_assoc($result))
			{
				$count = $row['count'];
			}
		}
		$response["unread_private_messages"] = $count;
		$public_count = 0;
		$query = "SELECT COUNT(DISTINCT messages.id) AS count FROM messages INNER JOIN chat_group_members ON chat_group_members.member_id = $account_id AND messages.group_id IS NOT NULL AND messages.receiver_id IS NULL AND messages.group_id = chat_group_members.group_id AND chat_group_members.is_active = 1 LEFT JOIN messages_views ON messages.id = messages_views.message_id AND messages_views.account_id <> $account_id LEFT JOIN chat_groups ON messages.group_id = chat_groups.id WHERE chat_groups.is_active = 1";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			while($row = mysqli_fetch_assoc($result))
			{
				$public_count = $row['count'];
			}
		}
		$response["unread_public_messages"] = $public_count;
		return $response;
	}

	function set_single_message_seen($connection, $account_id, $message_id, $response)
	{
		$query = "SELECT id FROM messages WHERE id = $message_id AND sender_id <> $account_id";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			$query = "SELECT id FROM messages_views WHERE message_id = $message_id AND account_id = $account_id";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) == 0)
			{
				$query = "INSERT INTO messages_views (message_id, account_id) VALUES($message_id, $account_id)";
				$result = mysqli_query($connection, $query);
			}
			$response["successful"] = true;
		}
		else
		{
			$response["error"] = "MESSAGE_NOT_EXISTS";
		}
		return $response;
	}

	function set_bulk_message_seen($connection, $account_id, $last_message_id, $sender_id, $group_id, $response)
	{
		$query = "SELECT receiver_id, group_id, send_time FROM messages WHERE id = $last_message_id AND sender_id <> $account_id";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			$msg_receiver_id = -1;
			$msg_group_id = -1;
			$send_time = date('Y-m-d H:i:s');
			while($row = mysqli_fetch_assoc($result))
			{
				$msg_receiver_id = $row['receiver_id'];
				$msg_group_id = $row['group_id'];
				$send_time = $row['send_time'];
			}
			if($sender_id != null && $msg_receiver_id == $account_id)
			{
				$query = "SELECT id FROM messages WHERE sender_id = $sender_id AND send_time <= $send_time AND id NOT IN (SELECT message_id FROM messages_views WHERE account_id = $account_id)";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					while($row = mysqli_fetch_assoc($result))
					{
						$id = $row['id'];
						$query = "INSERT INTO messages_views (message_id, account_id) VALUES($id, $account_id)";
						$result = mysqli_query($connection, $query);
					}
				}
			}
			if($group_id != null && $group_id == $msg_group_id)
			{
				$query = "SELECT id FROM chat_group_members WHERE group_id = $group_id AND member_id = $account_id AND is_active = 1";
				$result = mysqli_query($connection, $query);
				if($result && mysqli_num_rows($result) > 0)
				{
					$query = "SELECT id FROM messages WHERE group_id = $group_id AND send_time <= $send_time AND id NOT IN (SELECT message_id FROM messages_views WHERE account_id = $account_id)";
					$result = mysqli_query($connection, $query);
					if($result && mysqli_num_rows($result) > 0)
					{
						while($row = mysqli_fetch_assoc($result))
						{
							$id = $row['id'];
							$query = "INSERT INTO messages_views (message_id, account_id) VALUES($id, $account_id)";
							$result = mysqli_query($connection, $query);
						}
					}
				}
			}
			$response["successful"] = true;
		}
		else
		{
			$response["error"] = "MESSAGE_NOT_EXISTS";
		}
		return $response;
	}

	function get_messages_dynamic_data_update($connection, $messages, $response)
	{

	}

	function create_group($connection, $account_id, $group_username, $group_display_name = "", $group_description = "", $group_picture_url = "", $response)
	{
		$query = "SELECT id FROM chat_groups WHERE username = $group_username AND is_active > 0";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			$response["error"] = "GROUP_USERNAME_TAKEN";
		}
		else
		{

		}
		return $response;
	}

	function delete_group($connection, $account_id, $group_id, $response)
	{
		$query = "SELECT id FROM chat_groups WHERE id = $group_id AND is_active > 0";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			$query = "SELECT id FROM chat_group_members WHERE member_id = $account_id AND group_id = $group_id AND is_active > 0 AND role = 0";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				$query = "UPDATE chat_group_members SET is_active = 0 WHERE group_id = $group_id";
				mysqli_query($connection, $query);
				$query = "UPDATE chat_groups SET is_active = 0 WHERE id = $group_id";
				mysqli_query($connection, $query);
				$query = "DELETE FROM messages WHERE group_id = $group_id";
				mysqli_query($connection, $query);
				$response["successful"] = true;
			}
			else
			{
				$response["error"] = "DELETE_GROUP_NOT_ALLOWED";
			}
		}
		else
		{
			$response["error"] = "GROUP_NOT_EXISTS";
		}
		return $response;
	}

	function join_group($connection, $account_id, $group_id, $response)
	{
		$response["requested"] = false;
		$response["joined"] = false;
		if($accountaccount_id_d != null && $group_id != null)
		{
			$query = "SELECT blocked, anyone_join FROM chat_groups WHERE id = $group_id AND is_active > 0";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				$is_blocked = true;
				$anyone_can_join = false;
				while($row = mysqli_fetch_assoc($result))
				{
					$is_blocked = ($row['is_blocked'] > 0);
					$anyone_can_join = ($row['anyone_join'] > 0);
				}
				if($is_blocked)
				{
					$response["error"] = "BLOCKED";
				}
				else
				{
					$membership_id = -1;
					$already_member = false;
					$query = "SELECT id, is_active FROM chat_group_members WHERE member_id = $account_id AND group_id = $group_id";
					$result = mysqli_query($connection, $query);
					if($result && mysqli_num_rows($result) > 0)
					{
						while($row = mysqli_fetch_assoc($result))
						{
							$membership_id = $row['id'];
							$already_member = ($row['is_active'] > 0);
						}
					}
					if($already_member)
					{
						$response["error"] = "ALREADY_MEMBER";
					}
					else
					{
						if($membership_id > 0)
						{
							$query = "UPDATE chat_group_members SET role = 1, is_active = 1, group_id = $group_id, member_id = $account_id, joined_time = CURRENT_TIMESTAMP, can_send_message = 1 WHERE id = $membership_id";
							mysqli_query($connection, $query);
						}
						else
						{
							$query = "INSERT INTO chat_group_members (role, group_id, member_id) VALUES(1, $group_id, $account_id)";
							mysqli_query($connection, $query);
							$membership_id = mysqli_insert_id($connection);
						}
						$response["membership_id"] = $membership_id;
						$response["successful"] = true;
					}
				}
			}
			else
			{
				$response["error"] = "GROUP_NOT_EXISTS";
			}
		}
		return $response;
	}

	function send_message($connection, $account_id, $key, $text, $target_account_id, $target_group_id, $have_attachment, $attachment_url, $is_forwarded, $forwarded_from_id, $response)
	{
		if($target_account_id != null && $target_account_id > 0)
		{
			$query = "SELECT COUNT(users_blacklist.id) AS is_blocked FROM accounts LEFT JOIN users_blacklist ON users_blacklist.blocked_id = $account_id AND users_blacklist.blocker_id = $target_account_id AND users_blacklist.is_active > 0 WHERE accounts.id = $target_account_id";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				$is_blocked = true;
				while($row = mysqli_fetch_assoc($result))
				{
					$is_blocked = ($row['is_blocked'] > 0);
				}
				if($is_blocked)
				{
					$response["error"] = "BLOCKED";
				}
				else
				{
					$have_attach = 0;
					$attach = "n";
					if($have_attachment)
					{
						$have_attach = 1;
						$attach = $attachment_url;
					}
					$query = "INSERT INTO messages (sender_id, receiver_id, encryption_key, message_text, have_attachment, attachment_url) VALUES($account_id, $target_account_id, '$key', '$text', $have_attach, '$attach')";
					mysqli_query($connection, $query);
					$response["message_id"] = mysqli_insert_id($connection);
					$response["successful"] = true;
				}
			}
			else
			{
				$response["error"] = "NO_RECEIVER";
			}
		}
		else if($target_group_id != null && $target_group_id > 0)
		{
			$query = "SELECT chat_group_members.role, chat_group_members.can_send_message, chat_groups.blocked FROM chat_group_members INNER JOIN chat_groups ON chat_group_members.group_id = chat_groups.id AND chat_groups.is_active > 0 WHERE chat_group_members.member_id = $account_id AND chat_group_members.group_id = $target_group_id AND chat_group_members.is_active > 0";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				$is_blocked = true;
				$can_send_message = false;
				while($row = mysqli_fetch_assoc($result))
				{
					$is_blocked = ($row['blocked'] > 0);
					$can_send_message = ($row['can_send_message'] > 0);
				}
				if($is_blocked)
				{
					$response["error"] = "BLOCKED";
				}
				else if(!$can_send_message)
				{
					$response["error"] = "NOT_ALLOWED";
				}
				else
				{
					$have_attach = 0;
					$attach = "n";
					if($have_attachment)
					{
						$have_attach = 1;
						$attach = $attachment_url;
					}
					$query = "INSERT INTO messages (sender_id, group_id, encryption_key, message_text, have_attachment, attachment_url) VALUES($account_id, $target_group_id, '$key', '$text', $have_attach, '$attach')";
					mysqli_query($connection, $query);
					$response["message_id"] = mysqli_insert_id($connection);
					$response["successful"] = true;
				}
			}
			else
			{
				$response["error"] = "NO_RECEIVER";
			}
		}
		else
		{
			$response["error"] = "NO_RECEIVER";
		}
		return $response;
	}

	function delete_message($connection, $account_id, $message_id, $for_everyone = true, $response)
	{
		$query = "SELECT messages.sender_id, messages.receiver_id, messages.sender_delete, messages.receiver_delete, messages.have_attachment, COUNT(messages_views.id) AS views_count FROM messages LEFT JOIN messages_views ON messages.id = messages_views.message_id WHERE messages.id = $message_id GROUP BY messages.id";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) == 1)
		{
			$delete_for_sender = 0;
			$delete_for_receiver = 0;
			while($row = mysqli_fetch_assoc($result))
			{
				$sender_id = $row['sender_id'];
				$receiver_id = $row['receiver_id'];
				$sender_delete = $row['sender_delete'];
				$receiver_delete = $row['receiver_delete'];
				$have_attachment = $row['have_attachment'];
				$views_count = $row['views_count'];
				if($sender_id == $account_id)
				{
					$delete_for_sender = 1;
					if($for_everyone && $views_count == 0)
					{
						$delete_for_receiver = 1;
					}
				}
				else if($receiver_id != null && $receiver_id == $account_id)
				{
					$delete_for_receiver = 1;
				}
				if($delete_for_sender > 0 || $delete_for_receiver > 0)
				{
					$query = "UPDATE messages SET sender_delete = $delete_for_sender, receiver_delete = $delete_for_receiver WHERE id = $message_id";
					mysqli_query($connection, $query);
					$response["successful"] = true;
					$response["for_sender"] = ($delete_for_sender > 0);
					$response["for_receiver"] = ($delete_for_receiver > 0);
				}
				else
				{
					$response["error"] = "DELETE_NOT_ALLOWED";
				}
			}
		}
		else
		{
			$response["error"] = "MESSAGE_NOT_EXIST";
		}
		return $response;
	}

	function get_conversations($connection, $account_id, $response)
	{
		$private_conversations = array();
		$public_conversations = array();
		$id_list_private = array();
		$id_list_public = array();
		$query = "SELECT contacts.contact AS id, accounts.username, accounts.picture_url, accounts.firstname, accounts.middlename, accounts.lastname, (contacts.total_messages - contacts.seen_messages - ROUND(((contacts.sender_sum - contacts.contact * contacts.total_messages)/($account_id - contacts.contact)))) AS unread_messaged FROM (SELECT messages.sender_id, messages.receiver_id, (messages.sender_id + messages.receiver_id - $account_id) as contact, SUM(messages.sender_id) as sender_sum, MAX(messages.send_time) AS last_send_time, COUNT(messages.id) AS total_messages, COUNT(messages_views.id) AS seen_messages FROM messages LEFT JOIN messages_views ON messages.id = messages_views.message_id AND messages_views.account_id = $account_id WHERE (messages.sender_id = $account_id OR messages.receiver_id = $account_id) AND messages.receiver_id IS NOT NULL AND messages.receiver_id > 0 GROUP BY contact) AS contacts INNER JOIN accounts ON contacts.contact = accounts.id";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			while($row = mysqli_fetch_assoc($result))
			{
				$id_list_private[] = $row['id'];
				array_push($private_conversations, $row);
			}
		}
		$query = "SELECT chat_groups.id AS id, chat_groups.username, chat_groups.picture_url, chat_groups.is_active, chat_groups.display_name, chat_groups.blocked, COUNT(messages.id) - ROUND(SUM(messages_views.account_id) / $account_id) AS unread_messages FROM chat_group_members INNER JOIN chat_groups ON chat_groups.id = chat_group_members.group_id LEFT JOIN messages ON chat_group_members.group_id = messages.group_id LEFT JOIN messages_views ON messages_views.message_id = messages.id AND messages_views.account_id = $account_id WHERE chat_group_members.member_id = $account_id AND chat_group_members.is_active > 0 GROUP BY chat_groups.id";
		$result = mysqli_query($connection, $query);
		if($result && mysqli_num_rows($result) > 0)
		{
			while($row = mysqli_fetch_assoc($result))
			{
				if($row['is_active'] <= 0 && $row['blocked'] <= 0)
				{
					$id_list_public[] = $row['id'];
				}
				array_push($public_conversations, $row);
			}
		}
		$period = CONNECTION_CHECK_PERIOD + 5;
		if(count($private_conversations) > 0)
		{
			$query = "SELECT account_id FROM sessions WHERE account_id IN (" . implode(',', $id_list_private) . ") AND activity >= CURRENT_TIMESTAMP - INTERVAL " . $period . " SECOND ORDER BY account_id ASC";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				usort($private_conversations, 'compare_array_id');
				$j = 0;
				while($row = mysqli_fetch_assoc($result))
				{	
					for ($i = $j; $i < count($private_conversations); $i++)
					{
						if ($private_conversations[$i]['id'] === $row["account_id"]) 
						{
							$private_conversations[$i]["is_online"] = 1;
							$j = $i + 1;
							break;
						}
					}
				}
			}
		}
		if(count($public_conversations) > 0)
		{
			$query = "SELECT chat_groups.id AS id, COUNT(sessions.id) AS online_count FROM chat_groups LEFT JOIN chat_group_members ON chat_group_members.group_id = chat_groups.id AND chat_group_members.is_active > 0 LEFT JOIN sessions ON sessions.account_id = chat_group_members.member_id AND activity >= CURRENT_TIMESTAMP - INTERVAL " . $period . " SECOND WHERE chat_groups.id IN (" . implode(',', $id_list_public) . ") GROUP BY chat_groups.id ORDER BY chat_groups.id ASC";
			if($result && mysqli_num_rows($result) > 0)
			{
				usort($public_conversations, 'compare_array_id');
				$j = 0;
				while($row = mysqli_fetch_assoc($result))
				{	
					for ($i = $j; $i < count($public_conversations); $i++)
					{
						if ($public_conversations[$i]['id'] === $row["id"]) 
						{
							$public_conversations[$i]["online_count"] = $row["online_count"];
							$j = $i + 1;
							break;
						}
					}
				}
			}
		}
		$response["private"] = $private_conversations;
		$response["public"] = $public_conversations;
		return $response;
	}

	function get_messages($connection, $account_id, $sender_id, $group_id, $last_message_id = 1, $max = 20, $response)
	{
		$query = "SELECT send_time FROM messages WHERE id = $last_message_id";
		$result = mysqli_query($connection, $query);
		$send_time = date('Y-m-d H:i:s');
		if($result && mysqli_num_rows($result) > 0)
		{
			while($row = mysqli_fetch_assoc($result))
			{
				$send_time = $row['send_time'];
			}
		}
		else
		{
			$query = "SELECT MIN(send_time) AS oldest_send_time FROM messages";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				while($row = mysqli_fetch_assoc($result))
				{
					$send_time = $row['oldest_send_time'];
				}
			}
		}
		$messages = array();
		if($sender_id != null && $sender_id > 0)
		{
			$query = "SELECT messages.id, messages.sender_id, messages.receiver_id, messages.group_id, messages.is_forwarded, messages.forwaerded_from_id, messages.sender_delete, messages.receiver_delete, messages.encryption_key, messages.message_text, messages.have_attachment, messages.attachment_url, messages.downloaded, messages.send_time, COUNT(messages_views.id) as views_count FROM messages LEFT JOIN messages_views ON messages.id = messages_views.message_id WHERE ((messages.sender_id = $account_id AND messages.receiver_id = $sender_id) OR (messages.sender_id = $sender_id AND messages.receiver_id = $account_id)) AND messages.send_time >= '$send_time' AND messages.id <> $last_message_id GROUP BY messages.id ORDER BY messages.send_time ASC LIMIT $max";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				while($row = mysqli_fetch_assoc($result))
				{

					array_push($messages, $row);
				}
				$response["last_message_id"] = end($messages)['id'];
			}
		}
		if($group_id != null && $group_id > 0)
		{
			$query = "SELECT messages.id, messages.sender_id, messages.receiver_id, messages.group_id, messages.is_forwarded, messages.forwaerded_from_id, messages.sender_delete, messages.receiver_delete, messages.encryption_key, messages.message_text, messages.have_attachment, messages.attachment_url, messages.downloaded, messages.send_time, COUNT(messages_views.id) as views_count FROM messages LEFT JOIN messages_views ON messages.id = messages_views.message_id WHERE messages.group_id = $group_id AND messages.send_time >= '$send_time' AND messages.id <> $last_message_id GROUP BY messages.id ORDER BY messages.send_time ASC LIMIT $max";
			$result = mysqli_query($connection, $query);
			if($result && mysqli_num_rows($result) > 0)
			{
				while($row = mysqli_fetch_assoc($result))
				{
					array_push($messages, $row);
				}
				$response["last_message_id"] = end($messages)['id'];
			}
		}
		else
		{
			$response["error"] = "NO_CONTACT";
		}
		// $response["is_there_more_message"] = (count($messages) >= $max);
		$response["messages"] = $messages;
		return $response;
	}

	function compare_array_id($array1, $array2)
	{
		return strnatcmp($array1['id'], $array2['id']);
	}

?>
