<?php

namespace Wise\Service;

class WiseCrypt
{
    private static $salt = "WISE_SECURE_SALT_V1";
    
    // Encrypted URL storage
    private static $secure_endpoints = [
        "check" => "UIwaHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS9saWNlbnNlL2NoZWNrPnq", 
        "activate" => "NzWaHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS9saWNlbnNlL2FjdGl2YXRllVc",
        "websites" => "BLgaHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS93ZWJzaXRlcw==9L7",
        "product" => "GC3aHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS9wcm9kdWN0E67",
        "broadcast" => "jWbaHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS9icm9hZGNhc3QtbWVzc2FnZXM=i2X"
    ];

    public static function getEndpoint($key)
    {
        if (isset(self::$secure_endpoints[$key])) {
            // Remove 3 chars from start and 3 from end
            $d = substr(self::$secure_endpoints[$key], 3, -3);
            return base64_decode($d);
        }
        return "";
    }

    public static function obfuscate($string)
    {
        $result = "";
        $saltLen = strlen(self::$salt);
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr(self::$salt, ($i % $saltLen) - 1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
        return base64_encode($result);
    }

    public static function deobfuscate($string)
    {
        $string = base64_decode($string);
        $result = "";
        $saltLen = strlen(self::$salt);
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr(self::$salt, ($i % $saltLen) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }
        return $result;
    }
    
    public static function generateSignature($data)
    {
         return hash_hmac("sha256", json_encode($data), self::$salt);
    }
}

