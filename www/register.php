<?php
include "header.php";

// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);

require "vendor/autoload.php";

$VALIDATION_ERRORS = array();

if(count($_POST)) {
  // Some form submissions - validate
  // Need to validate other fields before registration fields, as PHPAuth only provides a 'register' method which goes ahead and registers a user.


  if(!$_POST['name']) {
    $VALIDATION_ERRORS['name'] = $I18N->t('please_enter_your') . ' ' . strtolower($I18N->t('name'));
  }
  if(!$_POST['email']) {
    $VALIDATION_ERRORS['email'] = $I18N->t('please_enter_your') . ' ' . strtolower($I18N->t('email'));
  }
  if(!$_POST['password']) {
    $VALIDATION_ERRORS['password'] = $I18N->t('please_enter_your') . ' ' . strtolower($I18N->t('password'));
  }
  if(!$_POST['password_repeat']) {
    $VALIDATION_ERRORS['password_repeat'] = $I18N->t('please_retype_your') . ' ' . strtolower($I18N->t('password'));
  }
  if(!$_POST['country_id']) {
    $VALIDATION_ERRORS['country'] = $I18N->t('please_choose_your') . ' ' . strtolower($I18N->t('country_of_residence'));
  }
  if(!$_POST['city']) {
    $VALIDATION_ERRORS['city'] = $I18N->t('please_choose_your') . ' ' . strtolower($I18N->t('city'));
  }
  if(!$_POST['language1_id']) {
    $VALIDATION_ERRORS['language1'] = $I18N->t('please_choose_your') . ' ' . strtolower($I18N->t('native_language'));
  }
  // Only go ahead with registration if other fields validate OK, since this will create the user.
  if(!count($VALIDATION_ERRORS)) {
    $config = new PHPAuth\Config($dbh);
    $auth   = new PHPAuth\Auth($dbh, $config);

    $ret = $auth->register($_POST['email'], $_POST['password'], $_POST['password_repeat']);
    if($ret['error']) {
      $VALIDATION_ERRORS['general'] = $ret['message'];
    } else {
      // User record created OK. Now we have to modify it to add the other data.
      $sth = $dbh->prepare('select id from User where Email = ?');
      $sth->execute([$_POST['email']]);
      $row = $sth->fetch();
      $user_id = $row[0];

      // Store main User data
      $sth = $dbh->prepare('update User set Name = ?, CountryCode = ?, City = ?, Host = ? where id = ?');
      $sth->execute([$_POST['name'], $_POST['country_id'], $_POST['city'], $_POST['HOST'], $user_id]);

      // Store language details
      $sth = $dbh->prepare('insert into UserLanguage (UserID, LevelID, LanguageCode) values(?, ?, ?)');
      // First language is always native
      $sth->execute([$user_id, 1, $_POST['language1_id']]);
      // Second language
      if($_POST['language2_id'])
        $sth->execute([$user_id, $_POST['language2_level'], $_POST['language2_id']]);
      // Third language
      if($_POST['language3_id'])
        $sth->execute([$user_id, $_POST['language3_level'], $_POST['language3_id']]);

      // Done creating user. Report success.
      echo '<p>' . $I18N->t('registration_confirmation') . '</p>';
    }
    }
}

function map_entities($val) {
  $val = htmlentities($val, ENT_QUOTES);
}

$POST_ENC = $_POST;
array_walk($POST_ENC, 'map_entities');

$VAL_ENC = $VALIDATION_ERRORS;
array_walk($VAL_ENC, 'map_entities');

$GLYPHS = $VALIDATION_ERRORS;

function glyphify(&$val) {
  if($val)
    $val = '<span class="glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>';
  else
    $val = '';
}
array_walk($GLYPHS, 'glyphify');

$ERROR_CLASSES = $VALIDATION_ERRORS;

function error_class_make(&$val) {
  if($val)
    $val = ' has-error has-feedback';
}
array_walk($ERROR_CLASSES, 'error_class_make');

function success_class_make(&$val) {
  if($val)
    $val = ' has-success has-feedback';
}

