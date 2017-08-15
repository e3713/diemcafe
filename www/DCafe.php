<?php

class CafeUser {
	private $dbh;
	public $id;
	public $email;
	public $name;
	public $country;
	public $dsc;
	public $city;
	public $notify;
	public $host;
	public $languages;

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

	public function country($TranslationLanguage) {
		return new CafeCountry($this->dbh, $this->country_id, $TranslationLanguage);
	}

	/* What conversation am I attached to? */
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

	public function attach_to_conversation($conversation) {
		$sth = $this->dbh->prepare('insert into UserConversation set UserID = ?, ConversationID = ?');
		$sth->execute([$this->id, $conversation->id]);
	}

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

	public function speaks($language_id) {
			foreach($this->languages as $language) {
				if($language['LanguageCode'] == $language_id)
					return true;
			}
			return false;
	}

	public function hosted_table() {
		$sth = $this->dbh->prepare('select TableID from CafeTable where HostUserID = ?');
		$sth->execute([$this->id]);

		if($row = $sth->fetch(PDO::FETCH_NUM))
			return new CafeTable($row[0]);

		return NULL;

	}

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


class CafeEvent {

	private $dbh;

	const event_end_sql = 'date_add(Start, interval ((select count(*) from Round inner join Section on Section.SectionID = Round.SectionID where Section.EventID = Event.EventID) * DiscussionTime + ExtraTime ) minute)';

	public $id;
	public $start;
	public $name;
	public $num_tables;
	public $registration_end;
	public $discussion_time;
	public $extra_time;
	public $end;

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

	/* Return state of event: waiting, running, finished  */
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


	/* Return list of event sections */
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

// Interim algorithm that allocates users at random
	public function allocate_conversations($max_users_per_table) {

		// Delete any existing table/conversation setup
		$this->reset();

		$users = CafeUser::enumerate($this->dbh, true); // User logged-in users only

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

// Draft algorithm by Adam that allocates users to tables ad hoc (not Pedrojuan's)
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
}

class CafeSection {

	private $dbh;

	public $id;
	public $section_number;
	public $name;
	public $event_id;
	public $question;

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

	/* Return a set of Round objects for the section */
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

	public function previous() {
		// Identify and return the previous section.
		$sth = $this->dbh->prepare('select SectionID from Section where EventID = ? and SectionNumber < ?');
		$sth->execute([$this->event_id, $this->section_number]);
		if($row = $sth->fetch()) {
			return new CafeSection($this->dbh, $row['SectionID']);
		}
		return NULL;

	}

	public function next() {
		// Identify and return the previous section.
		$sth = $this->dbh->prepare('select SectionID from Section where EventID = ? and SectionNumber > ?');
		$sth->execute([$this->event_id, $this->section_number]);
		if($row = $sth->fetch()) {
			return new CafeSection($this->dbh, $row['SectionID']);
		}
		return NULL;

	}

}

class CafeRound {
	private $dbh;

	public $id;
	public $round_number;
	public $section_id;

	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;

		$sth = $this->dbh->prepare("select * from Round where RoundID = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		$this->round_number = $row['RoundNumber'];
		$this->section_id = $row['SectionID'];
	}

	public function section() {
		return new CafeSection($this->dbh, $this->section_id);
	}

	public function sequence($event_id) {
		// Get a list of round IDs, ordered by Section.SectionNumber then Round.RoundNumber
		$sth = $this->dbh->prepare('select Round.RoundID from Event inner join Section on Section.EventID = Event.EventID inner join Round on Round.SectionID = Section.SectionID where Event.EventID = ? order by Section.SectionNumber, Round.RoundNumber');
		$sth->execute([$event_id]);
		$rows = $sth->fetchAll();

		for($i = 0; $i < count($rows) && $rows[$i][0] != $this->id; $i++) {}
		return $i;
	}

	public function start() {
			// Figure out this round's start time. based on the event start time and the position of this round in the sequence
				$section = new CafeSection($this->dbh, $this->section_id);
				$event = new CafeEvent($this->dbh, $section->event_id);

				return $event->start + $this->sequence($event->id) * $event->discussion_time * 60;
	}

