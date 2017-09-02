<?php

/*! \mainpage DiEM Cafe Developer Documentation
 *
 * \section general General considerations
 *
 * All objects have a unique ID, assigned by MySQL when inserting into the database.
 * Constructors load an existing record from the database.
 * Where programmatic creation of records is required, a 'create' method is implemented, which inserts a record and then calls the constructor, thus loading the record back out of the database. This is a bit suboptimal but ensures absolute consistency between the database and the object model, eg in the case that MySQL assigns default values to fields.
 *
 * \section object_model Object model
 * - Each cafe event is represented by a CafeEvent class, corresponding to the Event table in the database.
 * - An event consists of a number of sections, represented by the CafeSection class, stored in the Section table in the database.
 * - Each section is divided into a number of rounds, represented by the CafeRound class.
 * - During the event, conversations take place at tables. A set of users, the participants, is attached to a conversation. A table persists throughout the life of the event; during each round a conversation takes place at a given table.
 * - A table is represented by the CafeTable object, stored in the CafeTable database table.
 * - A conversation is represented by the CafeConversation object, stored in the Conversation table.
 * - During each conversation, users make notes by recording 'thoughts' (text snippets) in the database, represnted by the CafeThought class, stored in the Thought table in the database.
 *
 * \section event_setup Event Setup
 * Events have to be configured manually by inserting the appropriate records into the database, as in the example that follows:
 * - insert into Event set Name = 'My Event', DiscussionTime = NN, ExtraTime = N, Start = 'YYYY-MM-DD HH:MM:SS'; -- main Event record.
 * - insert into Section set EventID = <ID of previously created event>, SectionNumber = 1; -- repeat for all sections; increment SectionNumber each time.
 * - insert into Round set SectionID = <ID of previously created Section, RoundNumber = 1; -- repeat for all rounds; increment RoundNumber each time.
 * - insert into Question set SectionID = <ID of previously created Round, TranslationLanguage = 'en', Val = 'This is a question'; -- repeat for all questions in all available translation languages.
 *
 * \section table_allocateion Table Allocation
 * To allocate users to tables, visit allocate-tables.php before the event starts, and click the button.
 */
//!  Represents a user of the DiEM Cafe
/*!
  The frontend creates an instance of this class to represent the logged-in user. Any user-related operations should hang off this class.
*/

class CafeUser {
	private $dbh; /*!< Database handle for SQL queries */
	public $id; /*!< Integer: user ID (auto allocated by MySQL) */
	public $email; /*!< String: Email address = login username */
	public $name; /*!< String: Full name */
	public $country_id; /*!< String: Country ID, eg 'es' */
	public $dsc; /*!< String: DSC - unused */
	public $city; /*!< String: City - probably equivalent to DSC */
	public $notify; /*!< Bool: Whether or not to notify user of forthcoming events */
	public $host; /*!< Whether the user is willing to host a table */
	public $languages; /*!< Array of language data, indicating languages user speaks and level spoken */

	//! Creates an instance of CafeUser, loaded from an existing database record.
	/*!
		\param $dbh_in Database handle
		\param $id_in Integer: ID of user record in database
		\return CafeCountry instance
	*/
	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;
		$sth = $this->dbh->prepare("select * from User where id = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		$this->email = $row['Email'];
		$this->name = $row['Name'];
		$this->country_id = $row['CountryCode'];
		$this->dsc = $row['DSC'];
		$this->city = $row['City'];
		$this->notify = $row['Notify'];
		$this->host = $row['Host'];

		// Grab languages

		$sth = $this->dbh->prepare('select LanguageCode, LevelID from UserLanguage where UserID = ?');
		$sth->execute([$this->id]);
		$this->languages = $sth->fetchAll();
	}

	//! Returns a CafeCountry record that represents the country of residence of the user, localised to the currently-selected interface language.
     /*!
       \param $TranslationLanguage Language to use for localised country text, eg 'en'
       \return CafeCountry instance
     */
	public function country($TranslationLanguage) {
		return new CafeCountry($this->dbh, $this->country_id, $TranslationLanguage);
	}

	//! Determines the current conversation to which the user is attached, based on the current time
     /*!
       \return CafeConversation instance
     */
	public function current_conversation() {
		$current_event = CafeEvent::current($this->dbh);
		$round = $current_event->current_round();
		$sth = $this->dbh->prepare('select UserConversation.ConversationID from UserConversation inner join Conversation on Conversation.ConversationID = UserConversation.ConversationID where UserConversation.UserID = ? and Conversation.RoundID = ?');
		$sth->execute([$this->id, $round->id]);
		$row = $sth->fetch();
		if($row)
			return new CafeConversation($this->dbh, $row['ConversationID']);

		return NULL;
	}

	//! Return the previous conversation for this user. What's actually used in the UI is the previous conversation for the current *table*, ie for the *host* of the current table - who is also a user, of course - in order to get the Thoughts associated with that conversation. See table.php
	/*!
	\param $current_conversation Conversation to which the user is currently attached (used to infer current round)
	\return CafeConversation instance
	*/
	public function previous_conversation($current_conversation) {
		$round = new CafeRound($this->dbh, $current_conversation->round_id);
		$previous_round = $round->previous();
		$sth = $this->dbh->prepare('select UserConversation.ConversationID from UserConversation inner join Conversation on Conversation.ConversationID = UserConversation.ConversationID where UserConversation.UserID = ? and Conversation.RoundID = ?');
		$sth->execute([$this->id, $previous_round->id]);
		$row = $sth->fetch();
		if($row['ConversationID'])
			return new CafeConversation($this->dbh, $row['ConversationID']);

		return NULL;
	}

