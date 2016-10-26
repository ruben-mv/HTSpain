<?php
	session_start();

	//Error reporting
	error_reporting(0);
	ini_set('display_errors', '1');

	require_once('config.php');
	require_once('lib/C_DB.php');
	require_once('lib/C_Template.php');
	require_once('lib/H_main.php');
	require_once('lib/recaptchalib.php');

	//Import actions
	require_once('lib/C_Action.php');
	$dir = opendir('action');
	while($file = readdir($dir)) {
		if(substr($file,-4)=='.php')
			require_once('action/'.$file);
	}
	closedir($dir);
	
	//IMPORTANT: $_POST variables overwrite $_GET variables
	import_request_variables('gp','gp_');
	
	$db = new C_DB($config['sql_server'],$config['sql_db'],$config['sql_user'],$config['sql_pass']);
	if (! $db)
		die(mysql_error());
	
	//Load session
	if(empty($_SESSION['name'])) {
		if(!empty($_COOKIE['name'])) {
			$user = $db->getUserSession($_COOKIE['name']);
			if(!empty($user['name'])) {
				$_SESSION['user_type'] = $user['type'];
				$_SESSION['name'] = $user['name'];
				$_SESSION['team'] = $user['team'];
			}
		}
	}
	
	//Language
	if(empty($_SESSION['lang'])) {
		$_SESSION['lang'] = 'en';
		$browser_langs = split(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		foreach($browser_langs as $lg) {
			$l = strtolower(substr($lg,0,2));
			if(in_array($l,$config['available_languages'])) {
				$_SESSION['lang'] = $l;
				break;
			}
		}
	}
	if(isset($gp_gui_lang) && in_array(strtolower($gp_gui_lang), $config['available_languages']))
		$_SESSION['lang'] = $gp_gui_lang;
	require_once('./lang/'.strtoupper($_SESSION['lang']).'.php');
	
	//User type
	if(! isset($_SESSION['user_type']))
		$_SESSION['user_type'] = 0;
	
	//Action
	$access = $config['action_access'];
	if(empty($gp_pag))
		$gp_pag = 'index';
	if(! isset($access[$gp_pag]))
		$gp_pag = '404';
	else if($access[$gp_pag] == -1)
		$gp_pag = 'under_construction';
	else if($access[$gp_pag] > $_SESSION['user_type'])
		$gp_pag = 'forbbiden';
	$action = str_replace(' ', '', ucwords(str_replace('_', ' ', $gp_pag)));
	if(! class_exists('C_'.ucfirst($action).'Action'))
		$action = '404';
	
	$tpl_main = new C_Template('main.tpl');
	eval('$action = new C_'.ucfirst($action).'Action($config, $db, $tpl_main);');
	$action->execute();
	
	$tpl_main->set('title',i18n('main.title'));
	$tpl_main->set('languages',$config['available_languages']);
	$tpl_menu = new C_Template('menu.tpl');
	$tpl_menu->set('user_type',$_SESSION['user_type']);
	$tpl_menu->set('user_types',$config['user_types']);
	$tpl_main->set('menu',$tpl_menu);
	$tpl_main->set('is_logued',$_SESSION['user_type'] > 0);
	//$tpl_main->set('paypal',$config['paypal']);
	echo $tpl_main->parse();
?>
