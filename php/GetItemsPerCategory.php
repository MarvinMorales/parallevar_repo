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
        $Group = $data -> {"Group_Key"};
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

        function getNewAllies($db, $db2, $connection, $tag) {
            $arrayGen = [];
            $table = $db.".allies_geomarket";
            $table2 = $db2.".promos_parallevar";
            $table3 = $db.".backimages_categories";
            $SQL_GET_ALL_ALLIES = "SELECT `ID`, `Menu_name`, `Ally_name`, `Background_Image`, \n
            `Stars_Count`, `Logo_Route`, `Open`, `Kind_of_Menu`, `Order_Time`, `Delivery_Price`, \n
            `kind_of_vendor`, `Views`, `Likes`, `Date_member`, \n
            `Order_Time` FROM $table WHERE `kind_of_vendor` = '$tag'";
            $numOfTables = $connection -> prepare($SQL_GET_ALL_ALLIES);
            $numOfTables -> execute();
            while ( $row1 = $numOfTables -> fetchAll(PDO::FETCH_ASSOC) ) {
                $finalJsonArray1 = $row1;
            }
            $SQL_GET_GROUPS = "SELECT `Kind_of_Menu` FROM $table WHERE `kind_of_vendor` = '$tag' GROUP BY `Kind_of_Menu`";
            $numOfTables = $connection -> prepare($SQL_GET_GROUPS);
            $numOfTables -> execute();
            while ( $row2 = $numOfTables -> fetchAll(PDO::FETCH_ASSOC) ) {
                $finalJsonArray2 = $row2;
            }
            $SQL_GET_CATEGORY_PROMOS = "SELECT `Promo_Name`, `Image_Name`, `Promo_Owner_Brand` FROM $table2";
            $numOfTables = $connection -> prepare($SQL_GET_CATEGORY_PROMOS);
            $numOfTables -> execute();
            while ( $row3 = $numOfTables -> fetchAll(PDO::FETCH_ASSOC) ) {
                $finalJsonArray3 = $row3;
            }
            $SQL_GET_CATEGORY_IMAGES = "SELECT `Category`, `Image_Name` FROM $table3 WHERE `Section` = '$tag'";
            $numOfTables = $connection -> prepare($SQL_GET_CATEGORY_IMAGES);
            $numOfTables -> execute();
            while ( $row4 = $numOfTables -> fetchAll(PDO::FETCH_ASSOC) ) {
                $finalJsonArray4 = $row4;
            }
            for ($i = 0; $i < count($finalJsonArray2); $i++) {
                foreach ($finalJsonArray4 as $sub) {
                    foreach ($sub as $Clave => $Val) {
                        if ($Clave === "Category") {
                            if ($Val === $finalJsonArray2[$i]['Kind_of_Menu']) {
                                $finalJsonArray2[$i]['CategoryImg'] = $sub['Image_Name'];
                            }
                        } 
                    }
                }
            }
            $arra1 = array("Obj1" => $finalJsonArray1);
            $arra2 = array("Obj2" => $finalJsonArray2);
            $arra3 = array("Obj3" => $finalJsonArray3);
            array_push($arrayGen, $arra1);
            array_push($arrayGen, $arra2);
            array_push($arrayGen, $arra3);
            echo json_encode($arrayGen);
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

                getNewAllies(_DATABASE2, _DATABASE4, $conn, $Group);
    
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