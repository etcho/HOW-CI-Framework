<?php

/**
 * Inicia as constante usadas na paginação de resultados
 * @param integer $resultados_por_pagina
 * @param integer $tamanho_paginacao
 */
function usar_paginacao($resultados_por_pagina = 10, $tamanho_paginacao = 2) {
    define('TAMANHO_PAGINACAO', $tamanho_paginacao);
    define('RESULTADOS_POR_PAGINA', $resultados_por_pagina);
    $_GET["pagina"] = isset($_GET["pagina"]) && $_GET["pagina"] > 0 ? $_GET["pagina"] : 1;
}

/**
 * Retorna o html da paginação
 * @param integer $total_resultados
 * @param integer $pagina
 * @param asd $url
 * @param type $parametros
 * @return string
 */
function paginacao($total_resultados, $pagina, $url, $parametros = "") {
    $paginacao = "";
    if ($total_resultados > RESULTADOS_POR_PAGINA) {
        $exibidas = array();
        for ($pag = 1; $pag <= ceil($total_resultados / RESULTADOS_POR_PAGINA); $pag++) {
            if ($pag >= $pagina - TAMANHO_PAGINACAO && $pag <= $pagina + TAMANHO_PAGINACAO) {
                $exibidas[] = $pag;
                if ($pag == $pagina)
                    $paginacao .= "<li class=\"active\"><a href=\"#\">" . $pag . " <span class=\"sr-only\">(current)</span></a></li>";
                else
                    $paginacao .= "<li><a href=\"" . site_url($url . "/?pagina=" . $pag . $parametros) . "\">" . $pag . " <span class=\"sr-only\">(current)</span></a></li>";
            }
        }
        if ($pagina != 1 && !in_array(1, $exibidas)) {
            $paginacao = "<li class=\"" . ($pagina == 1 ? "disabled" : "") . "\"><a href=\"" . site_url($url . "/?pagina=1" . $parametros) . "\">&laquo;</a></li>" . $paginacao;
        }
        $ultima = ceil($total_resultados / RESULTADOS_POR_PAGINA);
        if (!in_array($ultima, $exibidas) && $pagina < ceil($total_resultados / RESULTADOS_POR_PAGINA)) {
            $paginacao .= "<li><a href=\"" . site_url($url . "/?pagina=" . $ultima . $parametros) . "\">&raquo;</a></li>";
        }
        $paginacao = "<ul class=\"pagination\">" . $paginacao;
        $paginacao .= "</ul>";
    }
    return $paginacao;
}

?>
