<?php
require "vendor/autoload.php";

include_once "dbconnect.php";
include "DCafe.php";
include "i18n.php";

$I18N = new I18N();

$config = new PHPAuth\Config($dbh);
$auth   = new PHPAuth\Auth($dbh, $config);
$LOGIN_USER = NULL;

if ($auth->isLogged()) {
  $uid = $auth->getSessionUID($_COOKIE[$auth->config->cookie_name]);
  $LOGIN_USER = new CafeUser($dbh, $uid);
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/bassjobsen/bootstrap-3-typeahead/bootstrap3-typeahead.js"></script>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.3/css/bootstrap-select.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.3/js/bootstrap-select.min.js"></script>
    <script>
    var CafeTimer = function(el, target_utime, direction, on_tick) {
      this.target_utime = target_utime;
      this.el = el;
      this.direction = direction;
      this.on_tick = on_tick;
    };

    function pad_zero(num, len) {
      var n = new String(num);
      while(n.length < len) {
        n = '0' + num;
      }
      return n;
    }

    CafeTimer.prototype = {

    update: function() {
      var delta;
      delta = Math.floor((new Date(this.target_utime * 1000) - new Date()) / 1000);
      if(this.direction == 'up')
        delta = -delta;

        if (delta < 0) {
            delta = 0;
        }
        var orig_delta = delta;

        var days = Math.floor(delta / 86400);
        delta -= days * 86400;
        var hours = Math.floor(delta / 3600);
        delta -= hours * 3600;
        var minutes = Math.floor(delta / 60);
        delta -= minutes * 60;
        var seconds = delta;

        $(this.el).html((days ? days + ' days, ' : '') + pad_zero(hours, 2) + ':' + pad_zero(minutes, 2) + ':' + pad_zero(seconds, 2));
        if(this.on_tick) {
          // Tick callback
          this.on_tick(orig_delta);
        }
    },


    start: function() {
      this.update();
      var oThis = this;
        setTimeout(function() {
          oThis.start();
        }, 1000);
    }
    };

function set_language() {
    document.cookie = 'lang=' + $('#language option:selected').val() + '; expires=Fri, 01 Jan 2038 00:00:00 UTC; path=/';
    window.location.reload();
}
</script>

    <title>DiEM Caf&eacute;</title>

    <!-- Bootstrap -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap theme -->
    <link href="bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
    body {
  padding-top: 70px;
  padding-bottom: 30px;
}
a.navbar-brand { padding: 10px }
a.navbar-brand img { width: 75px; height: 32px }
div.input-group { margin-bottom: 10px }
A.carousel-control { width: 5% }
.carousel-control.left, .carousel-control.right { background-image: none }
DIV.jumbotron { background-image: linear-gradient(to right,rgba(236,80,34,0.1) 0,rgba(237,29,36,0.1) 100%); border-radius: 10px; }
DIV.jumbotron-alt { background-image: linear-gradient(to right,rgba(236,80,34,0.2) 0,rgba(237,29,36,0.2) 100%); border-radius: 10px; }
DIV.jumbotron OL, DIV.jumbotron UL { font-size: 18px }
    </style>
  </head>
  <body>

<nav class="navbar navbar-default navbar-fixed-top">
     <div class="container">
       <div class="navbar-header">
         <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
           <span class="sr-only">Toggle navigation</span>
           <span class="icon-bar"></span>
           <span class="icon-bar"></span>
           <span class="icon-bar"></span>
         </button>
         <a class="navbar-brand" href="#"><img src="/img/dcafelogo.png"/></a>
       </div>
       <div id="navbar" class="navbar-collapse collapse">
         <ul class="nav navbar-nav">
           <li><a href="/"><?php echo htmlentities($I18N->t('home'));?></a></li>
           <?php
           if(!$_SUPPRESS_NAV) {
             if($LOGIN_USER) {
               echo '<li class="dropdown">' .
               '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">' . htmlentities($LOGIN_USER->name) . ' <span class="caret"></span></a>' .
               '<ul class="dropdown-menu"><li><a href="/logout.php">' . $I18N->t('log_out') . '</a></li></ul></li>';
             } else {
               echo '<li><a href="/login.php">' . $I18N->t('log_in') . '</a></li>';
             }
           }
           ?>
           <li>
             <select name="language" id="language" class="selectpicker" onchange="set_language()">
             <?php
             $sth = $dbh->prepare('select LanguageCode, Val from Language where TranslationLanguage = ? and LanguageCode in ("en", "es")');
             $sth->execute([$I18N->lang]);
             $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
              echo '<option value="' . $row['LanguageCode'] . '"' . ($row['LanguageCode'] == $I18N->lang ? ' selected="selected"' : '') . '>' . htmlentities($row['Val']) . '</option>';
            }
?>
            </select>
            <script>
            <?php
            echo "$('#language').selectpicker('val', '$I18N->lang');\n";
            ?>
            </script>
           </li>
         </ul>
       </div><!--/.nav-collapse -->
     </div>
   </nav>

   <div class="container" role="main">
