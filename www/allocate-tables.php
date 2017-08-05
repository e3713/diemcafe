<?php
include "header.php";

$current_event = CafeEvent::current($dbh);

if($current_event) {
  if($current_event->state() == 'running') {
    echo '<p class="warning">Event is currently running - declining to generate tables.</p>';
  } elseif ($current_event->state() == 'finished') {
    echo '<p class="warning">No upcoming event.</p>';
  } else {
    // Waiting event - proceed if asked to do so.
    if($_POST['run']) {
        $unallocable_users = $current_event->allocate_conversations(intval($_POST['max_users_per_table']));
        if($unallocable_users) {
          echo '<p class"warning">The following users could not be allocated to a table:</p><ul>';
          foreach($unallocable_users as $user) {
            echo '<li>Section: ' . $user[0]->section_number . ' Round: ' . $user[1]->round_number . ' - ' . htmlentities($user[2]->name) . '&lt;' . htmlentities($user[2]->email) . '&gt;</li>';
          }
          echo '</ul>';
        }
        echo '<p>Table allocation complete</p>';
    }
  }

}
?>
<form method="post" action="" >
  <div class="form-group">
    <label class="control-label" for="max_users_per_table">Max users per table:</label>
    <input type="text" class="form-control" length="3" placeholder="Max users per table" name="max_users_per_table" value="" aria-describedby="max_users_per_table_help">
  </div>

<button type="submit" class="btn btn-primary" name="run" value="1">Allocate Conversations</button>
</form>

<?php
include "footer.php";
?>
