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
    
        function getNumberOfTables($db, $connection) {
            $SQL_GET_NUM_OF_TABLES = "SELECT COUNT(*) from Information_Schema.Tables WHERE TABLE_TYPE = 'BASE TABLE' and table_schema = ?";
            $numOfTables = $connection -> prepare($SQL_GET_NUM_OF_TABLES);
            $numOfTables -> execute(array($db));
            $row = $numOfTables -> fetch(PDO::FETCH_NUM);
            $tables = $row[0];
            return $tables;
        }
    
        function getNameOfTables($db, $connection, $num) {
            $nameOfTables = $connection -> prepare("SELECT table_name AS TableNameAlias FROM Information_Schema.Tables WHERE table_schema = ?");
            $nameOfTables -> execute(array($db));
            $thisArray = array();
            $index = 0;
            while ($newRow = $nameOfTables -> fetch(PDO::FETCH_NUM)) {
                array_push($thisArray, $newRow[$index]);
            }
            return $thisArray;
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
                $numberTables = getNumberOfTables($database, $conn);
                $nameOfTables = getNameOfTables($database, $conn, $numberTables);
        
                $finalJsonArray = array();
                //For loop to run inside the table names array
                for ($i = 0; $i < $numberTables; $i++) {
                    $table = $database.".".$nameOfTables[$i];
                    $Top_Numbers = $conn -> prepare("SELECT * FROM ". $table ." ORDER BY Weekly_Sum DESC LIMIT 1");
                    $Top_Numbers -> execute();
                    $newRow = $Top_Numbers -> fetch(PDO::FETCH_ASSOC);
                    array_push($finalJsonArray, $newRow);
                }
        
                //Verifying if result has more than 5 rows or not to be sliced before sending data
                if (count($finalJsonArray) > 5) {
                    $arrayToSend = slice($finalJsonArray, 4);
                    echo $arrayToSend;
                } else {
                    echo json_encode($finalJsonArray);
                }
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