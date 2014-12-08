<?php

/**
 * Converte uma data do tipo 10/05/2000 para 2000-05-10
 * @param date $data
 * @return string
 */
function data_br_to_bd($data) {
    return vazio($data) ? "" : substr($data, 6, 4) . "-" . substr($data, 3, 2) . "-" . substr($data, 0, 2);
}

/**
 * Converte uma data do tipo dd/mm/2000 para mm/dd/2000
 * @param date $data
 * @return string
 */
function data_br_to_en($data) {
    return vazio($data) ? "" : substr($data, 3, 2) . "/" . substr($data, 0, 2) . "/" . substr($data, 6, 4);
}

/**
 * Converte uma data do tipo 2000-05-10 para 10/05/2000
 * @param date $data
 * @return string
 */
function data_bd_to_br($data) {
    return vazio($data) ? "" : substr($data, 8, 2) . "/" . substr($data, 5, 2) . "/" . substr($data, 0, 4);
}

/**
 * Converte algo do tipo "2012-03-22 11:14:30" para "22/03/2012 às 11:14"
 * @param datetime $datetime
 * @return string
 */
function datetime_to_br($datetime) {
    return data_bd_to_br($datetime) . " às " . hora_from_datetime($datetime);
}

/**
 * Verifica se a data passada é válida
 * @param date $data
 * @param string $padrao
 * @return boolean
 */
function data_valida($data, $padrao = "br", $aceita_vazio = false) {
    if ($aceita_vazio && vazio($data)){
        return true;
    }
    if ($padrao == "br") {
        $data = explode("/", $data);
        if (count($data) == 3) {
            return checkdate($data[1] + 0, $data[0] + 0, $data[2] + 0) == 1;
        }
    } else {
        $data = explode("-", $data);
        if (count($data) == 3) {
            return checkdate($data[1] + 0, $data[2] + 0, $data[0] + 0) == 1;
        }
    }
    return false;
}

/**
 * Retorna a hora de um campo datetime passado
 * @param date $data
 * @return string
 */
function hora_from_datetime($data) {
    return substr($data, 11, 5);
}

/**
 * Retorna a diferença em horas entre as duas horas passadas
 * @param time $inicial
 * @param time $final
 * @return float
 */
function diferenca_entre_horas($inicial, $final, $retorno = "horas") {
    $inicial = strlen($inicial) == 5 ? $inicial . ":00" : $inicial;
    $final = strlen($final) == 5 ? $final . ":00" : $final;
    $inicial = explode(":", $inicial);
    $final = explode(":", $final);
    $segundos_inicial = $inicial[2] + ($inicial[1] * 60) + ($inicial[0] * 60 * 60);
    $segundos_final = $final[2] + ($final[1] * 60) + ($final[0] * 60 * 60);
    $diferenca = abs($segundos_final - $segundos_inicial);
    $horas = bcdiv($diferenca, 60 * 60, 0);
    $minutos = bcdiv($diferenca - ($horas * 60 * 60), 60, 0);
    $segundos = $diferenca - ($horas * 60 * 60) - ($minutos * 60);
    $horas = strlen($horas) == 1 ? "0" . $horas : $horas;
    $minutos = strlen($minutos) == 1 ? "0" . $minutos : $minutos;
    $segundos = strlen($segundos) == 1 ? "0" . $segundos : $segundos;
    $dif = $horas . ":" . $minutos . ":" . $segundos;
    if ($retorno == "horas") {
        $dif = explode(':', $dif);
        return $dif[0] + ($dif[1] / 60) + ($dif[2] / 60 / 60);
    } else {
        return substr($dif, 0, 5);
    }
}

/**
 * Retorna o dia da semana de uma determinada data
 * @param date $data
 * @param string $padrao
 * @return string
 */
function dia_semana($data, $padrao = "bd") {
    if ($padrao == "br")
        $data = data_br_to_bd($data);
    $dias = array("sábado", "domingo", "segunda", "terça", "quarta", "quinta", "sexta");
    if (is_numeric(substr($data, 5, 2)) && is_numeric(substr($data, 8, 2)) && is_numeric(substr($data, 0, 4))) {
        $w = date("w", mktime(0, 0, 0, substr($data, 5, 2), substr($data, 8, 2), substr($data, 0, 4)));
        $w = $w == 6 ? 0 : $w + 1;
        return $dias[$w];
    }
    return false;
}

/**
 * Executa operações do tipo "+3 years" ou "-5 days" na data passada
 * @param date $data
 * @param string $operacao
 * @return date
 */
function operacao_data($data, $operacao) {
    $timestamp = strtotime($data);
    return date("Y-m-d", strtotime($operacao, $timestamp));
}

/**
 * Converte uma data tipo 5.5 para 05:30
 * @param float $hora
 * @return string
 */
function hora_float_to_time($hora) {
    list($inteiro) = explode('.', $hora);
    $decimal = $hora - $inteiro;
    $minutos = abs(round(60 * $decimal));
    if ($inteiro >= 0 && $decimal >= 0) {
        $inteiro = strlen($inteiro) == 1 ? "0" . $inteiro : $inteiro;
    } else {
        $inteiro = strlen($inteiro) == 2 ? "-0" . $inteiro * -1 : $inteiro;
    }
    $minutos = strlen($minutos) == 1 ? "0" . $minutos : $minutos;
    return $inteiro . ":" . $minutos;
}

/**
 * Retorna a quantidade de dias entre duas datas
 * @param date $data1
 * @param date $data2
 * @param boolean $conta_com_ultima_data
 * @return integer
 */
