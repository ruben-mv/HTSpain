<?php
class C_SkillCheckAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('skillcheck.pageTitle')));
		$tpl_skillcheck = new C_Template('admin/skillcheck.tpl');
		if(isset($_REQUEST['submit'])){
			require_once('lib/C_Requirements.php');
			$rq = new C_Requirements($this->db);
			$tpl_skillcheck->set('pots',$rq->getPotenciales(array(	'Age'	=> $_REQUEST['age'],
											'AgeDays' => $_REQUEST['ageDays'],
											'KeeperSkill' => $_REQUEST['keeper'],
											'PlaymakerSkill' => $_REQUEST['playmaker'],
											'ScorerSkill' => $_REQUEST['scorer'],
											'PassingSkill' => $_REQUEST['passing'],
											'WingerSkill' => $_REQUEST['winger'],
											'DefenderSkill' => $_REQUEST['defender'],
											'SetPiecesSkill' => $_REQUEST['setPieces']
											))
				);
		}
		$this->tpl_main->set('content',$tpl_skillcheck);
	}
}
?>
