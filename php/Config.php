<?php
/*
Add more variables as you need to make the project scalable
Be careful using the vars as they are written
*/
define('_USERNAME', 'Marvin'); //Database user
define('_PASSWORD', 'hassan2016'); //Database password
define('_SERVERNAME', 'localhost'); //Host
define('_DATABASE1', 'allies_menus'); //Database menus
define('_DATABASE2', 'customers_parallevar'); //Database Allies
define('_DATABASE3', 'legal_user_parallevar'); //Database users Parallevar
define('_DATABASE4', 'allies_promos'); //Database users Parallevar

function noCache() {
    header("Expires: Tue, 01 Jul 2001 06:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}