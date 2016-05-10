<?php
require __DIR__.'/vendor/autoload.php';
$api=new \Schorsch3000\Pilight\Api();
$api->setApiUrl('http://10.0.10.1:5001');
$screens=$api->getSwitch('lamp');


var_dump($screens->getState());
$screens->setState(false);
var_dump($screens->getState());
sleep(3);
$screens->setState(true);
var_dump($screens->getState());