function success_glyph_make(&$val) {
  $val = '<span class="glyphicon glyphicon-ok form-control-feedback" aria-hidden="true"></span>';
}

if(count($_POST)) {
  $EC2 = $_POST;

  array_walk($EC2, 'success_class_make');

  $ERROR_CLASSES = array_merge($EC2, $ERROR_CLASSES);

  $GL2 = $_POST;
  array_walk($GL2, 'success_glyph_make');
  $GLYPHS = array_merge($GL2, $GLYPHS);

}

echo <<<'ENDSCRIPT'
<script>
$.get("countries.php", function(data){
  $country = $("#country");
  $country.typeahead({ source:data, displayText: function(itm) { return itm.Val; } });
$country.change(function() {
  var current = $country.typeahead("getActive");
  console.log('COUNTRY_CHANGE');

  if (current) {
    // Some item from your model is active!
    if (current.Val == $country.val()) {
      $('#country_id').attr('value', current.CountryCode);
      // This means the exact match is found. Use toLowerCase() if you want case insensitive match.
    } else {
      // This means it is only a partial match, you can either add a new item
      // or take the active if you don't want new items
    }
  } else {
    // Nothing is active so it is a new value (or maybe empty value)
    $('#country_id').attr('value', '');
  }
});
},'json');


$.get("languages.php", function(data){

  for(var i = 1; i < 4; i++) {
  $cur_language = $("#language" + i);
  $cur_language.typeahead({ source:data, displayText: function(itm) { return itm.Val; } });
  $cur_language.change(function() {
  var current = $(this).typeahead("getActive");
  console.log('CHANGE');
  if (current) {
    console.log('CURRENT', current);
    // Some item from your model is active!
    if (current.Val == $(this).val()) {
      $('#' + $(this).attr('data-id')).attr('value', current.LanguageCode);
      // This means the exact match is found. Use toLowerCase() if you want case insensitive match.
    } else {
      // This means it is only a partial match, you can either add a new item
      // or take the active if you don't want new items
    }
  } else {
    // Nothing is active so it is a new value (or maybe empty value)
    $('#' + $(this).attr('data-id')).attr('value', '');
  }
});
}
},'json');

</script>
ENDSCRIPT;

