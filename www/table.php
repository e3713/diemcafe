<?php

require "vendor/autoload.php";

include "header.php";

echo <<< EOALARM
<audio id="alarm">
  <source src="alarm.mp3" type="audio/mp3">
</audio>
<audio id="siren">
  <source src="siren.mp3" type="audio/mp3">
</audio>
EOALARM;

if(($current_event = CafeEvent::current($dbh)) && $current_event->state() == 'running' && $_GET['id']) {


  echo '<div class="row">';
  echo '<div class="col-xs-8"><h1>' . $I18N->t('event') . ': ' . $current_event->name . '</h1></div>';
  echo '<div class="col-xs-4"><span id="round_ending" class="text-warning hidden"><span class="glyphicon glyphicon-time" aria-hidden="true"></span> ' . $I18N->t('round_ending') . '</span><span id="round_ended" class="text-danger hidden"><span class="glyphicon glyphicon-time" aria-hidden="true"></span> ' . $I18N->t('round_ended') . '</span></div>';
  echo '</div>';

  // SECURITY: check that current user is actually attached to this conversation. If not, bail.
  $conversation = new CafeConversation($dbh, intval($_GET['id']));
  $table = $conversation->table();
  $round = $conversation->round();
  $section = $round->section();

  $is_hosting = ($LOGIN_USER->id == $table->host_user_id);
  $time = $round->end();

  echo <<< EOSCRIPT
<script>

var alarmed = false;
var ended = false;

function timer_actions(remaining) {
    if(remaining < 180) {
      // Three minute warning
      $('#round_ending').removeClass('hidden');
      $('#round_ending_help').removeClass('hidden');
      $('#timer_text').addClass('text-danger');
      if(!alarmed) {
        document.getElementById('alarm').play();
        alarmed = true;
      }
    }
    if(remaining == 0) {
      // Ended
      $('#round_ending_help').addClass('hidden');
      $('#round_ending').addClass('hidden');
      $('#round_ended_help').removeClass('hidden');
      $('#round_ended').removeClass('hidden');
      $('#next_table').removeClass('hidden');
      if(!ended)
        document.getElementById('siren').play();
      ended = true;
    }
}

var timer = new CafeTimer('#timer', $time, 'down', timer_actions);
timer.start();

function save_zoom() {
  $.post("savezoom.php", { 'url': $('#zoom_link').val(), 'conversation_id': $conversation->id }, function(data) {
    $('#controls').removeClass('hidden');
  });
}

function poll_for_zoom() {
  $.post("checkzoom.php", { 'conversation_id': $conversation->id }, function(data) {
    if(data.url) {
      $('#zoom_launch_button').attr('href', data.url);
      $('#zoom_launch_text').html(data.url);
      $('#zoom_launch').removeClass('hidden');
      $('#zoom_wait').hide();
      $('#controls').removeClass('hidden');
    } else {
      setTimeout(function() { poll_for_zoom(); }, 5000);
    }
  }, 'json');
}

function update_thoughts() {
  $.post("pollthoughts.php", { 'conversation_id': $conversation->id }, function(data) {
    $('#thoughts').html('<tr><td>' + data.join('</td></tr><tr><td>') + '</td></tr>');
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
  if($is_hosting) {
    $zoom_enc = htmlentities($conversation->zoom_link, ENT_QUOTES);
    $set_zoom_link = htmlentities($I18N->t('set_zoom_link'));
    $save = htmlentities($I18N->t('save'));
    echo '<p>' . $I18N->t('host_zoom_help') . '</p>';
    echo <<< EOZOOM
    <div class="form-group">
      <label class="control-label" for="zoom_link">$set_zoom_link:</label>
      <input type="text" class="form-control" id="zoom_link" placeholder="$set_zoom_link" name="email" value="$zoom_enc">
      <button type="submit" class="btn btn-primary" onclick="save_zoom()">$save</button>
  </div>
EOZOOM;
  } else {
    // Zoom launch button - hidden if link not ready yet.
    echo '<div' . ($conversation->zoom_link ? '' : ' class="hidden"' ) . ' id="zoom_launch">';
    echo '<a class="btn btn-default" id="zoom_launch_button" data-toggle="tooltip" title="' . $I18N->t('join_table_tooltip') . '" role="button" href="' . htmlentities($conversation->zoom_link, ENT_QUOTES) . '" target="_blank">' . $I18N->t('join_table') . '</a>';
    echo '<p>' . $I18N->t('zoom_launch_failover') . ' <span id="zoom_launch_text">' . htmlentities($conversation->zoom_link, ENT_QUOTES) . '</span></p>';
    echo '</div>';

    if($conversation->zoom_link) {
    } else {
      // Waiting for host to enter Zoom link - poll via AJAX until entered
      echo '<p id="zoom_wait">' . $I18N->t('waiting_zoom_link') . '</p>';
      echo '<script>poll_for_zoom();</script>';
    }

  }
  echo '</div>';

  echo '<div id="controls" ' . ($conversation->zoom_link ? '' : ' class="hidden"') . '>';

  echo '<div class="row">';
  echo '<h2>' . $I18N->t('question_to_be_discussed') . ': ' . htmlentities($section->question($I18N->lang)) . ' </h2>';
  echo '</div>';

  echo '<div class="row">';
  echo '<div class="col-xs-6"><b>' . $I18N->t('hosted_by') . '</b>: ' . htmlentities($table->host()->name) . '</div>';
  echo '<div class="col-xs-6"><b>' . $I18N->t('language') . '</b>: ' . htmlentities($table->language($I18N->lang)->name) . '</div>';
  echo '</div>';
  echo '<div class="row">';
  echo '<div class="col-xs-6"><b>' . $I18N->t('section') . '</b>: ' . htmlentities($section->section_number) . '</div>';
  echo '<div class="col-xs-6"><b>' . $I18N->t('round') . '</b>: ' . htmlentities($round->round_number) . '</div>';
  echo '</div>';

  echo '<div class="row">';
  echo '<div class="col-xs-6"><b>' . $I18N->t('start_time') . '</b>: ' . htmlentities(date('G:i:s', $round->start())) . '</div>';
  echo '<div class="col-xs-6" id="timer_text"><b>' . $I18N->t('time_remaining') . '</b>: <span id="timer"></span></div>';
  echo '</div>';

  echo '<div class="row">';

  echo '<h2>' . $I18N->t('participants') . '</h2>';
  echo '</div>';

  echo '<table class="table table-striped">';
  echo '<thead><tr>';
  echo '<th>' . $I18N->t('name') . '</th>';
  echo '<th>' . $I18N->t('country'). '</th>';
  echo '<th>DSC</th>';
  echo '<th>' . $I18N->t('city') . '</th>';
  echo '</tr></thead>';

  echo '<tbody>';
  $users = $conversation->users();
  foreach ($users as $user) {
    echo '<tr>';
    echo '<td>' . htmlentities($user->name) . '</td>';
    echo '<td>' . htmlentities($user->country($I18N->lang)->name) . '</td>';
    echo '<td>' . htmlentities($user->dsc) . '</td>';
    echo '<td>' . htmlentities($user->city) . '</td>';
    echo '</tr>';
  }
  echo '</table>';

  echo '<div class="row">';
  echo '<p>';
  echo '<span id="round_ending_help" class="hidden text-warning">' . $I18N->t('round_ending_help') . '</span>';
  echo '<span id="round_ended_help" class="hidden text-danger">' . $I18N->t('round_ended_help') . ' ' . strtolower($I18N->t($is_hosting ? 'round' : 'table')) . '.</span>';
  echo '</p>';
  echo '</div>';

  echo '<div class="row">';
  echo '<h2>' . $I18N->t('thoughts_and_reflections') . '</h2>';
  echo '</div>';

  echo '<div class="row">' . $I18N->t('thoughts_help') . '</div>';

  // Thought submission controls
  echo '<div class="row' . (count($thoughts) >= 5 ? ' hidden' : '' ) . '" id="submit_thoughts">';
  echo '<div class="form-group">';
  echo '<label class="control-label" for="thought">' . $I18N->t('enter_thought') . ':</label>';
  echo '<input type="text" maxlength="255" class="form-control" placeholder="' . $I18N->t('enter_thought_placeholder') . '" name="thought" id="thought" aria-describedby="thought_help">';
  echo '<button type="submit" class="btn btn-primary" onclick="save_thought()">' . $I18N->t('submit') . '</button>';
  echo '</div>';
  echo '</div>';

  echo '<table id="thoughts" class="table table-striped">';
  $thoughts = $conversation->thoughts();
  foreach ($thoughts as $thought) {
    echo '<tr><td>' . htmlentities($thought->text) . '</td></tr>';
  }
  echo '</table>';

  echo '</div>'; // controls

  if($next_conversation = $LOGIN_USER->next_conversation($conversation)) {
    echo '<div class="row">';
    echo '<a class="btn btn-default hidden" id="next_table" href="table.php?id=' . $next_conversation->id . '" role="button">' . ($is_hosting ? $I18N->t('next_round') : $I18N->t('next_table')) . '</a>';
    echo '</div>';
  } else {
    // No next conversation - so this is the last.
    echo '<div class="row">';
    echo '<p id="next_table" class="text-success"><b>' . $I18N->t('event_end_help') . '</b></p>';
    echo '</div>';
  }


} else {
  echo '<p>' . $I18N->t('no_active_event') . '</p>';
}
?>
 <?php
 include "footer.php";
 ?>
