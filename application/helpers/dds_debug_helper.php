<?php

/**
 * Escreve no console do browser
 * @param string, object ou array $value
 */
function console_write($value) {
    $value = json_encode($value);
    echo "<script>console.log($value)</script>";
}

/**
 * Executa o print_r involto da tag <pre>
 * @param array $array
 */
function print_r_pre($array) {
    echo '<pre>';
    print_r($array);
    echo '</pre>';
}

?>