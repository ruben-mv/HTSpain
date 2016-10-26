<?php
/*** STAFF ***/
class C_U20StaffAction extends C_Action {
	public function execute() {
		$aux = new Caux_StaffAction($this->config, $this->db, $this->tpl_main);
		$aux->execute('u20');
	}
}
class C_AbsStaffAction extends C_Action {
	public function execute() {
		$aux = new Caux_StaffAction($this->config, $this->db, $this->tpl_main);
		$aux->execute('abs');
	}
}
class Caux_StaffAction extends C_Action {
	public function execute($nt) {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('nt.staff.pageTitle', i18n('nt.'.$nt))));
		$users = $this->db->getUsers($nt);
		foreach($users as $k=>$u) {
			if($u['type']<0)
				unset($users[$k]);
		}
		$tpl_staff = new C_Template('nt/staff.tpl');
		$tpl_staff->set('users',$users);
		$tpl_staff->set('user_types',$this->config['user_types']);
		$this->tpl_main->set('content',$tpl_staff);
	}
}



/*** ABS ***/

class C_AbsSkillAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('nt.abs.skill.pageTitle'));
		$tpl_skill = new C_Template('nt/abs_skill.tpl');
		$tpl_skill->set('requirements',$this->db->getABS_requirements());
		$this->tpl_main->set('content',$tpl_skill);
	}
}

class C_AbsPotAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('nt.abs.potential.pageTitle'));
		$this->tpl_main->set('content',new C_Template('nt/abs_pot.tpl'));
	}
}



/*** U20 ***/

class C_U20AgeAction extends C_Action {
	public function execute() {
		require_once('lib/C_WCup.php');
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('nt.u20.age.pageTitle')));
		$wc = new C_WCup();
		$tpl_u20_age = new C_Template('nt/u20_age.tpl');
		$tpl_u20_age->set('calendar',$wc->getU20_Calendar());
		$this->tpl_main->set('content', $tpl_u20_age);
	}
}

class C_U20SkillAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('nt.u20.skill.pageTitle'));
		$tpl_skill = new C_Template('nt/u20_skill.tpl');
		$tpl_skill->set('requirements',$this->db->getU20_requirements_2());
		$this->tpl_main->set('content',$tpl_skill);
	}
}
?>