	//! Return the next conversation for this user. Used to generate 'next table' link in UI.
	/*!
	\param $current_conversation Conversation to which the user is currently attached (used to infer current round)
	\return CafeConversation instance
	*/
	public function next_conversation($current_conversation) {
		$round = new CafeRound($this->dbh, $current_conversation->round_id);
		$next_round = $round->next();
		if(!$next_round)
			return NULL;
		$sth = $this->dbh->prepare('select UserConversation.ConversationID from UserConversation inner join Conversation on Conversation.ConversationID = UserConversation.ConversationID where UserConversation.UserID = ? and Conversation.RoundID = ?');
		$sth->execute([$this->id, $next_round->id]);
		$row = $sth->fetch();
		if($row['ConversationID'])
			return new CafeConversation($this->dbh, $row['ConversationID']);

		return NULL;
	}

	//! Get the complete list of conversastions to which this user is attached.
	/*!
	\param $event_id ID of event for which to get conversations
	\return Array of CafeConversation instances
	*/
	public function conversations($event_id) {
		if(!$event_id) {
			$current_event = CafeEvent::current($this->dbh);
			$event_id = $current_event->id;
		}

		// Return ist of conversations to which user has been allocated
		$sth = $this->dbh->prepare('select UserConversation.ConversationID from UserConversation inner join Conversation on Conversation.ConversationID = UserConversation.ConversationID inner join Round on Round.RoundID = Conversation.RoundID inner join Section on Round.SectionID = Section.SectioNID where UserConversation.UserID = ? and Section.EventID = ? order by Section.SectionNumber, Round.RoundNumber');
		$sth->execute([$this->id, $event_id]);
		$rows = $sth->fetchAll(PDO::FETCH_NUM);

		$results = array();
		foreach($rows as $row) {
			array_push($results, new CafeConversation($this->dbh, $row[0]));
		}
		return $results;
	}

	//! Attach a user to a Conversation
	/*!
	\param $conversation CafeConversation instance to which to attach.
	*/
	public function attach_to_conversation($conversation) {
		$sth = $this->dbh->prepare('insert into UserConversation set UserID = ?, ConversationID = ?');
		$sth->execute([$this->id, $conversation->id]);
	}

	//! Get list of users speaking X languages. This is not used in the live code and does not restrict to the current event atm.
	/*!
	\param $dbh Database handle
	\param $x Number of languages user must speak
	\return Array of CafeUser instances
	*/
	public static function x_linguals($dbh, $x) {
			// Get a list of all mono/bi/tri lingual users (determined by $x)
			// TODO: ideally restrict this to users for this particular event. Currently all registered users are used.
			$sth = $dbh->prepare('select UserID from UserLanguage group by UserID having count(*) = ?');
			$sth->execute([$x]);
			$rows = $sth->fetchAll(PDO::FETCH_NUM);
			$results = array();
			foreach($rows as $row) {
				array_push($results, new CafeUser($dbh, $row[0]));
			}
			return $results;
	}

	//! Test f this user speaks the specified language
	/*!
	\param $language_id Language code, eg 'en'
	\return Bool: true if user speaks the language, false if not
	*/
	public function speaks($language_id) {
			foreach($this->languages as $language) {
				if($language['LanguageCode'] == $language_id)
					return true;
			}
			return false;
	}

	//! Return the table this user hosts. TODO: not restricted to current event, and a user may host multiple tables once we move to tables being associated with Sections not Events.
	/*!
	\return CafeTable instance or NULL if the user is not hosting a table.
	*/
	public function hosted_table() {
		$sth = $this->dbh->prepare('select TableID from CafeTable where HostUserID = ?');
		$sth->execute([$this->id]);

		if($row = $sth->fetch(PDO::FETCH_NUM))
			return new CafeTable($row[0]);

		return NULL;

	}

	//! Get list of users willing to host tables.
	/*!
	\param $dbh Database handle
	\param $logged_in_only Restrict to only those users logged in. TODO this will change once user have to explicitly opt in to a given event.
	\return Array of CafeUser instances
	*/
	public static function hosts($dbh, $logged_in_only) {
		if($logged_in_only)
			$SQL = 'select User.id from User inner join sessions on sessions.uid = User.id where sessions.expiredate > now() and User.Host = 1';
		else {
			$SQL = 'select User.id from User where Host = 1';
		}
		$sth = $dbh->prepare($SQL);
		$sth->execute();
		$rows = $sth->fetchAll(PDO::FETCH_NUM);

		$results = array();
		foreach($rows as $row) {
			array_push($results, new CafeUser($dbh, $row[0]));
		}
		return $results;

	}

	//! Count total number of users.
	/*!
	\param $dbh Database handle
	\param $logged_in_only Restrict to only those users logged in. TODO this will change once user have to explicitly opt in to a given event.
	\return Integer: count of users
	*/
	public static function count($dbh, $logged_in_only) {
		if($logged_in_only)
			$SQL = 'select count(*) from User inner join sessions on sessions.uid = User.id where sessions.expiredate > now()';
		else {
			$SQL = 'select count(*) from User';
		}
		$sth = $dbh->prepare($SQL);
		$sth->execute();
		$row = $sth->fetch(PDO::FETCH_NUM);
		return $row[0];
	}

	//! List all users on the system.
	/*!
	\param $dbh Database handle
	\param $logged_in_only Restrict to only those users logged in. TODO this will change once user have to explicitly opt in to a given event.
	\return Array of CafeUser instances
	*/
	public static function enumerate($dbh, $logged_in_only) {
			if($logged_in_only)
				$SQL = 'select User.id from User inner join sessions on sessions.uid = User.id where sessions.expiredate > now()';
			else {
				$SQL = 'select User.id from User';
			}
			$sth = $dbh->prepare($SQL);
			$sth->execute();
			$rows = $sth->fetchAll(PDO::FETCH_NUM);
			$results = array();
			foreach($rows as $row) {
				array_push($results, new CafeUser($dbh, $row[0]));
			}
			return $results;
	}

}

/*
function host_sort($a, $b) {
	// Put those speaking most languages first
	$ret = count($b->languages) <=> count($a->languages);
	if($ret)
		return $ret;

	// Else: shuffle
	return rand(-1, 1);
}
*/

