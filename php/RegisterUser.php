<?php
    /*Make sure you have created the current database 
    and replace the credentials inside variables
    using the the Config.php file*/
    include_once('Config.php');

    $servername = _SERVERNAME;
    $database = _DATABASE3;
    $username = _USERNAME;
    $password = _PASSWORD;
    $table = "users";

    $json_Gotten = file_get_contents("php://input");
    $data = json_decode($json_Gotten);
    
    $name = $data -> {"username"};
    $lastname = $data -> {"lastname"};
    $email = $data -> {"email"};
    $pass = $data -> {"password"};
    $country = $data -> {"country"};
    $city = $data -> {"city"};
    $deviceOS = $data -> {"deviceOS"};
    $phone = $data -> {"phone"};
    $ip_address = "";

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }

    $userToken = "I_AM_NEW_USER";

    function getOS() { 
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $os_platform =   "Bilinmeyen İşletim Sistemi";
        $os_array =   array(
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );
        foreach ( $os_array as $regex => $value ) { 
            if ( preg_match($regex, $user_agent ) ) {
                $os_platform = $value;
            }
        }   
        return $os_platform;
    }

   $OperatingSystem = "Android";

    function InsertNewUser($db, $tb, $connection, $a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l, $m) {
        global $database;
        $theTable = $database.".".$tb;
        $passHashed = hash('md5', $d);
        $SQL_INSERT_NEW_USER = "INSERT INTO $theTable (UserName, LastName, Email, \n
        Passwords, Country, City, DeviceOS, PhoneNumber, Last_IP_Address, \n
        Requests_Made, Money_Spent, Requests_Cancelled, User_Rating) \n
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $connection -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $newUser = $connection -> prepare($SQL_INSERT_NEW_USER);
        if ($newUser -> execute([$a, $b, $c, $passHashed, $e, $f, $g, $h, $i, $j, $k, $l, $m])) {
            return true;
        } else {
            return false;
        }
    }

    function CheckIfUserExists($tb, $em, $connection) {
        global $database;
        $theTable = $database.".".$tb;
        $SQL_CHECK_IF_USER_EXISTS = "SELECT * FROM $theTable WHERE Email = ?";
        $connection -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $numOfTables = $connection -> prepare($SQL_CHECK_IF_USER_EXISTS);
        $numOfTables -> execute([$em]);
        $columns = $numOfTables -> fetchColumn();
        if ($columns > 0) {
            return true;
        } else {
            return false;
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

        if (isset($name) && isset($lastname) && isset($email) && isset($pass) && 
        isset($country) && isset($phone) && isset($deviceOS) && isset($phone)) {
            $great = CheckIfUserExists($table, $email, $conn);
            if ( $great === true ) {
                $response = array('Created' => false, "USER" => $name, "Details" => "Email '".$email."' already exists");
                echo json_encode($response);
            } else if ( $great === false ) {
               InsertNewUser($database, $table, $conn, $name, $lastname, $email, 
               $pass, $country, $city, $deviceOS, $phone, $ip_address, 
               0, 0.00, 0, 0.0);
               $response = array("Created" => true, "USER" => $name, "UserToken" => $userToken);
               echo json_encode($response);
            }
        }

    } catch (Exception $e) {
        die("Error: ". $e -> getMessage() . PHP_EOL);
    } finally {
        unset($conn);
    }
    