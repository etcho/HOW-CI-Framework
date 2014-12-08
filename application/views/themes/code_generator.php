<script src="<?= base_url() ?>assets/themes/default/js/jquery-1.9.1.min.js"></script>
<script src="<?= base_url() ?>assets/js/helper.js"></script>
<script>
    var schema = <?= json_encode($_SESSION["schema"]) ?>;
    var validation_functions = <?= json_encode(HowCore::validationFunctions()) ?>;

    function check_all_tables(check) {
        $("input[type='checkbox']").not(check).each(function () {
            this.checked = check.checked;
        });
    }

    function check_all_gets(check, table) {
        $("[name='gerar_get_" + table + "[]']").each(function () {
            this.checked = check.checked;
        });
    }

    function check_all_sets(check, table) {
        $("[name='gerar_set_" + table + "[]']").each(function () {
            this.checked = check.checked;
        });
    }

    arrays = ["tables", "classes", "acts_as_list", "aal_fields", "aal_scopes", "acts_as_tree", "aat_fields", "aat_orders", "aat_destroy", "gets", "sets", "bt_nomes", "bt_fields", "bt_tables", "bt_classes", "hm_tables", "hm_fields", "hm_nomes", "hm_classes", "hm_orders", "hm_destroy", "validates", "param_validates", "custom_validates"];
    for (i in arrays) {
        eval("var " + arrays[i] + " = [];");
    }

    function gerar_modelos(is_first) {
        qry = "input[type='hidden'][name='tables[]']";
        if (is_first == null) {
            i = 0;
            $(qry).each(function () {
                tablename = $(this).val();
                tables.push(tablename);
                classes.push($($("input[name='classes[]']")[i]).val());
                acts_as_list.push($("input[name='acts_as_list[]']")[i].checked ? 1 : 0);
                aal_fields.push($($("select[name='aal_fields[]']")[i]).val());
                aal_scopes.push($($("select[name='aal_scopes[]']")[i]).val());
                acts_as_tree.push($("input[name='acts_as_tree[]']")[i].checked ? 1 : 0);
                aat_fields.push($($("select[name='aat_fields[]']")[i]).val());
                aat_orders.push($($("input[name='aat_orders[]']")[i]).val());
                aat_destroy.push($($("select[name='aat_destroy[]']")[i]).val());
                arr_temp = [];
                $("[name='gerar_get_" + tablename + "[]']").each(function () {
                    if (this.checked) {
                        arr_temp.push($(this).val());
                    }
                });
                gets.push(arr_temp);
                arr_temp = [];
                $("[name='gerar_set_" + tablename + "[]']").each(function () {
                    if (this.checked) {
                        arr_temp.push($(this).val());
                    }
                });
                sets.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='bt_nomes[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                bt_nomes.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='bt_fields[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                bt_fields.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='bt_tables[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                bt_tables.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='bt_classes[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                bt_classes.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='hm_tables[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                hm_tables.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='hm_fields[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                hm_fields.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='hm_nomes[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                hm_nomes.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='hm_classes[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                hm_classes.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='hm_orders[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                hm_orders.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name='hm_destroy[]']").each(function () {
                    arr_temp.push($(this).val());
                });
                hm_destroy.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name^='validate[']").each(function () {
                    if (this.checked) {
                        arr_temp.push("&" + $(this).attr("name"));
                        $("#table_" + tablename + " [column='" + $(this).attr("column") + "'][validate='" + $(this).attr("validate") + "'][name^='param_validate[']").each(function () {
                            arr_temp.push("&" + $(this).attr("name") + "=" + $(this).val());
                        });
                    }
                });
                validates.push(arr_temp);
                arr_temp = [];
                $("#table_" + tablename + " [name^='custom_validate_nome']").each(function(){
                    temp = "&" + $(this).attr("name") + "=" + $(this).val();
                    temp += "&" + $(this).closest(".validate").children(".param").children(".function").attr("name") + "=" + $(this).closest(".validate").children(".param").children(".function").val();
                    temp += "&" + $(this).closest(".validate").children(".param").children("textarea").attr("name") + "=" + $(this).closest(".validate").children(".param").children("textarea").val().replace(/\n/g, "{{linebreak}}");
                    arr_temp.push(temp);
                });
                custom_validates.push(arr_temp);
                i++;
            });
            gerar_modelos(true);
        }

        if (is_first == true && tables.length > 0) {
            data = "table=" + tables[0] + "&class=" + classes[0] + "&acts_as_list=" + acts_as_list[0] + "&aal_field=" + aal_fields[0];
            if (aal_scopes.length > 0 && aal_scopes[0] != null) {
                for (i = 0; i < aal_scopes[0].length; i++) {
                    data += "&aal_scope[]=" + aal_scopes[0][i];
                }
            }
            data += "&acts_as_tree=" + acts_as_tree[0] + "&aat_field=" + aat_fields[0] + "&aat_order=" + aat_orders[0] + "&aat_destroy=" + aat_destroy[0];
            if (gets.length > 0) {
                for (i = 0; i < gets[0].length; i++) {
                    data += "&gets[]=" + gets[0][i];
                }
            }
            if (sets.length > 0) {
                for (i = 0; i < sets[0].length; i++) {
                    data += "&sets[]=" + sets[0][i];
                }
            }
            for (i in bt_nomes[0]) {
                data += "&bt_nomes[]=" + bt_nomes[0][i];
            }
            for (i in bt_fields[0]) {
                data += "&bt_fields[]=" + bt_fields[0][i];
            }
            for (i in bt_tables[0]) {
                data += "&bt_tables[]=" + bt_tables[0][i];
            }
            for (i in bt_classes[0]) {
                data += "&bt_classes[]=" + bt_classes[0][i];
            }
            for (i in hm_tables[0]) {
                data += "&hm_tables[]=" + hm_tables[0][i];
            }
            for (i in hm_fields[0]) {
                data += "&hm_fields[]=" + hm_fields[0][i];
            }
            for (i in hm_nomes[0]) {
                data += "&hm_nomes[]=" + hm_nomes[0][i];
            }
            for (i in hm_classes[0]) {
                data += "&hm_classes[]=" + hm_classes[0][i];
            }
            for (i in hm_orders[0]) {
                data += "&hm_orders[]=" + hm_orders[0][i];
            }
            for (i in hm_destroy[0]) {
                data += "&hm_destroy[]=" + hm_destroy[0][i];
            }
            for (i in validates[0]) {
                data += validates[0][i];
            }
            for (i in custom_validates[0]){
                data += custom_validates[0][i];
            }
            $.ajax({
                url: "<?= site_url("code_generator/gerar_modelo/") ?>",
                data: data,
                method: "get",
                dataType: "json",
                success: function (data) {
                    for (i in arrays) {
                        eval(arrays[i] + ".shift();");
                    }
                    $("#log").append("<p>Tabela <i>" + data.table + "</i> gerada (arquivo " + data.class + ".php)</p>");
                    $("#log").scrollTop($("#log p").length * 50);
                    gerar_modelos(true);
                }
            })
        }
    }

    function add_belongs_to(table, nome, field, referenced_table, classe) {
        classe = classe == null && nome != null ? underscore_to_camel_case(nome, true) : classe;
        html = '<div>';
        html += '<a href="#" onclick="$(this).parent().remove(); return false;">[remover]</a> ';
        html += '   <label>nome: </label><input type="text" size="20" value="' + (nome == null ? "" : nome) + '" name="bt_nomes[]" /> &nbsp; ';
        html += '   <label>field: </label><select name="bt_fields[]">';
        tbl = find_table(table);
        for (i = 0; i < tbl.columns.length; i++) {
            html += '       <option value="' + tbl.columns[i].name + '"' + (field == tbl.columns[i].name ? " selected" : "") + '>' + tbl.columns[i].name + '</option>';
        }
        html += '   </select> &nbsp; ';
        html += '   <label>tabela: </label><select name="bt_tables[]" onchange="change_bt_table(this)">';
        for (i = 0; i < schema.tables.length; i++) {
            html += '       <option value="' + schema.tables[i].name + '"' + (referenced_table == schema.tables[i].name ? " selected" : "") + '>' + schema.tables[i].name + '</option>';
        }
        html += '   </select> &nbsp; ';
        html += '   <label>class: </label><input type="text" size="20" value="' + (classe == null ? "" : classe) + '" name="bt_classes[]" /> &nbsp; ';
        html += '</div>';
        $("#table_" + table + " .content_belongs_to").append(html);
    }

    function add_has_many(table, referenced_table, field) {
        html = '<div>';
        html += '<a href="#" onclick="$(this).parent().remove(); return false;">[remover]</a> ';
        html += '   <label>tabela: </label><select name="hm_tables[]" onchange="change_hm_table(this)">';
        for (i = 0; i < schema.tables.length; i++) {
            html += '       <option value="' + schema.tables[i].name + '"' + (referenced_table == schema.tables[i].name ? " selected" : "") + '>' + schema.tables[i].name + '</option>';
        }
        html += '   </select> &nbsp; ';
        html += '   <label>field: </label><select name="hm_fields[]">';
        tbl = find_table(table);
        html += '   </select> &nbsp; ';
        html += '   <label>nome: </label><input type="text" size="20" value="" name="hm_nomes[]" /> &nbsp; ';
        html += '   <label>class: </label><input type="text" size="20" value="" name="hm_classes[]" /> &nbsp; ';
        html += '   <label>order: </label><input type="text" size="20" value="" name="hm_orders[]" /> &nbsp; ';
        html += '   <label>destroy dependants: </label><select name="hm_destroy[]"><?= options_for_select(array("Não", "Sim")) ?></select> &nbsp; ';
        html += '</div>';
        $("#table_" + table + " .content_has_many").append(html);
        change_hm_table($("#table_" + table + " .content_has_many [name='hm_tables[]']:last"));
    }

    function change_bt_table(el) {
        $(el).parent().children("[name='bt_classes[]']").val(underscore_to_camel_case($(el).val(), true));
    }

    function change_hm_table(el) {
        tbl = find_table($(el).val());
        options = "";
        for (i in tbl.columns) {
            options += '<option' + (tbl.columns[i].name == $(el).closest(".table").attr("tablename") + "_id" ? " selected" : "") + ' value="' + tbl.columns[i].name + '">' + tbl.columns[i].name + '</option>';
        }
        $(el).parent().children("[name='hm_fields[]']").html(options);
        $(el).parent().children("[name='hm_nomes[]']").val(tbl.name + "s");
        $(el).parent().children("[name='hm_classes[]']").val(underscore_to_camel_case(tbl.name, true));
    }

    function find_table(name) {
        for (i = 0; i < schema.tables.length; i++) {
            if (schema.tables[i].name == name) {
                return schema.tables[i];
            }
        }
    }

    function toggle_validate(check) {
        $(check).parent().toggleClass("validate_checked");
    }

    function add_custom_validate(table, column) {
        html = '<div class="validate" style="line-height: 19px;" column="' + column + '">';
        html += '<b>nome: </b> callback_validation_<span class="param"><input type="text" size="30" class="nome" name="custom_validate_nome[' + column + '][]"></span> <a href="#" onclick="custom_validate_gerar(this); return false;">[gerar]</a><br>';
        html += '<i>public function</i> validation_<span class="param"><input type="text" size="40" class="function" name="custom_validate_functionname[' + column + '][]"></span> {<br>';
        html += '<span class="param"><textarea name="custom_validate_function[' + column + '][]"></textarea></span><br>';
        html += '}';
        html += '</div>';
        $("#table_" + table + " [column='" + column + "'].content_custom_validates").append(html);
    }
    
    function custom_validate_gerar(link){
        nome = $(link).parent().children(".param").children(".nome").val();
        if (nome.indexOf("[") > -1){
            func = nome.substring(0, nome.indexOf("["));
        } else {
            func = nome;
        }
        shortname = func;
        func += "($" + $(link).parent().attr("column");
        if (nome.indexOf("[") > -1 && nome.indexOf("]") > -1){
            func += ", $segundo_parametro";
        }
        func += ")";
        $(link).parent().children(".param").children(".function").val(func);
        $(link).parent().children(".param").children("textarea").val("if (false){\n    $this->form_validation->set_message('validation_" + shortname + "', 'Mensagem de erro');\n    return false;\n}\nreturn true;");
    }
