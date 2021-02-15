<?php
require_once "core.cli.php";
require_once "config.cli.php";


foreach ($gpioWorkers as $key => $value){
    shell_exec("screen -dmS " . $key . "-worker sudo php watcher.php" . $value);
}

echo "\n" . "Press any key to stop all workers..." . "\n";
fgetc(STDIN);

foreach ($gpioWorkers as $key => $value){
    shell_exec("sudo screen -X -S " . $key . "-worker quit");
}
