<?php
class C_HtLoginAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.players.pageTitle')));
		$tpl_form = new C_Template('ht/players_login.tpl');
		$tpl_form->set('is_nt_coach',$_SESSION['user_type'] == 2);
		$this->tpl_main->set('content',$tpl_form);
	}
}



class C_SubmitAction extends C_Action {
	public function execute() {
		require_once('lib/C_HT.php');
		$ht = new C_HT($this->config['ht_agent'], $this->config['ht_id'], $this->config['ht_key']);
		//0 successfull
		//-1 login failed
		//-2 Authorization failed. Could be incorrect ChppID, ChppKey or combination of both
		//-3 Authorization failed because ChppKey is locked
		if (! $ht->login($_REQUEST['username'],$_REQUEST['password'])){
			$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.players.pageTitle')));
			$errors = array();
			switch($ht->getLoginResult()) {
				case -1:
					$errors[] = i18n('ht.login.error.userPass'); break;
				case -2:
				case -3:
					$errors[] = i18n('ht.login.error.license'); break;
			}
			$tpl_err = new C_Template('ht/players_login.tpl');
			$tpl_err->set('errors',$errors);
			$tpl_err->set('is_nt_coach',$_SESSION['user_type'] == 2);
			$this->tpl_main->set('content',$tpl_err);
		}
		else{
			$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.players.submitted.pageTitle')));
			require_once('lib/C_Requirements.php');
			$rq = new C_Requirements($this->db);
			$team = $ht->getTeam();
			if($_REQUEST['nt_team'] == 1){ //Jugadores de la selección
				$isCoach = false;
				$players = array();
				if($team['UserID'] == $ht->getABSCoachID()){
						$isCoach = true;
						$players = $ht->getNTPlayers('ABS');
				}
				if($team['UserID'] == $ht->getU20CoachID()){
						$isCoach = true;
						$playersU20 = $ht->getNTPlayers('U20');
						foreach($playersU20 as $k => $v)
							$players[$k] = $v;
				}
				if($isCoach){
					$this->tpl_main->set('page_title', i18n('pageTitle','Jugadores convocados'));
					foreach($players as $p){
						$test = $rq->testPlayer($p,true);
						$players[$p['PlayerID']]['action'] = i18n('ht.players.submitted.'.strtolower($test['Action']));
						$players[$p['PlayerID']]['U20_player_type'] = $test['U20_player_type'];
						$players[$p['PlayerID']]['ABS_player_type'] = $test['ABS_player_type'];
						$players[$p['PlayerID']]['potencial'] = $test['Potencial'];
						switch($test['Action']){
							case 'Add':
								$this->db->addPlayer($ht->getFetchedDate(),$players[$p['PlayerID']]);
								break;
							case 'Upd':
								$this->db->updatePlayer($ht->getFetchedDate(),$players[$p['PlayerID']]);
								break;
						}
					}
					$tpl_players = new C_Template('ht/players_submitted.tpl');
					$tpl_players->set('players',$players);
					$this->tpl_main->set('content',$tpl_players);
				}
				else{
					$this->tpl_main->set('page_title', i18n('pageTitle','No eres seleccionador'));
				}
			}
			//Jugadores de un equipo
			else{
				$players = $ht->getPlayers();
				foreach($players as $p){
					$test = $rq->testPlayer($p);
					$players[$p['PlayerID']]['action'] = i18n('ht.players.submitted.'.strtolower($test['Action']));
					$players[$p['PlayerID']]['U20_player_type'] = $test['U20_player_type'];
					$players[$p['PlayerID']]['ABS_player_type'] = $test['ABS_player_type'];
					$players[$p['PlayerID']]['potencial'] = $test['Potencial'];
					switch($test['Action']){
						case 'Add':
							$this->db->addPlayer($ht->getFetchedDate(),$players[$p['PlayerID']],$ht->getTraining(),$team);
							break;
						case 'Upd':
							$this->db->updatePlayer($ht->getFetchedDate(),$players[$p['PlayerID']],$ht->getTraining(),$team);
							break;
					}
				}
				$tpl_players = new C_Template('ht/players_submitted.tpl');
				$tpl_players->set('players',$players);
				$this->tpl_main->set('content',$tpl_players);
			}		
			$ht->logout();
		}
	}
}



