<?php
$dbh = new PDO("mysql:host=localhost;dbname=diem_cafe", "diem_cafe", "");
// Force results character set. Otherwise, on live system, results may not be returned in UTF8, causing htmlentities() to barf and return an empty string.
$dbh->query('set character_set_results = "utf8mb4"');
?>
