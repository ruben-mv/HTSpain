<?php
class C_IndexAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('welcome.pageTitle')));
		$tpl_welcome = new C_Template('welcome.tpl');
		$news = $this->db->getNews($_SESSION['lang'],5);
		foreach($news as $i=>$n)
			$news[$i] = news_parse($n);
		$tpl_welcome->set('news',$news);
		$this->tpl_main->set('content',$tpl_welcome);
	}
}



class C_ForbbidenAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('forbidden.pageTitle')));
	}
}



class C_UnderConstructionAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('under-construction.pageTitle')));
		$this->tpl_main->set('content',new C_Template('under_construction.tpl'));
	}
}



class C_404Action extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('404')));
		$this->tpl_main->set('content',new C_Template('404.tpl'));
	}
}
?>
