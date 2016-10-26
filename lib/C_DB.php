<?php
class C_DB {
	private $server;
	private $database;
	private $login;
	private $pass;
	private $conn;
	
	/***** CONNECTION *****/
	
	public function __construct($server,$database,$login,$pass)
	{
		$this->server = $server;
		$this->database = $database;
		$this->login = $login;
		$this->pass = $pass;
		$this->conn = false;
		
		$ok = $this->connect();
		$this->disconnect();
		
		if(! $ok)
			return false;
	}
	
	public function connect(){
		$this->conn = mysql_connect($this->server, $this->login, $this->pass);
		if ($this->conn){
			if(mysql_select_db($this->database,$this->conn))
				@mysql_query("SET NAMES 'utf8';");
				return true;
		}
		return false;
	}
	
	public function disconnect(){
		return mysql_close($this->conn);
	}
	
	private function select($sql,$key = null){
		$result = array();
		if($this->connect()){
			$r = mysql_query($sql,$this->conn);
			while($row = mysql_fetch_array($r, MYSQL_ASSOC))
				($key != null) ? $result[$row[$key]] = $row : $result[] = $row;
			mysql_free_result($r);
		}
		$this->disconnect();
		return $result;
	}
	
	private function transaction($sql){
		$result = false;
		if($this->connect())
			$result = true;
			mysql_query("begin");
			foreach($sql as $q){
				mysql_query($q);
				if(mysql_errno() != 0){
					echo mysql_error();
					mysql_query("rollback");
					$result = false;
					break;
				}
			}
			if($result){
				mysql_query("commit");
			}
		$this->disconnect();
		return $result;
	}
	
	
	
	
	
	/***** USER *****/
	
	public function getUser($user,$pass){
		$user = htmlentities($user);
		$pass = md5($pass);
		$r = $this->select('select name,team,type from users where name = "'.$user.'" and password = "'.$pass.'"');
		return $r[0];
	}
	
	public function getUsers($team=NULL){
		$sql = 'select name,type,team,description from users ';
		if($team!=NULL)
			$sql .= 'where team IS NOT NULL AND team IN ("both",'.(($team=='both')?'"u20","abs"':'"'.$team.'"').') ';
		$sql .= 'order by type desc, description asc ';
		$r = $this->select($sql);
		return $r;
	}
	
	public function getUserSession($session){
		$r = $this->select('select name,type,team from users where session = "'.$session.'"');
		return $r[0];
	}
	public function addUserSession($user, $session){
		$sql[0] = 'update users set session = "'.$session.'" where name = "'.$user.'"';
		return $this->transaction($sql);
	}
	public function delUserSession($user){
		$sql[0] = 'update users set session = null where name = "'.$user.'"';
		return $this->transaction($sql);
	}
	
	public function addUser($u,$p,$nt,$t,$d){
		$u = htmlentities($u);
		$p = md5($p);
		$t = ($t>0) ? -$t : $t;
		$exists = $this->select('select 1 from users where name = "'.$u.'"');
		if($exists[0][1] === '1')
			return false;
		
		$sql[0] = "insert into users (name,password,team,type,description) values ('$u','$p','$nt','$t','$d')";
		
		return $this->transaction($sql);
	}
	
	public function updateUser($u){
		if(count($u)<2)
			return;
		$sql = 'update users set ';
		foreach($u as $k=>$v) {
			if($k=='name')
				continue;
			if($k=='password') {
				if(!empty($v))
					$v = md5($v);
				else
					continue;
			}
			$v = (empty($v)) ? 'NULL' : '"'.$v.'"';
			$sql .= $k.'='.$v.', ';
		}
		$sql = substr($sql,0,-2).' where name = "'.$u['name'].'"';
		return $this->transaction(array($sql));
	}
	
	public function deleteUser($u){
		$sql = 'delete from users where name="'.$u.'" ';
		return $this->transaction(array($sql));
	}
	
	
	
	
	
	/***** NEWS *****/
	
	public function getPieceOfNews($id, $lang){
		$sql = "select id, lang, team, user, publication_date, title, text from news where id=$id and lang='$lang' order by publication_date desc";
		$r = $this->select($sql);
		return $r[0];
	}
	
	public function getNews($lang=NULL, $num=NULL, $team=NULL){
		$sql = "select id, lang, team, user, publication_date, title, text from news";
		$cond = array();
		if(!empty($lang))
			$cond[] = " lang = '".strtolower($lang)."'";
		if(!empty($team))
			$cond[] = " and team = '$team'";
		if(count($cond)>0) {
			$sql .= " where ";
			foreach($cond as $c)
				$sql .= $c." and ";
			$sql = substr($sql,0,-4);
		}
		$sql .= " order by publication_date desc";
		if(!empty($num))
			$sql .= " limit $num";
		
		return $this->select($sql);
	}
	
