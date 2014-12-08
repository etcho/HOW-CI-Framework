<?php
class test_dds_datas_helper extends CodeIgniterUnitTestCase
{
    function __construct($name = '') {
        error_reporting(0);
        parent::__construct($name);
    }
    
    public function teste_data_valida(){
        $this->assertTrue(data_valida("19/09/2014"));
        $this->assertTrue(data_valida("19/09/14"));
        $this->assertTrue(data_valida("9/4/14"));
        $this->assertFalse(data_valida("32/09/2014"));
        $this->assertFalse(data_valida("19/13/2014"));
        $this->assertFalse(data_valida("19/09/2014", "bd"));
        $this->assertFalse(data_valida(""));
        $this->assertTrue(data_valida("", null, true));
    }
    
    public function teste_data_br_to_bd(){
        $this->assertTrue(data_br_to_bd("12/10/1988"), "1988-10-12");
    }
    
    public function teste_data_bd_to_br(){
        $this->assertTrue(data_bd_to_br("1988-10-12"), "12/10/1988");
    }
    
    public function teste_hora_from_datetime(){
        $this->assertEqual(hora_from_datetime("2014-09-19 16:04:00"), "16:04");
    }
    
    public function teste_dia_da_semana(){
        $this->assertEqual(dia_semana("2014-09-22"), "segunda");
        $this->assertEqual(dia_semana("2014-09-10"), "quarta");
        $this->assertEqual(dia_semana("15/02/2009", "br"), "domingo");
        $this->assertEqual(dia_semana("16/02/2009", "br"), "segunda");
    }
    
    public function teste_operacao_com_data(){
        $this->assertEqual(operacao_data("2014-09-22", "+1 year"), "2015-09-22");
        $this->assertEqual(operacao_data("2014-09-22", "-10 days"), "2014-09-12");
        $this->assertEqual(operacao_data("2014-09-22", "+4 months +2 days"), "2015-01-24");
    }
    
    public function teste_hora_float_to_time(){
        $this->assertEqual(hora_float_to_time(1.5), "01:30");
        $this->assertEqual(hora_float_to_time(0.3), "00:18");
        $this->assertEqual(hora_float_to_time(31.033333333333), "31:02");
    }
    
    public function teste_diferenca_entre_horas(){
        $this->assertEqual(diferenca_entre_horas("01:00:00", "07:30:00"), 6.5);
        $this->assertEqual(diferenca_entre_horas("12:00:00", "07:30:10", "time"), "04:29");
        $this->assertEqual(diferenca_entre_horas("23:59:59", "00:00:00", "time"), "23:59");
    }
    
    public function teste_hora_valida() {
        $this->assertTrue(hora_valida("09:48:25"));
        $this->assertTrue(hora_valida("9:1:2"));
        $this->assertTrue(hora_valida("09:48"));
        $this->assertFalse(hora_valida("09:60:25"));
        $this->assertFalse(hora_valida("26:48:25"));
    }
    
    public function teste_datetime_valido(){
        $this->assertTrue(datetime_valido("2014-10-07 09:38:04"));
        $this->assertFalse(datetime_valido("2014-13-07 09:38:04"));
        $this->assertFalse(datetime_valido("2014-10-07 25:38:04"));
    }
    
    public function teste_data_is_entre(){
        $this->assertTrue(data_is_entre("2014-10-24", "2014-05-01", "2014-10-24"));
        $this->assertTrue(data_is_entre("2014-10-24", "2014-10-24", "2014-10-29"));
        $this->assertFalse(data_is_entre("2014-10-24", "2014-05-01", "2014-09-24"));
    }
}