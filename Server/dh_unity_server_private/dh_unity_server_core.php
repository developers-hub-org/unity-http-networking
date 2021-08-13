<?php

	function process_request($hash0, $hash1, $hash2, $path)
	{
		$response = array();
		$result = array();
		$result["message"] = 'NULL';
		if(file_exists($path . "/encryption.php"))
		{
		    include_once ($path . "/encryption.php");
		}
		else
		{
		    // Fatal Error
		}
		if(file_exists($path . "/connection.php") && file_exists($path . "/response.php") && file_exists($path . "/config.php"))
		{
		    include_once ($path . "/connection.php");
		    include_once ($path . "/config.php");
			$con = getConnection($path);
			if($con != null)
			{
				$data = decrypt($hash1, AES_PASSWORD, $hash0);
				$json = json_decode($data);
				if(json_last_error() == JSON_ERROR_NONE)
				{
					if(md5($json->validation . MD5_PASSWORD) == $hash2)
					{
					    include_once ($path . "/response.php");
					    try 
					    {
                            $result["data"] = get_response($con, $json);
                            $result["message"] = 'SUCCESSFUL';
                        } 
                        catch (Exception $e) 
                        {
                            $result["message"] = 'ERROR_QUERY';
                            $result["error"] = $e->getMessage();
                        }
					}
					else
					{
						$result["message"] = 'ERROR_VALIDATION_SERVER';
					}
				}
				else
				{
					$result["message"] = 'ERROR_PACKAGE';
				}
				mysqli_close($con);
			}
			else
			{
				$result["message"] = 'ERROR_CONNECTION';
			}
		}
		else
		{
			$result["message"] = 'ERROR_SERVER_CONFIG';
		}
		$iv = generateIV(16);
		$response["hash0"] = encrypt(json_encode($result), AES_PASSWORD, $iv);
		$response["hash1"] = $iv;
		$response["hash2"] = md5($result["message"] . MD5_PASSWORD);
		return json_encode($response);
	}

?>