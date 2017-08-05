<?php
include "header.php";

$current_event = CafeEvent::current($dbh);

?>
    <div class="jumbotron">
       <h1>DiEM Caf&eacute;</h1>
       <p>Welcome to DiEM Caf&eacute;</p>
       <?php
      switch($current_event->state()) {
        case 'waiting':
          echo '<p>' . $I18N->t('next_event') . ': ' . htmlentities($current_event->name) . '</p>';
          echo '<p>' . $I18N->t('time_remaining') . ': <span id="timer"></span></p>';
          break;
        case 'running':
          echo '<p>' . $I18N->t('current_event') . ': ' . htmlentities($current_event->name) . '</p>';
          echo '<p>' . $I18N->t('time_remaining') . ': <span id="timer"></span></p>';
          echo '<p><a href="eventoverview.php">' . $I18N->t('click_here_to_participate') . '</a>.</p>';
          break;
        case 'finished':
          echo '<p>' . $I18N->t('last_event') . ': ' . htmlentities($current_event->name) . '</p>';
          break;
      }
      ?>
     </div>
     <?php
     if($current_event->state() == 'running' || $current_event->state() == 'waiting') {
        if($current_event->state() == 'running') {
          $time = $current_event->end;
          $opt = 'down';
        }  else {
          $time = $current_event->start;
          $opt = 'down';
        }
        echo <<< EOTIMER
     <script>
     var timer = new CafeTimer('#timer', $time, '$opt');
     timer.start();
     </script>
EOTIMER;
}
     ?>

<?php
 include "footer.php";
?>
