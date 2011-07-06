<?php

/**
 *  Insert 5 entries to 3 different MemoryLists each.  Then use a multi query to grab them
 *  in sorted order by time, giving us a potential real time view of multiple application events.
 *  
 *  Runs a long time due to sleep calls (so we can see the different time values are sorted)
 *  
 *  @author T Pierce <tyler.pierce@gmail.com>
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);

$v         = 5;
$aggregate = true;
$aggLevel  = true; // default full agg

require 'config/boot_memoryList.php';


$memoryList = new MemoryList('likes');

// 5 inserts into test_structure 1
for ($i = 0 ; $i < 15 ; $i++)
{
    switch (rand(1, 3))
    {
        case 1 :
            $result = $memoryList->setName('likes'.$v)->insert('liked-' . rand(1, 5));
            break;
        
        case 2 :
            $result = $memoryList->setName('comments'.$v)->insert('commented-on-' . rand(1, 5));
            break;

        case 3 :
            $result = $memoryList->setName('views'.$v)->insert('viewed-' . rand(1, 5));
            break;
    }
}

if ($aggregate)
{
    $results = $memoryList->setName('likes'.$v)->multi(array('comments'.$v, 'views'.$v))->aggregate($aggLevel)->query();
}
else
{
    $results = $memoryList->setName('likes'.$v)->multi(array('comments'.$v, 'views'.$v))->query();
}

echo '<p>Result of query: <table><tr><th>TIME</th><th>KEY</th><th>VALUE</th><th>STRUCTURE</th></tr>';

foreach ($results as $result)
{
    echo '<tr><td>' . $result['TIM'] . '</td><td>' . $result['KEY'] . '</td><td>' . $result['VAL'] . '</td><td>' . $result['EXT'] . '</td></tr>';
}

echo '</table></p>';

echo '<p>Done test <a href="test_multiQuery.php">Run Again</a>.</p>';

