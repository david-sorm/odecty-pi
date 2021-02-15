<?php
require "config.cli.php";

define("PDO_DSN", "mysql:host=" . mysqlHost . ";dbname=" .mysqlDatabase. ";charset=" . mysqlCharset);
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => true
];
$pdo = new PDO(PDO_DSN, mysqlUsername, mysqlPassword, $opt);

$thresholds = array();

function processAPIoutput($apiOutputArray){
    $apiOutputArray = explode(ApiDelimiter, ltrim($apiOutputArray, ApiDelimiter));
    return $apiOutputArray;
}

function postAPIrequest($requestArray){
    GLOBAL $apiUsername;
    GLOBAL $apiPassword;
    GLOBAL $apiURL;

    $requestArray["login"] = $apiUsername;
    $requestArray["password"] = $apiPassword;
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestArray);
        $unprocessedResult = curl_exec($ch);

        return $unprocessedResult;
    } catch (Exception $e){
        $i = 0;
        $unprocessedResult = "";
        while ($i <= (count($requestArray) - 2)){
            $unprocessedResult = $unprocessedResult . ApiDelimiter . ApiErrorPrefix . ApiErrorServerOffline;
        }
    }

}

function cli_array_var_dump($arrayToDump){
    $return = "";
    foreach ($arrayToDump as $key => $value){
        $return = $return . " " . $key . " => " . $value . ",";
    }
    $return = substr($return, 0, -1);
    return $return;
}

function pulseRecordExists($counter, $date){
    GLOBAL $pdo;
    $stmt = $pdo->prepare("SELECT pulses FROM " . mysqlMeasurementsTable . " WHERE counter = ? and date = ?;");
    $stmt->execute([$counter, $date]);
    $pulseExists = $stmt->fetchAll();
    return (bool) $pulseExists;
}

function syncIfThresholdExceeded(){
    GLOBAL $pdo;

}