//!  Represents a specific Cafe event, eg 'DiEM Cafe Passau'
/*!
  Key static methods on this class use the current time to determine if there is a currently-running event, one that previously ended, or an up-coming event.
	TODO: this assumes that only one event can take place simultaneously on the system. In theory there could be more than one.
*/

class CafeEvent {

	private $dbh; /*!< Database handle for SQL queries */

	const event_end_sql = 'date_add(Start, interval ((select count(*) from Round inner join Section on Section.SectionID = Round.SectionID where Section.EventID = Event.EventID) * DiscussionTime + ExtraTime ) minute)';

	public $id; /*!< Integer: event ID (auto allocated by MySQL) */
	public $start; /*!< Start date/time, seconds since epoch */
	public $name; /*!< Event name TODO Currently non-localised - allow for localisation of event names? */
	public $num_tables; /*!< Number of tables required for event. In final algorithm, this is dynamic. SCHEDULE FOR REMOVAL */
	public $registration_end; /*!< Currently unused. Date/time after which users may not register. SCHEDULLE FOR REMOVAL */
	public $discussion_time; /*!< Discussion time per round */
	public $extra_time; /*!<  Extra time allowed in final round */
	public $end; /*!< End date/time, seconds since epoch. Computed based on event start time, number of sections/rounds, discussion_time and extra_time */

	//! Creates an instance of CafeEvent, loaded from an existing database record.
	/*!
		\param $dbh_in Database handle
		\param $id_in Integer: ID of event record in database
		\return CafeEvent instance
	*/
	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;
		$event_end_sql = self::event_end_sql;

		$sth = $this->dbh->prepare("select *, unix_timestamp(Start) as _Start, unix_timestamp(RegistrationEnd) as _RegistrationEnd, unix_timestamp($event_end_sql) as _End from Event where EventID = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();

		$this->start = $row['_Start'];
		$this->name = $row['Name'];
		// Note: number of tables actually associated with the event must meet the constraint that it is the same as NumTables
		$this->num_tables = $row['NumTables'];
		$this->registration_end = $row['_RegistrationEnd'];
		$this->discussion_time = $row['DiscussionTime'];
		$this->extra_time = $row['ExtraTime'];
		$this->end = $row['_End'];
	}

	//! Determines the 'current' event, if there is one, based on the current time. This is either the currently-running event, the next scheduled event, or the last event, in that order.
	/*!
		\param $dbh_in Database handle
		\return CafeEvent instance or NULL
	*/
	public static function current($dbh_in) {

		// Decide on the 'current' event based on timing data in the database. This is either the currently-active event, or the next upcoming event, or
		// the previous event if there is no upcoming event, in that order.

		$event_end_sql = self::event_end_sql;

		// Is there a current event? We compute the end time based on the start time, number of rounds, and discussion time
		$sth = $dbh_in->prepare("select EventID from Event where now() >= Start and now() <= $event_end_sql");

		$sth->execute();
		$row = $sth->fetch();
		if($row)
			return new CafeEvent($dbh_in, $row['EventID']);

		// No current event - is there a future event?
		$sth = $dbh_in->prepare('select EventID from Event where Start > now() order by Start asc limit 1');
		$sth->execute();
		$row = $sth->fetch();
		if($row)
			return new CafeEvent($dbh_in, $row['EventID']);

		// No future event - get last event

		$sth = $dbh_in->prepare('select EventID from Event where Start < now() order by Start desc limit 1');
		$sth->execute();
		$row = $sth->fetch();
		if($row)
			return new CafeEvent($dbh_in, $row['EventID']);

		// No current, previous or future events
		return NULL;
	}

	//! Determine the state of this event: 'running', 'waiting' if it's a future event or 'finished' if it's the last event.
	/*!
		\return String: 'running', 'waiting' or 'finished'
	*/
	public function state() {
		$t = time();
		if($t < $this->start ) {
			return 'waiting';
		}
		if($t <= $this->end) {
			return 'running';
		}
		return 'finished';
	}

	//! Determine the current round, based on the current time. TODO doesn't take into account ExtraTime
	/*!
		\return CafeRound instance, or NULL if no current round (eg if event not running)
	*/
	public function current_round() {
			// Determine, based on the time elapsed since the start of the event, the current round we are in.
			// We assume sections occur in sequence, as described by Section.SectionNumber, rounds in sequence as described by Round.RoundNumber
			// Each Round takes a fixed time, apart from the final round which qualifiees for ExtraTime

			// If the event is not running there's no current round
			if($this->state() != 'running')
				return NULL;

			$elapsed = time() - $this->start;

			// Figure out how many rounds have elapsed so far
			$num_rounds = floor($elapsed / $this->discussion_time / 60);
			// Get a list of round IDs, ordered by Section.SectionNumber then Round.RoundNumber
			$sth = $this->dbh->prepare('select Round.RoundID from Event inner join Section on Section.EventID = Event.EventID inner join Round on Round.SectionID = Section.SectionID where Event.EventID = ? order by Section.SectionNumber, Round.RoundNumber limit ' . $num_rounds . ', 1');
			$sth->execute([$this->id]); //, $num_rounds]);
			$row = $sth->fetch();

			// Glitch - no current round. Shouldn't happen since end time of event is determined dynamically based on the round setup.
			if(!$row)
				return NULL;

			return new CafeRound($this->dbh, $row['RoundID']);
	}

	//! Count total number of rounds in event
	/*!
		\return Integer: total number of rounds
	*/
	public function num_rounds() {
		$sth = $this->dbh->prepare('select count(*) as C from Event inner join Section on Section.EventID = Event.EventID inner join Round on Round.SectionID = Section.SectionID where Event.EventID = ?');
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		return $row['C'];
	}

