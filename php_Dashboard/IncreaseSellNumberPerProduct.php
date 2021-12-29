
    <?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    //Be carefully when using the API, the values must be hidden and not be an Open API
    include_once('Config.php');
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

    $servername = _SERVERNAME;
    $database = _DATABASE2;
    $username = _USERNAME;
    $password = _PASSWORD;
    $CustomersTable = "users";

    if (file_get_contents("php://input") != null) {
        //Receiving data from POST method and making a json decoded
        $json_Gotten = file_get_contents("php://input");
        $data = json_decode($json_Gotten);

        $key = $data -> {"API_KEY"};
        $FinalPrice = $data -> {"FinalPrice"};
        $CustomerEmail = $data -> {"CustomerEmail"};
        $AllieTable = $data -> {"Table"};
        $AllieName = $data -> {"AllieName"};
        $Products = $data -> {"Products"};

        function confirmApiKey($key, $sr, $db, $un, $ps) {
            $table = $db.".branch_offices_geomarket";
            $arrOptions = array(
                PDO::ATTR_EMULATE_PREPARES => FALSE, 
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
            );
            $conn = new PDO("mysql:host = $sr; dbname = $db", $un, $ps, $arrOptions);
            $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $SQL_CONFIRM_KEY = "SELECT * FROM $table WHERE `Token` = ?";
            $numOfTables = $conn -> prepare($SQL_CONFIRM_KEY);
            $numOfTables -> execute([$key]);
            if ($numOfTables -> rowCount() === 1) {
                return true;
            } else {
                return false;
            }
        }

        /*============================================= Functions to update values ======================================================*/

        function UpdateAllieValues($db, $conn, $tab, $F_Price, $Name) {
            $table = $db.".".$tab; $fat = (float) $F_Price;
            $SQL_UPDATE_ALLIE_VALUES = "UPDATE $table SET `Daily_Revenue` = `Daily_Revenue` + $fat WHERE `Ally_name` = '$Name'";
            $t_s = $conn -> prepare($SQL_UPDATE_ALLIE_VALUES);
            $t_s -> execute();
            return "AllieUpdated";
        }

        function UpdateMenuValues($db, $conn, $tab, $ProductsObj) {
            $table = $db.".".$tab;
            $Products = json_decode($ProductsObj, true);
            foreach ($Products as $clave => $val) {
                $fat = (float) $val['UnitPrice'];
                $SQL_UPDATE_MENU_VALUES = "UPDATE $table SET `Weekly_Sum` = `Weekly_Sum` + $fat, \n
                `Sales_Count` = `Sales_Count` + 1 WHERE `Producto` = '$clave'";
                $t_s = $conn -> prepare($SQL_UPDATE_MENU_VALUES);
                $t_s -> execute();
            } return "MenuUpdated";
        }

        function UpdatesCustomerValues($db, $conn, $tab, $F_Price, $Email) {
            $table = $db.".".$tab; $fat = (float) $F_Price;
            $SQL_UPDATE_CUSTOMER_VALUES = "UPDATE $table SET `Money_Spent` = `Money_Spent` + $fat WHERE `Email` = '$Email'";
            $t_s = $conn -> prepare($SQL_UPDATE_CUSTOMER_VALUES);
            $t_s -> execute();
            return "CustomerUpdated";
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

            if (confirmApiKey($key, $servername, _DATABASE2, $username, $password)) {
                $first = UpdateAllieValues(_DATABASE2, $conn, 'allies_geomarket', $FinalPrice, $AllieName);
                $second = UpdateMenuValues(_DATABASE1, $conn, $AllieTable, $Products);
                $third = UpdatesCustomerValues(_DATABASE3, $conn, 'users', $FinalPrice, $CustomerEmail);
                echo json_encode(array($first => true, $second => true, $third => true));
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
    