<?php
require_once('config_env.php');
/*config_env.php format:
<?php
//SQL server
$config['sql_server'] = '';
$config['sql_db'] = '';
$config['sql_user'] = '';
$config['sql_pass'] = '';
//CHPP License
$config['ht_agent'] = '';
$config['ht_id'] = ;
$config['ht_key'] = '';
//ReCAPTCHA library
$config['public_key'] = "";
$config['private_key'] = "";
//Contact
$config['contact_ht_teamid'] = '';
$config['contact_ht_userid'] = '';
$config['contact_ht_username'] = '';

//Down for mantenaince
//die('<h1>HTSpain est&aacute; siendo actualizado, disculpe las molestias.</h1><br><h1>HTSpain it\'s been updated, apologize for the inconvenience.</h1><br>');
?>
*/

//User types
$config['user_types'] = array(
	'unreg' => 0,
	'scout' => 1,
	'coach' => 2,
	'admin' => 3,
);

//Action access
//This array MUST have ALL actions
//Value indicates minimum user_type required for the action (-1 indicates under construction)
$config['action_access'] = array(
	'index'           => $config['user_types']['unreg'],
	//Login
	'login'           => $config['user_types']['unreg'],
	'logout'          => $config['user_types']['unreg'],
	'register'        => $config['user_types']['unreg'],
	//HT Login
	'ht_login'        => -1,//$config['user_types']['unreg'],
	'submit'          => -1,//$config['user_types']['unreg'],
	'returnee'        => -1,//$config['user_types']['unreg'],
	//U20
	'u20_age'         => $config['user_types']['unreg'],
	'u20_skill'       => $config['user_types']['unreg'],
	'u20_staff'       => $config['user_types']['unreg'],
	//ABS
	'abs_pot'         => $config['user_types']['unreg'],
	'abs_skill'       => $config['user_types']['unreg'],
	'abs_staff'       => $config['user_types']['unreg'],
	//Guides
	'nt_guide'        => $config['user_types']['unreg'],
	'youth_guide'     => $config['user_types']['unreg'],
	//Tools
	'mid_calc'        => $config['user_types']['unreg'],
	'train_calc'      => $config['user_types']['unreg'],
	//Staff
	'staff_returnees' => $config['user_types']['scout'],
	'staff_players'	  => $config['user_types']['scout'],
	//Administration
	'news'            => $config['user_types']['admin'],
	'users'           => $config['user_types']['admin'],
	'lang_check'      => $config['user_types']['admin'],
	'skill_check'	  => $config['user_types']['scout'],
);

//Languages
$config['available_languages'] = array('es', 'en');
?>
