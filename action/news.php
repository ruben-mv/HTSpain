<?php
class C_NewsAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('news.pageTitle')));
		$tpl_news = new C_Template('admin/news.tpl');
		if(isset($_REQUEST['op'])||isset($_REQUEST['load'])) {
			if($_REQUEST['op']=='edit'||$_REQUEST['op']=='translate'||isset($_REQUEST['load'])) {
				$n = $this->db->getPieceOfNews($_REQUEST['id'],$_REQUEST['lang']);
				if(empty($n) && isset($_REQUEST['load'])) {
					$n = $this->db->getPieceOfNews($_REQUEST['id'],$_REQUEST['old_lang']);
					$tpl_news->set('load_lang_fail',true);
				}
				$tpl_news->set('piece_of_news',$n);
				if(isset($_REQUEST['op']))
					$tpl_news->set('op',$_REQUEST['op']);
				elseif(isset($_REQUEST['load']))
					$tpl_news->set('op','translate');
			}
			elseif($_REQUEST['op']=='delete')
				$this->db->delNews($_REQUEST['id']);
		}
		if(isset($_REQUEST['submit'])) {
			if($_REQUEST['team'] != 1 && $_REQUEST['team'] != 2)
				$_REQUEST['team'] = 0;
			$this->db->updateNews($_REQUEST['id'],$_REQUEST['lang'],$_REQUEST['team'],$_SESSION['name'],$_REQUEST['title'],$_REQUEST['text']);
		}
		$news_db = $this->db->getNews();
		$news_ar = array();
		foreach($news_db as $n) {
			$n = news_parse($n);
			$news_ar[$n['id']]['id'] = $n['id'];
			$news_ar[$n['id']]['team'] = $n['team'];
			$news_ar[$n['id']]['user'] = $n['user'];
			$news_ar[$n['id']]['publication_date'] = $n['publication_date'];
			$news_ar[$n['id']]['langs'][$n['lang']]['title'] = $n['title'];
			$news_ar[$n['id']]['langs'][$n['lang']]['text'] = $n['text'];
		}
		$tpl_news->set('news',$news_ar);
		$tpl_news->set('languages',$this->config['available_languages']);
		$this->tpl_main->set('content',$tpl_news);
	}
	
}
?>
