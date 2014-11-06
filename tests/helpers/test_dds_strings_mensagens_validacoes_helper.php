<?php
class test_dds_strings_mensagens_validacoes_helper extends CodeIgniterUnitTestCase
{
    function __construct($name = '') {
        error_reporting(0);
        parent::__construct($name);
    }
    
    public function teste_underscore_to_camel_case(){
        $this->assertEqual(underscore_to_camel_case("teste"), "teste");
        $this->assertEqual(underscore_to_camel_case("teste", true), "Teste");
        $this->assertEqual(underscore_to_camel_case("teste_mimimi"), "testeMimimi");
        $this->assertEqual(underscore_to_camel_case("teste_mimimi", true), "TesteMimimi");
        $this->assertEqual(underscore_to_camel_case(""), "");
    }
    
    public function teste_formatar_cpf(){
        $this->assertEqual(formatar_string("12345678910", "cpf"), "123.456.789-10");
        $this->assertEqual(formatar_string("1234567", "cpf"), "1234567");
    }
    
    public function teste_is_null(){
        $this->assertTrue(isNull(null));
        $this->assertTrue(isNull("null"));
        $this->assertTrue(isNull("{{null}}"));
    }
    
    public function teste_vazio(){
        $this->assertTrue(vazio(""));
        $this->assertTrue(vazio("     "));
        $this->assertTrue(vazio("   \n  \n  \n"));
    }
    
    public function teste_camel_case_to_underscore(){
        $this->assertEqual(camel_case_to_underscore("Usuario"), "usuario");
        $this->assertEqual(camel_case_to_underscore("UsuarioPrincipal"), "usuario_principal");
        $this->assertEqual(camel_case_to_underscore("UsuarioPrincipalId"), "usuario_principal_id");
        $this->assertEqual(camel_case_to_underscore("usuarioPrincipalId"), "usuario_principal_id");
    }
}