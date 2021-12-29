<?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    include_once('Config.php');

    $servername = _SERVERNAME;
    $database = _DATABASE3;
    $username = _USERNAME;
    $password = _PASSWORD;
    $table = "users";
    
    $json_Gotten = file_get_contents("php://input");
    $data = json_decode($json_Gotten);

    $parallevar_usuario = $data -> {"UserName"};
    $parallevar_pass = $data -> {"PassCode"};
    $PushToken = $data -> {"PushToken"};
    $ip_address = "";

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }

    //Section to create user token to be able to consume the Parallevar API Service
    $actual_Date = date("d-m-Y")."-".date("H:i:s");
    $firsHashPass = hash('md5', $parallevar_pass);
    $finalPassPhase = $actual_Date."|".$ip_address."|".$firsHashPass."|".$parallevar_usuario;
    $userToken = hash('md5', $finalPassPhase);

    function SetPushToken($PushToken, $UName, $conn, $db) {
        $table = $db.".push_notifications_tokens";
        $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $SQL_SET_PUSH_TOKENS = "INSERT INTO $table (`Token`, `UserName`) \n
        VALUES(?, ?) ON DUPLICATE KEY UPDATE `Token` = ?";
        $numOfTables = $conn -> prepare($SQL_SET_PUSH_TOKENS);
        $numOfTables -> execute([$PushToken, $UName, $PushToken]);
    }

    $dataToSend = [];

    function LogInRegisterUser($db, $tb, $c, $u, $p, $push) {
        global $database;
        global $dataToSend;
        global $userToken;
        $theTable = $database.".".$tb;
        $passHashed = hash('md5', $p);
        $SQL_FIND_USER = "SELECT * FROM $theTable WHERE `Email` = ?";
        $SQL_UPDATE_TOKEN = "UPDATE $theTable SET `Session_User_Token` = ? WHERE `Email` = ? AND `Passwords` = ?";
        $SQL_COMPARE_PASS = "SELECT * FROM $theTable WHERE `Email` = ? AND `Passwords` = ?";
        $c -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $columns_user = $c -> prepare($SQL_FIND_USER);
        $columns_user -> execute([$u]);
        if ( $columns_user -> rowCount() === 1 ) {
            $columns_pass = $c -> prepare($SQL_COMPARE_PASS);
            $columns_pass -> execute([$u, $passHashed]);
            if ($columns_pass -> rowCount() === 1) {
                $Update = $c -> prepare($SQL_UPDATE_TOKEN);
                $Update -> execute([$userToken, $u, $passHashed]);
                $PushTokenGranted = SetPushToken($push, $u, $c, $db);   
                $dataToSend = array("Access" => true, "API_Key" => $userToken);
                return $dataToSend['Access'];
            } else { 
                
                $dataToSend = array("Access" => "PASSWORD_NOT_CORRECT");
                return $dataToSend['Access'];
            }
        } else {
            $dataToSend = array("Access" => false);
            return $dataToSend['Access'];
        }
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

      if (isset($parallevar_usuario) && isset($parallevar_pass)) {
          $requestUserConnection = LogInRegisterUser($database, $table, $conn, $parallevar_usuario, $parallevar_pass, $PushToken);

          if ($requestUserConnection === true) {
                $response = array(
                    "Access" => true, 
                    "Details" => "UserLoggedSuccessfuly", 
                    "ConnectedFrom" => $ip_address, 
                    "API_Key" => $dataToSend['API_Key']
                );
                $response = json_encode($response);
                echo $response;
          } else if ($requestUserConnection === false) {
                $response = array("Access" => false, "Details" => "UserDoesNotExist", "RequestedFrom" => $ip_address);
                $response = json_encode($response);
                echo $response;
          } else if ($requestUserConnection === "PASSWORD_NOT_CORRECT") {
                $response = array("Access" => false, "Details" => "PasswordNotCorrect", "RequestedFrom" => $ip_address);
                $response = json_encode($response);
                echo $response;
          }
        }

    } catch (Exception $e) {
        die("Error: ". $e -> getMessage() . PHP_EOL);
    } finally {
        unset($conn);
    }
    