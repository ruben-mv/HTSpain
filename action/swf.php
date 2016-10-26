<?php
class C_MidCalcAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('midfield-calc.pageTitle')));
		$this->tpl_main->set('content',new C_Template('swf/midfield_calc.tpl'));
	}
}



class C_TrainCalcAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('train-calc.pageTitle')));
		$this->tpl_main->set('content',new C_Template('swf/training_calc.tpl'));
	}
}



class C_YouthGuideAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('youth-guide.pageTitle')));
		$this->tpl_main->set('content',new C_Template('swf/youth_guide.tpl'));
	}
}



class C_NtGuideAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('nt-guide.pageTitle')));
		$this->tpl_main->set('content',new C_Template('swf/nt_guide.tpl'));
	}
}
?>
