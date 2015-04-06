<?php

class Code_Generator extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library("howcore");
        $this->output->set_template("code_generator");
    }

    /**
     * Renderiza a tela inicial
     */
    function index() {
        $this->load->view("code_generator/index");
    }

    /**
     * Renderiza a tela para a escolha de quais tabelas o gerador irá gerar
     */
    function escolher_tabelas() {
        $this->load->view("code_generator/escolher_tabelas");
    }

    /**
     * Obtem os metadados dos banco, como tabelas, campos, tipos e constraints e armazena isso na sessão
     */
    function ler_schema() {
        $_SESSION["schema"] = array("tables" => array());
        //obtendo as tabelas
        $tables = mysql_query("SELECT * FROM information_schema.tables WHERE table_schema = '" . $this->db->database . "'");
        echo mysql_error();
        while ($table = mysql_fetch_array($tables)) {
            $arr_table = array("name" => $table["TABLE_NAME"], "columns" => array(), "constraints" => array());

            //obtendo as colunas
            $columns = mysql_query("SELECT * FROM information_schema.columns WHERE table_schema = '" . $this->db->database . "' AND table_name = '" . $table["TABLE_NAME"] . "'");
            while ($column = mysql_fetch_array($columns)) {
                $arr_column = array("name" => $column["COLUMN_NAME"], "position" => $column["ORDINAL_POSITION"], "is_nullable" => $column["IS_NULLABLE"] == "YES" ? true : false, "type" => $column["DATA_TYPE"], "max_length" => $column["CHARACTER_MAXIMUM_LENGTH"], "numeric_precision" => $column["NUMERIC_PRECISION"], "numeric_scale" => $column["NUMERIC_SCALE"], "extra" => $column["EXTRA"]);
                $arr_table["columns"][] = $arr_column;
            }

            //obtendo as constraint
            $constraints = mysql_query("SELECT * FROM information_schema.key_column_usage WHERE constraint_schema = '" . $this->db->database . "' AND table_name = '" . $table["TABLE_NAME"] . "' AND constraint_name <> 'PRIMARY'");
            while ($constraint = mysql_fetch_array($constraints)) {
                $arr_constraint = array("column" => $constraint["COLUMN_NAME"], "referenced_table" => $constraint["REFERENCED_TABLE_NAME"], "referenced_column" => $constraint["REFERENCED_COLUMN_NAME"], "unique" => false);
                $arr_table["constraints"][] = $arr_constraint;
            }
            $unique_constraints = mysql_query("SELECT * FROM information_schema.table_constraints tc INNER JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name WHERE tc.constraint_schema = '" . $this->db->database . "' AND tc.table_name = '" . $table["TABLE_NAME"] . "' AND kcu.constraint_schema = '" . $this->db->database . "' AND kcu.table_name = '" . $table["TABLE_NAME"] . "' AND tc.constraint_type = 'UNIQUE'");
            while ($constraint = mysql_fetch_array($unique_constraints)) {
                $exists = false;
                foreach ($arr_table["constraints"] as $i => $const){
                    if (array_key_exists("column", $const) && $const["column"] == $constraint["COLUMN_NAME"]){
                        $arr_table["constraints"][$i]["unique"] = true;
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $arr_constraint = array("column" => $constraint["COLUMN_NAME"], "referenced_table" => "", "referenced_column" => "", "unique" => true);
                    $arr_table["constraints"][] = $arr_constraint;
                }
            }

            $_SESSION["schema"]["tables"][] = $arr_table;
        }

        set_message("sucesso", "Schema lido com sucesso!");
        redirect("code_generator/escolher_tabelas");
    }

    /**
     * Renderiza a tela onde os dados da tabela serão informados antes de serem gerados
     */
    function formulario() {
        $data = array("classes" => array(), "acts_as_tree_permitidos" => array());
        foreach ($_GET["tables"] as $table) {
            $data["classes"][$table] = underscore_to_camel_case($table, true);
            foreach ($_SESSION["schema"]["tables"] as $tbl) {
                if ($tbl["name"] == $table) {
                    $cols = map($tbl["columns"], "name");
                    if (in_array("rgt", $cols) && in_array("lft", $cols) && in_array("lvl", $cols)) {
                        $data["acts_as_tree_permitidos"][] = $table;
                    }
                }
            }
        }

        $this->load->view("code_generator/formulario", $data);
    }

    function gerar_modelo() {
        $result = array("table" => $_GET["table"], "class" => $_GET["class"]);
        $lines = array();

        $classname = $_GET["class"];
        $file = fopen("application/models/" . $classname . ".php", "w");
        $lines[] = "<?php";
        $lines[] = "";
        $lines[] = "class " . $classname . " extends MY_Model{";
        $lines[] = $this->gentab() . "protected \$_table = '" . $_GET["table"] . "';";

        //acts_as_list
        if ($_GET["acts_as_list"] == 1) {
            $str = "public \$acts_as_list";
            if (!isNull($_GET["aal_field"]) || (isset($_GET["aal_scope"]) && !vazio($_GET["aal_scope"]))) {
                $arr_temp = array();
                if (!isNull($_GET["aal_field"])) {
                    $arr_temp[] = "'field' => '" . $_GET["aal_field"] . "'";
                }
                if (isset($_GET["aal_scope"]) && !vazio($_GET["aal_scope"])) {
                    $arr_temp[] = "'scope' => array('" . implode("', '", $_GET["aal_scope"]) . "')";
                }
                $str .= " = array(" . implode(", ", $arr_temp) . ")";
            }
            $lines[] = $this->gentab() . $str . ";";
        }

        //acts_as_tree
        if ($_GET["acts_as_tree"] == 1) {
            $str = "public \$acts_as_tree";
            if (!isNull($_GET["aat_field"]) || !vazio($_GET["aat_order"]) || $_GET["aat_destroy"] == 1) {
                $arr_temp = array();
                if (!isNull($_GET["aat_field"])) {
                    $arr_temp[] = "'field' => '" . $_GET["aat_field"] . "'";
                }
                if (!vazio($_GET["aat_order"])) {
                    $arr_temp[] = "'order' => '" . $_GET["aat_order"] . "'";
                }
                if ($_GET["aat_destroy"] == 1) {
                    $arr_temp[] = "'destroy_dependants' => true";
                }
                $str .= " = array(" . implode(", ", $arr_temp) . ")";
            }
            $lines[] = $this->gentab() . $str . ";";
        }

        //belongs_to
        if (isset($_GET["bt_nomes"]) && !vazio($_GET["bt_nomes"])) {
            $lines[] = $this->gentab() . "public \$belongs_to = array(";
            for ($i = 0; $i < count($_GET["bt_nomes"]); $i++) {
                $lines[] = $this->gentab(2) . "'" . $_GET["bt_nomes"][$i] . "' => array('field' => '" . $_GET["bt_fields"][$i] . "', 'class' => '" . $_GET["bt_classes"][$i] . "')" . ($i < count($_GET["bt_nomes"]) - 1 ? "," : "");
            }
            $lines[] = $this->gentab() . ");";
        }

        //has_many
        if (isset($_GET["hm_tables"]) && !vazio($_GET["hm_tables"])) {
            $lines[] = $this->gentab() . "public \$has_many = array(";
            for ($i = 0; $i < count($_GET["hm_tables"]); $i++) {
                $line = $this->gentab(2) . "'" . $_GET["hm_nomes"][$i] . "' => array('class' => '" . $_GET["hm_classes"][$i] . "', 'field' => '" . $_GET["hm_fields"][$i] . "'";
                if (!vazio($_GET["hm_orders"][$i])) {
                    $line .= ", 'order' => '" . $_GET["hm_orders"][$i] . "'";
                }
                if ($_GET["hm_destroy"][$i] == 1) {
                    $line .= ", 'destroy_dependants' => true";
                }
                $line .= ")";
                if ($i < count($_GET["hm_tables"]) - 1) {
                    $line .= ",";
                }
                $lines[] = $line;
            }
            $lines[] = $this->gentab() . ");";
        }

        //validates
        $cvs_gerados = array();
        if (array_key_exists("validate", $_GET) || array_key_exists("custom_validate_nome", $_GET)) {
            $lines[] = $this->gentab() . "static \$validates = array(";
            if (array_key_exists("validate", $_GET)) {
                $i = 0;
                foreach ($_GET["validate"] as $column => $validates) {
                    $arr_temp = array();
                    foreach ($validates as $validate => $value) {
                        $arr_temp[] = $validate;
                        if (array_key_exists("param_validate", $_GET) && array_key_exists($column, $_GET["param_validate"]) && array_key_exists($validate, $_GET["param_validate"][$column])) {
                            if ($validate == "is_unique" && vazio($_GET["param_validate"][$column][$validate])) {
                                $arr_temp[count($arr_temp) - 1] .= "[" . $column . "]";
                            } else {
                                $arr_temp[count($arr_temp) - 1] .= "[" . $_GET["param_validate"][$column][$validate] . "]";
                            }
                        }
                    }
                    if (array_key_exists("custom_validate_nome", $_GET) && array_key_exists($column, $_GET["custom_validate_nome"])) {
                        foreach ($_GET["custom_validate_nome"][$column] as $cv) {
                            $arr_temp[] = "callback_validation_" . $cv;
                            $cvs_gerados[] = $cv;
                        }
                    }
                    $lines[] = $this->gentab(2) . "array('" . $column . "', '" . $column . "', '" . implode("|", $arr_temp) . "'),";
                    $i++;
                }
            }
            if (array_key_exists("custom_validate_nome", $_GET)) {
                foreach ($_GET["custom_validate_nome"] as $column => $cvs_column) {
                    $arr_temp = array();
                    foreach ($cvs_column as $cvn) {
                        if (!in_array($cvn, $cvs_gerados)) {
                            $arr_temp[] = $cvn;
                        }
                    }
                    if (!vazio($arr_temp)) {
                        $lines[] = $this->gentab(2) . "array('" . $column . "', '" . $column . "', '" . implode("|", $arr_temp) . "'),";
                    }
                }
            }
            if (substr($lines[count($lines) - 1], strlen($lines[count($lines) - 1]) - 1, 1) == ",") {
                $lines[count($lines) - 1] = substr($lines[count($lines) - 1], 0, strlen($lines[count($lines) - 1]) - 1);
            }
            $lines[] = $this->gentab() . ");";
        }

        //custom validates functions
        if (array_key_exists("custom_validate_functionname", $_GET)) {
            foreach ($_GET["custom_validate_functionname"] as $column => $functionnames) {
                $i = 0;
                foreach ($functionnames as $fn) {
                    $lines[] = "";
                    $lines[] = $this->gentab() . "public function validation_" . $fn . " {";
                    $function = explode("{{linebreak}}", $_GET["custom_validate_function"][$column][$i]);
                    foreach ($function as $line) {
                        $lines[] = $this->gentab(2) . $line;
                    }
                    $lines[] = $this->gentab() . "}";
                    $i++;
                }
            }
        }

        //gets e sets
        if (isset($_GET["gets"])) {
            if (!vazio($_GET["gets"])) {
                foreach ($_GET["gets"] as $get) {
                    $lines[] = "";
                    $lines[] = $this->gentab() . "public function get" . underscore_to_camel_case($get, true) . "() {";
                    $lines[] = $this->gentab(2) . "return \$this->get('" . $get . "');";
                    $lines[] = $this->gentab() . "}";
                }
            }
        }
        if (isset($_GET["sets"])) {
            if (!vazio($_GET["sets"])) {
                foreach ($_GET["sets"] as $set) {
                    $lines_temp = array("");
                    $lines_temp[] = $this->gentab() . "public function set" . underscore_to_camel_case($set, true) . "(\$" . $set . ") {";
                    $lines_temp[] = $this->gentab(2) . "\$this->set('" . $set . "', \$" . $set . ");";
                    $lines_temp[] = $this->gentab() . "}";
                    $inserted_in_the_middle = false;
                    for ($i = 0; $i < count($lines); $i++) {
                        if (strpos($lines[$i], "public function get" . underscore_to_camel_case($set, true)) > 0) {
                            array_splice($lines, $i + 3, 0, $lines_temp);
                            $inserted_in_the_middle = true;
                            break;
                        }
                    }
                    if (!$inserted_in_the_middle) {
                        $lines = array_merge($lines, $lines_temp);
                    }
                }
            }
        }

        $lines[] = "}";
        foreach ($lines as $i => $line) {
            fwrite($file, $line . ($i < count($lines) - 1 ? "\n" : ""));
        }
        fclose($file);

        echo json_encode($result);
        exit;
    }

    /**
     * Retorna uma string contendo um determinado número de tabulações para as linhas dos arquivos gerados
     * @param int $count
     * @return string
     */
    function gentab($count = 1) {
        return str_repeat("    ", $count);
    }

}