if(count($VALIDATION_ERRORS) || !count($_POST)) {
  $TEXTS = [
    'name' => htmlentities($I18N->t('name')),
    'email' => htmlentities($I18N->t('email')),
    'password' => htmlentities($I18N->t('password')),
    'password_repeat' => htmlentities($I18N->t('password_repeat')),
    'country_of_residence' => htmlentities($I18N->t('country_of_residence')),
    'city_of_residence' => htmlentities($I18N->t('city_of_residence')),
    'native_language' => htmlentities($I18N->t('native_language')),
    'other_language' => htmlentities($I18N->t('other_language')),
    'level' => htmlentities($I18N->t('level')),
    'lang_level_2' => htmlentities($I18N->t('lang_level_2')),
    'lang_level_3' => htmlentities($I18N->t('lang_level_3')),
    'do_you_want_to_host' => htmlentities($I18N->t('do_you_want_to_host')),
    'yes' => htmlentities($I18N->t('yes')),
    'no' => htmlentities($I18N->t('no')),
    'register' => htmlentities($I18N->t('register')),
    'register_intro' => htmlentities($I18N->t('register_intro'))
  ];
echo <<<ENDFORM
<p>$TEXTS[register_intro]</p>
<p>$VAL_ENC[general]</p>
<form method="post" action="" class="form-horizontal">
<div class="form-group$ERROR_CLASSES[name]">
  <label class="control-label" for="name">$TEXTS[name]:</label>
  <input type="text" class="form-control" placeholder="$TEXTS[name]" name="name" value="$POST_ENC[name]" aria-describedby="name_help">
  $GLYPHS[name]
</div>
<div class="form-group$ERROR_CLASSES[email]">
  <label class="control-label" for="email">$TEXTS[email]:</label>
  <input type="email" class="form-control" placeholder="$TEXTS[email]" name="email" value="$POST_ENC[email]" aria-describedby="email_help">
  $GLYPHS[email]
</div>
<div class="form-group$ERROR_CLASSES[password]">
  <label class="control-label" for="password">$TEXTS[password]:</label>
  <input type="password" class="form-control" placeholder="$TEXTS[password]" name="password" value="$POST_ENC[password]" aria-describedby="password_help">
  $GLYPHS[password]
</div>
<div class="form-group$ERROR_CLASSES[password_repeat]">
  <label class="control-label" for="password_repeat">$TEXTS[password_repeat]:</label
  <span class="form-group-addon" id="basic-addon3"></span>
  <input type="password" class="form-control" placeholder="$TEXTS[password_repeat]" name="password_repeat" value="$POST_ENC[password_repeat]" aria-describedby="password_repeat_help">
  $GLYPHS[password_repeat]
</div>
<div class="form-group$ERROR_CLASSES[country]">
  <label class="control-label" for="country">$TEXTS[country_of_residence]:</label>
  <input type="text" id="country" class="form-control" autocomplete="off" placeholder="$TEXTS[country_of_residence]" name="country" value="$POST_ENC[country]" aria-describedby="country_help">
  $GLYPHS[country]
  <input type="hidden" name="country_id" id="country_id" value="$POST_ENC[country_id]"/>
</div>
<div class="form-group$ERROR_CLASSES[city]">
  <label class="control-label" for="city">$TEXTS[city_of_residence]:</label>
  <input type="text" class="form-control" placeholder="$TEXTS[city_of_residence]" name="city" value="$POST_ENC[city]" aria-describedby="city_help">
  $GLYPHS[city]
</div>

<div class="form-group$ERROR_CLASSES[language1]">
  <label class="control-label" for="language1">$TEXTS[native_language]:</label>
  <input type="text" id="language1" data-id="language1_id" autocomplete="off" class="form-control" placeholder="$TEXTS[native_language]" name="language1" value="$POST_ENC[language1]" aria-describedby="language1_help">
  $GLYPHS[language1]
  <input type="hidden" name="language1_id" id="language1_id" value="$POST_ENC[language1_id]"/>
</div>

<div class="form-group$ERROR_CLASSES[language2]">
  <label class="control-label" for="language2">$TEXTS[other_language]:</label>
  <input type="text" id="language2" data-id="language2_id" autocomplete="off" class="form-control" placeholder="$TEXTS[other_language]" name="language2" value="$POST_ENC[language2]" aria-describedby="language2_help">
  $GLYPHS[language2]
  <input type="hidden" name="language2_id" id="language2_id" value="$POST_ENC[language2_id]"/>
  </div>

  <div class="form-group">
  <label class="control-label" for="language2_level">$TEXTS[level]:</label>
  <select name="language2_level" class="selectpicker"><option value="2">$TEXTS[lang_level_2]</option><option value="3">$TEXTS[lang_level_3]</option></select>
</div>

<div class="form-group$ERROR_CLASSES[language3]">
  <label class="control-label" for="language3">$TEXTS[other_language]:</label>
  <input type="text" id="language3" data-id="language3_id" autocomplete="off" class="form-control" placeholder="$TEXTS[other_language]" name="language3" value="$POST_ENC[language3]" aria-describedby="language3_help">
  $GLYPHS[language3]
  <input type="hidden" name="language3_id" id="language3_id" value="$POST_ENC[language3_id]"/>
  </div>

  <div class="form-group">
  <label class="control-label" for="language3_level">$TEXTS[level]:</label>
  <select name="language3_level" class="selectpicker"><option value="2">$TEXTS[lang_level_2]</option><option value="3">$TEXTS[lang_level_3]</option></select>
</div>

<div class="form-group">
<label class="control-label" for="host">$TEXTS[do_you_want_to_host]</label>
  <select name="host" class="selectpicker"><option value="0" default>$TEXTS[no]</option><option value="1">$TEXTS[yes]</option></select>
</div>

<button type="submit" class="btn btn-primary">$TEXTS[register]</button>
</form>
ENDFORM;
}
?>
 <?php
 include "footer.php";
 ?>
