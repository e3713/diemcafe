<?php
include "header.php";

$current_event = CafeEvent::current($dbh);

?>
    <div class="jumbotron">
       <h1>DiEM Caf&eacute;</h1>
       <?php
      switch($current_event->state()) {
        case 'waiting':
          echo '<p>' . $I18N->t('next_event') . ': ' . htmlentities($current_event->name) . '</p>';
          echo '<p><a href="eventinfo.php">' . $I18N->t('click_here_for_more_information') . '</a></p>';
          echo '<p>' . $I18N->t('time_remaining') . ': <span id="timer"></span></p>';
          if(!$LOGIN_USER) {
            echo '<p><a href="register.php">' . $I18N->t('click_here_to_register') . '</a>.</p>';
            echo '<p><b>If you have already registered, please <a href="login.php">log in</a> if you want to participate.</b></p>';
          }
          echo '<p class="hidden" id="overview_link"><a href="eventoverview.php">' . $I18N->t('click_here_to_participate') . '</a>.</p>';
          break;
        case 'running':
          echo '<p>' . $I18N->t('current_event') . ': ' . htmlentities($current_event->name) . '</p>';
          echo '<p><a href="eventinfo.php">' . $I18N->t('click_here_for_more_information') . '</a></p>';
          echo '<p>' . $I18N->t('time_remaining') . ': <span id="timer"></span></p>';
          echo '<p><a href="eventoverview.php">' . $I18N->t('click_here_to_participate') . '</a>.</p>';
          break;
        case 'finished':
          echo '<p>' . $I18N->t('last_event') . ': ' . htmlentities($current_event->name) . '</p>';
          echo '<p>We are sorry to announce that the event scheduled for next week has had to be postponed pending further development of the DiEM Café system.</p>';
          echo '<p>We will inform you as soon as everything is ready.</p>';
          echo '<p>If you have IT skills and would like to volunteer to help with the development of the platform please contact us at: <a href="mailto:did.diem25@gmail.org">did.diem25@gmail.org</a>.</p>';
          break;
      }
      ?>
     </div>
     <?php
     switch($I18N->lang) {
       case 'de':
       include "help-body-de.php";
       break;

       case 'es':
       include "help-body-es.php";
       break;

       case 'fr':
       include "help-body-fr.php";
       break;

       case 'it':
       include "help-body-it.php";
       break;

       default:
         include "help-body.php";
     }

     if($current_event->state() == 'running' || $current_event->state() == 'waiting') {
        if($current_event->state() == 'running') {
          $time = $current_event->end;
          $opt = 'down';
          $func = '';
        }  else {
          $time = $current_event->start;
          $opt = 'down';
          $func = ', timer_actions';
        }
        echo <<< EOTIMER
     <script>
     var overview_link_revealed = false;

     function timer_actions(remaining) {
        if(remaining == 0 && !overview_link_revealed) {
          // Waiting event and timer has gone to zero - reveal event overview link.
          $('#overview_link').removeClass('hidden');
          overview_link_revealed = true;
        }
     }

     var timer = new CafeTimer('#timer', $time, '$opt'$func);
     timer.start();
     </script>
EOTIMER;
}
     ?>

<?php
 include "footer.php";
?>