	//! Get the nth round in the event, base zero
	/*!
		\return CafeRound instance
	*/
	public function nth_round($n) {
		// Get a list of round IDs, ordered by Section.SectionNumber then Round.RoundNumber
		$sth = $this->dbh->prepare('select Round.RoundID from Event inner join Section on Section.EventID = Event.EventID inner join Round on Round.SectionID = Section.SectionID where Event.EventID = ? order by Section.SectionNumber, Round.RoundNumber limit ' . $n . ', 1');
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		if(!$row)
			return NULL;

		return new CafeRound($this->dbh, $row['RoundID']);
	}



	//! Get list of all sections in the event
	/*!
		\return Array of CafeSection instances
	*/
	public function sections() {

		$sth = $this->dbh->prepare('select SectionID from Section where EventID = ?');
		$sth->execute([$this->id]);
		$rows = $sth->fetchAll(PDO::FETCH_NUM);

		$results = array();
		foreach($rows as $row) {
			array_push($results, new CafeSection($this->dbh, $row[0]));
		}
		return $results;
	}

	//! Delete tables, conversations, user to conversation relationships for this event - used to reset state before running table allocation algorithm.
	public function reset() {
		// Clear tables and conversations

		// Sanity check: don't clear a running event
		if($this->state() == 'running')
			return;

			$sth = $this->dbh->prepare('delete from CafeTable where EventID = ?');
			$sth->execute([$this->id]);

			$sections = $this->sections();
			foreach ($sections as $section) {
				$rounds = $section->rounds();
				foreach($rounds as $round) {
					$sth = $this->dbh->prepare('delete Conversation, UserConversation from Conversation inner join UserConversation on UserConversation.ConversationID = Conversation.ConversationID where Conversation.RoundID = ?');
					$sth->execute([$round->id]);
				}
			}
	}

	//! Allocate users to conversations at random, not taking into account language preferences. Used in first two test runs of the system. Filters for currently logged-in users.
	/*!
		\param $max_users_per_table Maximum number of users on each table. Once a table is full, a new one is created.
		\return Empty array (intended to contain users who could not be allocated to tables, but that never happens with this algorithm)
	*/
	public function allocate_conversations_RANDOM($max_users_per_table) {

		// Delete any existing table/conversation setup
		$this->reset();

		$users = CafeUser::enumerate($this->dbh, true); // Use logged-in users only

		// Number of tables = number of users / max users per table, rounded up
		$num_tables = ceil(CafeUser::count($this->dbh, true) / $max_users_per_table);

		// List of users that volunteered to be hosts
		$hosts = CafeUser::hosts($this->dbh, true); // Only those currently logged in

		// NB if there are insufficient hosts, we will silently violate max_users_per_table.
		shuffle($hosts);

		// Pick the first N hosts
		$table_hosts = array_splice($hosts, 0, $num_tables);
		$tables = array();

		$table_hosts_by_id = array();

		foreach ($table_hosts as $host) {
			$table = CafeTable::create($this->dbh, $this->id, $host->id, $host->languages[0]['LanguageCode']); // TODO we just pick the host's first language here
			array_push($tables, $table);
			$table_hosts_by_id[$host->id] = true;
		}

		$sections = $this->sections();
		$unallocable_users = array();

		foreach ($sections as $section) {
			echo '<p>Section ' . $section->section_number . '</p>';
			$rounds = $section->rounds();
			foreach ($rounds as $round) {
				$conversations = array();
				// Create a conversation for each table
				foreach ($tables as $table) {
					$conversation = CafeConversation::create($this->dbh, $round->id, $table->id);
					array_push($conversations, $conversation);
					// The host needs themselves to be attached to the conversation,as a participant
					$table->host()->attach_to_conversation($conversation);
				}

				// Now allocate users to conversations. Skip users who have been chosen as hosts.
				$these_users = $users;
				shuffle($these_users);
				$i = 0;
				foreach($these_users as $user) {

					if($table_hosts_by_id[$user->id])
						continue;
					$user->attach_to_conversation($conversations[$i++]);

					// We cycle around the conversations. Since the users have already been shuffled, this should result in a random but even distribution
					if($i >= count($conversations))
					$i = 0;
				}
			}
	}
	return array();
}

//! Allocate users to conversations, taking into account language preferences. Draft (non-working) version of algorithm by Adam, not following Pedrojuan's model.
/*!
	\param $max_users_per_table Maximum number of users on each table. Once a table is full, a new one is created.
	\return Empty array (intended to contain users who could not be allocated to tables, but that never happens with this algorithm)
*/
	public function allocate_conversations_adam($max_users_per_table) {
			// Main algorithm: attempt to allocate users to conversations (tables), taking into account their language preferences.
			$sections = $this->sections();
			// List of users that volunteered to be hosts
			$hosts = CafeUser::hosts($this->dbh);
			var_dump($hosts);
			$unallocable_users = array();

			foreach ($sections as $section) {
				echo '<p>Section ' . $section->section_number . '</p>';
				$rounds = $section->rounds();
				foreach ($rounds as $round) {
					echo '<p>Round ' . $round->round_number . '</p>';
					$working_conversations = array();
					// Start with monolingual users and work up to bilingual, trilingual
					for($x_lingual = 1; $x_lingual <=3 ; $x_lingual++) {
						echo '<p>Processing users speaking only ' . $x_lingual . ' languages.</p>';
							$users = CafeUser::x_linguals($this->dbh, $x_lingual);

							foreach($users as $user) {
								echo '<p>Processing user ' . htmlentities($user->name) . '&lt;' . htmlentities($user->email) . '&gt;</p>';
								// Check if there's an existing conversation we can allocate the user to. Randomise the list each time.
								$allocated_user = false;
								shuffle($working_conversations);
								foreach($working_conversations as $conversation) {
									if($user->speaks($conversation->table()->language_id)) {
										echo '<p>User speaks language for existing conversation...';
										if($conversation->full) {
											echo '<p>But conversation is full.</p>';
											continue;
										}
										echo '<p>Attaching.</p>';
										// User speaks language - allocate to conversation
										$user->attach_to_conversation($conversation);
										if(count($conversation->users()) >= $max_users_per_table) {
											echo '<p>Conversation is now full.</p>';
											$conversation->full = true;
										}
										$allocated_user = true;
										break;
									}
								}

								if(!$allocated_user) {
									// Failed to allocate a user. Try to create a new conversation
									// Check for existing tables first.
									$tables = $this->tables;
									shuffle($tables);
									echo '<p>Looking for table for which to create new conversation for user.</p>';
									foreach($tables as $table) {
										if($user->speaks($table->language_id)) {
											// User can be allocated to this table. If there's no existing conversation, create one.
											// If there is, the user should already have been allocated to this table, so keep going.
											if($table->conversation_for_round($round->id))
												continue;
											echo '<p>Creating new conversation for user, with existing table.</p>';
											$conversation = CafeConversation::create($this->dbh, $table->id, $round->id);
											array_push($working_conversations, $conversation);
											$user->attach_to_conversation($conversation);
											$allocated_user = true;
											break;
										}
									}
								}

								if(!$allocated_user) {
									// No existing table. Try to create a new table with a host that speaks the language of the user
									// Prefer multilingual hosts.
									$sorted_hosts = $hosts;
									usort($hosts, 'host_sort');
									echo '<p>No existing table found for user. Looking for suitable host.</p>';
									foreach ($sorted_hosts as $host) {
										if(!$host->hosted_table()) {
											// Does this host speak a language the user speaks?
											echo '<p>Checking user against host ' . htmlentities($host->name) . '&lt;' . htmlentities($host->email) . '&gt;</p>';
											foreach($host->languages as $language) {
												echo '<p>Checking langauge "' . $language['LanguageCode'] . '"</p>';
												if($user->speaks($language['LanguageCode'])) {
													// Create a new table, for this language, with this host, and attach the user.
													echo '<p>Creating new table with host ' . htmlentities($host->name) . '&lt;' . htmlentities($host->email) . '&gt;</p>';
													$table = CafeTable::create($this->dbh, $this->id, $host->id, $language->id);
													// Create a corresponding conversation
													$conversation = CafeConversation::create($this->dbh, $round->id, $table->id);
													array_push($working_conversations, $conversation);
													$user->attach_to_conversation($conversation);
													$allocated_user = true;
													break;
												}
											}
											if($allocated_user)
												break;
										}
									}
								}
								if(!$allocated_user) {
									// Can't allocate user to a table.
									echo '<p>Unable to allocate user to a table.</p>';
									array_push($unallocable_users, [$section, $round, $user]);
								}
							} // next $user
						} // next $x_lingual
					} // next $round
				} // next $section
				return $unallocated_users;
			}

