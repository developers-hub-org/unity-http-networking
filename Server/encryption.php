<?php

	function encrypt($data, $password, $iv)
	{
	    $method = 'aes-256-cbc';
		$key = substr(hash('sha256', $password, true), 0, 32);
		$encrypted = base64_encode(openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv));
		return $encrypted;
	}
    
	function decrypt($data, $password, $iv)
	{
        $method = 'aes-256-cbc';
		$key = substr(hash('sha256', $password, true), 0, 32);
		$decrypted = openssl_decrypt(base64_decode($data), $method, $key, OPENSSL_RAW_DATA, $iv);
		return $decrypted;
	}
    
	function generateIV($length)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';
		for ($i = 0; $i < $length; $i++)
		{
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
		return $string;
	}

?>