class C_ReturneeAction extends C_Action {
	public function execute() {
		if(isset($_REQUEST['cmdweblogin'])) {
			require_once('lib/C_HT.php');
			$ht = new C_HT($this->config['ht_agent'], $this->config['ht_id'], $this->config['ht_key']);
			//0 successfull
			//-1 login failed
			//-2 Authorization failed. Could be incorrect ChppID, ChppKey or combination of both
			//-3 Authorization failed because ChppKey is locked
			if (! $ht->login($_REQUEST['username'],$_REQUEST['password'])){
				$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.returnee.pageTitle')));
				$errors = array();
				switch($ht->getLoginResult()) {
					case -1:
						$errors[] = i18n('ht.login.error.userPass'); break;
					case -2:
					case -3:
						$errors[] = i18n('ht.login.error.license'); break;
				}
				$tpl_err = new C_Template('ht/players_login.tpl');
				$tpl_err->set('errors',$errors);
				$this->tpl_main->set('content',$tpl_err);
			} else {
				$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.returnee.info.pageTitle')));
				$returnee = $ht->getReturnee();
				$returnee_db = $this->db->getReturnee($returnee['UserID']);
				if($returnee_db) {
					$returnee['NT'] = $returnee_db['NT'];
					$returnee['FastPoll'] = $returnee_db['FastPoll'];
					$returnee['Comments'] = $returnee_db['Comments'];
				}
				$tpl_returnee = new C_Template('ht/returnee_info.tpl');
				$tpl_returnee->set('returnee',$returnee);
				//Unset uneeded fields
				unset($returnee['NT']);
				unset($returnee['FastPoll']);
				unset($returnee['Comments']);
				$this->db->updateReturnee($returnee);
				$this->tpl_main->set('content',$tpl_returnee);
			}
		} elseif(isset($_REQUEST['submit_info'])) {
			$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.returnee.info.pageTitle')));
			$returnee['UserID'] = $_REQUEST['user_id'];
			$returnee['TeamID'] = $_REQUEST['team_id'];
			$returnee['NT'] = $_REQUEST['nt'];
			$returnee['FastPoll'] = $_REQUEST['fast_poll'];
			$returnee['Comments'] = $_REQUEST['comments'];			
			$returnee['UserName'] = $_REQUEST['user_name'];
			$returnee['TeamName'] = $_REQUEST['team_name'];
			$this->db->updateReturnee($returnee);
			$returnee['Cash'] = $_REQUEST['cash'];
			$returnee['ExpectedCash'] = $_REQUEST['expected_cash'];
			$returnee['TrainingType'] = $_REQUEST['training_type'];
			$returnee['TrainerSkill'] = $_REQUEST['trainer_skill'];
			$returnee['AssistantTrainers'] = $_REQUEST['assistant_trainers'];
			$returnee['KeeperTrainers'] = $_REQUEST['keeper_trainers'];
			$returnee['TrainingLevel'] = $_REQUEST['training_level'];
			$returnee['StaminaTrainingPart'] = $_REQUEST['stamina_training_part'];
			$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.returnee.info.pageTitle')));
			$tpl_returnee = new C_Template('ht/returnee_info.tpl');
			$tpl_returnee->set('saved',true);
			$tpl_returnee->set('returnee',$returnee);
			$this->tpl_main->set('content',$tpl_returnee);
		} elseif (isset($_REQUEST['submit_info_delete'])) {
			$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.returnee.info.pageTitle')));
			$this->db->deleteReturnee($_REQUEST['user_id'],$_REQUEST['team_id']);
			$tpl_returnee = new C_Template('ht/returnee_login.tpl');
			$tpl_returnee->set('deleted',true);
			$this->tpl_main->set('content',$tpl_returnee);
		} else {
			$this->tpl_main->set('page_title', i18n('pageTitle',i18n('ht.returnee.pageTitle')));
			$this->tpl_main->set('content',new C_Template('ht/returnee_login.tpl'));
		}
	}
}
?>