			//! Allocate users to conversations, taking into account language preferences. Adapter method around Miguel's code in Cafe.php
			/*!
				\param $max_users_per_table Maximum number of users on each table - UNUSED.
			*/
			// ARN: $max_users_per_table is not used here as this is hardcoded in Miguel's code.
			public function allocate_conversations($max_users_per_table) {
				$this->reset();
				/***Create object Cafe with parameters Session Id and rounds number***/
				/* ARN: session ID appears not to be used. Passing Event ID here. */
				$Diem = new Cafe($this->id, $this->num_rounds());


				/***Add users to the array using Cafe::addClient(1,2,3,4,5) method***
				*** 1-> array() containing languages (each element of the array corresponds to a language)***
				*** 2-> str() city ***
				*** 3-> str() dsc ***
				*** 4-> bool() Host ***
				*** 5-> str() User_ID ***/
				$users = CafeUser::enumerate($this->dbh, true); // Use logged-in users only

				foreach($users as $user)
				{
					$languages = array();
					foreach($user->languages as $lang) {
						array_push($languages, $lang['LanguageCode']);
					}
					$Diem->addClient($user->id, $languages, $user->city, $user->dsc, $user->host);
				}

				/*** Invoke Cafe::generate_host_list() method (No parameters required)***/
				$Diem->generate_host_list();

				$miguel_tables_to_adam_tables = array();

				/***From now on all users are asigned to a table for each round***
				***Next loops show how to get every single user_id ($client_id) from each table ($table->getTable())****
				*** The first user on each table is always the host ***/
				foreach($Diem->getRounds() as $round_no => $round)
				{
					echo "===================================================================<br/>";
					echo "Session: " . $Diem->getSession() . " // Round: " . $round_no . "<br/>";
					foreach ($round as $key => $group)
					{
						$adam_round = $this->nth_round($round_no);

						foreach ($group as $table)
						{
							$table_id = $table->getTable();
							echo "Table ID: " . $table->getTable() . "<br/>";
							$host = $table->getChairs()[0];
							// Extract language from table ID (from Miguel's code)
							$lang = substr($table->getTable(), -2, 2);
							if($miguel_tables_to_adam_tables[$table_id]) {
								$adam_table = $miguel_tables_to_adam_tables[$table_id];
							} else {
								$adam_table = CafeTable::create($this->dbh, $this->id, $host, $lang);
								$miguel_tables_to_adam_tables[$table_id] = $adam_table;
							}
							echo "Host ID: " . $table->getChairs()[0] . "<br/>";

							// Create a corresponding conversation
							$conversation = CafeConversation::create($this->dbh, $adam_round->id, $adam_table->id);
							//print_r($table->getChairs()[0]);
							foreach ($table->getChairs() as $key => $client_id)
							{
								// Add user to conversation - including the table host.
								$user = new CafeUser($this->dbh, $client_id);
								$user->attach_to_conversation($conversation);
								if ($key > 0)
									echo "Client " . $key . " ID: " . $client_id . "<br/>";
							}
							echo "---------------------------------------------------" . "<br/>";
						}
					}
				}
			}
}

//!  Represents a section of the Cafe (a Cafe Event is divided into Sections, each with one or more Rounds)
/*!
*/

class CafeSection {

