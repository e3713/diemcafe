<?php

// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);

include_once "dbconnect.php";


require "vendor/autoload.php";

$FORM_ERROR = '';

if($_POST['username'] || $_POST['password']) {

  $config = new PHPAuth\Config($dbh);
  $auth   = new PHPAuth\Auth($dbh, $config);

  $ret = $auth->login($_POST['username'], $_POST['password'], 1);
  if($ret['error']) {
    $FORM_ERROR = $ret['message'];
  } else {
    setcookie($auth->config->cookie_name, $ret['hash'], $ret['expire'], '/');
    echo $ret['message'];
  }
}

include "header.php";

$USERNAME = htmlentities($_POST['username'], ENT_QUOTES);
$PASSWORD = htmlentities($_POST['password'], ENT_QUOTES);
$ERROR_CLASS = '';

if($FORM_ERROR || !($USERNAME || $PASSWORD )) {
  if($FORM_ERROR) {
    $ERROR_CLASS = ' has-error';
    echo ' <p class="has-error">' . htmlspecialchars($FORM_ERROR) . "</p>";
}

$TEXTS = [
  'login_intro' => htmlentities($I18N->t('login_intro')),
  'email' => htmlentities($I18N->t('email')),
  'password' => htmlentities($I18N->t('password')),
  'log_in' => htmlentities($I18N->t('log_in')),
  'no_username_password' => htmlentities($I18N->t('no_username_password')),
  'click_here_to_register' => htmlentities($I18N->t('click_here_to_register')),
];

  echo <<<ENDFORM
  <p>$TEXTS[login_intro]</p>
  <form method="post" action="">
  <div class="input-group$ERROR_CLASS">
    <span class="input-group-addon" id="basic-addon1">$TEXTS[email]:</span>
    <input type="email" class="form-control" placeholder="$TEXTS[email]" name="username" value="$USERNAME"aria-describedby="basic-addon1">
  </div>
  <div class="input-group$ERROR_CLASS">
    <span class="input-group-addon" id="basic-addon1">$TEXTS[password]:</span>
    <input type="password" class="form-control" placeholder="$TEXTS[password]" name="password" value="$PASSWORD" aria-describedby="basic-addon1">
  </div>
<button type="submit" class="btn btn-primary">$TEXTS[log_in]</button>
<p>$TEXTS[no_username_password] <a href="register.php">$TEXTS[click_here_to_register]</a>.</p>
</form>
ENDFORM;
}
?>
 <?php
 include "footer.php";
 ?>
