<?php

    if(isset($_POST['hash0']) && isset($_POST['hash1']) && isset($_POST['hash2']))
    {
        unity_client_request($_POST['hash0'], $_POST['hash1'], $_POST['hash2']);
    }
	
	exit();
	
	function unity_client_request($hash0, $hash1, $hash2)
	{
    	if(file_exists("files.ini"))
    	{
			$files_ini = parse_ini_file("files.ini");
			if(file_exists($files_ini['core_file_name']))
			{
				$config = $files_ini['core_file_name'];
			}
			else
			{
				$link_ini_path = get_root_path() . $files_ini['link_file_name'];
				$config = null;
				$create_link = true;
				if(is_file($link_ini_path))
				{
					$link_ini = parse_ini_file($link_ini_path);
					if(is_file($link_ini['config_path']))
					{
						$config = $link_ini['config_path'];
						$create_link = false;
					}
					else
					{
						unlink($link_ini_path);
					}
				}
				if($create_link)
				{
					$config = get_file_path($files_ini['core_file_name']);
					if($config != null)
					{
						file_put_contents($link_ini_path, "config_path = \"" . $config . "\"");
					}
				}
			}
    	    if($config != null)
    	    {
    	        include_once $config;
    	        echo process_request($hash0, $hash1, $hash2, pathinfo($config, PATHINFO_DIRNAME));
    	        exit();
    	    }
    	}
    	echo("ERROR_SERVER_CONFIG");
	}
	
	function get_file_path($file_name)
	{
	    $iterator = new RecursiveDirectoryIterator(get_root_path());
        foreach(new RecursiveIteratorIterator($iterator) as $file)
        {
            if ($file->getExtension() == 'php')
            {
                if($file->getFilename() == $file_name)
                {
                    return $file;
                }
            }
        }
        return null;
	}
	
	/*
	function get_base_path()
	{
		$path = correct_path(getcwd());
		if(substr_count($path, DIRECTORY_SEPARATOR . 'public_html') == 1)
		{
			$path = substr($path, 0, strpos($path, DIRECTORY_SEPARATOR . "public_html"));
			return $path . DIRECTORY_SEPARATOR;
		}
		else if(substr_count($path, DIRECTORY_SEPARATOR . 'htdocs') == 1)
		{
			$path = substr($path, 0, strpos($path, DIRECTORY_SEPARATOR . "htdocs"));
			return $path . DIRECTORY_SEPARATOR;
		}
		else if(substr_count($path, DIRECTORY_SEPARATOR . 'www') == 1)
		{
			$path = substr($path, 0, strpos($path, DIRECTORY_SEPARATOR . "www"));
			return $path . DIRECTORY_SEPARATOR;
		}
		return null;
	}
	*/

	function get_root_path()
	{
		$public_path = correct_path($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR;
		$private_path = correct_path(dirname($public_path)) . DIRECTORY_SEPARATOR;
		if(is_writable($private_path) == false)
		{
		  $private_path = $public_path;
		}
		return $private_path;
	}
	
	function correct_path($path)
	{
		return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	}
  
?>
