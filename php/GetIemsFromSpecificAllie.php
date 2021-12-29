<?php
    //GetIemsFromSpecificAllies.php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    //Be carefully when using the API, the values must be hidden and not be an Open API
    include_once('Config.php');

    $servername = _SERVERNAME;
    $database_menu = _DATABASE1;
    $database_ally = _DATABASE2;
    $username = _USERNAME;
    $password = _PASSWORD;

    if (file_get_contents("php://input") != null) {
        $json_Gotten = file_get_contents("php://input");
        $data = json_decode($json_Gotten);

        $table_ally = "allies_geomarket";
        $table_menu = $data -> {"table_menu"};
        $id = $data -> {"Id"};
        $apiKey = $data -> {"API_KEY"};
        $TableMenu = "";

        function confirmApiKey($sr, $db, $un, $ps) {
            global $apiKey;
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
            $numOfTables -> execute([$apiKey]);
            if ($numOfTables -> rowCount() === 1) {
                return true;
            } else {
                return false;
            }
        }

        /*============================= Function to get the last 5 Allies Logos ================================*/
        function getAllieInfo($db, $connection, $tab, $ID) {
            global $TableMenu;
            $finalDataGotten = array();
            $table = $db.".".$tab;
            $SQL_GET_ALLIES = "SELECT `ID`, `Ally_name`, `Logo_Route`, `Background_Image`,\n 
            `Likes`, `Open`, `Views`, `Stars_Count`, `Menu_name`, `Order_Time`, `Date_member`, \n
            `Background_Image` FROM $table WHERE `ID` = $ID";
            $numOfTables = $connection -> prepare($SQL_GET_ALLIES);
            $numOfTables -> execute();
            $rows = $numOfTables -> fetchAll(PDO::FETCH_ASSOC);
            $f1 = new DateTime(date("Y-m-d"));
            $f2 = new DateTime($rows[0]["Date_member"]);
            $num = $f2 -> diff($f1);
            $rows[0]["Date_member"] = $num -> days;
            $newRows[] = $rows;
            $TableMenu = $rows[0]["Menu_name"];
            return $newRows;
        }

        /*=======================================================================================================*/
        function getMenuRow($db, $connection, $tab) {
            global $TableMenu;
            $finalDataGotten = array();
            $table = $db.".".$tab;
            $SQL_GET_MENU = "SELECT * FROM $table";
            $numOfTables = $connection -> prepare($SQL_GET_MENU);
            $numOfTables -> execute();
            while ($row = $numOfTables -> fetch(PDO::FETCH_ASSOC)) {
                array_push($row, $row['TableName'] = $TableMenu);
                $finalDataGotten[] = $row;
            }
            return $finalDataGotten;
        }
        /*=======================================================================================================*/
        if (confirmApiKey($servername, _DATABASE3, $username, $password)) {
            try {
                //Making conection to database using PDO
                //It is possible to make the connection file inside other file and inlcude here
                $arrOptions = array(
                    PDO::ATTR_EMULATE_PREPARES => FALSE, 
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
                );
    
                $conn = new PDO("mysql:host = $servername; dbname = $database_ally", $username, $password, $arrOptions);
                $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //echo "The Connection was successfully!". PHP_EOL;
    
                $conn2 = new PDO("mysql:host = $servername; dbname = $database_menu", $username, $password, $arrOptions);
                $conn2 -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //echo "The Connection was successfully!". PHP_EOL;
                
                $mydata1 = getAllieInfo($database_ally, $conn, $table_ally, $id);
                $myData2 = getMenuRow($database_menu, $conn2, $table_menu);
    
                array_push($mydata1, $myData2);
                echo json_encode($mydata1);
                
            } catch (Exception $e) {
                die("Error: ". $e -> getMessage() . PHP_EOL);
            } finally {
                unset($conn);
            }
        } else {
            echo file_get_contents("API_KEY_INCORRECT.html");
        }

    } else {
        echo file_get_contents("Alert_API.html");
    }

