<?php

/**
 *  Insert many entries to MemoryList (not a super sophisticated test)
 *  
 *  @author T Pierce <tyler.pierce@gmail.com>
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);


$amountTest = 50000;


require 'config/boot_memoryList.php';


$timeStart = microtime(true);

$memoryList = new MemoryList('test_structure');

// 5 inserts into test_structure
for ($i = 0 ; $i < $amountTest ; $i++)
{
    $result = $memoryList->insert('somedata-' . rand(1, 5));
}

$timeTotal = round(microtime(true) - $timeStart, 7);


echo '<p>Index of last insert: ' . $result . ' and ran in ' . $timeTotal . ' seconds.</p>';

echo '<p>Done test <a href="test_insert_stress.php">Run Again</a>.</p>';

