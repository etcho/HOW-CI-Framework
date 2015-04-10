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
