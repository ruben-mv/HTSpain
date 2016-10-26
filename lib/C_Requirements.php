<?php
class C_Requirements{
	private $rABS;
	private $rU20;
	private $tr_len;
	private $db;
	
	public function __construct($db){
		$this->db = $db;
		$this->rABS = $db->getABS_requirements();
		$this->rU20 = $db->getU20_requirements();
		$this->tr_len = $db->getMinTrainingLen();
	}
	
	public function testPlayer($p,$nt_coach = false){
		$result = array('U20_player_type' => '',
						'ABS_player_type' => '',
						'Potencial' => 0,
						'Action' => 'None');
		
		$p_aux = $this->db->getPlayer($p['PlayerID']);
		trigger_error($p['PlayerName']);
		if($p_aux['U20_player_type'] != '' || $p_aux['U20_state'] == 'C'){ //Exists as U20 player
			trigger_error('Exists as U20 player');
			if($p['Age'] >= 21){
				trigger_error('>=21');
				$result['U20_player_type'] = '';
				$result['U20_state'] = '';
				$result['Action'] = 'Upd';
			}else{
				trigger_error('<21');
				$result['U20_player_type'] = $p_aux['U20_player_type'];
				$result['Action'] = 'Upd';
			}
		}elseif($p['Age'] < 21){ //Don't exists as U20 player
			trigger_error('Dont exists as U20 player and Age < 21');
			extract($p);
			$age = $Age + ($AgeDays/1000);
			foreach($this->rU20 as $r){
				eval($r['expr']); //$is_u20 is defined here
				trigger_error($r['expr']);
				if($is_u20){
					$result['U20_player_type'] = $r['player_type'];
					$result['Action'] = 'Add';
					break;
				}
			}
			if($nt_coach && $result['Action'] == 'None'){
				trigger_error('Action = None');
				$result['Action'] = 'Add';
				$result['U20_player_type'] = '';
			}
			
		}
		$potencial['Value'] = 0;
		if($p_aux['ABS_player_type'] != '' || $p_aux['ABS_state'] == 'C'){ //Exists as NT-player
			switch($p_aux['ABS_state']){				
				case 'C':  //Inserted by NT-Coach
				case '':
					$potencial = $this->getPotencial($p,$p_aux['player_type']);
					//Get best potencial
					foreach($this->rABS as $t => $row){
						if($row['Specialty'] == 0 || $row['Specialty'] == $p['Specialty']){
							$pot_aux = $this->getPotencial($p,$t);
							if 	(($pot_aux['Diff'] < $potencial['Diff'] || $potencial['Value'] == 0) &&
								$pot_aux['Value'] > 0)
								$potencial = $pot_aux;
						}
					}
					break;
			}
			$result['Action'] = 'Upd';
		} else { //Not exists as NT-player
			//Get best VALID potencial
			$potencial['Diff'] = 0;
			foreach($this->rABS as $t => $row){
				if($row['Specialty'] == 0 || $row['Specialty'] == $p['Specialty']){
					$pot_aux = $this->getPotencial($p,$t);
					if 	(($pot_aux['Diff'] <= 0 || $p['ABS_state'] == 'C') &&
						($pot_aux['Diff'] < $potencial['Diff'] || $potencial['Value'] == 0) &&
						$pot_aux['Value'] > 0)
						$potencial = $pot_aux;
				}
			}
		}
		
		if ($potencial['Type'] != '' || $nt_coach){
			$result['ABS_player_type'] = $potencial['Type'];
			$result['Potencial'] = $potencial['Value'];
			switch($result['Action']){
				case 'Upd':
					$result['Action'] = 'Upd';
					break;
				default:
					$result['Action'] = 'Add';
			}
		}
		return $result;
	}		
	
	public function getPotencial($player,$type,$tr = null){
		$p = $player;
		if($training == null){
			//$tr_f = (1+(7-7)*0.1) * (1+(log10(11)-log10(9+1))*0.2) * (100/100) * (1/(1-12/100));
			$tr_f = 1.14577106480869; //Mister bueno 12% condición 9 auxiliares
		}
		else{
			//TODO :S
			//[KeeperTrainers] => 1
			//[AssistantTrainers] => 9
			//[TrainingType] => 4
			$tr_f = (1+(7-$tr['TrainerSkill'])*0.1) * (1+(log10(11)-log10($tr['A']+1))*0.2) * (100/$tr['TrainingLevel']) * (1/(1-$tr['StaminaTrainingPart']/100));
		}
		
		if(is_array($this->rABS[$type])){
			foreach($this->rABS[$type] as $sk => $v){
				if(isset($this->tr_len[$sk]['weeks'])){
					while($p[$sk] < $v){
						if($p['Age'] > 45 or $p[$sk] < 2)
							return array(	'Age' => 0,
											'AgeDays' => 0,
											'Type' => $type,
											'Value' => 0);						
						$age_f = pow(1.04,($p['Age']-17));
						$sk_f = 1 + log10((floor($p[$sk])+1-0.5)/7)/log10(5);
						$sk_inc = 1/($age_f * $sk_f * $tr_f * ($this->tr_len["$sk"]['weeks']));
						$p[$sk] += $sk_inc;
						$p['AgeDays'] += 7;
						if($p['AgeDays'] >= 112){
							$p['Age'] += 1;
							$p['AgeDays'] -= 112;
						}
					}
				}
			}
				
			return array(	'Age' => $p['Age'],
							'AgeDays' => $p['AgeDays'],
							'Type' => $type,
							'Value' => $p['Age'] + $p['AgeDays']/112,
							'AgePot' => $this->agePot($player['Age'],$player['AgeDays'],$this->rABS[$type]['age']),
							'Diff' => ($p['Age'] + $p['AgeDays']/112) - $this->agePot($player['Age'],$player['AgeDays'],$this->rABS[$type]['age']));
		} else
			return array();
	}
	
	public function getMaxPotencial($type){
		return $this->rABS[$type]['age'];
	}
	
	private function agePot($y,$d,$p){
		return $p + (exp(($y + $d/112)/26) - exp(17/26));
	}
	
	public function getPotenciales($p){
		$pots = array();
		foreach($this->rABS as $t => $row){
			$pot = $this->getPotencial($p,$t);
			$pots[$t] = array(	'player_type' 	=> $t,
								'potencial'	  	=> $pot['Value'],
								'pot_req'		=> $this->rABS[$t]['age'],
								'pot_req_age'	=> $pot['AgePot'],
								'pot_diff'		=> $pot['Diff']
							);
		}
		return $pots;
	}
}
?>
