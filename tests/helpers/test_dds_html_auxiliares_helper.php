<?php
class test_dds_html_auxiliares_helper extends CodeIgniterUnitTestCase
{
    function __construct($name = '') {
        error_reporting(0);
        parent::__construct($name);
    }
	
    public function teste_breadcrumb_vazio(){
        $breadcrumb = breadcrumb();
        $this->assertEqual($breadcrumb, '<ol class="breadcrumb"><li><a href="'.site_url("inicio").'">Início</a></li></ol>');
    }
    
    public function teste_breadcrumb_com_chave_numerica(){
        $breadcrumb = breadcrumb(array(0 => "teste"));
        $this->assertEqual($breadcrumb, '<ol class="breadcrumb"><li><a href="'.site_url("inicio").'">Início</a></li><li class="active">teste</li></ol>');
    }
    
    public function teste_breadcrumb_padrao(){
        $breadcrumb = breadcrumb(array(site_url("controller/action") => "teste"));
        $this->assertEqual($breadcrumb, '<ol class="breadcrumb"><li><a href="'.site_url("inicio").'">Início</a></li><li><a href="'.  site_url("controller/action").'">teste</a></li></ol>');
    }
    
    public function teste_breadcrumb_padrao_com_mais_itens(){
        $breadcrumb = breadcrumb(array(site_url("controller/action") => "teste", site_url("controller2/action2") => "teste2"));
        $this->assertEqual($breadcrumb, '<ol class="breadcrumb"><li><a href="'.site_url("inicio").'">Início</a></li><li><a href="'.  site_url("controller/action").'">teste</a></li><li><a href="'.  site_url("controller2/action2").'">teste2</a></li></ol>');
    }
}