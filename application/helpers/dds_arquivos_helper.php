<?php

/**
 * "Cospe" um arquivo para download
 * @param string $file
 * @return boolean
 */
function send_file($file) {
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        return true;
    } else
        return false;
}

function remover_arquivo($arquivo) {
    return file_exists($arquivo) ? unlink($arquivo) : false;
}

/**
 * Retorna uma string contendo o conteudo do arquivo incluído
 * @param string $arquivo
 * @param array $params
 * @return string
 */
function carregar_arquivo($arquivo, $params = array()) {
    ob_start();
    include($arquivo);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

?>