function dias_entre_datas($data1, $data2, $conta_com_ultima_data = false) {
    $time_inicial = strtotime($data1);
    $time_final = strtotime($data2);
    $diferenca = $time_final - $time_inicial;
    $dias = (int) floor($diferenca / (60 * 60 * 24));
    if ($conta_com_ultima_data) {
        $dias += 1;
    }
    return $dias;
}

/**
 * Verifica se a data1 é maior que a data2
 * @param date $data1
 * @param date $data2
 * @return boolean
 */
function data1_maior_que_data2($data1, $data2) {
    $data1 = strtotime($data1);
    $data2 = strtotime($data2);
    return ($data1 > $data2);
}

/**
 * Retorna um array com todas as datas compreendidas entre a data_inicio e a data_fim junto com as datas extras para
 * formar a semana. Da data_inicio voltamos até encontrar 1 domingo e da data_fim avançamos até encontrar 1 sábado.
 * @param date $data_inicio
 * @param date $data_fim
 * @return array
 */
function data_buscar_semanas_completas($data_inicio, $data_fim) {
    $datas = array();
    $data_loop = $data_inicio;
    //Volto no tempo até que a data_inicio seja domingo, ou seja, primeiro dia da semana
    while (true) {
        list($ano, $mes, $dia) = explode("-", $data_loop);
        if (dia_semana($ano . "-" . $mes . "-" . $dia) == "domingo") {
            break;
        }
        $data_loop = operacao_data($data_loop, "-1 day");
        array_unshift($datas, $data_loop);
    }
    $data_loop = $data_inicio;
    //Preencho as datas desde a data_inicio até a data_fim
    while (true) {
        $datas[] = $data_loop;
        if ($data_loop == $data_fim) {
            break;
        }
        $data_loop = operacao_data($data_loop, '+1 day');
    }
    $data_loop = $data_fim;
    //Avanço no tempo até que a data_fim seja sábado, ou seja, último dia da semana
    while (true) {
        list($ano, $mes, $dia) = explode("-", $data_loop);
        if (dia_semana($ano . "-" . $mes . "-" . $dia) == "sábado") {
            break;
        }
        $data_loop = operacao_data($data_loop, "+1 day");
        $datas[] = $data_loop;
    }
    return $datas;
}

/**
 * Verifica se a hora passada está no formato hh:mm:ss e é uma hora válida
 * @param time $hora
 * @return boolean
 */
function hora_valida($hora) {
    list($hora, $minuto, $segundo) = explode(":", $hora);
    if (!in_array($hora, range(0, 23)) || !in_array($minuto, range(0, 59)) || !in_array($segundo, range(0, 59))) {
        return false;
    }
    return true;
}

/**
 * Verifica se o datetime passado é válido
 * @param datetime $datetime
 * @return boolean
 */
function datetime_valido($datetime) {
    list($data, $hora) = explode(" ", $datetime);
    if (!data_valida($data, "bd")) {
        return false;
    }
    if (!hora_valida($hora)) {
        return false;
    }
    return true;
}

/**
 * Retorna a data de hoje
 * @param string $padrao
 * @return string
 */
function hoje($padrao = "bd") {
    return $padrao == "bd" ? date("Y-m-d") : date("d/m/Y");
}

/**
 * Retorna o datetime deste momento
 * @return string
 */
function now() {
    return date("Y-m-d H:i:s");
}

/**
 * Retorna algo do tipo '10 minutos atrás' em relação ao datetime passado
 * @param datetime $datetime
 * @return string
 */
function tempo_relativo($datetime){
	$timestamp = mktime(substr($datetime, 11, 2), substr($datetime, 14, 2), substr($datetime, 17, 2), substr($datetime, 5, 2), substr($datetime, 8, 2), substr($datetime, 0, 4));
	$segundos = strtotime("+0 minutes") - $timestamp;
	if ($segundos < 60) // < 1 minuto
		$retorno = $segundos == 1 ? "1 segundo" : $segundos." segundos";
	elseif ($segundos < 3600){ // < 1 hora
		$minutos = floor($segundos / 60);
		$retorno = $minutos == 1 ? "1 minuto" : $minutos." minutos";
	}
	elseif ($segundos < 86400){ // < 1 dia
		$horas = floor($segundos / 60 / 60);
		$retorno = $horas == 1 ? "1 hora" : $horas." horas";
	}
	elseif ($segundos < 2592000){ // < 1 mes
		$dias = floor($segundos / 60 / 60 / 24);
		$retorno = $dias == 1 ? "1 dia" : $dias." dias";
	}
	elseif ($segundos < 31536000){ // < 1 ano
		$meses = floor($segundos / 60 / 60 / 24 / 30);
		$retorno = $meses == 1 ? "1 mês" : $meses." meses";
	}
	else{
		$anos = floor($segundos / 60 / 60 / 24 / 30 / 12);
		$retorno = $anos == 1 ? "1 ano" : $anos." anos";
	}
	return $retorno." atrás";
}

/**
 * Verifica se uma data está contida entre duas outras datas
 * @param date $data
 * @param date $periodo1
 * @param date $periodo2
 * @return boolean
 */
function data_is_entre($data, $periodo1, $periodo2){
    $data = strtotime($data);
    $periodo1 = strtotime($periodo1);
    $periodo2 = strtotime($periodo2);
    return $data >= $periodo1 && $data <= $periodo2;
}

?>
