
    <?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    //Be carefully when using the API, the values must be hidden and not be an Open API
    include_once('Config.php');

    $servername = _SERVERNAME;
    $database = _DATABASE2;
    $username = _USERNAME;
    $password = _PASSWORD;
    $table = "allies_geomarket";

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

        /*============================= Function to get the last 5 Allies Logos ================================*/
        function getNewAllies($db, $connection, $tab) {
            $finalJsonArray = array();
            $table = $db.".".$tab;
            $SQL_GET_NEW_ALLIES = "SELECT `ID`, `Menu_name`, `Ally_name`, `Background_Image`, \n
            `Stars_Count`, `Logo_Route`, `Open`, `Views`, `Likes`, `Date_member`, \n
            `Order_Time` FROM $table ORDER BY `Date_member` DESC";
            $numOfTables = $connection -> prepare($SQL_GET_NEW_ALLIES);
            $numOfTables -> execute();
            $rows = $numOfTables -> fetchAll(PDO::FETCH_ASSOC);
            $newRows = array();
            foreach ($rows as $myGroup) {
                $f1 = new DateTime(date("Y-m-d"));
                $f2 = new DateTime($myGroup["Date_member"]);
                $num = $f2 -> diff($f1);
                $myGroup["Date_member"] = $num -> days;
                $newRows[] = $myGroup;
            }
            while ($row = array_shift($newRows)) {
                array_push($finalJsonArray, $row);
            }
            echo json_encode($finalJsonArray);
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
    