    <?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    //Be carefully when using the API, the values must be hidden and not be an Open API
    include_once('Config.php');

    $servername = _SERVERNAME;
    $database = _DATABASE1;
    $username = _USERNAME;
    $password = _PASSWORD;

    if (file_get_contents("php://input") != null) {
        //Receiving data from POST method and making a json decoded
        $json_Gotten = file_get_contents("php://input");
        $data = json_decode($json_Gotten);

        //Making a words array slicing the string written by customer
        $itemID = $data -> {"itemID"};
        $itemTable = $data -> {"itemTable"};
        $API_KEY = $data -> {"API_KEY"};

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

        function getProductInfo($db, $id, $table, $connection) {
            $finalTable = $db.".".$table;
            $SQL_GET_NUM_OF_TABLES = "SELECT * from $finalTable WHERE `ID` = ?";
            $SQL_GET_DRINKS_AND_EXTRAS = "SELECT * FROM $finalTable WHERE `Extras_Tag` != NULL OR `Extras_Tag` != ''";
            $numOfTables = $connection -> prepare($SQL_GET_NUM_OF_TABLES);
            $numOfTables -> execute([$id]);
            while ($row = $numOfTables -> fetch(PDO::FETCH_ASSOC)) {
                $finalDataGotten[] = $row;
            }
            $Extras = $connection -> prepare($SQL_GET_DRINKS_AND_EXTRAS);
            $Extras -> execute();
            while ($row2 = $Extras -> fetch(PDO::FETCH_ASSOC)) {
                $finalDataGotten2[] = $row2;
            }
            array_push($finalDataGotten, $finalDataGotten2);
            return $finalDataGotten;
        }

        /*====================================================================================================*/

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

            /*===============================================================================*/
            //Verifying if result has more than 5 rows or not to be sliced before sending data
            //echo getProductInfo($database, $itemID, $itemTable, $conn);
            if (confirmApiKey($API_KEY, $servername, _DATABASE3, $username, $password)) {
                $mydata1 = getProductInfo($database, $itemID, $itemTable, $conn);
                echo json_encode($mydata1);
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