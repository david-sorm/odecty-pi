<?php

if (empty($argv[1])){
    echo "Please specify the operation:" . PHP_EOL;
    echo "php " . basename($_SERVER['PHP_SELF']) . " listBinds - lists all bound GPIO ports and their names" . PHP_EOL;
    echo "--- COMMANDS BELOW SHOULD NOT BE RUN BY USERS ---" . PHP_EOL;
    echo "php " . basename($_SERVER['PHP_SELF']) . " watcher (GPIO port ID) - starts watcher on a GPIO port (not for human use)" . PHP_EOL;
    echo "php " . basename($_SERVER['PHP_SELF']) . " apisync - syncs data to API server (not for human use, only for troubleshooting)" . PHP_EOL;
} else {
    switch ($argv[1]){
        case "listBinds":
            require_once "core.cli.php";
            echo "GPIO Port => Name" . PHP_EOL;
            print_r($gpioWorkers);
            return 0;
            break;

        case "watcher":
            require_once "core.cli.php";
            if ($argv[2] == "virtual"){
                $virtualGPIO = true;
            }
            if (empty($argv[2]) && !$virtualGPIO){
                echo "Error: No pin given. Please specify your GPIO pin after `watcher` argument." . PHP_EOL;
            } else if ($virtualGPIO){
                virtualPulseDaemon();
            } else {
                if (empty($gpioWorkers[$argv[2]])){
                  echo "Error: This pin is not bound. Please bound the pin in the configuration file." . PHP_EOL;
                } else {
                    echo "Starting watcher on pin #" . $argv[2] . " with name `" . $gpioWorkers[$argv[2]] . "`..." . PHP_EOL;
                    if (file_exists("/odecty/")){
                        pulseDaemon($argv[2]);
                    } else {
                        shell_exec("mkdir /odecty");
                        pulseDaemon($argv[2]);
                    }
                }
            }
            break;

        case "apisync":
            require_once "core.cli.php";
            apiSync();
            break;
        default:
            echo "Unknown command. For help, run this file without any arguments." . PHP_EOL;
            return 1;
            break;

    }
}
