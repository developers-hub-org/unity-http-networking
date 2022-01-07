<?php
	
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
	
?>
