<br>
<fieldset>
    <legend>Tabelas a serem geradas</legend>
    <form method="get" action="<?= site_url("code_generator/formulario") ?>">
        <div>
            <input type="checkbox" value="1" id="check_all" onclick="check_all_tables(this)" /> <label for="check_all"><b>Marcar todos</b></label>
        </div>
        <?php foreach ($_SESSION["schema"]["tables"] as $table) { ?>
        <div>
            <input type="checkbox" name="tables[]" value="<?= $table["name"] ?>" id="checkbox_<?= $table["name"] ?>" /> <label for="checkbox_<?= $table["name"] ?>"><?= $table["name"] ?></label>
        </div>
        <?php } ?>
        <br>
        <input type="submit" value="Gerar" />
    </form>
</fieldset>
