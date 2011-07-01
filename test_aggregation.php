<?php

/**
 *  Test aggregation
 *  
 *  @author T Pierce <tyler.pierce@gmail.com>
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);


$levels = 5000;


require 'config/boot_memoryList.php';


$timeStart = microtime(true);

$memoryList = new MemoryList('test_structure');

$aggregateResult = $memoryList->setWaypoint(true)->useWaypoint(true)->aggregate($levels)->query();

$timeTotal = round(microtime(true) - $timeStart, 7);


$newMl = $memoryList->limit(50)->query();

echo '<p>Result after aggregation: ' . print_r($newMl, true) . ' and ran in ' . $timeTotal . ' seconds.</p>';

echo '<p>Done test <a href="test_aggregation.php">Run Again</a>.</p>';

