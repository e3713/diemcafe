<?php

require "vendor/autoload.php";

include_once "dbconnect.php";

  $config = new PHPAuth\Config($dbh);
  $auth   = new PHPAuth\Auth($dbh, $config);

  $ret = $auth->logout($_COOKIE[$auth->config->cookie_name]);
  setcookie($auth->config->cookie_name, '', 0, '/');

  include "header.php";

?>
<p>You are now logged out.</p>
 <?php
 include "footer.php";
 ?>
