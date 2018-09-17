<?php
require __DIR__ . '/vendor/autoload.php';
require_once ("autoloader/autoload.php");

//var_dump($_GET);
require_once("pages/pg.php");
$exams = new exams();


switch ($action) {
    case "all":
        require('pages/all.php');
        break;
    default:
        echo "Opa...";
}



?>