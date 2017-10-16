<?php
//This code creates a set of tables in each one of them the same host will
//receive the visit of different users in each round. The users are separated
//according to their language preferences. A graphical illustration of how the
//users are sorted before they are distributed in the tables can be found in
//"Algorithm_grahpic_representation.jpg" The original drwing in
//"Algorithm_grahpic_representation.odp"
		class Table{
			var $table_id;
			var $chairs;


			function __construct($table,$host_id)
			{
				$this->table_id = $table;
				$this->chairs = array();
				$this->addChairs($host_id);
			}

			function getTable()
			{
				return $this->table_id;
			}

			function getChairs()
			{
				return $this->chairs;
			}

			function addChairs($client_id)
			{
				array_push($this->chairs,$client_id);
			}
		}
		class Client{
			var $client_id;
			var $language;
			var $diem_group;
			var $nationality;
			var $host;

			function __construct($client,$languages,$diem,$nation,$host)
			{
				$this->client_id = $client;
				$this->diem_group = $diem;
				$this->nationality = $nation;
				$this->host =  $host;
				$this->language = array();
				foreach ($languages as $lan)
				{
					array_push($this->language,$lan);
				}
			}

			function getClient()
			{
				return $this->client_id;
			}

			function getDiem()
			{
				return $this->diem_group;
			}

			function getNation()
			{
				return $this->nationality;
			}

			function getLanguage()
			{
				return $this->language;
			}
			function getHost()
			{
				return $this->host;
			}

			function delete_language($item)
			{
				unset($this->language[$item]);
			}
		}
		class Cafe{
			var $session_id;
			var $tables;
			var $clients;
			var $rounds;
			var $hosts_list;
			var $monolingual;
			var $bilingual;
			var $trilingual;
			var $bil_used;
			var $tril_used;
			var $lang_used;
			var $rounds_total;

			function __construct($session,$rounds_no)
			{
				$this->session_id = $session;
				$this->tables = array();
				$this->clients = array();
				$this->rounds = array();
				$this->hosts_list = array();
				$this->monolingual = array();
				$this->bilingual = array();
				$this->trilingual = array();
				$this->bil_used = array();
				$this->tril_used = array();
				$this->lang_used = array();
				$this->rounds_total = $rounds_no;
			}

			function getSession()
			{
				return $this->session_id;
			}

			function getTables()
			{
				return $this->tables;
			}

			function getClients()
			{
				return $this->clients;
			}

			function ClientIsHost($client_name)
			{
				foreach($this->clients as $ref => $client)
				{
					if ($client->getClient() == $client_name)
						return $client->getHost();
				}
			}

			function getClienById($client_name)
			{
				foreach($this->clients as $ref => $client)
				{
					if ($client->getClient() == $client_name)
						return $client;
				}
			}

			function getRounds()
			{
				return $this->rounds;
			}

			function getHosts()
			{
				return $this->hosts_list;
			}

			function getMono()
			{
				return $this->monolingual;
			}

			function getBil()
			{
				return $this->bilingual;
			}

			function getTril()
			{
				return $this->trilingual;
			}

			function get_lang_used()
			{
				return $this->lang_used;
			}

			function addTable($table_id,$host_id,$key)
			{
				if (!array_key_exists($key,$this->tables))
					$this->tables[$key] = array();
				array_push($this->tables[$key], new Table($table_id,$host_id));
			}

			function addClient($client_id,$lan,$diem,$nat,$host)
			{
					array_push($this->clients, new Client($client_id,$lan,$diem,$nat,$host));
			}

			function addRound()
			{
					array_push($this->rounds, $this->getTables());
					$this->deleteTables();
			}

			function addHost($key,$host_array)
			{
				if (array_key_exists($key,$this->hosts_list))
					array_push($this->hosts_list[$key],$host_array);
				else
					$this->hosts_list[$key] = $host_array;
			}

			function setMono($mono)
			{
					$this->monolingual = $mono;
			}

			function setBil($bil)
			{
					$this->bilingual = $bil;
			}

			function setTril($tril)
			{
					$this->trilingual = $tril;
			}

			function deleteTables()
			{
				$this->tables = array();
			}

			function deleteTable($key,$position)
			{
				unset($this->tables[$key][$position]);
			}
			function get_bil_used()
			{
					return $this->bil_used;
			}

			function get_tril_used()
			{
					return $this->tril_used;
			}

			function delete_client($item)
			{
				unset($this->clients[$item]);
			}
			function generate_host_list()
			{
				$monoling = array();
				$biling = array();
				$triling = array();
				$hosts = array();
				$languages = array();


				foreach ($this->getClients() as $client)
				{
					//reduce the number of languages to 3 for all clients
					//get all languages
					$lang_array = $client->getLanguage();
					if (count($lang_array)>3)
					{
						$lang_array_temp = array();
						$lang_key = array_rand($lang_array,3);
						foreach ($lang_key as $key)
							array_push($lang_array_temp,$lang_array[$key]);
						$lang_array = $lang_array_temp;
					}
					sort($lang_array);
					$key = generate_key($lang_array);

					// Whata are we doing here???
					if (count($lang_array)==1)
					{
						if(array_search($lang_array[0],$languages) === false)
							array_push($languages,$lang_array[0]);
					}
				}

				foreach ($this->getClients() as $ref_1 => $client)
				{
					$key = $client->getLanguage();
					foreach($key as $ref => $lan)
					{
					if (!in_array($lan,$languages))
						{
							$this->getClients()[$ref_1]->delete_language($ref);
						}
					}
					if (count($key) == 0)
					{
						$this->delete_client($ref_1); //REVISAR ???
					}
				}

				//here we clasify clients according to their language using the
				//language key. We generate the arrais monolingual, biling, triling
				// each one defined by a language key. Hosts are included.
				//"generate_key" generates a string which  concatenating user languages
				//in a unique way ESP,FRA not FRA,ESP, always in alfabetical order.
				foreach ($this->getClients() as $client)
				{
					$lang_array = $client->getLanguage();
					sort($lang_array);
					$key = generate_key($lang_array);
					if (count($lang_array) == 1)
					{
						if (array_key_exists($key,$monoling))
							array_push($monoling[$key],$client->getClient());
						else
							 $monoling[$key] = array($client->getClient());
					}
					elseif (count($lang_array) == 2)
					{
						if (array_key_exists($key,$biling))
							array_push($biling[$key],$client->getClient());
						else
							 $biling[$key] = array($client->getClient());
					}
					elseif (count($lang_array) == 3)
					{
						if (array_key_exists($key,$triling))
							array_push($triling[$key],$client->getClient());
						else
							 $triling[$key] = array($client->getClient());
					}
				}

				// here we count the number of mono, bi and trilingual users who
				// take part in a particular language, in order to calculate
				//number of tables
				// We will divide bilingual users by two and trilinguals by Threaded
				// so they are only counted once.
				$table_array = array(); //this variable contains the number of tables
																//per language calculated base on number of participants
																//it is not the final number (table_no)
																//which takes into account number of available hosts
																//it actually should be called table count_table
				$mono_total = array();
				$count_bil = array(); //number of bilingual users that speak LAN ??? WARNING never used
				$bil_total  = array(); //number of bilingual users that will take part in
															 //the conversation per LAN (divided $count_bil/ 2)
															 //WARNING a beter name would be bil_final
				$count_tri  = array();//number of trilingual users that speak LAN ??? WARNING never used
				$tri_total = array(); //number of trilingual users that will take part in
															//the conversation per LAN ($count_tri / 3)
															//WARNING a beter name would be tril_final
				foreach ($languages as $language)
				{
					//all monolingual LAN (e.g. ESP) will take part in the LAN conversations
					$mono_total[$language] = count($monoling[$language]);
					$bil_total[$language] = 0;

					//we go through all bilingual language keys (language pairs)
					foreach ($biling as $key => $bil)
					{
						//we check if LAN is part of the language key
						if (strpos($key, $language) !== false)
						{
							//if so we count the total number of users who speak the language pair
							$count_bil[$key] = count($bil);
							//we add half the total numbers to this language group
							// we round up to make sure that all users are taken into account
							//note this may leave one less user for the second language in the
							//language pair when we distribute
							$bil_total[$language] += ceil(count($bil)/2);
						}
					}

					//we go through all trilingual language keys (language trio)
					$tri_total[$language] = 0;
					foreach ($triling as $key => $tri)
					{
						//we check if LAN (e.g. ESP) is part of the language key
						if (strpos($key, $language) !== false)
						{
							//if so we count the total number of users who speak the language trio
							$count_tri[$key] = count($tri);
							//we add one third the total numbers to this language group
							// we round up to make sure that all users are taken into account
							//note this may leave one less user for the second and third
							//languages in the language trio when we distribute
							$tri_total[$language] += ceil(count($tri)/3);
						}
					}

					//Handling conditions for low user numbers:
					//If total language users for a language is between
					// 5 and 8 we have two tables.
					if((($mono_total[$language] + $bil_total[$language] + $tri_total[$language]) > 5) and (($mono_total[$language] + $bil_total[$language] + $tri_total[$language]) < 8))
						$table_array[$language] = 2;
					//if less than 5 users only one table
					elseif (($mono_total[$language] + $bil_total[$language] + $tri_total[$language]) < 5)
						$table_array[$language] = 1;
				  //else we will calculate 4 users per table. we round down because
					//it is better to have many users per table than empty tables.
					else
					$table_array[$language] = floor(($mono_total[$language] + $bil_total[$language] + $tri_total[$language])/4);

				}

				//These arrays "_ready" contain the users once we have removed the hosts
				$monolin_ready = array();
				$bilin_ready = array();
				$trilin_ready = array();
				$table_no = array(); //table number per LAN.

				//We change the order of languages to avoid systematic bias in asignation
				shuffle($languages);
				foreach ($languages as $language)
				{
					//we shuffle users to increase mixing in each round, and each session
					//this will also change hosts from one session to the other
					//(not between rounds)
					shuffle($monoling[$language]);
					$table_no[$language] = 0;
					foreach($monoling[$language] as $client)
					{
						//check if client is a host and if we need more tables
						if ($this->ClientIsHost($client) and ($table_no[$language] < $table_array[$language]))
						{
							//we generate a table ID with the sequence number and the language
							//and use this string to create a table where the host is
							//the current client
							$temp_key = $table_no[$language] . "_" . $language;
							$this->addTable($temp_key,$client,$language);
							$table_no[$language]++;
						}
						else
						{
							//else we add client to the monolingual_ready list (no hosts)
							if(array_key_exists($language,$monolin_ready))
								array_push($monolin_ready[$language],$client);
							else
							//if this is the first client and array does not exist
								$monolin_ready[$language] = array($client);
						}
					}
				}

				//repeat the above for bilinguals
				foreach($biling as $key => $biling_array)
				{
					//shuffle to allow clients to take part in all their languages
					shuffle($biling[$key]);
					foreach($biling_array as $client)
					{
						$lan1  = $this->getClienById($client)->getLanguage()[0];
						$lan2 = $this->getClienById($client)->getLanguage()[1];
						//we are going to parse thourgh the group looking for hosts
						//for tables of LAN1 or LAN2
						if ($this->ClientIsHost($client) and ($table_no[$lan1] < $table_array[$lan1]))
						{
							$temp_key = $table_no[$lan1] . "_" . $lan1;
							$this->addTable($temp_key,$client,$lan1);
							$table_no[$lan1]++;
						}
						elseif($this->ClientIsHost($client) and ($table_no[$lan2] < $table_array[$lan2]))
						{
							$temp_key = $table_no[$lan2] . "_" . $lan2;
							$this->addTable($temp_key,$client,$lan2);
							$table_no[$lan2]++;
						}
						else

						//when all tables have been created or if user is not a host
						//then it goes to the bilin_ready array
						{
							if(array_key_exists($key,$bilin_ready))
								array_push($bilin_ready[$key],$client);
							else
							//create array with first non host user
								$bilin_ready[$key] = array($client);
						}
					}
				}

				//repeat for trilingual users
				foreach($triling as $key => $triling_array)
				{
					shuffle($triling[$key]);
					foreach($triling_array as $client)
					{
						$lan1 = $this->getClienById($client)->getLanguage()[0];
						$lan2 = $this->getClienById($client)->getLanguage()[1];
						$lan3 = $this->getClienById($client)->getLanguage()[2];

						//we are going to parse thourgh the group looking for hosts
						//for tables of LAN1, LAN2 or LAN3
						if ($this->ClientIsHost($client) and ($table_no[$lan1] < $table_array[$lan1]))
						{
							$temp_key = $table_no[$lan1] . "_" . $lan1;
							$this->addTable($temp_key,$client,$lan1);
							$table_no[$lan1]++;
						}
						elseif($this->ClientIsHost($client) and ($table_no[$lan2] < $table_array[$lan2]))
						{
							$temp_key = $table_no[$lan2] . "_" . $lan2;
							$this->addTable($temp_key,$client,$lan2);
							$table_no[$lan2]++;
						}
						elseif($this->ClientIsHost($client) and ($table_no[$lan3] < $table_array[$lan3]))
						{
							$temp_key = $table_no[$lan3] . "_" . $lan3;
							$this->addTable($temp_key,$client,$lan3);
							$table_no[$lan3]++;
						}
						else
						//when all tables have been created or if user is not a host
						//then it goes to the trilin_ready array
						{
							if(array_key_exists($key,$trilin_ready))
								array_push($trilin_ready[$key],$client);
							else
								$trilin_ready[$key] = array($client);
						}
					}
				}


				$host_tables = array(); //Is this used at all???

				foreach ($this->getTables() as $key => $tables)
				{
					foreach ($tables as $key_2 => $tab)
						$host_tables[$key][$key_2] = clone $tab;
				}

				//we call the function generate tableswhich will assign users to tables
				//we pass all monolingual, bilingual and trilingual users without hosts,
				//a list of languages for which there are monolingual users and
				//the tables with their hosts
				for($i = 0; $i < $this->rounds_total; $i++)
				{
					generate_tables($this,$monolin_ready,$bilin_ready,$trilin_ready,array_keys($monolin_ready),$table_array);
					$this->addRound();
					foreach ($host_tables as $key => $tables)
					{
						foreach ($tables as $key_2 => $tab)
							$this->tables[$key][$key_2] = clone $tab;
					}
				}
			}
		}

		function generate_tables($Diem,$monolin_ready,$bilin_ready,$trilin_ready,$languages,$table_array)
		{
			$table_no = array();
			//users will contain all user mono, bi or trilingual who in this
			//particular round will be assigned to tables of language LAN ($key)
			$users = array();

			foreach($monolin_ready as $key => $mon_array)
			{
				//since we are doing this for each round, we shuffle users to start with
				shuffle($mon_array);
				$users[$key] = $mon_array;
			}

			foreach($bilin_ready as $key => $bil_array)
			{
				//since we are doing this for each round, we shuffle users to start with
				shuffle($bil_array); //WARNING ??? untested line
				$bilin_count = count($bil_array);
				$lan1  = $Diem->getClienById($bil_array[0])->getLanguage()[0];
				$lan2 = $Diem->getClienById($bil_array[0])->getLanguage()[1];
				for($i=0;$i<floor($bilin_count/2);$i++)
				{
					array_push($users[$lan1],$bil_array[$i]);
				}
				//we can have one more user in LAN2 as in LAN1
				for($i=floor($bilin_count/2);$i<$bilin_count;$i++)
				{
					array_push($users[$lan2],$bil_array[$i]);
				}
			}

			foreach($trilin_ready as $key => $tril_array)
			{
				//since we are doing this for each round, we shuffle users to start with
				shuffle($tril_array); //WARNING ??? untested line
				$trilin_count = count($tril_array);
				$lan1  = $Diem->getClienById($tril_array[0])->getLanguage()[0];
				$lan2 = $Diem->getClienById($tril_array[0])->getLanguage()[1];
				$lan3 = $Diem->getClienById($tril_array[0])->getLanguage()[2];
				for($i=0;$i<floor($trilin_count/3);$i++)
				{
					array_push($users[$lan1],$tril_array[$i]);
				}
				//for($i=floor($trilin_count/2);$i<floor(2*$trilin_count/3);$i++) BUG ???
				for($i=floor($trilin_count/3);$i<floor(2*$trilin_count/3);$i++) //Warning
													//BUG corrected
				{
					array_push($users[$lan2],$tril_array[$i]);
				}
				//LAN3 and LAN2 may have one more user than LAN 1:
				//								LAN1	LAN2	LAN3
				// count = 15 -->  5		 5		 5
				// count = 16 -->	 5		 5		 6
				// count = 17 -->	 5		 6		 6

				for($i=floor(2*$trilin_count/3);$i<$trilin_count;$i++)
				{
					array_push($users[$lan3],$tril_array[$i]);
				}
			}

			//now that we have the users er langua for this round we place them,
			//sequentially, in all tables.
			//if we have too few host tables will be overcrowded
			//if we have less than 4 users tables will be emptier
			foreach($languages as $language)
			{
				$table_no[$language] = 0;
				$user_count = count($users[$language]); //WARNING changed name from mono_count to user_count
				for ($i=0;$i<$user_count;$i++) //WARNING changed name from mono_count to user_count
				{
					if(count($users[$language])>0)
					{
						if(array_key_exists($language,$Diem->getTables()))
						{
							//we remove users (pop) from the array as we assign them to tables
							$Diem->getTables()[$language][$table_no[$language]]->addChairs(array_pop($users[$language]));
							$table_no[$language]++;

							//What is th
							if($table_no[$language] >= $table_array[$language])
							$table_no[$language] = 0;
						}
						else
						{
							break;
						}
					}
					else
						break;
				}
			}

		}

		function generate_key($lang_array)
		{
			$result = "";
				foreach ($lang_array as $key => $code)
				{
					if ($key == 0)
						$result = $code;
					else
						$result .= (',' . $code);
				}
				return $result;
		}
