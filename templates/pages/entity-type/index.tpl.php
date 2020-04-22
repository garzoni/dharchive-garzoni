<?php $this->include('layouts/default/begin.tpl.php'); ?>

<?php if ($this->entity_types) : ?>

<div class="row">
    <div class="six wide column">
        <table class="ui compact celled definition table entity-types">
            <thead class="full-width">
            <tr>
                <th><?php echo $this->text->get('entity_type.qualified_name'); ?></th>
            </tr>
            </thead>
            <tbody>
        <?php foreach ($this->entity_types as $entityType) : ?>

            <tr>
                <td class="link">
                    <a href="<?php echo $this->entity_type_view_url
                            . '/' . $entityType['id']; ?>">
                        <?php echo $entityType['qualified_name']; ?>
                    </a>
                </td>
            </tr>

        <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else : ?>
<div class="row">
    <div class="sixteen wide column">
        <?php $this->include('components/messages.tpl.php'); ?>
    </div>
</div>
<?php endif; ?>

<?php $this->include('layouts/default/end.tpl.php'); ?>
