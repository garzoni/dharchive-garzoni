<?php use Application\Models\Role; ?>

<?php if ($this->app->hasPermission('edit_roles')) : ?>
    <?php ob_start(); ?>
    <script>
        $(document).ready(function() {
            var table = $('.roles.table');
            table.find('.action').popup({
                position: 'top center'
            });
            table.find('.action.delete').on('click', function() {
                var button = $(this);
                jQuery.post('<?php echo $this->role_index_url . '/delete'; ?>', {
                    'role_id': button.closest('tr').attr('data-id')
                }).done(function(response) {
                    if (response.status === true) {
                        location.reload();
                    }
                });
            });
        });
    </script>
    <?php $this->addSnippet(ob_get_clean()); ?>
<?php endif; ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<?php if ($this->roles) : ?>
<div class="row">
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('app.roles'); ?>
                </h4>
                <?php if ($this->app->hasPermission('create_roles')) : ?>
                    <div class="tools">
                        <a href="<?php echo $this->role_index_url . '/create'; ?>"
                           data-tooltip="<?php echo $this->text->get('role.add'); ?>">
                            <i class="plus icon"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="content">
                <table class="ui compact celled table roles">
                    <thead class="full-width">
                    <tr>
                        <th><?php echo $this->text->get('role.name'); ?></th>
                        <?php if ($this->app->hasPermission('delete_roles')) : ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->roles as $role) : ?>
                        <tr data-id="<?php echo $role['id']; ?>">
                            <td class="link">
                                <a href="<?php echo $this->role_view_url . '/' . $role['id']; ?>">
                                    <?php echo $this->text->get('role.' . $role['code']); ?>
                                </a>
                            </td>
                            <?php if ($this->app->hasPermission('delete_roles')) : ?>
                                <td class="collapsing center aligned">
                                    <?php if ($role['code'] !== Role::ADMIN_ROLE_CODE) : ?>
                                        <a class="action delete">
                                            <i class="remove link icon"></i>
                                        </a>
                                    <?php else : ?>
                                        &nbsp
                                    <?php endif; ?>
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