</script>

<style>
    body{
        padding: 0;
        margin: 0;
    }

    .clear{
        clear: both;
    }

    .alert{
        margin-bottom: 5px;
    }

    .alert-success{
        color: #3FD452;
    }

    .alert-error{
        color: #E13300;
    }

    #log{
        position: fixed;
        max-height: 60px;
        overflow: auto;
        background-color: #EEE;
        border-top: 1px solid #666;
        bottom: 0;
        width: 100%;
        box-shadow: -5px 0px 5px #999;
    }

    #log p{
        margin: 0;
        font-family: monospace;
    }

    .validate{
        float: left;
        font-family: sans-serif;
        font-size: 10px;
        border-radius: 4px;
        padding: 0px 2px;
        margin-right: 2px;
        margin-bottom: 2px;
        background-color: #92dae4;
    }

    .validate_checked{
        background-color: #529aa4;
    }

    .validate .param input, .validate .param textarea{
        font-size: 10px;
        border: none;
        font-family: sans-serif;
    }
    
    .validate .param textarea{
        width: 330px;
        margin-left: 10px;
        height: 85px;
    }

    .nowrap{
        width: 1px;
        white-space: nowrap;
    }
</style>
<h1>Gerador de Modelos</h1>
<?= flash_messages() ?>
<div>
    <strong>Host: </strong><?= $this->db->hostname ?><br>
    <strong>Database: </strong><?= $this->db->database ?><br>
</div>

<?php echo $output ?>