<?php

/**
 * Converte algo do tipo algo_do_tipo para algoDoTipo
 * @param string $string
 * @param boolean $capitalizeFirstCharacter
 * @return string
 */
function underscore_to_camel_case($string, $capitalizeFirstCharacter = false) {
    $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    if (!$capitalizeFirstCharacter && strlen($str) > 0) {
        $str[0] = strtolower($str[0]);
    }
    return gettype($str) == "string" ? $str : "";
}

/**
 * Converte algo do tipo AlgoDoTipo para algo_do_tipo
 * @param string $string
 * @return string
 */
function camel_case_to_underscore($string) {
    $str = "";
    for ($i = 0; $i < strlen($string); $i++) {
        if ($i > 0 && strtolower($string[$i]) != $string[$i]) {
            $str .= "_";
        }
        $str .= strtolower($string[$i]);
    }
    return $str;
}

/**
 * Formata a string passada de acordo com o tipo de máscara passada
 * @param string $string
 * @param string $tipo
 * @return string
 */
function formatar_string($string, $tipo) {
    switch ($tipo) {
        case "cpf":
            if (strlen($string) != 11)
                return $string;
            else
                return substr($string, 0, 3) . "." . substr($string, 3, 3) . "." . substr($string, 6, 3) . "-" . substr($string, 9, 2);
            break;
        case "cnpj":
            if (strlen($string) != 14)
                return $string;
            else
                return substr($string, 0, 2) . "." . substr($string, 2, 3) . "." . substr($string, 5, 3) . "/" . substr($string, 8, 4) . "-" . substr($string, 12, 2);
            break;
        case "cep":
            if (strlen($string) != 8)
                return $string;
            else
                return substr($string, 0, 5) . "-" . substr($string, 5, 3);
            break;
        case "telefone":
            if (strlen($string) != 10)
                return $string;
            else
                return "(" . substr($string, 0, 2) . ") " . substr($string, 2, 4) . "-" . substr($string, 6, 4);
            break;
    }
}

/**
 * Retorna o html de cada tipo de mensagem customizada de informação
 * @param string $tipo
 * @param string $texto
 * @return string
 */
function custom_message($tipo, $texto) {
    switch ($tipo) {
        case "sucesso":
            $tipo = "success";
            break;
        case "erro":
            $tipo = "danger";
            break;
        case "alerta":
            $tipo = "warning";
            break;
        default:
            $tipo = "info";
            break;
    }
    $conteudo = '<div class="alert alert-' . $tipo . ' alert-dismissible" role="alert">' . $texto . '</div>';
    return $conteudo;
}

/**
 * Retorna o html das mensagens para serem exibidas nas páginas, tanto de formulário quanto mensagens flash
 * @return string
 */
function flash_messages() {
    $CI = get_instance();
    $CI->load->library("form_validation");
    $conteudo = '';
    $sucesso = $CI->session->userdata("msg_sucesso");
    $erro = $CI->session->userdata("msg_erro");
    $alerta = $CI->session->userdata("msg_alerta");
    $info = $CI->session->userdata("msg_info");
    if ($sucesso)
        $conteudo .= custom_message("sucesso", $sucesso);
    if ($erro)
        $conteudo .= custom_message("erro", $erro);
    if ($alerta)
        $conteudo .= custom_message("alerta", $alerta);
    if ($info)
        $conteudo .= custom_message("info", $info);
    $CI->session->unset_userdata(array("msg_sucesso", "msg_erro", "msg_alerta", "msg_info"));
    $conteudo .= validation_errors();
    return $conteudo;
}

/**
 * Coloca na sessão as mensagens de flash para serem obtidas através da function mensagens() posteriormente
 * @param string $tipo
 * @param string $mensagem
 */
function set_message($tipo, $mensagem) {
    $tipo = substr($tipo, 0, 4) == "msg_" ? $tipo : "msg_" . $tipo;
    get_instance()->session->set_userdata(array($tipo => $mensagem));
}

/**
 * Verifica se uma variável é nula, inclusive se seu conteúdo for "null"
 * @param type $var
 * @return boolean
 */
function isNull($var) {
    if ($var === null) {
        return true;
    } elseif ($var === "null" || $var === "{{null}}") {
        return true;
    }
    return false;
}

/**
 * Retorna se a string passada é vazia ou se o array informado é vazio. Verifica também se um texto somente com enters é vazio
 * @param string $string
 * @return boolean
 */
function vazio($string) {
    switch (gettype($string)) {
        case "array":
            return empty($string);
            break;
        default:
            return strlen(trim(str_replace("\n", "", $string))) == 0;
            break;
    }
}

/**
 * Transforma um array de string em uma string separada por ', ' e ' e ' para a última posição
 * @param array $array
 * @return string
 */
function array_humanizado($array) {
    $string = '';
    for ($i = 0; $i < count($array); $i++) {
        if (count($array) > 1 && $i == count($array) - 1) {
            $string.= ' e ' . $array[$i];
        } else {
            $string .= ($i > 0 ? ', ' : '' ) . $array[$i];
        }
    }
    echo $string;
}

/**
 * Verifica se a string informada possui o padrão de um hash MD5
 * @param string $md5
 * @return bool
 */
function isValidMd5($md5 = '') {
    return strlen($md5) == 32 && ctype_xdigit($md5);
}

/**
 * Retorna a string passada repetida x vezes
 * @param string $string
 * @param int $number
 * @return string
 */
function repeat_string($string, $number = 2) {
    $return = "";
    for ($i = 0; $i <= $number; $i++) {
        $return += $string;
    }
    return $string;
}

?>