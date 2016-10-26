<?php
class C_StaffReturneesAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('staff.returnee.pageTitle')));
		$tpl_returnee = new C_Template('staff/returnee.tpl');
		$tpl_returnee->set('returnees',$this->db->getReturnees());
		$this->tpl_main->set('content',$tpl_returnee);
	}
}
?>
