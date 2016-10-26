<?php
class C_Template{
	private $file;
	private $vars;
	
	public function __construct($file){
		$this->file = './tpl/'.$file;
		$this->vars = array();
	}
	
	public function set($name,$value){
		$this->vars[$name] = is_object($value) ? $value->parse() : $value;
	}
	
	public function parse(){
		extract($this->vars); //Extraer variables al namespace local
		ob_start(); //Abrir buffer
		include($this->file);
		$result = ob_get_contents();  //Leer buffer
        ob_end_clean(); // Cerrar buffer
        return $result;
	}
}
?>