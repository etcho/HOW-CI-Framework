<?php

/**
 * Dado um $array de arrays ou de objetos, temos:
 * Para $array de arrays é retornado um novo array contendo cada valor da posição $key no $array.
 * Para $array de objeto é retornado um novo array contendo cada valor no atributo $key do $array.
 * @example map(array('id' => 5, 'id' => 3), 'id') retornará array(5, 3)
 * @param array $array
 * @param string $key
 * @return array
 */
function map($array, $key) {
    return array_map(create_function('$elemento', ' return gettype($elemento) == "array" ? $elemento["' . $key . '"] : $elemento->get("' . $key . '"); '), $array);
}

/**
 * Retorna o primeiro elemento de um array
 * @param array $array
 * @return type
 */
function array_first($array){
    if (!vazio($array)){
        return $array[0];
    }
    return null;
}

?>