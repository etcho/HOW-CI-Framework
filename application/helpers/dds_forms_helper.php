<?php

/**
 * Retorna aquele * pra ser usado nos campos obrigatórios
 * @return string
 */
function obrigatorio() {
    return '<span class="obrigatorio" title="Campo obrigatório">*</span>';
}

/**
 * Retorna o html das options a partir de array para ser usado em selects
 * @param array $options
 * @param string $selecionado
 * @param boolean|string $prompt
 * @return string
 */
function options_for_select($options, $selecionado = "", $prompt = false, $usar_texto_como_valor = false) {
    if ($prompt != false) {
        if (gettype($prompt) == "boolean")
            $conteudo = array(options_for_select(array("null" => "-- Selecione --")));
        else
            $conteudo = array(options_for_select(array("null" => $prompt)));
    }
    foreach ($options as $valor => $texto){
        $valor = $usar_texto_como_valor ? $texto : $valor;
        $conteudo[] = '<option value="' . $valor . '"' . ($valor == $selecionado ? " selected" : "") . '>' . $texto . '</option>';
    }
    return implode("", $conteudo);
}

/**
 * Retorna uma string de options a partir da collection passada. A collection é um array de objetos.
 * @param array $collection
 * @param string $value
 * @param string $label
 * @param string $selecionado
 * @param boolean|string $prompt
 * @return string
 */
function options_for_select_from_collection($collection, $value, $label, $selecionado = "", $prompt = true) {
    foreach ($collection as $registro) {
        $registro = $registro->toArray();
        $options[$registro[$value]] = $registro[$label];
    }
    return options_for_select($options, $selecionado, $prompt);
}

?>
