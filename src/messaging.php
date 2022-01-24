<?php
	
	function get_unread_messages_count($connection, $account_id)
	{
		$response = array();
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
// set bulk seen based on time
	function set_message_seen($connection, $account_id, $message_id)
	{
		$query = "SELECT id FROM messages WHERE id = $message_id";
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
			$response["successful"] = "MESSAGE_NOT_EXISTS";
		}
		else
		{
			$response["error"] = "MESSAGE_NOT_EXISTS";
		}
		return $response;
	}

	function get_messages_dynamic_data_update($connection, $messages)
	{

	}

	function get_messages($connection, $account_id, $max = 10, $mark_as_delivered = true)
	{
		// need to eddect group
		$query = "SELECT messages.id, messages.sender_id, messages.receiver_type, messages.group_id, messages.encryption_key, messages.message_text, messages.is_file, messages.send_time, accounts.username FROM messages INNER JOIN accounts ON messages.sender_id = accounts.id WHERE messages.receiver_id = $account_id AND messages.delivered = 0 ORDER BY messages.send_time DESC LIMIT $max";
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
	
?>
