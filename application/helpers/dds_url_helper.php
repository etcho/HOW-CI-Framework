<?php

/**
 * Faz um redirect para o item anterior na cadeia de parâmetros da url.
 * Pode ser passado o tipo de mensagem e a mensagem para suprimir o set_message normalmente usado junto com o redirect.
 * @param string $tipo_msg
 * @param string $msg
 */
function redirect_to_parent($tipo_msg = "", $msg = "") {
    if (!vazio($tipo_msg) && !vazio($msg)) {
        set_message($tipo_msg, $msg);
    }

    $segments = get_instance()->uri->segment_array();
    while (is_numeric($segments[count($segments)])) {
        unset($segments[count($segments)]);
    }
    unset($segments[count($segments)]);
    redirect(implode("/", $segments));
}

/**
 * Retorna o url completa do item anterior na cadeia de parâmetros da url.
 * @return string
 */
function url_to_parent() {
    $segments = get_instance()->uri->segment_array();
    while (is_numeric($segments[count($segments)])) {
        unset($segments[count($segments)]);
    }
    unset($segments[count($segments)]);
    return site_url(implode("/", $segments));
}

/**
 * Verifica se o parâmetro passado coincide com a url atual.
 * Se passado true para considerar_url_parcial, verifica se o início coincide.
 * @param string $parametro
 * @param boolean $considerar_url_parcial
 * @return boolean
 */
function isUrlAtual($parametro, $considerar_url_parcial = false) {
    $parametros = explode("/", $parametro);
    array_unshift($parametros, null);
    unset($parametros[0]);
    $segments = get_instance()->uri->segments;
    if ($parametros == $segments || ($parametro == "/" && vazio($segments))) {
        return true;
    } else {
        if (count($parametros) == count($segments) || $considerar_url_parcial) {
            $segmentos_corretos = 0;
            for ($i = 1; $i <= count($parametros); $i++) {
                if (substr($parametros[$i], 0, 1) == ":") {
                    $segmentos_corretos++;
                } elseif (array_key_exists($i, $segments) && $parametros[$i] == $segments[$i]) {
                    $segmentos_corretos++;
                }
            }
            if ($segmentos_corretos == $i - 1) {
                return true;
            }
        }
    }
    return false;
}