	private $dbh;/*!< Database handle for SQL queries */
	public $id; /*!< Integer: section ID (auto allocated by MySQL) */
	public $section_number; /*!< Numerical index of section, eg 1, 2, 3. Intended to form part of a contiguous sequence, but the code doesn't assume that. */
	public $name; /*!< Section name. UNUSED - the Question for the given section is displayed instead. SCHEDULE FOR REMOVAL */
	public $event_id; /*!< ID of Event with which this Section is associated */
	public $question; /* Question associated with this section. Was a static string, now internationalisable. UNUSED, SCHEDULE FOR REMOVAL */

	//! Creates an instance of CafeSection, loaded from an existing database record.
	/*!
		\param $dbh_in Database handle
		\param $id_in Integer: ID of section record in database
		\return CafeSection instance
	*/
	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;

		$sth = $this->dbh->prepare("select * from Section where SectionID = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		$this->section_number = $row['SectionNumber'];
		$this->name = $row['Name'];
		$this->event_id = $row['EventID'];
	}

	//! Get the text of the Question associated with this section, in a specified localised language, or English if not available
	/*!
		\param $lang Language code of language in which to fetch question, eg 'en'
		\return String: question text for this section
	*/
	public function question($lang) {
			// Return the question text for this section, in the appropriate language. If not available, default to English.
			$sth = $this->dbh->prepare("select Val from Question where SectionID = ? and TranslationLanguage = ?");
			$sth->execute([$this->id, $lang]);
			if($row = $sth->fetch())
				return $row['Val'];

			// No record found - default to English
			$sth->execute([$this->id, 'en']);
			if($row = $sth->fetch())
				return $row['Val'];

			return NULL;
	}

	//! Get the rounds associated with this section
	/*!
		\return Array of CafeRound instances
	*/
	public function rounds() {
		$sth = $this->dbh->prepare('select RoundID from Round where SectionID = ?');
		$sth->execute([$this->id]);
		$rows = $sth->fetchAll(PDO::FETCH_NUM);
		$results = array();
		foreach($rows as $row) {
			array_push($results, new CafeRound($this->dbh, $row[0]));
		}
		return $results;
	}

	//! Get the previous section, by section number, in this event
	/*!
		\return CafeSection instance or NULL
	*/
	public function previous() {
		// Identify and return the previous section.
		$sth = $this->dbh->prepare('select SectionID from Section where EventID = ? and SectionNumber < ? order by SectionNumber desc limit 1');
		$sth->execute([$this->event_id, $this->section_number]);
		if($row = $sth->fetch()) {
			return new CafeSection($this->dbh, $row['SectionID']);
		}
		return NULL;

	}

	//! Get the next section, by section number, in this event
	/*!
		\return CafeSection instance or NULL
	*/
	public function next() {
		// Identify and return the previous section.
		$sth = $this->dbh->prepare('select SectionID from Section where EventID = ? and SectionNumber > ? order by SectionNumber limit 1');
		$sth->execute([$this->event_id, $this->section_number]);
		if($row = $sth->fetch()) {
			return new CafeSection($this->dbh, $row['SectionID']);
		}
		return NULL;

	}

}

//!  Represents a round within a given section of a given event.
/*!
*/

class CafeRound {
	private $dbh;/*!< Database handle for SQL queries */
	public $id; /*!< Integer: user ID (auto allocated by MySQL) */
	public $round_number; /*!< Numerical index of round, eg 1, 2, 3. Intended to form part of a contiguous sequence, but the code doesn't assume that. */
	public $section_id; /*!< ID of section this round forms part of */

	//! Creates an instance of CafeRound, loaded from an existing database record.
	/*!
		\param $dbh_in Database handle
		\param $id_in Integer: ID of user record in database
		\return CafeRound instance
	*/
	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;

		$sth = $this->dbh->prepare("select * from Round where RoundID = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		$this->round_number = $row['RoundNumber'];
		$this->section_id = $row['SectionID'];
	}

	//! Get the section this round forms part of.
	/*!
		\return CafeSection instance
	*/
	public function section() {
		return new CafeSection($this->dbh, $this->section_id);
	}

	//! Determine where in the sequence of rounds for the event the current round is located. Used to determine how many rounds have elapsed, and therefore, eg, start andend time of round.
	/*!
		\param $event_id ID of event to use as reference
		\return Integer: nth round, base zero.
	*/
	public function sequence($event_id) {
		// Get a list of round IDs, ordered by Section.SectionNumber then Round.RoundNumber
		$sth = $this->dbh->prepare('select Round.RoundID from Event inner join Section on Section.EventID = Event.EventID inner join Round on Round.SectionID = Section.SectionID where Event.EventID = ? order by Section.SectionNumber, Round.RoundNumber');
		$sth->execute([$event_id]);
		$rows = $sth->fetchAll();

		for($i = 0; $i < count($rows) && $rows[$i][0] != $this->id; $i++) {}
		return $i;
	}

	//! Compute round start time, based on position of round in sequence and round length.
	/*!
		\return Integer: round start time, seconds since epoch.
	*/
	public function start() {
			// Figure out this round's start time. based on the event start time and the position of this round in the sequence
				$section = new CafeSection($this->dbh, $this->section_id);
				$event = new CafeEvent($this->dbh, $section->event_id);

				return $event->start + $this->sequence($event->id) * $event->discussion_time * 60;
	}

	//! Determine if this round is the last one for the given event.
	/*!
		\param $event_id ID of event to use as reference
		\return Bool: true if this is the last round in the event
	*/
	public function last($event_id) {
		$sth = $this->dbh->prepare('select Round.RoundID from Event inner join Section on Section.EventID = Event.EventID inner join Round on Round.SectionID = Section.SectionID where Event.EventID = ? order by Section.SectionNumber desc, Round.RoundNumber desc limit 1');
		$sth->execute([$event_id]);
		$row = $sth->fetch(PDO::FETCH_NUM);
		return ($this->id == $row[0]);

	}

