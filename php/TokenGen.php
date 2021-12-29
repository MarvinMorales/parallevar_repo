<?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/

    $parallevar_usuario = "marvincitio@gmail.com";
    $parallevar_pass = "hassan201616";

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }

    function GenerateSHA512Token($var, $) {
        
    }

    try {
        //Making conection to database using PDO
        //It is possible to make the connection file inside other file and inlcude here
        $arrOptions = array(
            PDO::ATTR_EMULATE_PREPARES => FALSE, 
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, +
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
        );

        $conn = new PDO("mysql:host = $servername; dbname = $database", $username, $password, $arrOptions);
        $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //echo "The Connection was successfully!". PHP_EOL;

    } catch (Exception $e) {
        die("Error: ". $e -> getMessage() . PHP_EOL);
    } finally {
        unset($conn);
    }
    