<?php
include "header.php";
if($_POST['start']) {
  $sth = $dbh->prepare('update Event set Start = ? where EventID = 1');
  if($sth->execute([$_POST['start']]))
    echo '<p>Start time changed. <a href="eventoverview.php">Go to overview</a>.</p>';
}
?>

<form method="post" action="" class="form-horizontal">
<div class="form-group">
  <label class="control-label" for="start">Event start time (GMT +1):</label>
  <input type="text" class="form-control" placeholder="YYYY-MM-DD HH:MM:SS" name="start" value="" aria-describedby="start_help">
</div>
</form>
<?php
include "footer.php";
?>
