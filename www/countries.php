<?php

include "dbconnect.php";
include "i18n.php";

$I18N = new I18N();

$sth = $dbh->prepare('select CountryCode, Val from Country where TranslationLanguage = ?');
$sth->execute([$I18N->lang]);

$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
?>
