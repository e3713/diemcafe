<?php

require "vendor/autoload.php";

include "header.php";

echo <<< EOALARM
<audio id="alarm">
  <source src="alarm.mp3" type="audio/mp3">
</audio>
EOALARM;

if(($current_event = CafeEvent::current($dbh)) && $current_event->state() == 'running' && $_GET['id']) {


  // SECURITY: check that current user is actually attached to this conversation. If not, bail.
  echo '<div class="row">';
  echo '<div class="col-xs-8"><h1>' . $I18N->t('event') . ': ' . $current_event->name . '</h1></div>';
  echo '<div class="col-xs-4 has-error hidden" id="round_ending"><span class="glyphicon glyphicon-time" aria-hidden="true"></span> ' . $I18N->t('round_ending') , '</div>';
  echo '</div>';

  $conversation = new CafeConversation($dbh, intval($_GET['id']));
  $table = $conversation->table();
  $round = $conversation->round();
  $section = $round->section();

  $is_hosting = ($LOGIN_USER->id == $table->host_user_id);

  echo '<div class="row">';
  echo '<div class="col-xs-3">' . $I18N->t('section') . ': ' . htmlentities($section->section_number) . '</div>';
  echo '<div class="col-xs-3">' . $I18N->t('round') . ': ' . htmlentities($round->round_number) . '</div>';
  echo '<div class="col-xs-3">' . $I18N->t('hosted_by') . ': ' . htmlentities($table->host()->name) . '</div>';
  echo '<div class="col-xs-3">' . $I18N->t('language') . ': ' . htmlentities($table->language($I18N->lang)->name) . '</div>';
  echo '</div>';

  echo '<div class="row">';
  echo '<div class="col-xs-6">' . $I18N->t('start_time') . ': ' . htmlentities(date('G:i:s', $round->start())) . '</div>';
  echo '<div class="col-xs-6" id="timer_text">' . $I18N->t('time_remaining') . ': <span id="timer"></span></div>';
  echo '</div>';
  $time = $round->end();

  echo <<< EOSCRIPT
<script>

var alarmed = false;

function timer_actions(remaining) {
    if(remaining < 180) {
      // Three minute warning
      $('#round_ending').removeClass('hidden');
      $('#timer_text').addClass('text-danger');
      if(!alarmed) {
        document.getElementById('alarm').play();
        alarmed = true;
      }
    }
    if(remaining == 0) {
      // Ended
      $('#next_table').removeClass('hidden');

    }
}

var timer = new CafeTimer('#timer', $time, 'down', timer_actions);
timer.start();

function save_zoom() {
  $.post("savezoom.php", { 'url': $('#zoom_link').val(), 'conversation_id': $conversation->id }, function(data) {
  });
}

function poll_for_zoom() {
  $.post("checkzoom.php", { 'conversation_id': $conversation->id }, function(data) {
    if(data.url) {
      $('#zoom_launch').attr('href', data.url);
      $('#zoom_launch').removeClass('hidden');
      $('#zoom_wait').hide();
    } else {
      setTimeout(function() { poll_for_zoom(); }, 5000);
    }
  }, 'json');
}

function update_thoughts() {
  $.post("pollthoughts.php", { 'conversation_id': $conversation->id }, function(data) {
    $('#thoughts').html('<div class="row">' + data.join('</div><div class="row">') + '</div>');
    $('#thoughts').attr('num_thoughts', data.length);
    if(data.length >= 5 ) {
      $('#submit_thoughts').addClass('hidden');
    }
  }, 'json');
}

function poll_thoughts() {
  update_thoughts();
  var num_thoughts = $('#thoughts').attr('num_thoughts');
  if(num_thoughts === undefined || num_thoughts < 5)
    setTimeout(function() { poll_thoughts(); }, 10000);
}

poll_thoughts();

function save_thought() {
  $.post("savethought.php", { 'text': $('#thought').val(), 'conversation_id': $conversation->id }, function(data) {
    $('#thought').val('');
    update_thoughts();
  }, 'json');
}

</script>
EOSCRIPT;

  echo '<div class="row">';
  echo '<h2>' . $I18N->t('question_to_be_discussed') . ': ' . htmlentities($round->question) . ' </h2>';
  echo '</div>';
  echo '<div class="row">';
  if($is_hosting) {
    $zoom_enc = htmlentities($conversation->zoom_link, ENT_QUOTES);
    $set_zoom_link = htmlentities($I18N->t('set_zoom_link'));
    $save = htmlentities($I18N->t('save'));
    echo <<< EOZOOM
    <div class="form-group">
      <label class="control-label" for="zoom_link">$set_zoom_link:</label>
      <input type="text" class="form-control" id="zoom_link" placeholder="$set_zoom_link" name="email" value="$zoom_enc">
      <button type="submit" class="btn btn-primary" onclick="save_zoom()">$save</button>
  </div>
EOZOOM;
  } else {
    // Zoom launch button - hidden if link not ready yet.
    echo '<a class="btn btn-default' . ($conversation->zoom_link ? '' : ' hidden' ) . '" id="zoom_launch" data-toggle="tooltip" title="' . $I18N->t('join_table_tooltip') . '" role="button" href="' . $conversation->zoom_link . '" target="_blank">' . $I18N->t('join_table') . '</a>';
    if($conversation->zoom_link) {
    } else {
      // Waiting for host to enter Zoom link - poll via AJAX until entered
      echo '<p id="zoom_wait">' . $I18N->t('waiting_zoom_link') . '</p>';
      echo '<script>poll_for_zoom();</script>';
    }

  }
  echo '</div>';

  echo '<div class="row">';

  echo '<div class="col-xs-4"><h2>' . $I18N->t('previous_discussion') . '</h2>';
  if($previous_conversation = $LOGIN_USER->previous_conversation($conversation)) {
    foreach ($previous_conversation->thoughts() as $thought) {
      echo '<div class="row">' . htmlentities($thought->text) . '</div>';
    }
  }

  echo '</div>';

  echo '<div class="col-xs-4"><h2>' . $I18N->t('participants') . '</h2>';

  echo '<div class="row">';
  echo '<div class="col-xs-3">' . $I18N->t('name') . '</div>';
  echo '<div class="col-xs-3">' . $I18N->t('country'). '</div>';
  echo '<div class="col-xs-3">DSC</div>';
  echo '<div class="col-xs-3">' . $I18N->t('city') . '</div>';
  echo '</div>';

  $users = $conversation->users();
  foreach ($users as $user) {
    echo '<div class="row">';
    echo '<div class="col-xs-3">' . htmlentities($user->name) . '</div>';
    echo '<div class="col-xs-3">' . htmlentities($user->country($I18N->lang)->name) . '</div>';
    echo '<div class="col-xs-3">' . htmlentities($user->dsc) . '</div>';
    echo '<div class="col-xs-3">' . htmlentities($user->city) . '</div>';
    echo '</div>';
  }

  // Thought submission controls
  $thoughts = $conversation->thoughts();
  echo '<div class="row' . (count($thoughts) >= 5 ? ' hidden' : '' ) . '" id="submit_thoughts">';
  echo '<div class="form-group">';
  echo '<label class="control-label" for="thought">' . $I18N->t('enter_thought') . ':</label>';
  echo '<input type="text" class="form-control" placeholder="' . $I18N->t('enter_thought_placeholder') . '" name="thought" id="thought" aria-describedby="thought_help">';
  echo '<button type="submit" class="btn btn-primary" onclick="save_thought()">' . $I18N->t('submit') . '</button>';
  echo '</div>';

  echo '</div>';

  echo '</div>';

  echo '<div class="col-xs-4"><h2>' . $I18N->t('thoughts_and_reflections') . '</h2>';
  echo '<div id="thoughts">';
  foreach ($thoughts as $thought) {
    echo '<div class="row">' . htmlentities($thought->text) . '</div>';
  }
  echo '</div>';
  echo '</div>';

  echo '</div>';
  if($next_conversation = $LOGIN_USER->next_conversation($conversation)) {
    echo '<div class="row">';
    echo '<a class="btn btn-default hidden" id="next_table" href="table.php?id=' . $next_conversation->id . '" role="button">' . $I18N->t('next_table') . '</a>';
    echo '</div>';
  }

} else {
  echo '<p>' . $I18N->t('no_active_event') . '</p>';
}
?>
 <?php
 include "footer.php";
 ?>
