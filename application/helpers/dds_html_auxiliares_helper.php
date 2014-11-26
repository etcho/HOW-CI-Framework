<?php

/**
 * Gera o html do breadcrumb de acordo com o array passado
 * @param array $itens
 * @return string
 */
function breadcrumb($itens = array()) {
    $conteudo = '<ol class="breadcrumb"><li><a href="' . site_url("inicio") . '">Início</a></li>';
    foreach ($itens as $link => $rotulo)
        $conteudo .= strlen($link) == 0 || is_numeric($link) ? '<li class="active">' . $rotulo . '</li>' : '<li><a href="' . $link . '">' . $rotulo . '</a></li>';
    $conteudo .= '</ol>';
    return $conteudo;
}

/**
 * Corta o texto no comprimento passado e gera os links 'mais' e 'menos' qndo necessário
 * @param string $texto
 * @param integer $limite
 * @param boolean $mostrar_link
 * @return string
 */
function leia_mais_automatico($texto, $limite = 250, $mostrar_link = true) {
    $conteudo = "";
    $count = 1;
    $texto_original = $texto;
    $restante = "";
    if ($limite == 0)
        $restante = $texto_original;
    else {
        $texto = str_split($texto);
        foreach ($texto as $letra) {
            if ($count < $limite)
                $conteudo .= $letra;
            elseif ($letra != " ")
                $conteudo .= $letra;
            else {
                $restante = substr($texto_original, $count, strlen($texto_original));
                break;
            }
            $count++;
        }
    }
    $rand = rand(1, 99999999);
    if (!vazio($restante))
        if ($mostrar_link)
            $conteudo = trim($conteudo) . "<span id='reticencias_" . $rand . "'>... <a href='#' onclick='$(\"#reticencias_" . $rand . "\").hide(); $(\"#texto_restante_" . $rand . "\").show(); return false'>[mais]</a></span>" . "<span style='display: none' id='texto_restante_" . $rand . "'> " . $restante . " <a href='#' onclick='$(\"#reticencias_" . $rand . "\").show(); $(\"#texto_restante_" . $rand . "\").hide(); return false'>[menos]</a></span>";
        else
            $conteudo .= "...";
    return $conteudo;
}

/**
 * Retorna uma string contendo várias tags <br> de acordo com a quantidade passada
 * @param string $number
 * @return string
 */
function brs($number = 1){
    return str_repeat("<br/>", $number);
}

?>