function addPulseRecord($counter, $date, $pulses, $override = false){
    GLOBAL $pdo;
    if (pulseRecordExists($counter, $date)){
        if ($override){
            $stmt = $pdo->prepare("UPDATE `" . mysqlMeasurementsTable . "` SET pulses = ? WHERE counter = ? AND date = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE `" . mysqlMeasurementsTable . "` SET pulses = pulses + ? WHERE counter = ? AND date = ?");
        }
        $stmt->execute([$pulses, $counter, $date]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO `". mysqlMeasurementsTable . "` (`counter`, `date`, `pulses`) VALUES (?, ?, ?);");
        $stmt->execute([$counter, $date, $pulses]);
    }

}

function readPulseRecord($counter, $date){
    if (pulseRecordExists($counter,$date)){
        GLOBAL $pdo;

        $stmt = $pdo->prepare("SELECT `pulses` FROM " . mysqlMeasurementsTable . " WHERE counter = ? and date = ?;");
        $stmt->execute([$counter, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result["pulses"];
    } else {
        return 0;
    }
}

function writePulse($pin){
    $debugMessage = "Falling pulse " . $pin . " written at " . date("H:i:s") . " / " . time() . " (unix timestamp)";
    echo $debugMessage . PHP_EOL;
    shell_exec("echo '" . $debugMessage . "' >> /odecty/log");
    addPulseRecord($pin, time(), 1);
    return;
}

function convertGPIOtoWiringPi($gpio){
    $GPIOWPIMapping = array(
//  GPIO => WiringPi
        1 => null,
        2 => null,
        3 => 8,
        4 => null,
        5 => 9,
        6 => null,
        7 => 7,
        8 => 15,
        9 => null,
        10 => 16,
        11 => 0,
        12 => 1,
        13 => 2,
        14 => null,
        15 => 3,
        16 => 4,
        17 => null,
        18 => 5,
        19 => 12,
        20 => null,
        21 => 13,
        22 => 6,
        23 => 14,
        24 => 10,
        25 => null,
        26 => 11,
        27 => 30,// I2C
        28 => 31,// I2C
        29 => 21,
        30 => null,
        31 => 22,
        32 => 26,
        33 => 23,
        34 => null,
        35 => 24,
        36 => 27,
        37 => 25,
        38 => 28,
        39 => null,
        40 => 29
    );
    $wpi = $GPIOWPIMapping[(int)$gpio];
    return $wpi;
}

function pulseDaemon($GPIO){
    $WiringPi = convertGPIOtoWiringPi($GPIO);
    if (shell_exec("sudo gpio read ". $WiringPi) <= 1){
        echo "Waiting for pulses..." . PHP_EOL;
        while (true){
            if (shell_exec("sudo gpio read " . $WiringPi) == 1){
                while (true){
                    if(shell_exec("sudo gpio read " . $WiringPi) == 0){
                        writePulse($GPIO);
                        apiSync();
                        break;
                    }
                    usleep( 100000); // cekat desetinu sekundy, aby se nepretezoval zbytecne procesor (1s = 1 000 000 mikrosekund)
                }
            };
            usleep (100000);
        }
    } else {
      echo "Error while initalising watcher. Please check whether you're running the script as root, have needed dependencies, and on supported hardware" . PHP_EOL;
    }

}

function isCLI(){
    if( defined('STDIN') ){
        return true;
    } else if( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0){
        return true;
    } else {
        return false;
    }
}

function cinput($overrideshell = null){
    if ($overrideshell == null){
        GLOBAL $shell;
    } else {
        $shell = $overrideshell;
    }
    return readline($shell);
}

function cprintln($string, $overrideshell = null){
    if ($overrideshell == null){
        GLOBAL $shell;
    } else {
        $shell = $overrideshell;
    }
    echo $shell . $string . PHP_EOL;
}

function cprint($string){
    GLOBAL $shell;
    echo $shell . $string . PHP_EOL;
}

function apiTransfer($reqArray){
    GLOBAL $apiUsername;
    GLOBAL $apiPassword;
    GLOBAL $apiURL;

    $postReqArray = array();
    foreach ($reqArray as $key => $value){
        $postReqArray["request" . $key] = $value;

    }
    $postReqArray["login"] = $apiUsername;
    $postReqArray["password"] = $apiPassword;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postReqArray);
    $result = explode("%",curl_exec($ch));
    $return = array();
    foreach ($result as $key => $value){
        if ($key != 0){
            $return[($key - 1)] = $value;
        }
    }

    xdebug_var_dump($return);

}

function apiSync(){
    GLOBAL $pdo;
    GLOBAL $gpioWorkers;
    GLOBAL $apiURL;

    $postArray = array();

    // nacteni dat z mezidatabaze
    $connection = $pdo->query("SELECT * FROM " . mysqlMeasurementsTable);
    $dataArray = $connection->fetchAll(PDO::FETCH_ASSOC);
    if (empty($dataArray[0]["counter"])){
        echo "No data to sync. " . PHP_EOL;
        return;
    }
    // konverze dat na requesty k odeslani
    foreach ($dataArray as $key => $array){
        $counter = $gpioWorkers[$array["counter"]]; // prevest GPIO pin na nazev merice
        $postArray["request" . $key] = "send-data " . $counter . " " . $array["date"] . " " . $array["pulses"];
    }

    // poslani API requestu
    $result = processAPIoutput(postAPIrequest($postArray));

    echo "Records being sent to the API server (" . $apiURL . "): " . PHP_EOL . PHP_EOL;
    // zpracovani zaslanych dat
    $error = false;
    foreach ($result as $key => $value){
        $counter = $dataArray[$key]["counter"];
        $date = $dataArray[$key]["date"];
        $pulses = $dataArray[$key]["pulses"];

        if (str_split($value) == ApiErrorPrefix){
            // v pripade chyby vyhozene serverem zapsat do logu
            $error = true;
            shell_exec("echo 'Error from API server while running `" .$postArray["request" . $key]. "`: " . $value . " at " . time(). "(unix timestamp)' >> /odecty/log");
            echo "     `counter = " . $counter . ", date = " . $date . ", pulses = " . $pulses ."` : SERVER ERROR " . PHP_EOL;
        } else {
            $connection = $pdo->prepare("DELETE FROM " . mysqlMeasurementsTable . " WHERE counter = ? and date = ? and pulses = ?");
            $connection->execute([$counter, $date, $pulses]);
            echo "     `counter = " . $counter . ", date = " . $date . ", pulses = " . $pulses ."` : " . $value . PHP_EOL;
        }
    }


    echo PHP_EOL . "     |------------|" . PHP_EOL;
    if ($error){
        echo "     | SYNC ERROR |" . PHP_EOL;
    } else {
        echo "     |  SYNC  OK  |" . PHP_EOL;
    }
    echo "     |------------|" . PHP_EOL;

}

function virtualPulseDaemon(){
    echo "Which GPIO port to control?" . PHP_EOL;
    $GPIO = (int) readline();
    echo "Press ENTER to trigger GPIO event, write quit to exit." . PHP_EOL;
    while (true){
        if (readline() != "quit"){
            writePulse($GPIO);
            apiSync();
        } else {
            break;
        }
    }
}

