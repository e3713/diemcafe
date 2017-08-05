<?php

require "vendor/autoload.php";

include "header.php";

if(($current_event = CafeEvent::current($dbh)) && $current_event->state() == 'running') {

  echo '<h1>' . $I18N->t('event') . ': ' . $current_event->name . '</h1>';
  // echo '<div class="row">';
//  echo '<div class="col-xs-4">';
  echo '<p>' . $I18N->t('start_time') . ': ' . htmlentities(date('d/m/Y G:i', $current_event->start)) . '</p>';
  echo '<p>' . $I18N->t('end_time') . ': ' . htmlentities(date('d/m/Y G:i', $current_event->end)) . '</p>';

  $sections = $current_event->sections();

  echo '<ul class="list-group">';
  foreach ($sections as $section) {
    echo '<li class="list-group-item">' . htmlentities($section->name);
    echo '<ul class="list-group">';
    $rounds = $section->rounds();
    foreach($rounds as $round) {
      echo '<li class="list-group-item">' . htmlentities(date('G:i', $round->start())) . ': ' . htmlentities($round->question) . '</li>';
    }
    echo '</ul>';
    echo '</li>';
  }
  echo '</ul>';
  // echo '</div>';
  // echo '</div>';
  if($LOGIN_USER) {
    if($current_conversation = $LOGIN_USER->current_conversation()) {
      echo '<a class="btn btn-default" href="table.php?id=' . $current_conversation->id . '" role="button">' . $I18N->t('go_to_my_table') . '</a>';
    } else {
      echo '<p>' . $I18N->t('no_table_assigned_yet') . '</p>';
    }
  } else {
    echo '<p>' . $I18N->t('register_or_log_in') . '</p>';
  }
} else {
  echo '<p>' . $I18N->t('no_active_event') . '</p>';
}
?>
 <?php
 include "footer.php";
 ?>
