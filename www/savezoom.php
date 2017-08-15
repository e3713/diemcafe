<?php

include "dbconnect.php";

$zoom_url = '';

if(preg_match('/^\d+$/',$_POST['url'])) {
  // It's an ID - make it into a Zoom URL
  $zoom_url = 'https://zoom.us/j/' . $_POST['url'];
} elseif (filter_var($_POST['url'], FILTER_VALIDATE_URL) !== false) {
  // Well-formed URL
  $zoom_url = $_POST['url'];
}

$sth = $dbh->prepare('update Conversation set ZoomLink = ? where ConversationID = ?');
$sth->execute([$zoom_url, $_POST['conversation_id']]);

?>
