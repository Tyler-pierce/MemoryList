<?php

/**
 *  Insert 5 entries to MemoryList
 *  
 *  @author T Pierce <tyler.pierce@gmail.com>
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);


require 'config/boot_memoryList.php';


$memoryList = new MemoryList('test_struct');

// 5 inserts into test_structure
for ($i = 0 ; $i < 5 ; $i++)
{
    $result = $memoryList->insert('somedata-' . rand(1, 5));
}

print_r($memoryList->aggregate(true)->query());

echo '<p>Index of last insert: ' . $result . '</p>';

echo '<p>Done test <a href="test_insert.php">Run Again</a>.</p>';

