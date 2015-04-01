<?php

/**
 * Retorna um array contendo os campos da tabela passada
 * @param string $tabela
 * @return array
 */
function fields_of($tabela) {
    $cache = HowCore::getCachedFunction("fields_of", func_get_args());
    if (!($cache instanceof NullValue)){
        return $cache;
    }
    
    $CI = get_instance();
    $results = $CI->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $CI->db->database . "' AND TABLE_NAME = '" . $tabela . "'")->result();
    $list = array();
    foreach ($results as $row) {
        $list[] = $row->COLUMN_NAME;
    }
    
    HowCore::setCachedFunction("fields_of", func_get_args(), $list);
    return $list;
}

?>
