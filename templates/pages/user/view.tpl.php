<?php if ($this->app->hasPermission('edit_users')) : ?>
    <?php ob_start(); ?>
    <script>
        $(document).ready(function() {
            var table = $('.roles.table');
            table.find(':checkbox').on('change', function() {
                var checkbox = $(this);
                jQuery.post('<?php echo $this->user_index_url . '/set-role'; ?>', {
                    'user_id': <?php echo $this->user['id']; ?>,
                    'role_id': checkbox.closest('tr').attr('data-id'),
                    'action': (checkbox.is(':checked') ? 'add' : 'remove')
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
                    <?php echo $this->text->get('app.details'); ?>
                </h4>
                <div class="tools">
                    <a href="<?php echo $this->user_index_url . '/change-password/' . $this->user['id']; ?>"
                       data-tooltip="<?php echo $this->text->get('user.change_password'); ?>">
                        <i class="key icon"></i>
                    </a>
                    <a href="<?php echo $this->user_index_url . '/update/' . $this->user['id']; ?>"
                       data-tooltip="<?php echo $this->text->get('user.edit_profile'); ?>">
                        <i class="setting icon"></i>
                    </a>
                </div>
            </div>
            <div class="content">
                <table class="ui single line table details">
                    <tbody>
                        <tr>
                            <td><?php echo $this->text->get('user.username'); ?></td>
                            <td><?php echo $this->user['username']; ?></td>
                        </tr>
                        <tr>
                            <td><?php echo $this->text->get('user.email'); ?></td>
                            <td class="link">
                                <a href="mailto:<?php echo $this->user['email']; ?>">
                                    <?php echo $this->user['email']; ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo $this->text->get('user.registration_time'); ?></td>
                            <td><?php echo $this->user['registration_time']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('app.roles'); ?>
                </h4>
            </div>
            <div class="content">
            <table class="ui compact celled definition table roles">
                <thead class="full-width">
                <tr>
                    <th><?php echo $this->text->get('user.assigned'); ?></th>
                    <th><?php echo $this->text->get('user.role'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->roles as $role) : ?>
                    <tr data-id="<?php echo $role['id']; ?>">
                        <td class="collapsing">
                            <div class="ui fitted slider checkbox">
                                <input type="checkbox"<?php
                                    echo array_key_exists($role['id'], $this->user_roles)
                                        ? ' checked' : '';
                                    echo (!$this->app->hasPermission('edit_users'))
                                        ? ' disabled' : '';
                                    ?>>
                                <label></label>
                            </div>
                        </td>
                        <td><?php echo $this->text->get('role.' . $role['code']); ?></td>
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
