<?php
class C_Action {
	protected $db;
	protected $tpl_main;
	protected $config;
	
	public function __construct($p_config, $p_db, $p_tpl_main) {
		$this->config = $p_config;
		$this->db = $p_db;
		$this->tpl_main = $p_tpl_main;
	}
}
?>
