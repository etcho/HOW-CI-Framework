<?php

/**
 * Retorna um array contendo os campos da tabela passada
 * @param string $tabela
 * @return array
 */
function fields_of($tabela) {
    $CI = get_instance();
    $fields = mysql_query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".$CI->db->database."' AND TABLE_NAME = '".$tabela."'");
    $list = array();
    while ($field = mysql_fetch_array($fields))
        $list[] = $field["COLUMN_NAME"];
    return $list;
}

?>
