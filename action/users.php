<?php
class C_UsersAction extends C_Action {
	public function execute() {
		if($_REQUEST['update']) {
			$users = array();
			//Delete users
			if($_REQUEST['delete']) {
				foreach($_REQUEST['delete'] as $k=>$v)
					$this->db->deleteUser($k);
			}
			//Validate users
			if($_REQUEST['valid']) {
				foreach($_REQUEST['valid'] as $k=>$v)
					$users[$k]['type'] = $v;
			}
			//Change user type
			if($_REQUEST['usertype']) {
				foreach($_REQUEST['usertype'] as $k=>$v)
					$users[$k]['type'] = $v;
				
			}
			//Change team
			if($_REQUEST['team']) {
				foreach($_REQUEST['team'] as $k=>$v)
					$users[$k]['team'] = $v;
				
			}
			//Change description
			if($_REQUEST['description']) {
				foreach($_REQUEST['description'] as $k=>$v)
					$users[$k]['description'] = $v;
				
			}
			//Change password
			if($_REQUEST['password']) {
				foreach($_REQUEST['password'] as $k=>$v)
					$users[$k]['password'] = $v;
				
			}
			//Update
			foreach($users as $n=>$u) {
				$u['name'] = $n;
				$this->db->updateUser($u);
				if($u['name']==$_SESSION['name'] && isset($u['type']))
					$_SESSION['user_type'] = $u['type'];
			}
		}
		$this->tpl_main->set('page_title', i18n('pageTitle',i18n('users.pageTitle')));
		$tpl_users = new C_Template('admin/users.tpl');
		$users = $this->db->getUsers();
		foreach($users as $i=>$u) {
			$u['description'] = htmlentities($u['description']);
			$users[$i] = $u;
		}
		$tpl_users->set('users',$users);
		$tpl_users->set('user_types',$this->config['user_types']);
		$this->tpl_main->set('content',$tpl_users);
	}
}
?>
