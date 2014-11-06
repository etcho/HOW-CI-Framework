<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Erros extends CI_Controller {

    /**
     * Renderiza a tela de login
     */
    function acesso_negado() {
		$this->output->set_template("blank");
        $this->load->view('erros/acesso_negado');
    }

}

?>