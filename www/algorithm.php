	<?php
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
	}

	class Cafe{
		var $session_id;
		var $tables;
		var $clients;
		var $rounds;
		var $hosts_list;
		
		function __construct($session)
		{
			$this->session_id = $session;
			$this->tables = array();
			$this->clients = array();
			$this->rounds = array();
			$this->hosts_list = array();
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
		
		function getRounds()
		{
			return $this->rounds;
		}
		
		function getHosts()
		{
			return $this->hosts_list;
		}
		
		function addTable($table_id,$host_id)
		{
			array_push($this->tables,new Table($table_id,$host_id));
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
		
		function addHost($host_id)
		{
				array_push($this->hosts_list,$host_id);
		}
		
		function deleteTables()
		{
			$this->tables = array();
		}
		
		function generate_host_list()
		{
			$host_id = array();
			foreach ($this->getClients() as $client)
				if ($client->getHost())
					array_push($host_id,$client->getClient());
			
			$members_no = count($this->getClients());
			$host_no = count($host_id);
			$tables_no = min(ceil($members_no/5),$host_no);
			
			shuffle($host_id);
			for ($i = 0; $i < $tables_no; $i++) 
				$this->addHost($host_id[$i]);
		}
	}

	function generate_tables($Diem){
		$host_id = $Diem->getHosts();
		foreach ($host_id as $key => $host)
			$Diem->addTable($key,$host);
		
		$tables_no = count($host_id);
		$members_no = count($Diem->getClients());
		
		$Diem_Clients = array(); //Not host users array generation
		foreach ($Diem->getClients() as $client) 
			if (!in_array($client->getClient(),$host_id))
				
				array_push($Diem_Clients,$client->getClient());
		
		$members_no = min($tables_no * 5,$members_no) - $tables_no;
		shuffle($Diem_Clients);
		$j = 0;
		while ($members_no>0)
		{
			foreach ($Diem->getTables() as $table)
			{
				$table->addChairs($Diem_Clients[$j]);
				unset($Diem_Clients[$j]);
				$j++;
				$members_no--;
				if ($members_no == 0)
					break;
			}
		}
	}

	function generate_random_user()
	{
		$language_array = array("EN","ES","FR","DE","IT");
		$cities_array = array("Dublin","London","Madrid","Paris","Rome","Berlin");
		$nation_array = array("UK","ES","FR","DE","IT","IR");
		
		$lang_no = rand(1,count($language_array));
		$lang_key = array_rand($language_array,$lang_no);
		$rand_lang = array();
		if ($lang_no == 1) $lang_key = array($lang_key);
		foreach ($lang_key as $key)
			array_push($rand_lang,$language_array[$key]);
		
		$rand_city = $cities_array[array_rand($cities_array)];
		$rand_nat = $nation_array[array_rand($nation_array)];
		$rand_host = rand(0,1);
		
		return array($rand_lang,$rand_city,$rand_nat,$rand_host);
	}

	function test_code($users_no)
	{
		$Diem = new Cafe(1001);
		for ($i=0;$i<$users_no;$i++)
		{
			$rand_user =  generate_random_user();
			$Diem->addClient($i,$rand_user[0],$rand_user[1],$rand_user[2],$rand_user[3]);
		}
		$Diem->generate_host_list();
		for($j=0;$j<3;$j++)
		{
			generate_tables($Diem);
			$Diem->addRound();
		}
		/*foreach($Diem->getClients() as $client)
		{
		echo "User_ID: " . $client->getClient() . "\n";
		echo "Diem Group: " . $client->getDiem() . "\n";
		echo "Nationality: " . $client->getNation() . "\n";
		echo "Languages: ";
		foreach($client->getLanguage() as $lan){
			echo $lan . ",";
		}
		echo "\n";
		}*/
		echo "Test File for " . $users_no . " random clients \n";
		foreach($Diem->getRounds() as $round_no => $round)
		{
			echo "=================================================================== \n";
			echo "Session: " . $Diem->getSession() . " // Round: " . $round_no . "\n";
			foreach ($round as $table)
			{
				echo "Table ID: " . $table->getTable() . "\n";
				echo "Host ID: " . $table->getChairs()[0] . "\n";
				foreach ($table->getChairs() as $key => $client_id)
				{
					if ($key > 0)
						echo "Client " . $key . " ID: " . $client_id . "\n";
				}
				echo "---------------------------------------------------" . "\n";
			}
		}
	}

	test_code(37);
	?>