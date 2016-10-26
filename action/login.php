<?php
class C_LogoutAction extends C_Action {
	public function execute() {
		$this->db->delUserSession($_SESSION['name']);
		session_unset();
		session_destroy();
		$_SESSION = array();
		$_SESSION['user_type'] = 0;
		setcookie('name', '', time()-3600);
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('login-register.logout.pageTitle')));
		$this->tpl_main->set('content',new C_Template('login/logout.tpl'));
	}
}

class C_LoginAction extends C_Action {
	public function execute() {
		//Log in data submitted
		if(isset($_REQUEST['submit_login'])) {
			$user = $this->db->getUser($_REQUEST['login_username'],$_REQUEST['login_passwd']);
			if(isset($user['name'])){
				if($user['type'] > 0){
					//Login OK
					$_SESSION['user_type'] = $user['type'];
					$_SESSION['name'] = $user['name'];
					$_SESSION['team'] = $user['team'];
					if($_REQUEST['login_remember']) {
						$session = md5($user['name']);
						setcookie('name',$session,time()+(60*60*24*365));
						$this->db->addUserSession($user['name'], $session);
					} else {
						setcookie('name', '', time()-3600);
						$this->db->delUserSession($user['name']);
					}
					$this->tpl_main->set('page_title', i18n('pageTitle', i18n('login-register.login.pageTitle')));
					$this->tpl_main->set('content',new C_Template('login/login.tpl'));
				}
				else{
					//Account not validated yet
					$this->tpl_main->set('page_title', i18n('pageTitle',i18n('login-register.inactive.pageTitle')));
					$this->tpl_main->set('content',new C_Template('login/inactive.tpl'));
				}
			}
			else{
				//User/password incorrect
				$tpl_err = new C_Template('login/form.tpl');
				$tpl_err->set('errors',array(i18n('login-register.error.login.userPass')));
				$tpl_err->set('pub_key',$this->config['public_key']);
				$this->tpl_main->set('page_title', i18n('pageTitle', i18n('login-register.login.pageTitle')));
				$this->tpl_main->set('content',$tpl_err);
			}
		}
		//Show login template
		else{
			$this->tpl_main->set('page_title', i18n('pageTitle',i18n('login-register.pageTitle')));
			$tpl_form = new C_Template('login/form.tpl');
			$tpl_form->set('pub_key',$this->config['public_key']);
			$this->tpl_main->set('content',$tpl_form);
		}
	}
}


class C_RegisterAction extends C_Action {
	public function execute() {
		//Register account
		if(isset($_REQUEST['submit_reg'])){
			$errors = array();
			if(strlen($_REQUEST['username']) < 4)
				$errors[] = i18n('login-register.error.register.name.length');
			if(strlen($_REQUEST['passwd']) < 5)
				$errors[] = i18n('login-register.error.register.pass.length');
			if($_REQUEST['passwd'] != $_REQUEST['passwd2'])
				$errors[] = i18n('login-register.error.register.pass.diff');
			if(! in_array($_REQUEST['nt'], array('u20','abs')))
				$errors[] = i18n('login-register.error.register.nt');
			if(! is_numeric($_REQUEST['usertype']) || $_REQUEST['usertype'] < 1 || $_REQUEST['usertype'] > 3)
				$errors[] = i18n('login-register.error.register.userType');
			if ($_REQUEST["recaptcha_response_field"]) {
				$resp = recaptcha_check_answer ($this->config['private_key'],
		                        			$_SERVER["REMOTE_ADDR"],
					                        $_REQUEST["recaptcha_challenge_field"],
                        					$_REQUEST["recaptcha_response_field"]);

				if (! $resp->is_valid)
					$errors[] =  i18n('login-register.error.register.captcha');
			} else
				$errors[] =  i18n('login-register.error.register.captcha');

			if(empty($errors)) {
				if($this->db->addUser($_REQUEST['username'],$_REQUEST['passwd'],$_REQUEST['nt'],$_REQUEST['usertype'],$_REQUEST['description'])==false)
					$errors[] = i18n('login-register.error.register.existingUser');
			}
			if(!empty($errors)) {
				$tpl_err = new C_Template('login/form.tpl');
				$tpl_err->set('errors',$errors);
				$this->tpl_main->set('page_title', i18n('pageTitle', i18n('login-register.pageTitle')));
				$tpl_err->set('pub_key',$this->config['public_key']);
				$this->tpl_main->set('content',$tpl_err);
			} else {
				$this->tpl_main->set('page_title', i18n('pageTitle', i18n('login-register.register.pageTitle')));
				$this->tpl_main->set('content',new C_Template('login/register.tpl'));
			}
		}
	}
}
?>
