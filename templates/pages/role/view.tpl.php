<?php if ($this->app->hasPermission('edit_roles')) : ?>
    <?php ob_start(); ?>
    <script>
        $(document).ready(function() {
            var table = $('.permissions.table');
            table.find(':checkbox').on('change', function() {
                var checkbox = $(this);
                jQuery.post('<?php echo $this->role_index_url . '/set-permission'; ?>', {
                    'role_id': <?php echo $this->role['id']; ?>,
                    'permission_id': checkbox.closest('tr').attr('data-id'),
                    'action': (checkbox.is(':checked') ? 'add' : 'remove')
                });
            });
        });
    </script>
    <?php $this->addSnippet(ob_get_clean()); ?>
<?php endif; ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<?php if ($this->permissions) : ?>
<div class="row">
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('app.permissions'); ?>
                </h4>
            </div>
            <div class="content">
                <table class="ui compact celled table permissions">
                    <thead class="full-width">
                    <tr>
                        <th><?php echo $this->text->get('role.permission'); ?></th>
                        <?php if ($this->app->hasPermission('edit_roles')) : ?>
                            <th><?php echo $this->text->get('role.granted'); ?></th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->permissions as $permission) : ?>
                        <tr data-id="<?php echo $permission['id']; ?>">
                            <td><?php echo $this->text->get('permission.' . $permission['code']); ?></td>
                            <?php if ($this->app->hasPermission('edit_roles')) : ?>
                            <td class="collapsing">
                                <div class="ui fitted slider checkbox">
                                    <input type="checkbox"<?php
                                        echo array_key_exists(
                                                $permission['id'],
                                                $this->role_permissions
                                        ) ? ' checked' : '';
                                        echo $this->is_admin_role ? ' disabled' : '';
                                    ?>>
                                    <label></label>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->include('layouts/default/end.tpl.php'); ?>
