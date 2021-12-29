<?php
    header("Content-Type: text/event-stream\n\n");
    include_once('Config.php');
    noCache();
    $servername = _SERVERNAME;
    $database = _DATABASE3;
    $username = _USERNAME;
    $password = _PASSWORD;

    $arrOptions = array(
        PDO::ATTR_EMULATE_PREPARES => FALSE, 
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
    );

    $conn = new PDO("mysql:host = $servername; dbname = $database", $username, $password, $arrOptions);
    $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    function checkDB($db, $conn) {
        $table = $db.".users";
        $SQL_GET_USER_INFO = "SELECT `Score` FROM $table WHERE `Session_User_Token` = 'ad2c96046d427f6f05def8f267a9f6ff'";
        $numOfTables = $conn -> prepare($SQL_GET_USER_INFO);
        $numOfTables -> execute();
        $row = $numOfTables -> fetchAll(PDO::FETCH_ASSOC);
        return $row[0];
    }

    $score = 0;

    while (true) {
        $res = checkDB($database, $conn);
        if ($res['Score'] !== $score) {
            echo "event: ping\n";
            echo 'data: {"Score":'.$res['Score'].'}';
            echo "\n\n";
            $score = $res['Score'];
        }

        ob_flush();
        flush();
        sleep(4);
    }