	public function last($event_id) {
		$sth = $this->dbh->prepare('select Round.RoundID from Event inner join Section on Section.EventID = Event.EventID inner join Round on Round.SectionID = Section.SectionID where Event.EventID = ? order by Section.SectionNumber desc, Round.RoundNumber desc limit 1');
		$sth->execute([$event_id]);
		$row = $sth->fetch(PDO::FETCH_NUM);
		return ($this->id == $row[0]);

	}

	public function end() {
		$section = new CafeSection($this->dbh, $this->section_id);
		$event = new CafeEvent($this->dbh, $section->event_id);
		return $this->start() + ($event->discussion_time + ($this->last($event->id) ? $event->extra_time : 0)) * 60;
	}

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

class CafeTable {
	private $dbh;

	public $id;
	public $event_id;
	public $host_user_id;
	public $language_id;

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

	public static function create (\PDO $dbh_in, $event_id, $host_user_id, $language_id) {
		$sth = $dbh_in->prepare('insert into CafeTable set EventID = ?, HostUserID = ?, LanguageCode = ?');
		$sth->execute([$event_id, $host_user_id, $language_id]);
		$id = $dbh_in->lastInsertId();
		return new CafeTable($dbh_in, $id);
	}

	/* Return details of table language, code and localised language name */

	public function language($TranslationLanguage) {
		return new CafeLanguage($this->dbh, $this->language_id, $TranslationLanguage);
	}

	public function host() {
		return new CafeUser($this->dbh, $this->host_user_id);
	}

	public function conversation_for_round($round_id) {
			$sth = $this->dbh('select ConversationID from Conversation where RoundID = ?');
			$sth-execute([$round_id]);
			if($row = $sth->fetch(PDO::FETCH_NUM)) {
				return new CafeConversation($this->dbh, $row[0]);
			}
			return NULL;
	}

}

class CafeConversation {
	private $dbh;

	public $id;
	public $round_id;
	public $table_id;
	public $zoom_link;

	public $full; // Temporary property when allocating conversations - indicates if conversation is 'full', ie max participants reached
//	public $language_id;
//	public $thoughts;

	public function __construct(\PDO $dbh_in, $id_in) {
		$this->dbh = $dbh_in;
		$this->id = $id_in;

		$sth = $this->dbh->prepare("select * from Conversation where ConversationID = ?");
		$sth->execute([$this->id]);
		$row = $sth->fetch();
		$this->round_id = $row['RoundID'];
		$this->table_id = $row['TableID'];
		$this->zoom_link = $row['ZoomLink'];
//		$this->language_id = $row['LanguageCode'];
//		$this->thoughts = [$row['Thought1'], $row['Thought2'], $row['Thought3'], $row['Thought4'], $row['Thought5']];

	}

	public static function create(\PDO $dbh_in, $round_id, $table_id) {
			$sth = $dbh_in->prepare('insert into Conversation set RoundID = ?, TableID = ?');
			$sth->execute([$round_id, $table_id]);
			$id = $dbh_in->lastInsertId();
			return new CafeConversation($dbh_in, $id);
	}

	public function round() {
		return new CafeRound($this->dbh, $this->round_id);
	}

	public function table() {
		return new CafeTable($this->dbh, $this->table_id);
	}

	/* Return details of other users in the conversation */

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

class CafeThought {
	public $id;
	public $text;
	public $conversation_id;

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

class CafeCountry {
	public $code;
	public $name;

	public function __construct(\PDO $dbh_in, $code_in, $translation_language) {
		$this->dbh = $dbh_in;
		$this->code = $code_in;

		$sth = $this->dbh->prepare("select * from Country where CountryCode = ? and TranslationLanguage = ?");
		$sth->execute([$this->code, $translation_language]);
		$row = $sth->fetch();
		$this->name = $row['Val'];
	}

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

class CafeLanguage {
	public $code;
	public $name;

	public function __construct(\PDO $dbh_in, $code_in, $translation_language) {
		$this->dbh = $dbh_in;
		$this->code = $code_in;

		$sth = $this->dbh->prepare("select * from Language where LanguageCode = ? and TranslationLanguage = ?");
		$sth->execute([$this->code, $translation_language]);
		$row = $sth->fetch();
		$this->name = $row['Val'];
	}

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

 ?>
