<?php

define("ApiCoreUpdateURL", "http://192.168.0.103/");
define("ApiDelimiter", "%");
define("ApiRequestOk", "Done");
define("ApiErrorWrongCredentials", "WrongCredentials");
define("ApiErrorNotLoggedIn", "NotLoggedIn");
define("ApiErrorUnknownRequest", "UnknownRequest");
define("ApiErrorNoInputProvided", "NoInputProvided");
define("ApiErrorServerError", "ServerError: ");
define("ApiErrorServerOffline", "Server Offline!");
define("ApiErrorPrefix", "^");

$apiUsername = "1";
$apiPassword = "jedna";
$apiURL = "http://localhost/odecty-OOP/api.php";
$shell = " > ";
$virtualGPIO = true;
$gpioWorkers = array(
    "40" => "mesice_1",
    "37" => "mesice_2",
    "36" => "mesice_3",
    "33" => "mesice_4",
    "32" => "meisce_5",
    "29" => "mesice_6",
    "18" => "mesice_7",
    "15" => "mesice_8",
    "11" => "mesice_9"
);

define("mysqlHost","localhost");
define("mysqlUsername","root");
define("mysqlPassword","sorm6161");
define("mysqlDatabase","odecty-pi");
define("mysqlCharset", "utf8");
define("mysqlMeasurementsTable", "odectycache");
