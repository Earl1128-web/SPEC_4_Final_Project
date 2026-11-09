
    <?php

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    echo "TEST 1: Starting PHP<br>";

    require_once __DIR__ . '/includes/auth.php';
    echo "TEST 2: Authentication loaded<br>";

    require_once __DIR__ . '/config/database.php';
    echo "TEST 3: Database loaded<br>";

    require_once __DIR__ . '/includes/functions.php';
    echo "TEST 4: Functions loaded<br>";

    echo "ALL TESTS PASSED!";
