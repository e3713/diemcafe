<?php
class I18N {
  private $texts = [
  'en' => [
    'event' => 'Event',
    'start_time' => 'Start time',
    'end_time' => 'End time',
    'go_to_my_table' => 'Go to my table',
    'no_table_assigned_yet' => 'You haven\'t been assigned to a table yet. Reload and try again.',
    'register_or_log_in' => 'Please <a href="login.php">log in</a> or <a href="register.php">register</a> to participate.',
    'no_active_event' => 'There is no currently active event. <a href="/">Click here</a> to go back to the home page.',
    'round_ending' => 'Round is ending soon',
    'voice_round_ending' => 'The round is ending in three minutes. Please agree on your thoughts for this round and submit them to the system.',
    'round_ended' => 'Round has ended',
    'voice_round_ended' => 'The round has now ended. If you have finished recording your thoughts, when you are ready, click the button to go to your next table.',
    'section' => 'Section',
    'round' => 'Round',
    'table' => 'Table',
    'hosted_by' => 'Hosted by',
    'language' => 'Language',
    'time_remaining' => 'Time remaining',
    'question_to_be_discussed' => 'Question to be discussed',
    'set_zoom_link' => 'Set zoom link or ID',
    'zoom_link' => 'Zoom link',
    'save' => 'Save',
    'join_table_tooltip' => 'Click this button to launch the Zoom videoconferencing app to participate in the discussion.',
    'join_table' => 'Join table',
    'waiting_zoom_link' => "<b>You are now at the table</b>. Your host will start the Zoom conversation and provide the details to the system. Once that's done, a button will appear here that will allow you to join the conversation.",
    'previous_discussion' => 'Previous discussion',
    'participants' => 'Participants',
    'name' => 'Name',
    'country' => 'Country',
    'city' => 'City',
    'enter_thought' => 'Enter a thought',
    'enter_thought_placeholder' => 'Type a thought',
    'thoughts_help' => 'Discuss the current question in the Zoom conversation. Agree between you the results of this round, and then save up to 5 thoughts here. There area total of five thoughts shared among all the participants on this table, so please coordinate the submission of thoughts in the conversation.',
    'submit' => 'Submit',
    'thoughts_and_reflections' => 'Your thoughts and reflections',
    'next_table' => 'Go to next table',
    'next_round' => 'Go to next round',
    'log_out' => 'Log out',
    'log_in' => 'Log in',
    'please_enter_your' => 'Please enter your', // eg Please enter your email address
    'email' => 'Email',
    'password' => 'Password',
    'please_retype_your' => 'Please retype your', // eg Please retype your password
    'please_choose_your' => 'Please choose your',
    'country_of_residence' => 'Country of residence',
    'native_language' => 'Native language',
    'registration_confirmation' => "You have successfully registered. Please re-read the instructions on the <a href=\"/\">home page</a> and <b>make sure you're logged in</b> before the event starts.",
    'password_repeat' => 'Password (repeat)',
    'city_of_residence' => 'City of residence',
    'other_language' => 'Other language',
    'level' => 'Level', // as in level speaking language
    'lang_level_2' => 'Comfortable speaking',
    'lang_level_3' => 'Only if no other option',
    'do_you_want_to_host' => 'Do you want to host a table?',
    'yes' => 'Yes',
    'no' => 'No',
    'register' => 'Register',
    'next_event' => 'Next event',
    'current_event' => 'Current event',
    'click_here_to_participate' => 'Click here to participate',
    'last_event' => 'Last event',
    'register_intro' => 'Please enter your details here to register for DiEM Cafe.',
    'login_intro' => "Please enter your email address and password, and click 'Log in'.",
    'no_username_password' => "Don't have a username and password?",
    'click_here_to_register' => 'Click here to register',
    'home' => 'Home',
    'login_success' => 'You are now logged in. Please return to the <a href="/">home page</a> and wait for the event to start.',
    'host_zoom_help' => '<b>You are hosting this table</b>. Please start the Zoom app, begin a new conversation, and then copy and paste the conversation ID or the zoom link here, and click Save.',
    'zoom_launch_failover' => "If clicking the button doesn't work, please copy and paste this URL into a new browser tab or window:",
    'round_ending_help' => 'The round is ending soon. Please discuss together the thoughts you want to record at the end of this round, and coordinate between yourselves to submit them to the system.',
    'round_ended_help' => 'This round has now ended. When you are ready, click the button at the bottom of the page to go to the next',
    'event_end_help' => 'This event has now ended. Thank you very much for participating in DiEM Cafe!'
  ],
  'es' => [
    'event' => 'Evento',
    'start_time' => 'Hora comienzo',
    'end_time' => 'Hora finalizacion',
    'go_to_my_table' => 'Ir a mi mesa',
    'no_table_assigned_yet' => 'Todavia no te han asignado una mesa. Por favor vuelva a cargar la pagina y comprueba otra vez.',
    'register_or_log_in' => 'Por favor <a href="login.php">inicia sesion</a> o <a href="register.php">registra</a> para poder participar.',
    'no_active_event' => 'No hay event activo. <a href="/">Pulsa aqui</a> para volver a la pagina de inicio.',
    'round_ending' => 'Ronda termina pronto',
    'voice_round_ending' => 'La ronda termina en tres minutos. Por favor, ponerse de acuerdo sobre los pensamientos de esta ronda, y grabalos en el sistema.',
    'round_ended' => 'Ronda terminada',
    'voice_round_ended' => 'La ronda ha terminado. Se habeis terminado de grabar vuestros pensamientos, cuando quieras, haz click en el boton para ir a la siguiente mesa.',
    'section' => 'Seccion',
    'round' => 'Ronda',
    'table' => 'Mesa',
    'hosted_by' => 'Anfitrion',
    'language' => 'Idioma',
    'time_remaining' => 'Tiempo restante',
    'question_to_be_discussed' => 'Pregunta a debatir',
    'set_zoom_link' => 'Asignar enlace Zoom o ID',
    'zoom_link' => 'Enlace Zoom',
    'save' => 'Guardar',
    'join_table_tooltip' => 'Haz click aqui para lanzar el app de videoconferencia Zoom para participar en el debate.',
    'join_table' => 'Unete a la mesa',
    'waiting_zoom_link' => 'Esperando a que el anfitrion facilite el enlace Zoom.',
    'previous_discussion' => 'Debate anterior',
    'participants' => 'Participantes',
    'name' => 'Nombre',
    'country' => 'Pais',
    'city' => 'Ciudad',
    'enter_thought' => 'Escribe un pensamiento',
    'enter_thought_placeholder' => 'Escribe un pensamiento',
    'thoughts_help' => 'Debate la pregunta actual en la conversacion Zoom. Poneros de acuerdo sobre los resultados de esta ronda, y luego graba un maximo de 5 pensamientos aqui. Hay un total de cinco pensamientos compartidos entre todos los partipantes de esta mesa, asi que por favor coordina la entrada de pensamientos en la conversacion.',
    'submit' => 'Mandar',
    'thoughts_and_reflections' => 'Tus pensamientos y reflexiones',
    'next_table' => 'Ir a la siguiente mesa',
    'next_round' => 'Ir a la siguiente ronda',
    'log_out' => 'Cerrar sesion',
    'log_in' => 'Inicia sesion',
    'please_enter_your' => 'Por favor escribe tu', // eg Please enter your email address
    'email' => 'Email',
    'password' => 'Clave',
    'please_retype_your' => 'Por favor vuelve a escribir', // eg Please retype your password
    'please_choose_your' => 'Por favor elige tu',
    'country_of_residence' => 'Pais de residencia',
    'native_language' => 'Idioma maternal',
    'registration_confirmation' => "Has registrado con exito. Por favor lee otra vez las instrucciones en la <a href=\"/\">pagina de inicio</a> y <b>asegurate de iniciar sesion antes del comienzo del evento.",
    'password_repeat' => 'Clave (repetir)',
    'city_of_residence' => 'Ciudad de residencia',
    'other_language' => 'Otro idioma',
    'level' => 'Level', // as in level speaking language
    'lang_level_2' => 'Comodo hablando',
    'lang_level_3' => 'Solo si no hay otra opcion',
    'do_you_want_to_host' => 'Quieres ser anfitrion de una mesa?',
    'yes' => 'Si',
    'no' => 'No',
    'register' => 'Registrar',
    'next_event' => 'Proximo evento',
    'current_event' => 'Evento actual',
    'click_here_to_participate' => 'Haz click aqui par participar',
    'last_event' => 'Ultimo evento',
    'register_intro' => 'Por favor rellena el formulario para registrar para DiEM Cafe.',
    'login_intro' => "Por favor escribe tu email y contrasena, y haz click en 'Iniciar sesion'.",
    'no_username_password' => "No tienes claves?",
    'click_here_to_register' => 'Haz click aqui para registrar',
    'home' => 'Inicio',
    'login_success' => 'Has iniciado sesion. Por favor vuelve a la <a href="/">pagina de inicio</a> y espera el comienzo del evento.',
    'host_zoom_help' => '<b>Eres el anfitrion de esta mesa</b>. Por favor abre el app de Zoom, crea una nueva conversacion, y copia y pega el ID de conversacion o enlace Zoom aqui, y haz click en Guardar.',
    'zoom_launch_failover' => "Si haciendo click en el boton no funciona, por favor cpoia y pega este enlace en una ventana o tab nuevo:",
    'round_ending_help' => 'Esta ronda termina pronto. Por favor, decide entre todos los pensamientos que querais grabar al final de esta ronda, y coordina entre todos mandarlos al sistema.',
    'round_ended_help' => 'Esta ronda ha terminado. Cuando quieras, haz click en el boton al fondo para ir al siguiente',
    'event_end_help' => 'Este evento ha terminado. Muchas gracias por participar en DiEM Cafe!'
  ]
];

  public $lang;

  public function __construct() {
    // Try to get language from cookie
    $lang = $_COOKIE['lang'];
    $lang = $lang ? $lang : $_GET['lang'];
    $lang = $lang ? $lang : 'en';
    $this->lang = $lang;
  }

  public function set_lang($lang) {
    $this->lang = $lang;
    // Expires 2038-01-01 00:00:00
    setcookie('lang', $lang, mktime (0, 0, 0, 1, 1, 2038), '/');
  }

  public function t($tag) {
    // Return localised text for tag, or English if not available
    $txt = $this->texts[$this->lang][$tag];

    return $txt ? $txt : $this->texts['en'][$tag];
  }

  public function speech_lang($dbh) {
    // Determine speech language string based on currently configured language
    $sth = $dbh->prepare('select SpeechLanguage from Language where LanguageCode = ?');
    $sth->execute([$this->lang]);
    if($row = $sth->fetch()) {
      if($row['SpeechLanguage'])
        return $row['SpeechLanguage'];
    }

    // Default if no language
    return 'UK English Female';
  }

}
?>
