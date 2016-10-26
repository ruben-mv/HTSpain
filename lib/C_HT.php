<?php
require_once('PHT.php');

class C_HT
{
	private $PHT;
	private $fetchedDate;
	
	public function __construct($chppAgent, $chppId, $chppKey)
	{
		try{
			$this->PHT = new CHPPConnection($chppAgent, $chppId, $chppKey);
		}
		catch(HTError $e){
			echo $e->getErrorCode()." : ".$e->getError();
		}	
	}
	
	public function login($user,$password)
	{
		date_default_timezone_set("Europe/Madrid");
		$this->fetchedDate = date ("Y-m-d H:i:s");
		return $this->PHT->connectUser($user, $password);
	}
	
	public function getLoginResult(){
		return  $this->PHT->getLoginResult();
	}
	
	public function logout()
	{
		$this->PHT->disconnect();
	}
	
	public function getTeam()
	{
		return 	array(
					'UserName'	=> $this->PHT->getTeam()->getLoginName(),
					'UserID'	=> $this->PHT->getTeam()->getUserId(),
					'TeamName'	=> $this->PHT->getTeam()->getTeamName(),
					'TeamID'	=> $this->PHT->getTeam()->getTeamID(),
					'TrainingDate' => $this->PHT->getWorldDetails()->getLeagueById($this->PHT->getLeague()->getLeagueId())->getTrainingDate()
				);
	}
	
	public function getTraining()
	{
		$trainer = $this->PHT->getTraining()->getTrainerId();
		return array (	
			'KeeperTrainers'		=> $this->PHT->getClub()->getSpecialists()->getKeeperTrainers(),
		    'AssistantTrainers'		=> $this->PHT->getClub()->getSpecialists()->getAssistantTrainers(),
		    'TrainingLevel'			=> $this->PHT->getTraining()->getTrainingLevel(),
		    'StaminaTrainingPart' 	=> $this->PHT->getTraining()->getStaminaTrainingPart(),
		    'TrainingType'			=> $this->PHT->getTraining()->getTrainingType(),
		    'TrainerID'				=> $this->PHT->getTraining()->getTrainerId(),
		    'TrainerSkill'			=> $this->PHT->getPlayer($trainer)->getTrainerSkill(),
		    'TrainerType'			=> $this->PHT->getPlayer($trainer)->getTrainerType()
		);
	}
	
	public function getPlayers()
	{
		$players = array();
		$teamPlayers = $this->PHT->getTeamPlayers();
		for($i=1; $i<=$teamPlayers->getNumberPlayers(); $i++){
   			$player = $this->PHT->getPlayer($teamPlayers->getPlayer($i)->getId());
   			if($player->getNativeLeagueID() == 36){
   				$players[$player->getId()] = array(	'PlayerID' => $player->getId(),
										            'PlayerName' => $player->getName(),
										            'Age' => $player->getAge(),
										            'AgeDays' => $player->getDays(),
										            'TSI' => $player->getTsi(),
										            'PlayerForm' => $player->getForm(),
										            'Experience' => $player->getExperience(),
										            'Leadership' => $player->getLeadership(),
													'Salary' => $player->getSalary(HTMoney::Espana)/($player->isAbroad()?1.2:1),
										            'Agreeability' => $player->getAgreeability(),
										            'Aggressiveness' => $player->getAggressiveness(),
										            'Honesty' => $player->getHonesty(),
										            'Specialty' => $player->getSpeciality(),
										            'InjuryLevel' => $player->getInjury(),
										            'StaminaSkill' => $player->getStamina(),
										            'KeeperSkill' => $player->getKeeper(),
										            'PlaymakerSkill' => $player->getPlaymaker(),
										            'ScorerSkill' => $player->getScorer(),
										            'PassingSkill' => $player->getPassing(),
										            'WingerSkill' => $player->getWinger(),
										            'DefenderSkill' => $player->getDefender(),
										            'SetPiecesSkill' => $player->getSetPieces(),
										            'TrainerType' => $player->getTrainerType()
   													);
   			}
   		}
		return $players;
	}
	
	public function getABSCoachID()
	{
		return $this->PHT->getNationalTeamDetail(3035)->getCoachUserID();
	}
	
	public function getU20CoachID()
	{
		return $this->PHT->getNationalTeamDetail(3076)->getCoachUserID();
	}
	
	public function getNTPlayers($team){
		switch($team){
			case 'ABS':
				$teamPlayers = $this->PHT->getNationalPlayers(3035);
				break;
			case 'U20';
				$teamPlayers = $this->PHT->getNationalPlayers(3076);
				break;
		}
		$players = array();
		for($i=1; $i<= $teamPlayers->getNumberPlayers(); $i++){
   			$player = $this->PHT->getPlayer($teamPlayers->getPlayer($i)->getId());
   			$players[$player->getId()] = array(	'PlayerID' => $player->getId(),
										            'PlayerName' => $player->getName(),
										            'Age' => $player->getAge(),
										            'AgeDays' => $player->getDays(),
										            'TSI' => $player->getTsi(),
										            'PlayerForm' => $player->getForm(),
										            'Experience' => $player->getExperience(),
										            'Leadership' => $player->getLeadership(),
													'Salary' => $player->getSalary(HTMoney::Espana)/($player->isAbroad()?1.2:1),
										            'Agreeability' => $player->getAgreeability(),
										            'Aggressiveness' => $player->getAggressiveness(),
										            'Honesty' => $player->getHonesty(),
										            'Specialty' => $player->getSpeciality(),
										            'InjuryLevel' => $player->getInjury(),
										            'StaminaSkill' => $player->getStamina(),
										            'KeeperSkill' => $player->getKeeper(),
										            'PlaymakerSkill' => $player->getPlaymaker(),
										            'ScorerSkill' => $player->getScorer(),
										            'PassingSkill' => $player->getPassing(),
										            'WingerSkill' => $player->getWinger(),
										            'DefenderSkill' => $player->getDefender(),
										            'SetPiecesSkill' => $player->getSetPieces(),
										            'TrainerType' => $player->getTrainerType()
   													);
   			switch($team){
			case 'ABS':
				$players[$player->getId()]['ABS_state'] = 'C';
				break;
			case 'U20';
				$players[$player->getId()]['U20_state'] = 'C';
				break;
		}
   		}
   		return $players;
	}

	
	public function getU20Players()
	{
	
	}
	
	public function getFetchedDate()
	{
		return $this->fetchedDate;
	}
	
	public function getReturnee()
	{
		$team = $this->getTeam();
		$training = $this->getTraining();
		return array(	'UserName'	=> $team['UserName'],
						'UserID'	=> $team['UserID'],
						'TeamName'	=> $team['TeamName'],
						'TeamID'	=> $team['TeamID'],
						'Cash'		=> $this->PHT->getEconomy(HTMoney::Espana)->getCash(),
						'ExpectedCash' 	=> $this->PHT->getEconomy(HTMoney::Espana)->getCash() + $this->PHT->getEconomy(HTMoney::Espana)->getWeekExpected(),
						'TrainingType'	=> $training['TrainingType'],
			   		    'TrainerSkill'	=> $training['TrainerSkill'],
			   		    'AssistantTrainers'	=> $training['AssistantTrainers'],
						'KeeperTrainers'	=> $training['KeeperTrainers'],
		    			'TrainingLevel'		=> $training['TrainingLevel'],
		    			'StaminaTrainingPart'=> $training['StaminaTrainingPart']
					);
	}
}
?>
