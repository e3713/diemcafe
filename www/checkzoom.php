<?php

include "dbconnect.php";

$sth = $dbh->prepare('select ZoomLink from Conversation where ConversationID = ?');
$sth->execute([$_POST['conversation_id']]);
$row = $sth->fetch();

echo json_encode(['url' => $row['ZoomLink']]);

?>
