<?php

class MY_Form_validation extends CI_Form_validation {

    public function __construct() {
        parent::__construct();
        $this->_error_prefix = '<div class="alert alert-danger alert-dismissible" role="alert">';
        $this->_error_suffix = '</div>';
    }

    public function error_string($prefix = '', $suffix = '') {
        // No errrors, validation passes!
        if (count($this->_error_array) === 0) {
            return '';
        }

        if ($prefix == '') {
            $prefix = $this->_error_prefix;
        }

        if ($suffix == '') {
            $suffix = $this->_error_suffix;
        }

        // Generate the error string
        $str = '';
        foreach ($this->_error_array as $val) {
            if ($val != '') {
                $str .= $val . "<br>";
            }
        }

        return $prefix . $str . $suffix;
    }

    public function get_error_array() {
        return $this->_error_array;
    }

    public function reset_error_array() {
        $this->_error_array = array();
    }

    public function append_error_array($field, $error) {
        $this->_error_array[$field] = $error;
    }

    private function _execute_version_3($row, $rules, $postdata, $cycles) {
        // If the $_POST data is an array we will run a recursive call
        if (is_array($postdata)) {
            foreach ($postdata as $key => $val) {
                $this->_execute($row, $rules, $val, $key);
            }

            return;
        }

        // If the field is blank, but NOT required, no further tests are necessary
        $callback = FALSE;
        if (!in_array('required', $rules) && ($postdata === NULL OR $postdata === '')) {
            // Before we bail out, does the rule contain a callback?
            foreach ($rules as &$rule) {
                if (is_string($rule)) {
                    if (strncmp($rule, 'callback_', 9) === 0) {
                        $callback = TRUE;
                        $rules = array(1 => $rule);
                        break;
                    }
                } elseif (is_callable($rule)) {
                    $callback = TRUE;
                    $rules = array(1 => $rule);
                    break;
                }
            }

            if (!$callback) {
                return;
            }
        }

        // Isset Test. Typically this rule will only apply to checkboxes.
        if (($postdata === NULL OR $postdata === '') && !$callback) {
            if (in_array('isset', $rules, TRUE) OR in_array('required', $rules)) {
                // Set the message type
                $type = in_array('required', $rules) ? 'required' : 'isset';

                // Check if a custom message is defined
                if (isset($this->_field_data[$row['field']]['errors'][$type])) {
                    $line = $this->_field_data[$row['field']]['errors'][$type];
                } elseif (isset($this->_error_messages[$type])) {
                    $line = $this->_error_messages[$type];
                } elseif (FALSE === ($line = $this->CI->lang->line('form_validation_' . $type))
                        // DEPRECATED support for non-prefixed keys
                        && FALSE === ($line = $this->CI->lang->line($type, FALSE))) {
                    $line = 'The field was not set';
                }

                // Build the error message
                $message = $this->_build_error_msg($line, $this->_translate_fieldname($row['label']));

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }
            }

            return;
        }

