<?php
class C_LangCheckAction extends C_Action {
	public function execute() {
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('lang.check.pageTitle')));
		$tpl_lang = new C_Template('admin/lang_check.tpl');
		//Load all language files
		$langs = array();
		$dir = opendir('lang');
		while($file = readdir($dir)) {
			if(strlen($file)==6 && substr($file,-4)=='.php')
				$langs[] = substr($file,0,2);
		}
		closedir($dir);
		$tpl_lang->set('languages',$langs);
		$errors = array();
		if(isset($_REQUEST['submit'])) {
			$lang1 = @include('lang/'.strtoupper($_REQUEST['lang1']).'.php');
			$lang2 = @include('lang/'.strtoupper($_REQUEST['lang2']).'.php');
			//Must reload session's language file, otherwise the avobe code overwrites it
			include('lang/'.strtoupper($_SESSION['lang']).'.php');
			if(is_array($lang1) && is_array($lang2)) {
				$tpl_lang->set('lang1',$_REQUEST['lang1']);
				$tpl_lang->set('lang2',$_REQUEST['lang2']);
				$values = array();
				foreach($lang1 as $key=>$val)
					$values[$key][0] = $val;
				foreach($lang2 as $key=>$val)
					$values[$key][1] = $val;
				ksort($values);
				foreach($values as $k=>$v) {
					if(empty($v[0]) || empty($v[1])) {
						$tpl_lang->set('diff',true);
						break;
					}
				}
				$tpl_lang->set('values',$values);
			} else {
				if(! is_array($lang1))
					$errors[] = i18n('lang.check.error.noLang', $lang1);
				if(! is_array($lang2))
					$errors[] = i18n('lang.check.error.noLang', $lang2);
			}
		}
		$tpl_lang->set('errors',$errors);
		$this->tpl_main->set('content',$tpl_lang);
	}
}
?>
