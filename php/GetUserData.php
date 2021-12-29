<?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    include_once('Config.php');

    $servername = _SERVERNAME;
    $database = _DATABASE1;
    $username = _USERNAME;
    $password = _PASSWORD;

    if (file_get_contents("php://input") != null) {
        //Receiving data from POST method and making a json decoded
        $json_Gotten = file_get_contents("php://input");
        $data = json_decode($json_Gotten);
        $key = $data -> {"API_KEY"};

        function confirmApiKey($key, $sr, $db, $un, $ps) {
            $table = $db.".users";
            $arrOptions = array(
                PDO::ATTR_EMULATE_PREPARES => FALSE, 
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
            );
            $conn = new PDO("mysql:host = $sr; dbname = $db", $un, $ps, $arrOptions);
            $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $SQL_CONFIRM_KEY = "SELECT * FROM $table WHERE `Session_User_Token` = ?";
            $numOfTables = $conn -> prepare($SQL_CONFIRM_KEY);
            $numOfTables -> execute([$key]);
            if ($numOfTables -> rowCount() === 1) {
                return true;
            } else {
                return false;
            }
        }

        function GetUserInfo($key, $sr, $db, $un, $ps) {
            $finalData = array();
            $table = $db.".users";
            $arrOptions = array(
                PDO::ATTR_EMULATE_PREPARES => FALSE, 
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
            );
            $conn = new PDO("mysql:host = $sr; dbname = $db", $un, $ps, $arrOptions);
            $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $SQL_GET_USER_INFO = "SELECT `ID`, `UserName`, `LastName`, `Picture_Route`, `Email`, `PhoneNumber`, `Score`, `City`, `Country`, `User_Rating` FROM $table WHERE `Session_User_Token` = ?";
            $numOfTables = $conn -> prepare($SQL_GET_USER_INFO);
            $numOfTables -> execute([$key]);
            while ( $row = $numOfTables -> fetchAll(PDO::FETCH_ASSOC) ) {
                $finalData = $row;
            }
            return json_encode($finalData);
        }
    
        try {
            //Making conection to database using PDO
            //It is possible to make the connection file inside other file and inlcude here
            $arrOptions = array(
                PDO::ATTR_EMULATE_PREPARES => FALSE, 
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
            );
    
            $conn = new PDO("mysql:host = $servername; dbname = $database", $username, $password, $arrOptions);
            $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //echo "The Connection was successfully!". PHP_EOL;


            if (confirmApiKey($key, $servername, _DATABASE3, $username, $password)) {
                
                echo GetUserInfo($key, $servername, _DATABASE3, $username, $password);
                
            } else {
                echo file_get_contents("API_KEY_INCORRECT.html");
            }
            
        } catch (Exception $e) {
            die("Error: ". $e -> getMessage() . PHP_EOL);
        } finally {
            unset($conn);
        }
    } else {
        echo file_get_contents("Alert_API.html");
    }