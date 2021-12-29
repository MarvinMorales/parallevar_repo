<?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    $ip_address = "";

    function GenerateRandomNumber($ip) {
        if (!empty($ip)) {
            return rand(100000, 999999);
        }
    }

    try {

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        $random = GenerateRandomNumber($ip_address);
        echo json_encode(array("IP" => $ip_address, "Code" => $random));

    } catch (Exception $e) {
        die("Error: ". $e -> getMessage() . PHP_EOL);
    } finally {
        unset($conn);
    }
    