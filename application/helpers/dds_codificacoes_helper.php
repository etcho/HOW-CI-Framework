<?php

/**
 * Substitui caracteres especiais por seus respectivos códigos em html
 * @param string $string
 * @return string
 */
function inserir_acentos($string) {
    $a = "ÁÉÍÓÚÝáéíóúýÀÈÌÒÙàèìòùÃÕãõÂÊÎÔÛâêîôûÄËÏÖÜäëïöüÿÇçÑñ";
    $b = array(
        "&Aacute;",
        "&Eacute;",
        "&Iacute;",
        "&Oacute;",
        "&Uacute;",
        "&Yacute;",
        "&aacute;",
        "&eacute;",
        "&iacute;",
        "&oacute;",
        "&uacute;",
        "&yacute;",
        "&Agrave;",
        "&Egrave;",
        "&Igrave;",
        "&Ograve;",
        "&Ugrave;",
        "&agrave;",
        "&egrave;",
        "&igrave;",
        "&ograve;",
        "&ugrave;",
        "&Atilde;",
        "&Otilde;",
        "&atilde;",
        "&otilde;",
        "&Acirc;",
        "&Ecirc;",
        "&Icirc;",
        "&Ocirc;",
        "&Ucirc;",
        "&acirc;",
        "&ecirc;",
        "&icirc;",
        "&ocirc;",
        "&ucirc;",
        "&Auml;",
        "&Euml;",
        "&Iuml;",
        "&Ouml;",
        "&Uuml;",
        "&auml;",
        "&euml;",
        "&iuml;",
        "&ouml;",
        "&uuml;",
        "&yuml;",
        "&Ccedil;",
        "&ccedil;",
        "&Ntilde;",
        "&ntilde;"
    );

    for ($x = 0; $x < count($b); $x++) {
        $string = str_replace($a[$x], $b[$x], $string);
    }
    return $string;
}

/**
 * Substitui caracteres especiais por seus respectivos códigos em html
 * @param string $string
 * @return string
 */
function inserir_acentos2($string) {
    $a = array("Á" => "&Aacute;", "É" => "&Eacute;", "Í" => "&Iacute;", "Ó" => "&Oacute;", "Ú" => "&Uacute;", "Ý" => "&Yacute;", "á" => "&aacute;", "é" => "&eacute;", "í" => "&iacute;", "ó" => "&oacute;", "ú" => "&uacute;", "ý" => "&yacute;", "À" => "&Agrave;", "È" => "&Egrave;", "Ì" => "&Igrave;", "Ò" => "&Ograve;", "Ù" => "&Ugrave;", "à" => "&agrave;", "è" => "&egrave;", "ì" => "&igrave;", "ò" => "&ograve;", "ù" => "&ugrave;", "Ã" => "&Atilde;", "Õ" => "&Otilde;", "ã" => "&atilde;", "õ" => "&otilde;", "Â" => "&Acirc;", "Ê" => "&Ecirc;", "Î" => "&Icirc;", "Õ" => "&Ocirc;", "Û" => "&Ucirc;", "â" => "&acirc;", "ê" => "&ecirc;", "î" => "&icirc;", "ô" => "&ocirc;", "û" => "&ucirc;", "Ä" => "&Auml;", "Ë" => "&Euml;", "Ï" => "&Iuml;", "Ö" => "&Ouml;", "Ü" => "&Uuml;", "ä" => "&auml;", "ë" => "&euml;", "ï" => "&iuml;", "ö" => "&ouml;", "ü" => "&uuml;", "ÿ" => "&yuml;", "Ç" => "&Ccedil;", "ç" => "&ccedil;", "Ñ" => "&Ntilde;", "ñ" => "&ntilde;");
    foreach ($a as $antes => $depois)
        $string = str_replace($antes, $depois, $string);
    return $string;
}

/**
 * Retira os acentos e caracteres especiais de uma string, além de converte-la para lowercase
 * @param string $string
 * @return string
 */
function normalizar($string) {
    $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
    $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
    $permitidos = "abcdefghijklmnopqrstuvwxyz1234567890-";
    $array_permitidos = array();
    for ($i = 0; $i < strlen($permitidos); $i++)
        array_push($array_permitidos, $permitidos[$i]);
    $string = utf8_decode($string);
    $string = strtr($string, utf8_decode($a), $b);
    $string = str_replace(" ", "-", $string);
    $string = str_replace("/", "", $string);
    $string = strtolower($string);
    for ($i = 0; $i < strlen($string); $i++) {
        if (!in_array(substr($string, $i, 1), $array_permitidos))
            $string = str_replace(substr($string, $i, 1), "", $string);
    }
    while (str_replace("--", "-", $string) != $string)
        $string = str_replace("--", "-", $string);
    return utf8_encode($string);
}

/**
 * Converte um nome todo maiúsculo para uma string com as primeiras letras maiúscula ignorando algumas palavras
 * que não devem ser capitalizadas.
 * @param string $string
 * @return string
 */
function capitalizar_nome($string) {
    $ignored = array("de", "da", "das", "dos", "do", "e", "para", "que", "por", "pelo", "pela");
    $nome = array();
    $palavras = explode(" ", $string);
    foreach ($palavras as $palavra) {
        $palavra = mb_strtolower($palavra);
        if (in_array($palavra, $ignored)) {
            $nome[] = $palavra;
        } else {
            $nome[] = ucfirst($palavra);
        }
    }
    return implode(" ", $nome);
}

?>