/*
			***Create object Cafe with parameters Session Id and rounds number***
			$Diem = new Cafe(Session_ID,rounds_no);


			***Add users to the array using Cafe::addClient(1,2,3,4,5) method***
			*** 1-> array() containing languages (each element of the array corresponds to a language)***
			*** 2-> str() city ***
			*** 3-> str() dsc ***
			*** 4-> bool() Host ***
			*** 5-> str() User_ID ***
			for(****)
			{
				$Diem->addClient(languges,city,dsc,host,user_id);
			}


			*** Invoke Cafe::generate_host_list() method (No parameters required)***
			$Diem->generate_host_list();

			***From now on all users are asigned to a table for each round***
			***Next loops show how to get every single user_id ($client_id) from each table ($table->getTable())****
			*** The first user on each table is always the host ***
			foreach($Diem->getRounds() as $round_no => $round)
			{
				echo "=================================================================== \n";
				echo "Session: " . $Diem->getSession() . " // Round: " . $round_no . "\n";
				foreach ($round as $key => $group)
				{
					foreach ($group as $table)
					{
						echo "Table ID: " . $table->getTable() . "\n";
						echo "Host ID: " . $table->getChairs()[0] . "\n";
						//print_r($table->getChairs()[0]);
						foreach ($table->getChairs() as $key => $client_id)
						{
							if ($key > 0)
								echo "Client " . $key . " ID: " . $client_id . "\n";
						}
						echo "---------------------------------------------------" . "\n";
					}
				}
			}
		}
		*/
		?>
