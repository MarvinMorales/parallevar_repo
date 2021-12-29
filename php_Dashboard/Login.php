<?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables*/
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    include_once('Config.php');

    $servername = _SERVERNAME;
    $database = _DATABASE2;
    $username = _USERNAME;
    $password = _PASSWORD;
    $table = "branch_offices_geomarket";

    $json_Gotten = file_get_contents("php://input");
    $data = json_decode($json_Gotten);
    $parallevar_usuario = $data -> {"User"};
    $parallevar_pass = $data -> {"Pass"};
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

    $dataToSend = [];

    function JWT($user, $pass) {
        $arr = array('alg' => 'HS256', 'typ' => 'JWT');
        $arr2 = json_encode($arr);
        $encoded_header = base64_encode($arr2);
        $arr3 = array('User' => $user, 'Pass' => $pass);
        $arr33 = json_encode($arr3);
        $encoded_payload = base64_encode($arr33);
        $header_payload = $encoded_header . '.' . $encoded_payload;
        $secret_key = '3h6cd87a9012be01f62fd98c22';
        $signature = base64_encode(hash_hmac('sha256', $header_payload, $secret_key));
        $jwt_token = $header_payload . '.' . $signature;
        return $jwt_token;
    }

    function LogInRegisterUser($db, $tb, $c, $u, $p) {
        global $database;
        global $dataToSend;
        global $userToken;
        global $firsHashPass;
        $DataUser = array();
        $theTable = $database.".".$tb;
        $passHashed = hash('md5', $p);
        $SQL_FIND_USER = "SELECT * FROM $theTable WHERE `UserName` = ?";
        $SQL_COMPARE_PASS = "SELECT * FROM $theTable WHERE `UserName` = ? AND `Password` = ?";
        $c -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $columns_user = $c -> prepare($SQL_FIND_USER);
        $columns_user -> execute([$u]);
        if ( $columns_user -> rowCount() === 1 ) {
            $columns_pass = $c -> prepare($SQL_COMPARE_PASS);
            $columns_pass -> execute([$u, $passHashed]);
            while ( $row = $columns_pass -> fetchAll(PDO::FETCH_ASSOC) ) {
                $DataUser = $row;
            }
            if ($columns_pass -> rowCount() === 1) {  
                $token = JWT($u, $firsHashPass);
                $Acad = json_encode($DataUser[0]);
                $dataToSend = array("Access" => true, "Token" => $token, "UserData" => $Acad);
                $col = $c -> query("UPDATE $theTable SET `Token` = '$token' WHERE `UserName` = '$u'");
                return $dataToSend;
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
          $requestUserConnection = LogInRegisterUser($database, $table, $conn, $parallevar_usuario, $parallevar_pass);

          if ($requestUserConnection['Access'] === true) {
                $response = array(
                    "Access" => true, 
                    "Details" => "UserLoggedSuccessfuly",
                    "Token" =>  $requestUserConnection['Token'],
                    "ConnectedFrom" => $ip_address,
                    "DataUser" => $requestUserConnection['UserData']
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
    