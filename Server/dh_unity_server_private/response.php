<?php

    function get_response($connection, $json)
    {
		switch($json->request)
		{
			case 0:
				
				// ------ This is a test: Create table --------
				$query = "CREATE TABLE IF NOT EXISTS accounts(id int(11) AUTO_INCREMENT, username varchar(255) NOT NULL, password varchar(255) NOT NULL, score int(11), PRIMARY KEY (id))";
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
		}
		return 'NULL';
    }

?>