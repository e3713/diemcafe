<?php

include "dbconnect.php";

$sth = $dbh->prepare('insert into Thought set ConversationID = ?, Val = ?');
$sth->execute([$_POST['conversation_id'], $_POST['text']]);

echo json_encode(['result' => 'OK']);
?>
