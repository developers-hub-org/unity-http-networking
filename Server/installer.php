<?php
  //ini_set('display_errors', '0');
  if (!empty($_POST))
  {
    if(isset($_POST['project_name']) && isset($_POST['database_name']) && isset($_POST['database_user']) && isset($_POST['database_host']) && isset($_POST['database_pass']) && isset($_POST['aes_key']) && isset($_POST['md5_key']))
    {
      $project = trim($_POST['project_name']);
      $database = trim($_POST['database_name']);
      $username = trim($_POST['database_user']);
      $password = trim($_POST['database_pass']);
      $host = trim($_POST['database_host']);
      $aes = trim($_POST['aes_key']);
      $md5 = trim($_POST['md5_key']);
	    unset($_POST);
      $connection = mysqli_connect($host, $username, $password, $database);
      if(mysqli_connect_errno())
      {
        display_error("Database credentials are not valid.");
      }
      else
      {
        $have_permissions = does_have_mysql_permissions($connection);
        $accounts_table = mysqli_query($connection, "SELECT 1 from accounts LIMIT 1");
        if($accounts_table)
        {
          $queries = array(
            "ALTER TABLE accounts ADD COLUMN id INT(11) AUTO_INCREMENT PRIMARY KEY",
			"ALTER TABLE accounts ADD COLUMN is_verified TINYINT(1) DEFAULT 0",
            "ALTER TABLE accounts ADD COLUMN username VARCHAR(255)",
            "ALTER TABLE accounts ADD COLUMN password VARCHAR(255)",
			"ALTER TABLE accounts ADD COLUMN firstname VARCHAR(255) DEFAULT ''",
            "ALTER TABLE accounts ADD COLUMN lastname VARCHAR(255) DEFAULT ''",
            "ALTER TABLE accounts ADD COLUMN is_password_set TINYINT(1) DEFAULT 0",
            "ALTER TABLE accounts ADD COLUMN email VARCHAR(255) DEFAULT ''",
            "ALTER TABLE accounts ADD COLUMN is_email_verified TINYINT(1) DEFAULT 0",
            "ALTER TABLE accounts ADD COLUMN phone_number VARCHAR(255) DEFAULT ''",
			"ALTER TABLE accounts ADD COLUMN phone_country VARCHAR(255) DEFAULT 'us'",
            "ALTER TABLE accounts ADD COLUMN is_phone_verified TINYINT(1) DEFAULT 0",
			"ALTER TABLE accounts ADD COLUMN picture_url VARCHAR(1000) DEFAULT ''",
            "ALTER TABLE accounts ADD COLUMN score INT(11) DEFAULT 0",
            "ALTER TABLE accounts ADD COLUMN blocked TINYINT(1) DEFAULT 0",
			"ALTER TABLE accounts ADD COLUMN is_birthday_set TINYINT(1) DEFAULT 0",
			"ALTER TABLE accounts ADD COLUMN birthday DATETIME DEFAULT CURRENT_TIMESTAMP"
          );
          for($i = 0; $i < count($queries); $i++) 
          {
            mysqli_query($connection, $queries[$i]);
          }
        }
        else
        {
          $query = "CREATE TABLE IF NOT EXISTS accounts(id INT(11) AUTO_INCREMENT, is_verified TINYINT(1) DEFAULT 0, username VARCHAR(255), password VARCHAR(255), firstname VARCHAR(255) DEFAULT '', lastname VARCHAR(255) DEFAULT '', is_password_set TINYINT(1) DEFAULT 0, email VARCHAR(255) DEFAULT '', is_email_verified TINYINT(1) DEFAULT 0, phone_number VARCHAR(255) DEFAULT '', phone_country VARCHAR(255) DEFAULT 'us', is_phone_verified TINYINT(1) DEFAULT 0, picture_url VARCHAR(1000) DEFAULT '', score INT(11) DEFAULT 0, blocked TINYINT(1) DEFAULT 0, is_birthday_set TINYINT(1) DEFAULT 0, birthday DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id))";
          mysqli_query($connection, $query);
        }
        $sessions_table = mysqli_query($connection, "SELECT 1 from sessions LIMIT 1");
        if($sessions_table)
        {
          $queries = array(
            "ALTER TABLE sessions ADD COLUMN id INT(11) AUTO_INCREMENT PRIMARY KEY",
            "ALTER TABLE sessions ADD COLUMN account_id INT(11)",
            "ALTER TABLE sessions ADD COLUMN username VARCHAR(255)",
            "ALTER TABLE sessions ADD COLUMN session VARCHAR(50)",
            "ALTER TABLE sessions ADD COLUMN ip VARCHAR(50)",
            "ALTER TABLE sessions ADD COLUMN version VARCHAR(50)",
            "ALTER TABLE sessions ADD COLUMN activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
			"ALTER TABLE sessions ADD FOREIGN KEY (account_id) REFERENCES accounts(id)"
          );
          for($i = 0; $i < count($queries); $i++) 
          {
            mysqli_query($connection, $queries[$i]);
          }
        }
        else
        {
          $query = "CREATE TABLE IF NOT EXISTS sessions(id INT(11) AUTO_INCREMENT, account_id INT(11), username VARCHAR(255) NOT NULL, session VARCHAR(50) NOT NULL, ip VARCHAR(50) NOT NULL, version VARCHAR(50) NOT NULL, activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), FOREIGN KEY (account_id) REFERENCES accounts(id))";
          mysqli_query($connection, $query);
        }
		    $blacklist_table = mysqli_query($connection, "SELECT 1 from blacklist LIMIT 1");
        if($blacklist_table)
        {
          $queries = array(
            "ALTER TABLE blacklist ADD COLUMN id INT(11) AUTO_INCREMENT PRIMARY KEY",
            "ALTER TABLE blacklist ADD COLUMN ip VARCHAR(50)",
			      "ALTER TABLE blacklist ADD COLUMN reason INT(11) DEFAULT 0",
            "ALTER TABLE blacklist ADD COLUMN blocktime DATETIME DEFAULT CURRENT_TIMESTAMP"
          );
          for($i = 0; $i < count($queries); $i++) 
          {
            mysqli_query($connection, $queries[$i]);
          }
        }
        else
        {
          $query = "CREATE TABLE IF NOT EXISTS blacklist(id INT(11) AUTO_INCREMENT, ip VARCHAR(50) NOT NULL, reason INT(11) DEFAULT 0, blocktime DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id))";
          mysqli_query($connection, $query);
        }
		$messages_table = mysqli_query($connection, "SELECT 1 from messages LIMIT 1");
        if($messages_table)
        {
          $queries = array(
          "ALTER TABLE messages ADD COLUMN id int(11) AUTO_INCREMENT PRIMARY KEY",
          "ALTER TABLE messages ADD COLUMN sender_id INT(11)",
          "ALTER TABLE messages ADD COLUMN receiver_id INT(11)",
          "ALTER TABLE messages ADD COLUMN receiver_type INT(11) DEFAULT 0",
          "ALTER TABLE messages ADD COLUMN encryption_key VARCHAR(50)",
          "ALTER TABLE messages ADD COLUMN message_text VARCHAR(10000)",
          "ALTER TABLE messages ADD COLUMN delivered TINYINT(1) DEFAULT 0",
          "ALTER TABLE messages ADD COLUMN seen TINYINT(1) DEFAULT 0",
          "ALTER TABLE messages ADD COLUMN sender_delete TINYINT(1) DEFAULT 0",
          "ALTER TABLE messages ADD COLUMN receiver_delete TINYINT(1) DEFAULT 0",
          "ALTER TABLE messages ADD COLUMN send_time DATETIME DEFAULT CURRENT_TIMESTAMP",
          "ALTER TABLE messages ADD COLUMN seen_time DATETIME DEFAULT CURRENT_TIMESTAMP",
          "ALTER TABLE messages ADD FOREIGN KEY (sender_id) REFERENCES accounts(id)",
          "ALTER TABLE messages ADD FOREIGN KEY (receiver_id) REFERENCES accounts(id)"
          );
          for($i = 0; $i < count($queries); $i++) 
          {
            mysqli_query($connection, $queries[$i]);
          }
        }
        else
        {
          $query = "CREATE TABLE IF NOT EXISTS messages(id INT(11) AUTO_INCREMENT, sender_id INT(11), receiver_id INT(11), receiver_type INT(11) DEFAULT 0, encryption_key VARCHAR(50) NOT NULL, message_text VARCHAR(10000) NOT NULL, delivered TINYINT(1) DEFAULT 0, seen TINYINT(1) DEFAULT 0, sender_delete TINYINT(1) DEFAULT 0, receiver_delete TINYINT(1) DEFAULT 0, send_time DATETIME DEFAULT CURRENT_TIMESTAMP, seen_time DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), FOREIGN KEY (sender_id) REFERENCES accounts(id), FOREIGN KEY (receiver_id) REFERENCES accounts(id))";
          mysqli_query($connection, $query);
        }
		$users_blacklist_table = mysqli_query($connection, "SELECT 1 from users_blacklist LIMIT 1");
		if($users_blacklist_table)
		{
          $queries = array(
          "ALTER TABLE users_blacklist ADD COLUMN id int(11) AUTO_INCREMENT PRIMARY KEY",
		  "ALTER TABLE users_blacklist ADD COLUMN is_active TINYINT(1) DEFAULT 1",
          "ALTER TABLE users_blacklist ADD COLUMN blocker_id INT(11)",
          "ALTER TABLE users_blacklist ADD COLUMN blocked_id INT(11)",
          "ALTER TABLE users_blacklist ADD COLUMN block_type INT(11) DEFAULT 0",
          "ALTER TABLE users_blacklist ADD COLUMN block_time DATETIME DEFAULT CURRENT_TIMESTAMP",
          "ALTER TABLE users_blacklist ADD FOREIGN KEY (blocker_id) REFERENCES accounts(id)",
          "ALTER TABLE users_blacklist ADD FOREIGN KEY (blocked_id) REFERENCES accounts(id)"
          );
          for($i = 0; $i < count($queries); $i++) 
          {
            mysqli_query($connection, $queries[$i]);
          }
        }
        else
        {
          $query = "CREATE TABLE IF NOT EXISTS users_blacklist(id INT(11) AUTO_INCREMENT, is_active TINYINT(1) DEFAULT 1, blocker_id INT(11), blocked_id INT(11), block_type INT(11) DEFAULT 0, block_time DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), FOREIGN KEY (blocker_id) REFERENCES accounts(id), FOREIGN KEY (blocked_id) REFERENCES accounts(id))";
          mysqli_query($connection, $query);
        }
		$chat_groups_table = mysqli_query($connection, "SELECT 1 from chat_groups LIMIT 1");
		if($chat_groups_table)
		{
          $queries = array(
          "ALTER TABLE chat_groups ADD COLUMN id INT(11) AUTO_INCREMENT PRIMARY KEY",
		  "ALTER TABLE chat_groups ADD COLUMN is_active TINYINT(1) DEFAULT 1",
		  "ALTER TABLE chat_groups ADD COLUMN username VARCHAR(255) DEFAULT ''",
		  "ALTER TABLE chat_groups ADD COLUMN picture_url VARCHAR(1000) DEFAULT ''",
		  "ALTER TABLE chat_groups ADD COLUMN display_name VARCHAR(255)",
		  "ALTER TABLE chat_groups ADD COLUMN description VARCHAR(1000) DEFAULT ''",
          "ALTER TABLE chat_groups ADD COLUMN creator_id INT(11)",
		  "ALTER TABLE chat_groups ADD COLUMN blocked TINYINT(1) DEFAULT 0",
          "ALTER TABLE chat_groups ADD COLUMN block_type INT(11) DEFAULT 0",
          "ALTER TABLE chat_groups ADD COLUMN created_time DATETIME DEFAULT CURRENT_TIMESTAMP",
          "ALTER TABLE chat_groups ADD FOREIGN KEY (creator_id) REFERENCES accounts(id)"
          );
          for($i = 0; $i < count($queries); $i++) 
          {
            mysqli_query($connection, $queries[$i]);
          }
        }
        else
        {
          $query = "CREATE TABLE IF NOT EXISTS chat_groups(id INT(11) AUTO_INCREMENT, is_active TINYINT(1) DEFAULT 1, username VARCHAR(255) DEFAULT '', picture_url VARCHAR(1000) DEFAULT '', display_name VARCHAR(255), description VARCHAR(1000) DEFAULT '', creator_id INT(11), blocked TINYINT(1) DEFAULT 0, block_type INT(11) DEFAULT 0, created_time DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), FOREIGN KEY (creator_id) REFERENCES accounts(id))";
          mysqli_query($connection, $query);
        }
		$chat_group_members_table = mysqli_query($connection, "SELECT 1 from chat_group_members LIMIT 1");
		if($chat_group_members_table)
		{
          $queries = array(
          "ALTER TABLE chat_group_members ADD COLUMN id int(11) AUTO_INCREMENT PRIMARY KEY",
		      "ALTER TABLE chat_group_members ADD COLUMN is_active TINYINT(1) DEFAULT 1",
		      "ALTER TABLE chat_group_members ADD COLUMN role INT(11) DEFAULT 0",
		      "ALTER TABLE chat_group_members ADD COLUMN group_id INT(11)",
          "ALTER TABLE chat_group_members ADD COLUMN member_id INT(11)",
		      "ALTER TABLE chat_group_members ADD COLUMN can_send_message INT(1) DEFAULT 1",
          "ALTER TABLE chat_group_members ADD COLUMN joined_time DATETIME DEFAULT CURRENT_TIMESTAMP",
          "ALTER TABLE chat_group_members ADD FOREIGN KEY (group_id) REFERENCES chat_groups(id)",
		      "ALTER TABLE chat_group_members ADD FOREIGN KEY (member_id) REFERENCES accounts(id)"
          );
          for($i = 0; $i < count($queries); $i++) 
          {
            mysqli_query($connection, $queries[$i]);
          }
        }
        else
        {
          $query = "CREATE TABLE IF NOT EXISTS chat_group_members(id INT(11) AUTO_INCREMENT, is_active TINYINT(1) DEFAULT 1, role INT(11) DEFAULT 0, username VARCHAR(255), group_id INT(11), member_id INT(11), can_send_message INT(1) DEFAULT 1, joined_time DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), FOREIGN KEY (member_id) REFERENCES accounts(id), FOREIGN KEY (group_id) REFERENCES chat_groups(id))";
          mysqli_query($connection, $query);
        }
        $verification_codes_table = mysqli_query($connection, "SELECT 1 from verification_codes LIMIT 1");
        if($verification_codes_table)
        {
              $queries = array(
              "ALTER TABLE verification_codes ADD COLUMN id int(11) AUTO_INCREMENT PRIMARY KEY",
              "ALTER TABLE verification_codes ADD COLUMN is_used TINYINT(1) DEFAULT 0",
              "ALTER TABLE verification_codes ADD COLUMN code VARCHAR(255)",
              "ALTER TABLE verification_codes ADD COLUMN type INT(11) DEFAULT 0",
              "ALTER TABLE verification_codes ADD COLUMN account_id INT(11)",
              "ALTER TABLE verification_codes ADD COLUMN create_time DATETIME DEFAULT CURRENT_TIMESTAMP",
              "ALTER TABLE verification_codes ADD COLUMN expire_time DATETIME DEFAULT CURRENT_TIMESTAMP",
              "ALTER TABLE verification_codes ADD FOREIGN KEY (account_id) REFERENCES accounts(id)"
              );
              for($i = 0; $i < count($queries); $i++) 
              {
                mysqli_query($connection, $queries[$i]);
              }
            }
            else
            {
              $query = "CREATE TABLE IF NOT EXISTS verification_codes(id INT(11) AUTO_INCREMENT, is_used TINYINT(1) DEFAULT 0, code VARCHAR(255), type INT(11) DEFAULT 0, account_id INT(11), create_time DATETIME DEFAULT CURRENT_TIMESTAMP, expire_time DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), FOREIGN KEY (account_id) REFERENCES accounts(id))";
              mysqli_query($connection, $query);
            }
        mysqli_close($connection);
        $path = get_base_path();
        if($path != null)
        {
          $project_name = str_replace(" ", "_", strtolower($project));
          $private_path = $path['private_path'] . "unity_projects" . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;
          $public_path = $path['public_path'] . "unity_projects" . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;
          if(download_repository($public_path, $private_path, $project, $project_name, $database, $username, $password, $aes, $md5, $host))
          {
            $api_link = get_base_url();
            if(substr($api_link, -1) != "/")
            {
              $api_link =  $api_link . "/";
            }
            $api_link = $api_link . "unity_projects/" . $project_name . "/api.php";
            if(is_localhost())
            {
              $api_link = "http://localhost/unity_projects/" . $project_name . "/api.php";
            }
            echo "
            <html>
            <head>
            <title>Successful</title>
            <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css?family=Sora\">
            <style>
              html, body {height: 100%;}
              .container {margin: auto; text-align: center; height: 100%; width: 100%; vertical-align: middle;}
              .alert {padding: 20px; color: white;}
              body {font-family: 'Sora', serif; font-size: 15px;}
            </style>
            </head>
            <body style=\"background-color:#009b77;\">
              <div class=\"container\">
				<br/>
				<br/>
                <div class=\"alert\">
                  Installation has been completed successfully.
                </div>
                <div class=\"alert\">
                  API Link: <strong>" . $api_link . "</strong>
                </div>
                <div class=\"alert\">
                  AES Encryption Key: <strong>" . $aes . "</strong>
                </div>
                <div class=\"alert\">
                  MD5 Encryption Key: <strong>" . $md5 . "</strong>
                </div>".
                (!$have_permissions ? "
                <div class=\"alert\">
                  WARNING: We detected that you might not have necessary mysql permissions. Installation might have been corrupted.
                </div>
                " : "")
              ."</div>
            </body>
            ";
            unlink(__FILE__);
          }
          else
          {
            display_error("Failed to download files from repository.");
          }
        }
        else
        {
            display_error("Failed to find a valid hosting directory.");
        }
	    }
    }
    else
    {
      display_error("Something went wrong!");
    }
	  exit();
  }
  
  function debug_log($log)
  {
    echo("<strong>" . $log . "</strong><br/>");
  }

  function download_repository($public_path, $private_path, $project_name_original, $project_name, $database, $username, $password, $aes, $md5, $host)
  {
	
	set_time_limit(120);
	
	if ($private_path != $public_path && !file_exists($private_path)) 
	{
		mkdir($private_path, 0777, true);
	}
	if (!file_exists($public_path)) 
	{
		mkdir($public_path, 0777, true);
	}
    if (!file_exists($private_path . "templates")) 
    {
      mkdir($private_path . "templates", 0777, true);
    }
	
    $repository_link = "https://raw.githubusercontent.com/developers-hub-org/unity-http-networking/main/Server/";
	/*
	$files_to_download = array(
		$repository_link . "api.php" => $public_path . "api.php",
		$repository_link . "files.ini" => $public_path . "files.ini",
		$repository_link . "core.php" => $private_path . "api.php",
		$repository_link . "authentication.php" => $private_path . "authentication.php",
		$repository_link . "messaging.php" => $private_path . "messaging.php",
		$repository_link . "config.php" => $private_path . "config.php",
		$repository_link . "core_response.php" => $private_path . "core_response.php",
		$repository_link . "encryption.php" => $private_path . "encryption.php",
		$repository_link . "connection.php" => $private_path . "connection.php",
		$repository_link . "control.php" => $private_path . "control.php",
		$repository_link . "response.php" => $private_path . "response.php",
		$repository_link . "templates/email_verifIcation_code_template.html" => $private_path . "templates/email_verification_code_template.html"
	);
	*/
	
    if(file_exists($public_path . "api.php")) {unlink($public_path . "api.php");}
    $downloaded = download_file($repository_link . "api.php", $public_path);
    if(!$downloaded){return false;}
	
    if(file_exists($private_path . "core.php")) {unlink($private_path . "core.php");}
    $downloaded = download_file($repository_link . "core.php", $private_path);
	if(!$downloaded){return false;}
	
    if(file_exists($private_path . "templates/" . "verification_code_template.html")) {unlink($private_path . "templates/" . "verification_code_template.html");}
    $downloaded = download_file($repository_link . "templates/verifIcation_code_template.html", $private_path . "templates/");
    if(!$downloaded){return false;}

    if(file_exists($public_path . "files.ini")) {unlink($public_path . "files.ini");}
    $downloaded = download_file($repository_link . "files.ini", $public_path);
    if(!$downloaded){return false;}
	
    $prefix = generate_random_string(rand(10, 20));
    $core_file_name = "core_" . $project_name . "_" . $prefix . ".php";
    $link_file_name = "link_" . $project_name . "_" . $prefix . ".ini";
    rename($private_path . "core.php", $private_path . $core_file_name);
    file_put_contents($public_path . "files.ini", "core_file_name = " . $core_file_name . "\n" . "link_file_name = " . $link_file_name . "");
    chmod($private_path . $core_file_name, 0600);
    chmod($public_path . "files.ini", 0600);
	
    if(file_exists($private_path . "messaging.php")) {unlink($private_path . "messaging.php");}
    $downloaded = download_file($repository_link . "messaging.php", $private_path);
    if(!$downloaded){return false;}

    if(file_exists($private_path . "authentication.php")) {unlink($private_path . "authentication.php");}
    $downloaded = download_file($repository_link . "authentication.php", $private_path);
    if(!$downloaded){return false;}

    if(file_exists($private_path . "config.php")) {unlink($private_path . "config.php");}
    $downloaded = download_file($repository_link . "config.php", $private_path);
    if(!$downloaded){return false;}

    $confog_data = "
<?php
	define('PROJECT_NAME', '".$project_name_original."');
	define('API_VERSION', '1.0');
	define('DB_NAME', '".$database."');
	define('DB_USER', '".$username."');
	define('DB_PASSWORD', '".$password."');
	define('DB_HOST', '".$host."');
	define('AES_PASSWORD', '".$aes."');
	define('MD5_PASSWORD', '".$md5."');
?>";
    file_put_contents($private_path . "config.php", $confog_data);
	
    // TODO: If control already exist then don't delete it, just add new content.
    if(file_exists($private_path . "control.php")) {unlink($private_path . "control.php");}
    $downloaded = download_file($repository_link . "control.php", $private_path);
    if(!$downloaded){return false;}

    if(file_exists($private_path . "connection.php")) {unlink($private_path . "connection.php");}
    $downloaded = download_file($repository_link . "connection.php", $private_path);
    if(!$downloaded){return false;}
	
    if(file_exists($private_path . "encryption.php")) {unlink($private_path . "encryption.php");}
    $downloaded = download_file($repository_link . "encryption.php", $private_path);
    if(!$downloaded){return false;}
	
    if(file_exists($private_path . "core_response.php")) {unlink($private_path . "core_response.php");}
    $downloaded = download_file($repository_link . "core_response.php", $private_path);
    if(!$downloaded){return false;}

    if(!file_exists($private_path . "response.php"))
    {
      $downloaded = download_file($repository_link . "response.php", $private_path);
      if(!$downloaded){return false;}
    }
    
    return true;
  }
  
  function download_file($url, $dir)
  {
    $file_name = basename($url);
	$data = download_file_by_curl($url, 10);
	if(file_put_contents($dir . $file_name, $data))
	{
		chmod($dir . $file_name, 0600);
		return true;
	}
	/*
	if(ini_get('allow_url_fopen')) 
	{
		
	}
	else
	{
		if(file_put_contents($dir . $file_name, file_get_contents($url)))
		{
			chmod($dir . $file_name, 0600);
			return true;
		}
	}
	*/
	return false;
  }
  
  function download_file_by_curl($url, $timeout)
  {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
	$return = curl_exec($curl);
	curl_close($curl);
	return $return;
  }
  
  function get_base_url()
  {
	  return sprintf("%s://%s", isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http', $_SERVER['SERVER_NAME']);
  }

  function is_localhost() 
  {
	  $whitelist = array( '127.0.0.1', '::1');
	  if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) 
	  {
		  return true;
	  }
	  return false;
  }

  function get_base_path()
  {
    $public_path = correct_path($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR;
    $private_path = correct_path(dirname($public_path)) . DIRECTORY_SEPARATOR;
    $public_folder = correct_path(basename($public_path));
    if(is_writable($private_path) == false)
    {
      $private_path = $public_path;
    }
    $response = array();
    $response['private_path'] = $private_path;
    $response['public_path'] = $public_path;
    $response['public_folder'] = $public_folder;
    return $response;
  }
  
  function correct_path($path)
  {
	  return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
  }

  function does_have_mysql_permissions($connection)
  {
    $query = "SHOW GRANTS FOR CURRENT_USER";
    $result = mysqli_query($connection, $query);
    $select_permission = false;
    $insert_permission = false;
    $update_permission = false;
    $references_permission = false;
    $alter_permission = false;
    $delete_permission = false;
    $create_permission = false;
    $index_permission = false;
    if($result && mysqli_num_rows($result) > 0)
    {
      while($row = mysqli_fetch_assoc($result))
      {
        foreach ($row as &$value) 
        {
          $permissions = strtolower($value);
          if (strpos($permissions, 'all') !== false) 
          {
            $select_permission = true;
            $insert_permission = true;
            $update_permission = true;
            $references_permission = true;
            $alter_permission = true;
            $delete_permission = true;
            $create_permission = true;
            $index_permission = true;
            break 2;
          }
          if (strpos($permissions, 'select') !== false) 
          {
            $select_permission = true;
          }
          if (strpos($permissions, 'insert') !== false) 
          {
            $insert_permission = true;
          }
          if (strpos($permissions, 'update') !== false) 
          {
            $update_permission = true;
          }
          if (strpos($permissions, 'references') !== false) 
          {
            $references_permission = true;
          }
          if (strpos($permissions, 'alter') !== false) 
          {
            $alter_permission = true;
          }
          if (strpos($permissions, 'create') !== false) 
          {
            $create_permission = true;
          }
          if (strpos($permissions, 'index') !== false) 
          {
            $index_permission = true;
          }
        }
      }
    }
    return $select_permission && $insert_permission && $update_permission && $references_permission && $alter_permission && $delete_permission && $create_permission && $index_permission;
  }

  function display_error($message)
  {
    echo "
    <html>
    <head>
    <title>Error</title>
    <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css?family=Sora\">
    <style>
      html, body {height: 100%;}
      .container {margin: auto; text-align: center; height: 100%; width: 100%; vertical-align: middle;}
      .alert {padding: 20px; background-color: #bc243c; color: white;}
      body {font-family: 'Sora', serif; font-size: 15px;}
    </style>
    </head>
    <body>
    <div class=\"container\">
      <div class=\"alert\">
      <script type=\"text/javascript\">
        var a = document.getElementById(\"link_id\");
        a.onclick = function() 
        {
        location.reload();
        }
      </script>
      ".$message." Click <a id=\"link_id\" href=\"\">here</a> to try again.
      </div>
    </div>
    </body>
    ";
  }
  
  function generate_random_string($length)
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) 
	  {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Unity Web Hosting API</title>
        <link href="https://fonts.googleapis.com/css?family=Karla:400" rel="stylesheet" type="text/css">
        <style>
            html, body 
            {
                height: 100%;
            }
            body 
            {
                margin: 0;
                padding: 0;
                width: 100%;
				        height: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Karla';
            }
            .container 
            {
				        margin: auto;
                text-align: center;
                height: 100%;
				        width: 400px;
                vertical-align: middle;
            }
            .content 
            {
                text-align: center;
            }
            .title 
            {
                font-size: 20px;
            }
            .opt 
            {
                margin-top: 30px;
            }
            .opt a 
            {
              text-decoration: none;
              font-size: 150%;
            }
            a:hover 
            {
              color: red;
            }
            .button-git 
            {
              appearance: none;
              background-color: #FAFBFC;
              border: 1px solid rgba(27, 31, 35, 0.15);
              border-radius: 6px;
              box-shadow: rgba(27, 31, 35, 0.04) 0 1px 0, rgba(255, 255, 255, 0.25) 0 1px 0 inset;
              box-sizing: border-box;
              color: #24292E;
              cursor: pointer;
              display: inline-block;
              font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
              font-size: 14px;
              font-weight: 500;
              line-height: 20px;
              list-style: none;
              padding: 6px 16px;
              position: relative;
              transition: background-color 0.2s cubic-bezier(0.3, 0, 0.5, 1);
              user-select: none;
              -webkit-user-select: none;
              touch-action: manipulation;
              vertical-align: middle;
              white-space: nowrap;
              word-wrap: break-word;
            }
            .button-git:hover 
            {
              background-color: #F3F4F6;
              text-decoration: none;
              transition-duration: 0.1s;
            }
            .button-git:disabled 
            {
              background-color: #FAFBFC;
              border-color: rgba(27, 31, 35, 0.15);
              color: #959DA5;
              cursor: default;
            }
            .button-git:active 
            {
              background-color: #EDEFF2;
              box-shadow: rgba(225, 228, 232, 0.2) 0 1px 0 inset;
              transition: none 0s;
            }
            .button-git:focus 
            {
              outline: 1px transparent;
            }
            .button-git:before 
            {
              display: none;
            }
            .button-git:-webkit-details-marker 
            {
              display: none;
            }
            input[type=text], select 
            {
              width: 100%;
              padding: 12px 20px;
              margin: 8px 0;
              display: inline-block;
              border: 1px solid #ccc;
              border-radius: 4px;
              box-sizing: border-box;
            }
            input[type=submit] 
            {
              width: 100%;
              background-color: #4CAF50;
              color: white;
              padding: 14px 20px;
              margin: 8px 0;
              border: none;
              border-radius: 4px;
              cursor: pointer;
            }
            input[type=submit]:hover 
            {
              background-color: #45a049;
            }
            .alert 
            {
              padding: 20px;
              background-color: #f44336;
              color: white;
            }
            .closebtn 
            {
              margin-left: 15px;
              color: white;
              font-weight: bold;
              float: right;
              font-size: 22px;
              line-height: 20px;
              cursor: pointer;
              transition: 0.3s;
            }
            .closebtn:hover 
            {
              color: black;
            }
        </style>
    </head>
    <body>
        <br/>
        <div class="container">
            <div class="content">
              <table width="100%">
                <tr>
                    <td width="40%" align=Left>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/c4/Unity_2021.svg" alt="Unity Logo" style="width:80px;height:80px;">
                    </td>
                    <td width="20%">

                    </td>
                    <td width="40%" align=right>
                    <img src="https://upload.wikimedia.org/wikipedia/en/thumb/d/dd/MySQL_logo.svg/800px-MySQL_logo.svg.png" alt="MySQL Logo" style="width:80px;height:55px;">
                    </td>
                </tr>
              </table>
                <div class="title">Unity Web Hosting API Installer</div><br/>
                  <form id="dataForm" action="?" method="post" autocomplete="off">
                    <input type="text" id="project_name" name="project_name" placeholder="Project Name ..."><br/>
                    <input type="hidden" id="aes_key" name="aes_key" placeholder="AES Encryption Key ..."><!--<br/>-->
                    <input type="hidden" id="md5_key" name="md5_key" placeholder="MD5 Encryption Key ..."><!--<br/>-->
                    <input type="text" id="database_host" name="database_host" placeholder="Database Host ..." value="localhost"><br/>
                    <input type="text" id="database_name" name="database_name" placeholder="Database Name ..."><br/>
                    <input type="text" id="database_user" name="database_user" placeholder="Database Username ..."><br/>
                    <input type="text" id="database_pass" name="database_pass" placeholder="Database Password ..."><br/>
                  </form>
				  <table width="100%">
					<tr>
						<td width="50%" align=Left>
						<!-- <button id="generate_key" class="button-git" role="button" onclick="generateEncryptionKeys()">Generate Encryption Keys</button> -->
						</td>
						<td width="50%" align=right>
						<button id="confirm_install" class="button-git" role="button" onclick="confirmInstall()">Install</button><br/>
						</td>
					</tr>
				  </table>
                  <div id="info" class="info"><br/>
					
                  </div>
                <div class="opt">
                  
                </div>
            </div>
        </div>
    </body>
</html>

<script>
  function confirmInstall() 
  {
	generateEncryptionKeys();
    document.getElementById("info").innerHTML = "";
    var project = document.getElementById("project_name").value.trim();
    var database = document.getElementById("database_name").value.trim();
    var username = document.getElementById("database_user").value.trim();
    var password = document.getElementById("database_pass").value.trim();
    var host = document.getElementById("database_host").value.trim();
    var aes = document.getElementById("aes_key").value.trim();
    var md5 = document.getElementById("md5_key").value.trim();
    if (isStringNullOrEmpty(project)) 
    {
	    displayError("Project name can not be empty.");
    }
	  else if (!isAlphanumeric(project)) 
    {
	    displayError("Project name should only contain letters, numbers, space and dash.");
    }
    else if (isStringNullOrEmpty(aes)) 
    {
	    displayError("AES key can not be empty.");
    }
    else if (isStringNullOrEmpty(md5)) 
    {
	    displayError("MD5 key can not be empty.");
    }
    else if (isStringNullOrEmpty(host)) 
    {
	    displayError("Database host can not be empty.");
    }
    else if (isStringNullOrEmpty(database)) 
    {
	    displayError("Database name can not be empty.");
    }
    else if (isStringNullOrEmpty(username)) 
    {
	    displayError("Database username can not be empty.");
    }
    else
    {
      document.getElementById("confirm_install").disabled = true;
      // document.getElementById("generate_key").disabled = true;
      document.getElementById('project_name').readOnly = true;
      document.getElementById('database_name').readOnly = true;
      document.getElementById('database_user').readOnly = true;
      document.getElementById('database_pass').readOnly = true;
      document.getElementById('database_host').readOnly = true;
      document.getElementById('aes_key').readOnly = true;
      document.getElementById('md5_key').readOnly = true;
      document.getElementById('dataForm').submit();
	  document.getElementById("info").innerHTML = "<br/>Installing ...";
    }
  }

  function generateEncryptionKeys() 
  {
    document.getElementById("aes_key").value = generateKey(getRndomInteger(25, 32));
    document.getElementById("md5_key").value = generateKey(getRndomInteger(5, 15));
  }

  function generateKey(length) 
  {
    var key = '';
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789&^()!_-';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++) 
    {
      key += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return key;
  }

  function getRndomInteger(min, max) 
  {
    return Math.floor(Math.random() * (max - min) ) + min;
  }

  function isStringNullOrEmpty(str) 
  {
    return (!str || str.length === 0 );
  }
  
  function isAlphanumeric(str)
  {
	return str.match(/^[0-9A-Za-z\s\-\_]+$/);
  }
  
  function displayError(message)
  {
	  var data = "</br><div class=\"alert\"><span class=\"closebtn\" onclick=\"this.parentElement.style.display='none';\">&times;</span>"+message+"</div>";
    document.getElementById("info").innerHTML = data;
  }
</script>
