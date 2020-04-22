<?php
    use Application\Core\Type\Json\Schema as JsonSchema;
?>

<?php ob_start(); ?>
<script>
    // Initialize the editor with a JSON schema
    var editor = new JSONEditor(document.getElementById('editor_holder'), {
        iconlib: 'semantic',
        disable_collapse: false,
        disable_edit_json: true,
        disable_properties: true,
        schema: JSON.parse('<?php echo $this->schema; ?>'),
        choiceList: {
            url: '<?php echo $this->value_list_url . '/get'; ?>'
        }
    });
    document.getElementById('submit').addEventListener('click', function() {
        console.log(JSON.stringify(editor.getValue()));
        var errors = editor.validate();
        if (errors.length) {
            console.log(errors);
        }
    });
</script>

<?php $this->addSnippet(ob_get_clean()); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <h1><?php echo $this->qualified_name; ?></h1>
    </div>
</div>
<div class="row">
    <div class="ten wide column">
        <h3>JSON Schema</h3>
        <div class="ui segment">
            <pre><code class="language-json"><?php
                $schema = new JsonSchema($this->schema);
                echo $schema->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            ?></code></pre>
        </div>
    </div>
    <div class="six wide column">
        <h3>Generated Form</h3>
        <div class="ui segment">
            <div id="editor_holder" class="ui entity-properties form preview"></div>
            <button id="submit" class="ui primary button">
                <?php echo $this->text->get('app.save'); ?>
            </button>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