        // --------------------------------------------------------------------
        // Cycle through each rule and run it
        foreach ($rules as $rule) {
            $_in_array = FALSE;

            // We set the $postdata variable with the current data in our master array so that
            // each cycle of the loop is dealing with the processed data from the last cycle
            if ($row['is_array'] === TRUE && is_array($this->_field_data[$row['field']]['postdata'])) {
                // We shouldn't need this safety, but just in case there isn't an array index
                // associated with this cycle we'll bail out
                if (!isset($this->_field_data[$row['field']]['postdata'][$cycles])) {
                    continue;
                }

                $postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
                $_in_array = TRUE;
            } else {
                // If we get an array field, but it's not expected - then it is most likely
                // somebody messing with the form on the client side, so we'll just consider
                // it an empty field
                $postdata = is_array($this->_field_data[$row['field']]['postdata']) ? NULL : $this->_field_data[$row['field']]['postdata'];
            }

            // Is the rule a callback?
            $callback = $callable = FALSE;
            if (is_string($rule)) {
                if (strpos($rule, 'callback_') === 0) {
                    $rule = substr($rule, 9);
                    $callback = TRUE;
                }
            } elseif (is_callable($rule)) {
                $callable = TRUE;
            } elseif (is_array($rule) && isset($rule[0], $rule[1]) && is_callable($rule[1])) {
                // We have a "named" callable, so save the name
                $callable = $rule[0];
                $rule = $rule[1];
            }

            // Strip the parameter (if exists) from the rule
            // Rules can contain a parameter: max_length[5]
            $param = FALSE;
            if (!$callable && preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
                $rule = $match[1];
                $param = $match[2];
            }

            // Call the function that corresponds to the rule
            if ($callback OR $callable !== FALSE) {
                if ($callback) {
                    if (!method_exists($this->CI, $rule) && isset($_POST["_current_class"]) && !method_exists($_POST["_current_class"], $rule)) {
                        log_message('debug', 'Unable to find callback validation rule: ' . $rule);
                        $result = FALSE;
                    } else {
                        if (isset($_POST["_current_class"]) && method_exists($_POST["_current_class"], $rule)) {
                            eval('$instance = new ' . $_POST["_current_class"] . '();');
                            $result = $instance->$rule($postdata, $param);
                        } else {
                            // Run the function and grab the result
                            $result = $this->CI->$rule($postdata, $param);
                        }
                    }
                } else {
                    $result = is_array($rule) ? $rule[0]->{$rule[1]}($postdata) : $rule($postdata);

                    // Is $callable set to a rule name?
                    if ($callable !== FALSE) {
                        $rule = $callable;
                    }
                }

                // Re-assign the result to the master data array
                if ($_in_array === TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                }

                // If the field isn't required and we just processed a callback we'll move on...
                if (!in_array('required', $rules, TRUE) && $result !== FALSE) {
                    continue;
                }
            } elseif (!method_exists($this, $rule)) {
                // If our own wrapper function doesn't exist we see if a native PHP function does.
                // Users can use any native PHP function call that has one param.
                if (function_exists($rule)) {
                    // Native PHP functions issue warnings if you pass them more parameters than they use
                    $result = ($param !== FALSE) ? $rule($postdata, $param) : $rule($postdata);

                    if ($_in_array === TRUE) {
                        $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                    } else {
                        $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                    }
                } else {
                    log_message('debug', 'Unable to find validation rule: ' . $rule);
                    $result = FALSE;
                }
            } else {
                $result = $this->$rule($postdata, $param);

                if ($_in_array === TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                }
            }

            // Did the rule test negatively? If so, grab the error.
            if ($result === FALSE) {
                // Callable rules might not have named error messages
                if (!is_string($rule)) {
                    return;
                }

                // Check if a custom message is defined
                if (isset($this->_field_data[$row['field']]['errors'][$rule])) {
                    $line = $this->_field_data[$row['field']]['errors'][$rule];
                } elseif (!isset($this->_error_messages[$rule])) {
                    if (FALSE === ($line = $this->CI->lang->line('form_validation_' . $rule))
                            // DEPRECATED support for non-prefixed keys
                            && FALSE === ($line = $this->CI->lang->line($rule, FALSE))) {
                        $line = $this->CI->lang->line('form_validation_error_message_not_set') . '(' . $rule . ')';
                    }
                } else {
                    $line = $this->_error_messages[$rule];
                }

                // Is the parameter we are inserting into the error message the name
                // of another field? If so we need to grab its "field label"
                if (isset($this->_field_data[$param], $this->_field_data[$param]['label'])) {
                    $param = $this->_translate_fieldname($this->_field_data[$param]['label']);
                }

                // Build the error message
                $message = $this->_build_error_msg($line, $this->_translate_fieldname($row['label']), $param);

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }

                return;
            }
        }
    }

    function _execute_version_2($row, $rules, $postdata, $cycles) {
        // If the $_POST data is an array we will run a recursive call
        if (is_array($postdata)) {
            foreach ($postdata as $key => $val) {
                $this->_execute($row, $rules, $val, $cycles);
                $cycles++;
            }

            return;
        }

        // --------------------------------------------------------------------
        // If the field is blank, but NOT required, no further tests are necessary
        $callback = FALSE;
        if (!in_array('required', $rules) AND is_null($postdata)) {
            // Before we bail out, does the rule contain a callback?
            if (preg_match("/(callback_\w+(\[.*?\])?)/", implode(' ', $rules), $match)) {
                $callback = TRUE;
                $rules = (array('1' => $match[1]));
            } else {
                return;
            }
        }

        // --------------------------------------------------------------------
        // Isset Test. Typically this rule will only apply to checkboxes.
        if (is_null($postdata) AND $callback == FALSE) {
            if (in_array('isset', $rules, TRUE) OR in_array('required', $rules)) {
                // Set the message type
                $type = (in_array('required', $rules)) ? 'required' : 'isset';

                if (!isset($this->_error_messages[$type])) {
                    if (FALSE === ($line = $this->CI->lang->line($type))) {
                        $line = 'The field was not set';
                    }
                } else {
                    $line = $this->_error_messages[$type];
                }

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']));

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }
            }

            return;
        }

        // --------------------------------------------------------------------
        // Cycle through each rule and run it
        foreach ($rules As $rule) {
            $_in_array = FALSE;

            // We set the $postdata variable with the current data in our master array so that
            // each cycle of the loop is dealing with the processed data from the last cycle
            if ($row['is_array'] == TRUE AND is_array($this->_field_data[$row['field']]['postdata'])) {
                // We shouldn't need this safety, but just in case there isn't an array index
                // associated with this cycle we'll bail out
                if (!isset($this->_field_data[$row['field']]['postdata'][$cycles])) {
                    continue;
                }

                $postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
                $_in_array = TRUE;
            } else {
                $postdata = $this->_field_data[$row['field']]['postdata'];
            }

            // --------------------------------------------------------------------
            // Is the rule a callback?
            $callback = FALSE;
            if (substr($rule, 0, 9) == 'callback_') {
                $rule = substr($rule, 9);
                $callback = TRUE;
            }

            // Strip the parameter (if exists) from the rule
            // Rules can contain a parameter: max_length[5]
            $param = FALSE;
            if (preg_match("/(.*?)\[(.*)\]/", $rule, $match)) {
                $rule = $match[1];
                $param = $match[2];
            }

            // Call the function that corresponds to the rule
            if ($callback === TRUE) {
                if (!method_exists($this->CI, $rule) && isset($_POST["_current_class"]) && !method_exists($_POST["_current_class"], $rule)) {
                    continue;
                }

                if (isset($_POST["_current_class"]) && method_exists($_POST["_current_class"], $rule)) {
                    eval('$instance = new ' . $_POST["_current_class"] . '();');
                    $result = $instance->$rule($postdata, $param);
                } else {
                    // Run the function and grab the result
                    $result = $this->CI->$rule($postdata, $param);
                }

                // Re-assign the result to the master data array
                if ($_in_array == TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }

                // If the field isn't required and we just processed a callback we'll move on...
                if (!in_array('required', $rules, TRUE) AND $result !== FALSE) {
                    continue;
                }
            } else {
                if (!method_exists($this, $rule)) {
                    // If our own wrapper function doesn't exist we see if a native PHP function does.
                    // Users can use any native PHP function call that has one param.
                    if (function_exists($rule)) {
                        $result = $rule($postdata);

                        if ($_in_array == TRUE) {
                            $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                        } else {
                            $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                        }
                    } else {
                        log_message('debug', "Unable to find validation rule: " . $rule);
                    }

                    continue;
                }

                $result = $this->$rule($postdata, $param);

                if ($_in_array == TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }
            }

            // Did the rule test negatively?  If so, grab the error.
            if ($result === FALSE) {
                if (!isset($this->_error_messages[$rule])) {
                    if (FALSE === ($line = $this->CI->lang->line($rule))) {
                        $line = 'Unable to access an error message corresponding to your field name.';
                    }
                } else {
                    $line = $this->_error_messages[$rule];
                }

                // Is the parameter we are inserting into the error message the name
                // of another field?  If so we need to grab its "field label"
                if (isset($this->_field_data[$param]) AND isset($this->_field_data[$param]['label'])) {
                    $param = $this->_translate_fieldname($this->_field_data[$param]['label']);
                }

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']), $param);

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }

                return;
            }
        }
    }

    /**
     * Executes the Validation routines
     * ** Alterada para reconhecer os validations feitos no próprio modelo **
     * @access	private
     * @param	array
     * @param	array
     * @param	mixed
     * @param	integer
     * @return	mixed
     */
    protected function _execute($row, $rules, $postdata = NULL, $cycles = 0) {
        if (CI_VERSION >= 3) {
            return $this->_execute_version_3($row, $rules, $postdata, $cycles);
        } else {
            return $this->_execute_version_2($row, $rules, $postdata, $cycles);
        }
    }

    public function set_rules_version_3($field, $label = '', $rules = array(), $errors = array()) {
        // No reason to set rules if we have no POST data
        // or a validation array has not been specified
        if (count($_POST) == 0) {
            return $this;
        }

        // If an array was passed via the first parameter instead of individual string
        // values we cycle through it and recursively call this function.
        if (is_array($field)) {
            foreach ($field as $row) {
                // Houston, we have a problem...
                if (!isset($row['field'], $row['rules'])) {
                    continue;
                }

                // If the field label wasn't passed we use the field name
                $label = isset($row['label']) ? $row['label'] : $row['field'];

                // Add the custom error message array
                $errors = (isset($row['errors']) && is_array($row['errors'])) ? $row['errors'] : array();

                // Here we go!
                $this->set_rules($row['field'], $label, $row['rules'], $errors);
            }

            return $this;
        }

        // No fields? Nothing to do...
        if (!is_string($field) OR $field === '') {
            return $this;
        } elseif (!is_array($rules)) {
            // BC: Convert pipe-separated rules string to an array
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            } else {
                return $this;
            }
        }

        // If the field label wasn't passed we use the field name
        $label = ($label === '') ? $field : $label;

        $indexes = array();

        // Is the field name an array? If it is an array, we break it apart
        // into its components so that we can fetch the corresponding POST data later
        if (($is_array = (bool) preg_match_all('/\[(.*?)\]/', $field, $matches)) === TRUE) {
            sscanf($field, '%[^[][', $indexes[0]);

            for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
                if ($matches[1][$i] !== '') {
                    $indexes[] = $matches[1][$i];
                }
            }
        }

        // Build our master array
        $this->_field_data[$field] = array(
            'field' => $field,
            'label' => $label,
            'rules' => $rules,
            'errors' => $errors,
            'is_array' => $is_array,
            'keys' => $indexes,
            'postdata' => NULL,
            'error' => ''
        );

        return $this;
    }

    public function set_rules_version_2($field, $label = '', $rules = '') {
        // No reason to set rules if we have no POST data
        if (count($_POST) == 0) {
            return $this;
        }

        // If an array was passed via the first parameter instead of indidual string
        // values we cycle through it and recursively call this function.
        if (is_array($field)) {
            foreach ($field as $row) {
                // Houston, we have a problem...
                if (!isset($row['field']) OR ! isset($row['rules'])) {
                    continue;
                }

                // If the field label wasn't passed we use the field name
                $label = (!isset($row['label'])) ? $row['field'] : $row['label'];

                // Here we go!
                $this->set_rules($row['field'], $label, $row['rules']);
            }
            return $this;
        }

        // No fields? Nothing to do...
        if (!is_string($field) OR ! is_string($rules) OR $field == '') {
            return $this;
        }

        // If the field label wasn't passed we use the field name
        $label = ($label == '') ? $field : $label;

        // Is the field name an array?  We test for the existence of a bracket "[" in
        // the field name to determine this.  If it is an array, we break it apart
        // into its components so that we can fetch the corresponding POST data later
        if (strpos($field, '[') !== FALSE AND preg_match_all('/\[(.*?)\]/', $field, $matches)) {
            // Note: Due to a bug in current() that affects some versions
            // of PHP we can not pass function call directly into it
            $x = explode('[', $field);
            $indexes[] = current($x);

            for ($i = 0; $i < count($matches['0']); $i++) {
                if ($matches['1'][$i] != '') {
                    $indexes[] = $matches['1'][$i];
                }
            }

            $is_array = TRUE;
        } else {
            $indexes = array();
            $is_array = FALSE;
        }

        // Build our master array
        $this->_field_data[$field] = array(
            'field' => $field,
            'label' => $label,
            'rules' => $rules,
            'is_array' => $is_array,
            'keys' => $indexes,
            'postdata' => NULL,
            'error' => ''
        );

        return $this;
    }

    public function set_rules($field, $label = '', $rules = array(), $errors = array()) {
        if (CI_VERSION >= 3) {
            return $this->set_rules_version_3($field, $label, $rules, $errors);
        } else {
            $rules = $rules == array() ? "" : $rules;
            return $this->set_rules_version_2($field, $label, $rules);
        }
    }

    /**
     * Adiciona a validação que verifica se o campo é nulo usando a function isNull() como genérica para os validates
     * @param string $str
     * @return boolean
     */
    public function is_not_null($str) {
        return !isNull($str);
    }

    /**
     * Adiciona a validação data_valida como genérica para os validates
     * @param string $str
     * @param boolean $aceita_vazio
     * @return boolean
     */
    public function data_valida($str, $aceita_vazio = 'false') {
        $aceita_vazio = $aceita_vazio == "true";
        return data_valida($str, "bd", $aceita_vazio);
    }

    /**
     * Adiciona a validação para ver se a data é maior que hoje como genérica para os validates
     * @param string $str
     * @return boolean
     */
    public function not_data_futura($str) {
        return !data1_maior_que_data2($str, hoje());
    }

    /**
     * Adiciona a validação para ver se a data não anterior a x dias como genérica para os validates
     * @param string $str
     * @param int $dias_pra_tras
     * @return boolean
     */
    public function minimo_dias_anteriores($str, $dias_pra_tras) {
        if (data1_maior_que_data2(operacao_data(hoje(), "-" . $dias_pra_tras . " days"), $str)) {
            return false;
        }
        return true;
    }

    /**
     * Valida se o valor do campo está compreendido no intervalo passado.
     * O intervalo é passado no formato 1..6 (minimo..maximo)
     * @param string $valor
     * @param string $intervalo
     * @return boolean
     */
    public function valor_entre($valor, $intervalo) {
        list($minimo, $maximo) = explode("..", $intervalo);
        return $valor >= $minimo && $valor <= $maximo;
    }

    /**
     * Valida se a uma hora é menor que outra
     * @param string $hora1
     * @param string $campo_hora2
     * @return boolean
     */
    public function hora1_maior_hora2($hora1, $campo_hora2) {
        return !data1_maior_que_data2($hora1, $_POST[$campo_hora2]);
    }

    /**
     * Valida se uma data é menor que outra
     * @param string $data1
     * @param string $campo_data2
     * @return boolean
     */
    public function data1_maior_data2($data1, $campo_data2) {
        return !data1_maior_que_data2($data1, $_POST[$campo_data2]);
    }

    /**
     * Sobrescrita do validate para não precisar informar a tabela no field e funcionar também para o update e não só para o insert
     * @param string $str
     * @param string $field
     * @return boolean
     */
    public function is_unique($str, $field) {
        $field = explode('.', $field);
        if (count($field) == 1 && array_key_exists("_object", $_POST)) {
            $table = $_POST["_object"]->_tablename();
            $field = $field[0];
        } else {
            list($table, $field) = $field;
        }
        $where = "" . $field . " = '" . $str . "'";
        if (array_key_exists("_object", $_POST) && $_POST["_object"]->isPersisted()) {
            $where .= " AND id <> '" . $_POST["_object"]->getId() . "'";
        }
        $query = $this->CI->db->limit(1)->get_where($table, $where);
        return $query->num_rows() === 0;
    }

}

?>