	//! Compute round end time, based on position of round in sequence and round length.
	/*!
		\return Integer: round end time, seconds since epoch.
	*/
	public function end() {
		$section = new CafeSection($this->dbh, $this->section_id);
		$event = new CafeEvent($this->dbh, $section->event_id);
		return $this->start() + ($event->discussion_time + ($this->last($event->id) ? $event->extra_time : 0)) * 60;
	}

	//! Get previous round in event.
	/*!
		\return CafeRound instance or NULL
	*/
	public function previous() {
		// Identify and return the previous round.
		$sth = $this->dbh->prepare('select Round.RoundID from Round where SectionID = ? and RoundNumber < ?');
		$sth->execute([$this->section_id, $this->round_number]);
		if($row = $sth->fetch()) {
			return new CafeRound($this->dbh, $row['RoundID']);
		}
		// Nothing - try highest round number in previous section
		$section = new CafeSection($this->dbh, $this->section_id);
		$previous_section = $section->previous();
		$sth = $this->dbh->prepare('select Round.RoundID from Round where SectionID = ? order by RoundNumber desc limit 1');
		$sth->execute([$previous_section->id]);
		if($row = $sth->fetch()) {
			return new CafeRound($this->dbh, $row['RoundID']);
		}
		return NULL;
	}

	//! Get next round in event.
	/*!
		\return CafeRound instance or NULL
	*/
	public function next() {
		// Identify and return the previous round.
		$sth = $this->dbh->prepare('select Round.RoundID from Round where SectionID = ? and RoundNumber > ?');
		$sth->execute([$this->section_id, $this->round_number]);
		if($row = $sth->fetch()) {
			return new CafeRound($this->dbh, $row['RoundID']);
		}
		// Nothing - try lowest round number in next section
		$section = new CafeSection($this->dbh, $this->section_id);
		$next_section = $section->next();
		if(!$next_section)
			return NULL;

		$sth = $this->dbh->prepare('select Round.RoundID from Round where SectionID = ? order by RoundNumber asc limit 1');
		$sth->execute([$next_section->id]);
		if($row = $sth->fetch()) {
			return new CafeRound($this->dbh, $row['RoundID']);
		}
		return NULL;
	}

}

//!  Represents a table at the Cafe. A Table is associated with an Event (for now), and Conversations between various Users take place at these Tables. TODO associate tables with sections
/*!
*/

class CafeTable {
	private $dbh; /*!< Database handle for SQL queries */
	public $id; /*!< Integer: table ID (auto allocated by MySQL) */
	public $event_id; /*!< Integer: event ID with which this table is associated */
	public $host_user_id; /*!< Integer: user ID of host of this table (the person who creates the Zoom converation) */
	public $language_id; /* String: Language code of language spoken on this table, eg 'en' */

	//! Creates an instance of CafeTable, loaded from an existing database record.
	/*!
		\param $dbh_in Database handle
		\param $id_in Integer: ID of table record in database
		\return CafeTable instance
	*/
	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;

		$sth = $this->dbh->prepare("select * from CafeTable where TableID = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		$this->event_id = $row['EventID'];
		$this->host_user_id = $row['HostUserID'];
		$this->language_id = $row['LanguageCode'];
	}

	//! Creates an instance of CafeTable and store in database. Note that code actually calls the constructor, which reloads the just-created record from the database, which is a bit suboptimal but ensures consistency with the database, eg if MySQL assigns default values to fields.
	/*!
		\param $dbh_in Database handle
		\param $event_id Integer: ID of event to which to attach table
		\param $host_user_id Integer: ID of user hosting table
		\param $language_id String: language code of language spoken on table
		\return CafeTable instance
	*/
	public static function create (\PDO $dbh_in, $event_id, $host_user_id, $language_id) {
		$sth = $dbh_in->prepare('insert into CafeTable set EventID = ?, HostUserID = ?, LanguageCode = ?');
		$sth->execute([$event_id, $host_user_id, $language_id]);
		$id = $dbh_in->lastInsertId();
		return new CafeTable($dbh_in, $id);
	}

	//! Get details of language spoken at table, rendered in specified language
	/*!
		\param $TranslationLanguage String: language code of language spoken on table
		\return CafeLanguage instance
	*/
	public function language($TranslationLanguage) {
		return new CafeLanguage($this->dbh, $this->language_id, $TranslationLanguage);
	}

	//! Return user object representing host of this table.
	/*!
		\return CafeUser instance
	*/
	public function host() {
		return new CafeUser($this->dbh, $this->host_user_id);
	}

	//! Get Conversation associated with this Table for a particular Round, if any
	/*!
	\param $round_id Integer: ID of round for which to get convesation.
	\return CafeConversation instance or NULL
	*/
	public function conversation_for_round($round_id) {
			$sth = $this->dbh('select ConversationID from Conversation where TableID = ? and RoundID = ?');
			$sth-execute([$this->id, $round_id]);
			if($row = $sth->fetch(PDO::FETCH_NUM)) {
				return new CafeConversation($this->dbh, $row[0]);
			}
			return NULL;
	}

}

//!  Represents a conversation.
/*!
Conversations table place at a Table, during a particular Round, part of a Section of an Event. An actual Zoom teleconference conversation is represented by this class, which stores the Zoom URL amongst other properties.
*/
class CafeConversation {
	private $dbh; /*!< Database handle for SQL queries */
	public $id; /*!< Integer: user ID (auto allocated by MySQL) */
	public $round_id; /*!< Integer: ID of round with which this conversation is associated */
	public $table_id; /*!< Integer: Id of table with which this conversation is associated */
	public $zoom_link; /*!< String: URL of Zoom conversation this object represents */

	public $full; /*!< Bool: temporary property when allocating conversations - indicates if conversation is 'full', ie max participants reached */

	//! Creates an instance of CafeConversation, loaded from an existing database record.
	/*!
		\param $dbh_in Database handle
		\param $id_in Integer: ID of user record in database
		\return CafeConversation instance
	*/
	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;

		$sth = $this->dbh->prepare("select * from Conversation where ConversationID = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		$this->round_id = $row['RoundID'];
		$this->table_id = $row['TableID'];
		$this->zoom_link = $row['ZoomLink'];
	}

