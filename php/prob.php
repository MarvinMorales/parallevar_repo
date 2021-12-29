<?php
    $json_Gotten = file_get_contents("php://input");
    function cors() {
        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }
        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            exit(0);
        }
    }

    cors();

    $data = json_decode($json_Gotten);
    $w = $data -> {"Wid"};
    $h = $data -> {"Hei"};
    $r = $data -> {"BRad"};

    $Str = '
    <a href="https://www.parallevar.io" style="text-decoration:none;">
    <div style="width:100%; box-shadow:1px 1px 4px rgba(0,0,0,0.4); 
    border-radius:'.$r.'px; border:1px solid red; max-width:'.$w.'px; 
    height:'.$h.'px; background-image: linear-gradient(#ff6e54, #e72300); display:flex; 
    justify-content:center; align-items:center;">
    <img src="https://public.parallevar.io/img/parallevar-logo.png" style="width:75%"/>
    </div></a>
    ';

    $obj = ['Cat' => $Str];

    echo json_encode($obj, JSON_UNESCAPED_SLASHES);