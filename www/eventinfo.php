<?php
include "header.php";

switch($I18N->lang) {
  case 'de':
  include "eventinfo-body-de.php";
  break;

  case 'es':
  include "eventinfo-body-es.php";
  break;

  case 'fr':
  include "eventinfo-body-fr.php";
  break;

  case 'it':
  include "eventinfo-body-it.php";
  break;

  default:
    include "eventinfo-body.php";
}

include "footer.php";
?>