	public function updateNews($id,$lang,$team,$user,$title,$text) {
		$lang  = strtolower($lang);
		
		$op = 'insert';
		if(!empty($id) && is_numeric($id)) {
			$exists = $this->select("select publication_date from news where id=$id");
			if(count($exists)>0) {
				$publication_date = $exists[0]['publication_date'];
				$exists = $this->select("select 1 from news where id=$id and lang='$lang'");
				$op = ($exists[0][1] === '1') ? 'edit' : 'translate';
			}
		}
		
		switch($op) {
			case 'insert':
				$r = $this->select("select max(id) as max from news");
				$id = $r[0]['max']+1;
				$sql[0] = "insert into news(id, lang, team, user, title, text) values ($id, '$lang', '$team', '$user', '$title', '$text')";
				break;
			case 'translate':
				$sql[0] = "update news set team='$team', user='$user' where id=$id"; //Update common info for all languages
				$sql[1] = "insert into news(id, lang, publication_date, team, user, title, text) values ($id, '$lang', '$publication_date', '$team', '$user', '$title', '$text')"; //Insert new language
				break;
			case 'edit':
				$sql[0] = "update news set team='$team', user='$user' where id=$id"; //Update common info for all languages
				$sql[1] = "update news set title='$title', text='$text' where id=$id and lang='$lang'"; //Update language info
				break;
		}
		return $this->transaction($sql);
	}
	
	public function delNews($num){
		$sql[0] = "delete from news where id='$num'";
		return $this->transaction($sql);
	}
	
	
	
	
	
	/***** PLAYER *****/
	