	//! Creates an instance of CafeConversation and store in database. Note that code actually calls the constructor, which reloads the just-created record from the database, which is a bit suboptimal but ensures consistency with the database, eg if MySQL assigns default values to fields.
	/*!
		\param $dbh_in Database handle
		\param $round_id Integer: ID of round to which this conversation corresponds
		\param $table_id Integer: ID of table to which this conversation corresponds
		\return CafeConversation instance
	*/
	public static function create(\PDO $dbh_in, $round_id, $table_id) {
			$sth = $dbh_in->prepare('insert into Conversation set RoundID = ?, TableID = ?');
			$sth->execute([$round_id, $table_id]);
			$id = $dbh_in->lastInsertId();
			return new CafeConversation($dbh_in, $id);
	}

	//! Get the round with which this conversation is associated.
	/*!
		\return CafeRound instance
	*/
	public function round() {
		return new CafeRound($this->dbh, $this->round_id);
	}

	//! Get the table with which this conversation is associated.
	/*!
		\return CafeTable instance
	*/
	public function table() {
		return new CafeTable($this->dbh, $this->table_id);
	}

	//! List all the users in the conversation (including the host who is also attached to the table).
	/*!
		\return Array of CafeUser instances
	*/
	public function users() {
		$sth = $this->dbh->prepare('select UserID from UserConversation where ConversationID = ?');
		$sth->execute([$this->id]);
		$rows = $sth->fetchAll(PDO::FETCH_NUM);

		$results = array();
		foreach($rows as $row) {
			array_push($results, new CafeUser($this->dbh, $row[0]));
		}
		return $results;

	}

	//! Get the thoughts (user-submitted text snippets) associated with this conversation.
	/*!
		\return Array of CafeThought instances
	*/
	public function thoughts() {
		$sth = $this->dbh->prepare('select ThoughtID from Thought where ConversationID = ? order by Stamp');
		$sth->execute([$this->id]);
		$rows = $sth->fetchAll(PDO::FETCH_NUM);

		$results = array();
		foreach($rows as $row) {
			array_push($results, new CafeThought($this->dbh, $row[0]));
		}
		return $results;

	}

}

//!  Represents a 'thought' (text snippet) submitted to the system as part of a conversation by a user.
/*!
*/

class CafeThought {
	private $dbh; /*!< Database handle for SQL queries */
	public $id; /*!< Integer: user ID (auto allocated by MySQL) */
	public $text; /*!< Text of thought */
	public $conversation_id; /*!< ID of conversation to which this thought corresponds */

	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;

		$sth = $this->dbh->prepare("select * from Thought where ThoughtID = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		$this->text = $row['Val'];
		$this->conversation_id = $row['ConversationID'];
	}

}

//!  Represents a country (which a user specifies during registration), rendered in a specified language
/*!
*/

class CafeCountry {
	private $dbh; /*!< Database handle for SQL queries */
	public $code; /*!< Country code, eg 'es' */
	public $name; /*!< Country name, localised */

	//! Creates an instance of CafeCountry, loaded from an existing database record.
	/*!
		\param $dbh_in Database handle
		\param $code_in String: country code, eg 'es'
		\param $translation_language String: language in which to represent the country name, eg 'en'
		\return CafeCountry instance
	*/
	public function __construct(\PDO $dbh_in, $code_in, $translation_language) {
		$this->dbh = $dbh_in;
		$this->code = $code_in;

		$sth = $this->dbh->prepare("select * from Country where CountryCode = ? and TranslationLanguage = ?");
		$sth->execute([$this->code, $translation_language]);
		$row = $sth->fetch();
		$this->name = $row['Val'];
	}

	//! List all countries, localised to a particular language
	/*!
		\param $dbh_in Database handle
		\param $translation_language String: language in which to represent the country names, eg 'en'
		\return Array of CafeCountry instances
	*/
	public static function enum(\PDO $dbh_in, $translation_language) {
		$sth = $dbh_in->prepare("select * from Country where TranslationLanguage = ?");
		$sth->execute([$translation_language]);
		$row = $sth->fetchAll();
		$results = array();
		foreach($rows as $row) {
			array_push($results, new CafeCountry($this->dbh, $row[0]));
		}
		return $results;

	}

}

//!  Represents a language (which a user specifies during registration), rendered in a specified language
/*!
*/

class CafeLanguage {
	private $dbh; /*!< Database handle for SQL queries */
	public $code; /*!< Language code, eg 'en' */
	public $name; /*!< Name of language, localised */

	//! Creates an instance of CafeLanguage, loaded from an existing database record.
	/*!
		\param $dbh_in Database handle
		\param $code_in String: language code, eg 'en'
		\param $translation_language String: language in which to represent the language name, eg 'en'
		\return CafeLanguage instance
	*/
	public function __construct(\PDO $dbh_in, $code_in, $translation_language) {
		$this->dbh = $dbh_in;
		$this->code = $code_in;

		$sth = $this->dbh->prepare("select * from Language where LanguageCode = ? and TranslationLanguage = ?");
		$sth->execute([$this->code, $translation_language]);
		$row = $sth->fetch();
		$this->name = $row['Val'];
	}

	//! List all languages, names localised to a particular language
	/*!
		\param $dbh_in Database handle
		\param $translation_language String: language in which to represent the language names, eg 'en'
		\return Array of CafeLanguage instances
	*/
	public static function enum(\PDO $dbh_in, $translation_language) {
		$sth = $dbh_in->prepare("select * from Language where TranslationLanguage = ?");
		$sth->execute([$translation_language]);
		$row = $sth->fetchAll();
		$results = array();
		foreach($rows as $row) {
			array_push($results, new CafeLanguage($this->dbh, $row[0]));
		}
		return $results;

	}

}

// Miguel Gonzalez code + adapter to objects in this file.

include "table-allocation.php";


 ?>
