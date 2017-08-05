<?php

include "dbconnect.php";

$sth = $dbh->prepare('update Conversation set ZoomLink = ? where ConversationID = ?');
$sth->execute([$_POST['url'], $_POST['conversation_id']]);

?>
