
    <?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    //Be carefully when using the API, the values must be hidden and not be an Open API
    include_once('Config.php');

    $servername = _SERVERNAME;
    $database = _DATABASE3;
    $username = _USERNAME;
    $password = _PASSWORD;
    $table = "orders_made";

    if (file_get_contents("php://input") != null) {
        //Receiving data from POST method and making a json decoded
        $json_Gotten = file_get_contents("php://input");
        $data = json_decode($json_Gotten);
        $key = $data -> {"API_KEY"};
        $AllieName = $data -> {"AllieName"};
        $Total = $data -> {"TotalAmmount"};
        $RoadrunnerAPKIKEY -> $data{"RoadrunnerAPIKEY"};
        $OrderJSON = $data -> {"OrderJSON"};

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

        /*============================= Function to get the last 5 Allies Logos ================================*/
        function getNewAllies($db, $connection, $tab) {
            $table = $db.".".$tab;
            $TodayDate = date('Y-F-d H:i:s');
            $OrderHashed = hash('sha256', $OrderJSON."|".$TodayDate);

            $SQL_GET_USER = "SELECT `ID` FROM $table WHERE `Session_User_Token` = ?";
            $numOfTables1 = $connection -> prepare($SQL_GET_USER);
            $numOfTables1 -> execute([$key]);
            $rows1 = $numOfTables1 -> fetchAll(PDO::FETCH_ASSOC);
            $User = $rows1['ID'];

            $SQL_INSERT_ORDER_MATCHED = "INSERT INTO $table (`Allie_Name`, `Order_Hash`, `Order_Status`,\n 
            `Order_Date`, `User_ID_Involved`, `Roadrunner_ID_Involved`, `Total_Ammount`, `Order_JSON`) VALUES \n
            (?, ?, ?, ?, ?, ?, ?, ?)";
            $numOfTables = $connection -> prepare($SQL_INSERT_ORDER_MATCHED);
            $numOfTables -> execute([$$AllieName, $OrderHashed, "Pending", $TodayDate, $User, $RoadrunnerID, $Total, $OrderJSON]);

            $SQL_GET_ORDER_ID = "SELECT `ID`, `Order_Status` FROM $table WHERE `Order_Hash` = ?";
            $numOfTables2 = $connection -> prepare($SQL_GET_ORDER_ID);
            $numOfTables2 -> execute([$OrderHashed]);
            $rows2 = $numOfTables2 -> fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows2);
        }
        /*===============================================================================================================================*/

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
                getNewAllies($database, $conn, $table);
                //echo json_encode($myData);
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
    