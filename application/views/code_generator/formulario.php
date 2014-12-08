<div id="log">
</div>
<form>
    <?php
    if (isset($_SESSION["schema"])) {
        echo brs();
        foreach ($_SESSION["schema"]["tables"] as $table) {
            if (in_array($table["name"], $_GET["tables"])) {
                ?>
                <fieldset id="table_<?= $table["name"] ?>" style="background-color: #FFC" tablename="<?= $table["name"] ?>" class="table">
                    <input type="hidden" name="tables[]" value="<?= $table["name"] ?>" />
                    <legend>tabela <i><?= $table["name"] ?></i></legend>
                    <table>
                        <tr>
                            <td align="right"><label><b>classname: </b></label></td>
                            <td><input type="text" name="classes[]" value="<?= $classes[$table["name"]] ?>" size="25" /></td>
                        </tr>
                        <tr>
                            <td valign="bottom"><input type="checkbox" name="acts_as_list[]" id="acts_as_list_<?= $table["name"] ?>" value="1" /><label for="acts_as_list_<?= $table["name"] ?>"><b>acts_as_list: </b></label></td>
                            <td>
                                <label>field: </label><select name="aal_fields[]"><?= options_for_select(map($table["columns"], "name"), null, "-- Padrão (ordem) --", true) ?></select> &nbsp; 
                                <label>scope: </label><select multiple="multiple" size="3" name="aal_scopes[]"><?= options_for_select(map($table["columns"], "name"), null, false, true) ?></select> &nbsp; 
                            </td>
                        </tr>
                        <tr style="<?= !in_array($table["name"], $acts_as_tree_permitidos) ? "opacity: 0.4;" : "" ?>">
                            <td valign="bottom"><input type="checkbox" <?= in_array($table["name"], $acts_as_tree_permitidos) ? "" : "disabled='disabled'" ?> name="acts_as_tree[]" id="acts_as_tree_<?= $table["name"] ?>" value="1" /><label for="acts_as_tree_<?= $table["name"] ?>"><b>acts_as_tree: </b></label></td>
                            <td>
                                <label>field: </label><select name="aat_fields[]"><?= options_for_select(map($table["columns"], "name"), null, "-- Padrão (parent_id) --", true) ?></select> &nbsp; 
                                <label>order: </label><input type="text" size="15" name="aat_orders[]" placeholder="não obrigatório" /> &nbsp; 
                                <label>destroy dependants: </label><select name="aat_destroy[]"><?= options_for_select(array("Não", "Sim")) ?></select> &nbsp; 
                                <?php if (!in_array($table["name"], $acts_as_tree_permitidos)) { ?>
                                    <small><em>campos rgt, lft e lvl ausentes</em></small>
                                <?php } ?>
                            </td>
                        </tr>
                    </table>

                    <br>
                    <fieldset style="padding-bottom: 23px; background-color: #EEE">
                        <legend>Relacionamentos <i>$belongs_to</i> <a href="#" onclick="add_belongs_to('<?= $table["name"] ?>');
                                return false;">[+ adicionar]</a></legend>
                        <div class="content_belongs_to"></div>
                        <?php foreach ($table["constraints"] as $constraint) { ?>
                            <script> add_belongs_to("<?= $table["name"] ?>", "<?= substr($constraint["column"], strlen($constraint["column"]) - 3, 3) == "_id" ? substr($constraint["column"], 0, strlen($constraint["column"]) - 3) : $constraint["column"] ?>", "<?= $constraint["column"] ?>", "<?= $constraint["referenced_table"] ?>");</script>
                        <?php } ?>
                    </fieldset>
                    <small style="position: absolute; margin-top: -18px; margin-left: 13px;"><em>Os campos terminados com _id já são entendidos automaticamente como relacionamento belongs to, desde que as nomenclaturas estejam corretas. Defina como relacionamento apenas se desejar que fiquem explícitos na classe.</em></small>

                    <br>
                    <fieldset style="padding-bottom: 23px; background-color: #EEE">
                        <legend>Relacionamentos <i>$has_many</i> <a href="#" onclick="add_has_many('<?= $table["name"] ?>');
                                return false;">[+ adicionar]</a></legend>
                        <div class="content_has_many"></div>
                        <?php
                        foreach ($_SESSION["schema"]["tables"] as $tbl) {
                            foreach ($tbl["constraints"] as $const) {
                                if ($const["referenced_table"] == $table["name"]) {
                                    ?>
                                    <script> add_has_many("<?= $table["name"] ?>", "<?= $tbl["name"] ?>", "<?= $const["column"] ?>");</script>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </fieldset>

                    <br>
                    <table border style="background-color: #EEE; border: 1px solid #000; border-collapse: collapse;">
                        <tr>
                            <th class="nowrap"><input type="checkbox" onclick="check_all_gets(this, '<?= $table["name"] ?>')" id="check_all_gets_<?= $table["name"] ?>" /><label for="check_all_gets_<?= $table["name"] ?>">gerar get()</label></th>
                            <th class="nowrap"><input type="checkbox" onclick="check_all_sets(this, '<?= $table["name"] ?>')" id="check_all_sets_<?= $table["name"] ?>" /><label for="check_all_sets_<?= $table["name"] ?>">gerar set()</label></th>
                            <th>coluna</th>
                            <th>tipo</th>
                            <th>length</th>
                            <th>numeric_precision</th>
                            <th>numeric_scale</th>
                            <th>aceita_nulo</th>
                            <th>extra</th>
                        </tr>
                        <?php
                        $colors = array(0, "#EEE");
                        foreach ($table["columns"] as $column) {
                            $colors[0] ++;
                            ?>
                            <tr style="background-color: <?= $colors[($colors[0] % (count($colors) - 1)) + 1] ?>; border-top: 2px solid; border-bottom: 1px dashed;">
                                <td align="center"><input type="checkbox" name="gerar_get_<?= $table["name"] ?>[]" value="<?= $column["name"] ?>" /></td>
                                <td align="center"><input type="checkbox" name="gerar_set_<?= $table["name"] ?>[]" value="<?= $column["name"] ?>" /></td>
                                <td><b><?= $column["name"] ?></b></td>
                                <td><?= $column["type"] ?></td>
                                <td><?= $column["max_length"] ?></td>
                                <td><?= $column["numeric_precision"] ?></td>
                                <td><?= $column["numeric_scale"] ?></td>
                                <td><?= $column["is_nullable"] ? "SIM" : "NÃO" ?></td>
                                <td><?= $column["extra"] ?></td>
                            </tr>
                            <tr style="background-color: <?= $colors[($colors[0] % (count($colors) - 1)) + 1] ?>">
                                <td colspan="2" align="right">
                                    <b>validates</b><br>
                                    <a style="font-size: 12px" href="#" onclick="add_custom_validate('<?= $table["name"] ?>', '<?= $column["name"] ?>'); return false;">[add validate customizado]</a>
                                </td>
                                <td colspan="100">
                                    <?php
                                    foreach (HowCore::validationFunctions() as $validation) {
                                        $checked = false;
                                        $param_value = "";
                                        if (strpos($column["extra"], "auto_increment") === false) {
                                            if ($validation["name"] == "required" && !$column["is_nullable"]) {
                                                $checked = true;
                                            } elseif ($validation["name"] == "is_not_null" && !$column["is_nullable"]) {
                                                $checked = true;
                                            } elseif ($validation["name"] == "integer" && in_array($column["type"], array("int", "bigint", "short", "smallint", "long", "byte", "bit"))) {
                                                $checked = true;
                                            } elseif ($validation["name"] == "decimal" && in_array($column["type"], array("decimal", "double", "float"))) {
                                                $checked = true;
                                            } elseif ($validation["name"] == "data_valida" && $column["type"] == "date") {
                                                $checked = true;
                                                if ($column["is_nullable"]) {
                                                    $param_value = "true";
                                                }
                                            } elseif ($validation["name"] == "max_length" && $column["type"] == "char") {
                                                $checked = true;
                                                $param_value = $column["max_length"];
                                            } elseif ($validation["name"] == "is_unique"){
                                                foreach ($table["constraints"] as $const){
                                                    if ($column["name"] == $const["column"] && $const["unique"] == true){
                                                        $checked = true;
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                        <div class="validate<?= $checked ? " validate_checked" : "" ?>">
                                            <input style="vertical-align: middle" column="<?= $column["name"] ?>" validate="<?= $validation["name"] ?>" type="checkbox" name="validate[<?= $column["name"] ?>][<?= $validation["name"] ?>]" id="check_validate_<?= $table["name"] ?>_<?= $column["name"] ?>_<?= $validation["name"] ?>" onclick="toggle_validate(this)"<?= $checked ? " checked" : "" ?> /><label for="check_validate_<?= $table["name"] ?>_<?= $column["name"] ?>_<?= $validation["name"] ?>"><?= $validation["name"] ?></label>
                                            <?php if (isset($validation["param"])) { ?>
                                                <span class="param">
                                                    (<input title="<?= $validation["param"] ?>" type="text" column="<?= $column["name"] ?>" validate="<?= $validation["name"] ?>" name="param_validate[<?= $column["name"] ?>][<?= $validation["name"] ?>]" value="<?= $param_value ?>" placeholder="<?= $validation["param"] ?>" size="10" />)
                                                </span>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                    <div class="clear"></div>
                                    <div class="content_custom_validates" column="<?= $column["name"] ?>"></div>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                    <small><em>Os métodos gets e sets já são interceptados automaticamente para cada atributo. Marque os checkboxes apenas se quiser que esses métodos fiquem explícitos na classe.</em></small>
                </fieldset><br>
                <?php
            }
        }
        ?>
        <input type="button" id="button_gerar_modelos" value="Gerar Modelos" onclick="gerar_modelos()" />

        <pre>
            <?php //print_r($_SESSION["schema"])  ?>
        </pre>

    <?php } ?>
</form>