<?php
  ini_set('display_errors', '0');
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
        mysqli_close($connection);
        $path = get_base_path();
        if($path != null)
        {
          $project_name = str_replace(" ", "_", strtolower($project));
          $private_path = $path['private_path'] . "unity_projects" . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;
          $public_path = $path['public_path'] . "unity_projects" . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;
          if (!file_exists($private_path)) 
          {
            mkdir($private_path, 0777, true);
          }
          if (!file_exists($public_path)) 
          {
            mkdir($public_path, 0777, true);
          }
          if(download_repository($public_path, $private_path, $project_name, $database, $username, $password, $aes, $md5, $host))
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
              .alert {padding: 20px; background-color: #009b77; color: white;}
              body {font-family: 'Sora', serif; font-size: 15px;}
            </style>
            </head>
            <body>
              <div class=\"container\">
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
                </div>
              </div>
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
  
  function download_repository($public_path, $private_path, $project_name, $database, $username, $password, $aes, $md5, $host)
  {
    if(file_exists($public_path . "api.php")) {unlink($public_path . "api.php");}
    $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_public/api.php", $public_path);
    if(!$downloaded){return false;}
    
    if(file_exists($private_path . "dh_unity_server_core.php")) {unlink($private_path . "dh_unity_server_core.php");}
    $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_private/dh_unity_server_core.php", $private_path);
    if(!$downloaded)
    {
      $private_path = $public_path;
      if(file_exists($private_path . "dh_unity_server_core.php")) {unlink($private_path . "dh_unity_server_core.php");}
      $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_private/dh_unity_server_core.php", $private_path);
      if(!$downloaded){return false;}
    }

    if(file_exists($public_path . "files.ini")) {unlink($public_path . "files.ini");}
    $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_public/files.ini", $public_path);
    if(!$downloaded){return false;}

    $prefix = generate_random_string(rand(10, 20));
    $core_file_name = "unity_core_" . $project_name . "_" . $prefix . ".php";
    $link_file_name = "unity_link_" . $project_name . "_" . $prefix . ".ini";
    rename($private_path . "dh_unity_server_core.php", $private_path . $core_file_name);
    file_put_contents($public_path . "files.ini", "core_file_name = " . $core_file_name . "\n" . "link_file_name = " . $link_file_name . "");
    chmod($private_path . $core_file_name, 0600);
    chmod($public_path . "files.ini", 0600);

    if(file_exists($private_path . "config.php")) {unlink($private_path . "config.php");}
    $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_private/config.php", $private_path);
    if(!$downloaded){return false;}
    chmod($private_path . "config.php", 0600);

    $confog_data = "
    <?php
      define('DB_NAME', '".$database."');
      define('DB_USER', '".$username."');
      define('DB_PASSWORD', '".$password."');
      define('DB_HOST', '".$host."');
      define('AES_PASSWORD', '".$aes."');
      define('MD5_PASSWORD', '".$md5."');
      ?>";
    file_put_contents($private_path . "config.php", $confog_data);
    
    if(file_exists($private_path . "control.php")) {unlink($private_path . "control.php");}
    $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_private/control.php", $private_path);
    if(!$downloaded){return false;}
    chmod($private_path . "control.php", 0600);

    if(file_exists($private_path . "connection.php")) {unlink($private_path . "connection.php");}
    $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_private/connection.php", $private_path);
    if(!$downloaded){return false;}
    chmod($private_path . "connection.php", 0600);

    if(file_exists($private_path . "encryption.php")) {unlink($private_path . "encryption.php");}
    $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_private/encryption.php", $private_path);
    if(!$downloaded){return false;}
    chmod($private_path . "encryption.php", 0600);

    if(file_exists($private_path . "core_response.php")) {unlink($private_path . "core_response.php");}
    $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_private/core_response.php", $private_path);
    if(!$downloaded){return false;}
    chmod($private_path . "core_response.php", 0600);

    if(!file_exists($private_path . "response.php"))
    {
      $downloaded = download_file("https://raw.githubusercontent.com/dh-org/unity-mysql-api/main/Server/dh_unity_server_private/response.php", $private_path);
      if(!$downloaded){return false;}
    }
    chmod($private_path . "response.php", 0600);

    return true;
  }
  
  function download_file($url, $dir)
  {
    $file_name = basename($url);
    if(file_put_contents($dir . $file_name, file_get_contents($url)))
    {
        return true;
    }
	  return false;
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
    $path = correct_path(getcwd());
    if(substr_count($path, DIRECTORY_SEPARATOR . 'public_html') == 1)
    {
      $response = array();
      $path = substr($path, 0, strpos($path, DIRECTORY_SEPARATOR . "public_html"));
      $response['private_path'] = $path . DIRECTORY_SEPARATOR;
      $response['public_path'] = $path . DIRECTORY_SEPARATOR . "public_html" . DIRECTORY_SEPARATOR;
      $response['public_folder'] = "public_html";
      return $response;
    }
    else if(substr_count($path, DIRECTORY_SEPARATOR . 'htdocs') == 1)
    {
      $response = array();
      $path = substr($path, 0, strpos($path, DIRECTORY_SEPARATOR . "htdocs"));
      $response['private_path'] = $path . DIRECTORY_SEPARATOR;
      $response['public_path'] = $path . DIRECTORY_SEPARATOR . "htdocs" . DIRECTORY_SEPARATOR;
      $response['public_folder'] = "htdocs";
      return $response;
    }
    else if(substr_count($path, DIRECTORY_SEPARATOR . 'www') == 1)
    {
      $response = array();
      $path = substr($path, 0, strpos($path, DIRECTORY_SEPARATOR . "www"));
      $response['private_path'] = $path . DIRECTORY_SEPARATOR;
      $response['public_path'] = $path . DIRECTORY_SEPARATOR . "www" . DIRECTORY_SEPARATOR;
      $response['public_folder'] = "www";
      return $response;
    }
    return null;
  }
  
  function correct_path($path)
  {
	  return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
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
        <title>Unity MySQL API Installer</title>
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
                <div class="title">Unity MySQL API Installer</div><br/>
                  <form id="dataForm" action="?" method="post" autocomplete="off">
                    <input type="text" id="project_name" name="project_name" placeholder="Project Name ..."><br/>
                    <input type="text" id="aes_key" name="aes_key" placeholder="AES Encryption Key ..."><br/>
                    <input type="text" id="md5_key" name="md5_key" placeholder="MD5 Encryption Key ..."><br/>
                    <input type="text" id="database_host" name="database_host" placeholder="Database Host ..." value="localhost"><br/>
                    <input type="text" id="database_name" name="database_name" placeholder="Database Name ..."><br/>
                    <input type="text" id="database_user" name="database_user" placeholder="Database Username ..."><br/>
                    <input type="text" id="database_pass" name="database_pass" placeholder="Database Password ..."><br/>
                  </form>
				  <table width="100%">
					<tr>
						<td width="50%" align=Left>
						<button id="generate_key" class="button-git" role="button" onclick="generateEncryptionKeys()">Generate Encryption Keys</button>
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
    else if (isStringNullOrEmpty(aes)) 
    {
	    displayError("AES key can not be empty.");
    }
    else if (aes.length != 32) 
    {
	    displayError("AES key must be 32 characters but you entered " + aes.length + " characters.");
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
      document.getElementById("generate_key").disabled = true;
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
    document.getElementById("aes_key").value = generateKey(32);
    document.getElementById("md5_key").value = generateKey(getRndomInteger(10, 20));
  }

  function generateKey(length) 
  {
    var key = '';
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789&^()!_-';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) 
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
  
  function displayError(message)
  {
	  var data = "</br><div class=\"alert\"><span class=\"closebtn\" onclick=\"this.parentElement.style.display='none';\">&times;</span>"+message+"</div>";
    document.getElementById("info").innerHTML = data;
  }
</script>