	public function addPlayer($FetchedDate,$player,$training = null,$team = null){
		if($player['PlayerID'] !== 0){
			extract($player);
			if (is_array($training))
				extract($training);
			if (is_array($team))
				extract($team);
			$sql[0] = 	"insert into players (	FetchedDate,
												PlayerID,
												PlayerName,
												Age,
												AgeDays,
												TSI,
												PlayerForm,
												Experience,
												Leadership,
												Salary,
												Agreeability,
												Aggressiveness,
												Honesty,
												Specialty,
												InjuryLevel,
												StaminaSkill,
												KeeperSkill,
												PlaymakerSkill,
												ScorerSkill,
												PassingSkill,
												WingerSkill,
												DefenderSkill,
												SetPiecesSkill,
												TrainerType,
												KeeperTrainers,
												TrainingType,
												TrainerSkill,
												AssistantTrainers,
												TrainingLevel,
												StaminaTrainingPart,
												UserName,
												TeamID,
												TeamName,
												UserID,
												potencial,
												ABS_state,
												U20_state,
												U20_player_type,
												ABS_player_type
								) values (
												'$FetchedDate',
												'$PlayerID',
												'$PlayerName',
												'$Age',
												'$AgeDays',
												'$TSI',
												'$PlayerForm',
												'$Experience',
												'$Leadership',
												'$Salary',
												'$Agreeability',
												'$Aggressiveness',
												'$Honesty',
												'$Specialty',
												'$InjuryLevel',
												'$StaminaSkill',
												'$KeeperSkill',
												'$PlaymakerSkill',
												'$ScorerSkill',
												'$PassingSkill',
												'$WingerSkill',
												'$DefenderSkill',
												'$SetPiecesSkill',
												'$TrainerType',
												'$KeeperTrainers',
												'$TrainingType',
												'$TrainerSkill',
												'$AssistantTrainers',
												'$TrainingLevel',
												'$StaminaTrainingPart',
												'$UserName',
												'$TeamID',
												'$TeamName',
												'$UserID',
												'$potencial',
												'$ABS_state',
												'$U20_state',
												'$U20_player_type',
												'$ABS_player_type');";
				return ($this->transaction($sql) ? 1 : 0);
		}
	}
	
	public function updatePlayer($FetchedDate,$player,$training = null,$team = null)
	{ 
		if($player['PlayerID'] !== 0){
			extract($player);
			if (is_array($training))
				extract($training);
			if (is_array($team))
				extract($team);
			if(count($this->select('select 1 from players where PlayerID = '.$PlayerID)) == 1) {//Ya existe
			    $sql[0] = "delete from players_history
									where 	PlayerID = $PlayerID and FetchedDate > date_sub('$TrainingDate', interval 7 day);";
				if(! isset($TrainingDate))
					$sql[0] = "select 1;";
				$sql[1] =	"insert into players_history
								select		FetchedDate,
											PlayerID,
											Age,
											AgeDays,
											TSI,
											PlayerForm,
											Experience,
											Leadership,
											Salary,
											InjuryLevel,
											StaminaSkill,
											KeeperSkill,
											PlaymakerSkill,
											ScorerSkill,
											PassingSkill,
											WingerSkill,
											DefenderSkill,
											SetPiecesSkill,
											TrainerType,
											KeeperTrainers,
											TrainingType,
											TrainerSkill,
											AssistantTrainers,
											TrainingLevel,
											StaminaTrainingPart,
											UserName,
											TeamID,
											TeamName,
											UserID
											from players
								where PlayerID = '$PlayerID' and not FetchedDate in (select FetchedDate from players where PlayerID = '$PlayerID');";
								
								$sql[2] = 	"update players
												set
													FetchedDate = '$FetchedDate',
													Age = '$Age',
													AgeDays = '$AgeDays',
													TSI = '$TSI',
													PlayerForm = '$PlayerForm',
													Experience = '$Experience',
													Leadership = '$Leadership',
													Salary = '$Salary',
													InjuryLevel = '$InjuryLevel',
													StaminaSkill = '$StaminaSkill',
													KeeperSkill = '$KeeperSkill',
													PlaymakerSkill = '$PlaymakerSkill',
													ScorerSkill = '$ScorerSkill',
													PassingSkill = '$PassingSkill',
													WingerSkill = '$WingerSkill',
													DefenderSkill = '$DefenderSkill',
													SetPiecesSkill = '$SetPiecesSkill',
													TrainerType = '$TrainerType',
													KeeperTrainers = '$KeeperTrainers',
													TrainingType = '$TrainingType',
													TrainerSkill = '$TrainerSkill',
													AssistantTrainers = '$AssistantTrainers',
													TrainingLevel = '$TrainingLevel',
													StaminaTrainingPart = '$StaminaTrainingPart',
													UserName = '$UserName',
													TeamID = '$TeamID',
													TeamName = '$TeamName',
													UserID = '$UserID',
													ABS_state = '$ABS_state',
													U20_state = '$U20_state',
													potencial = '$potencial'
												where PlayerID = '$PlayerID';";
													//U20_player_type = '$U20_player_type',
													//ABS_player_type = '$ABS_player_type',
									return ($this->transaction($sql) ? 2 : 0);
			}
		}
	}
	
	public function getPlayer($id){
		$p =  $this->select('select * from players where PlayerID = '.htmlspecialchars($id));
		return $p[0];
	}
	
	public function getPlayers($team = null, $fields = null, $w = null){
		if(is_array($fields))
			$columns = implode(",",$fields);
		else
			$columns = '*';
			
		switch($team){
		case 'abs':
			$where = "ABS_player_type != '' or ABS_state != ''";
			break;
		case 'u20':
			$where = "U20_player_type != '' or U20_state != ''";
			break;
		}
		if($w != null)
			$where = "($where)".((isset($where)) ? ' and (' : '(').$w.')';
		if(isset($where))
			$where = ' where '.$where;

		return $this->select("select $columns from players $where;");
	}

	public function getPlayerTypes(){
		return $this->select('select player_type from requireABS;');
	}
	
	
	
	
	
	/***** RETURNEE *****/
	
	public function getReturnees() {
		$r = $this->select("select * from returnees order by TrainingType,TrainerSkill,UpdatedDate,ExpectedCash");
		return $r;
	}
	
	public function getReturnee($user_id) {
		$r = $this->select("select * from returnees where UserID=".$user_id);
		return $r[0];
	}
	
	public function updateReturnee($returnee) {
		$exists = $this->select("select 1 from returnees where UserID=".$returnee['UserID']);
		if(count($exists)>0) {
			$sql[0] = "update returnees set ";
			$sql[0] .= "UpdatedDate=CURRENT_TIMESTAMP,";
			foreach($returnee as $f=>$v) {
				if($f=='UserID' || $f=='TeamID')
					continue;
				$v = (empty($v)) ? "NULL" : "'".$v."'";
				$sql[0] .= $f."=".$v.",";
			}
			$sql[0] = substr($sql[0],0,-1)." where UserID=".$returnee['UserID'];
		} else {
			$sql[0] = "insert into returnees(%fields%) values(%values%) ";
			$fields = "";
			$values = "";
			foreach($returnee as $f=>$v) {
				$fields .= $f.",";
				$values .= "'".$v."',";
			}
			$sql[0] = str_replace('%fields%', substr($fields,0,-1), $sql[0]);
			$sql[0] = str_replace('%values%', substr($values,0,-1), $sql[0]);
		}
		if(isset($sql))
			return $this->transaction($sql);
	}
	
	public function deleteReturnee($u,$t){
		$sql[0] = "delete from returnees where UserID = '$u' and TeamID = '$t';";
		return $this->transaction($sql);
	}
	
	
	
	/***** OTHER *****/
	
	public function getABS_requirements(){
		return $this->select('select * from requireABS order by `Order`','player_type');
	}
	
	public function getU20_requirements_2(){
		return $this->select('select * from requireU20 order by id');
	}
	
	public function getU20_requirements(){
		return $this->select('select player_type,concat("$is_u20 = ($age <= ",age," && $",p_skill," >= ",p_value," && (",case s1_skill when "" then 0 else concat("$",s1_skill) end," >= ",case s1_skill when "" then 1 else s1_value end," or ",case s2_skill when "" then 0 else concat("$",s2_skill) end," >= ",case s2_skill when "" then 1 else s2_value end," or ",case s3_skill when "" then 0 else concat("$",s3_skill) end," >= ",case s3_skill when "" then 1 else s3_value end,"));") as expr from requireU20;');
	}
	
	public function getMinTrainingLen(){
		return $this->select('select skill,min(weeks) as weeks from training_len group by skill','skill');
	}
}
?>
