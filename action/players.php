<?php
class C_StaffPlayersAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('staff.players.pageTitle')));
		$tpl_players = new C_Template('staff/players.tpl');
		$tpl_players->set('date',$_REQUEST['date']);
		$tpl_players->set('time',$_REQUEST['time']);
		$tpl_players->set('both',$_SESSION['team'] == 'both');
		
		if(isset($_REQUEST['cmdexport'])){
			if(empty($_REQUEST['date']))
				$_REQUEST['date'] = '0000-00-00';
			if(empty($_REQUEST['time']))
				$_REQUEST['time'] = '00:00';
			
			$s_date = explode('-',$_REQUEST['date']);
			$s_time = explode(':',$_REQUEST['time']);
			if(	(isset($s_date[0]) && is_numeric($s_date[0]) && $s_date[0] >= 0 && $s_date[ 0] <= 9999) &&
				(isset($s_date[1]) && is_numeric($s_date[1]) && $s_date[1] >= 0 && $s_date[ 1] <=   12) &&
				(isset($s_date[2]) && is_numeric($s_date[2]) && $s_date[2] >= 0 && $s_date[31] <= 9999) &&
				(isset($s_time[0]) && is_numeric($s_time[0]) && $s_time[0] >= 0 && $s_time[ 0] <=   23) &&
				(isset($s_time[1]) && is_numeric($s_time[1]) && $s_time[1] >= 0 && $s_time[ 1] <=   59)) {
				$datetime = $_REQUEST['date'].' '.$_REQUEST['time'].':00';
				
				$arrydata = $this->db->getPlayers(	$_REQUEST['nt_team'],
													array(	'ABS_player_type','potencial','U20_player_type','PlayerName','PlayerID','Age','AgeDays',
															'TSI','PlayerForm','StaminaSkill','Experience','Leadership','KeeperSkill','DefenderSkill',
															'PlaymakerSkill','WingerSkill','PassingSkill','ScorerSkill','SetPiecesSkill','Specialty',
															'TeamID','TeamName','FetchedDate'),
													"FetchedDate >= '$datetime'");
													
				
        		foreach($arrydata as $k => $v){
    	    		$v['PlayerName'] 	= utf8_decode($v['PlayerName']);
        			$v['TeamName'] 		= utf8_decode($v['TeamName']);
        			$arrydata[$k] 		= $v;
        		}
        
        		require_once('lib/excel.php');
    	    	$filename = 'players_'.date("Ymd_Hi").'.xls';
		        $excelfile = "xlsfile://tmp/".$filename.".xls";  
				$fp = fopen($excelfile, "wb");  
				if (!is_resource($fp))
					die("Error al crear $excelfile");				
				fwrite($fp, serialize($arrydata));  
				fclose($fp);
				header ("Expires: ".gmdate("D,d M YH:i:s")." GMT");  
				header ("Last-Modified: ".gmdate("D,d M YH:i:s")." GMT");  
				header ("Cache-Control: no-cache, must-revalidate");  
				header ("Pragma: no-cache");  
				header ("Content-type: application/x-msexcel");
				header ("Content-Disposition: attachment; filename=\"".$filename."\"" );
				readfile($excelfile);
			}
			else
				$tpl_players->set('errors',array('Fecha y/u hora incorrecta'));
		}
		$this->tpl_main->set('content',$tpl_players);
	}
}
?>
