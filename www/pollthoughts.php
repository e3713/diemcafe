<?php

include "dbconnect.php";

$sth = $dbh->prepare('select Val from Thought where ConversationID = ?');
$sth->execute([$_POST['conversation_id']]);
$rows = $sth->fetchAll(PDO::FETCH_NUM);

function select1($itm) {
  return $itm[0];
}

echo json_encode(array_map('select1', $rows));

?>
