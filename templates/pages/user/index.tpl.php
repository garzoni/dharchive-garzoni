<?php if ($this->app->hasPermission('edit_users')) : ?>
    <?php ob_start(); ?>
    <script>
        $(document).ready(function() {
            var table = $('.users.table');
            table.find(':checkbox').on('change', function() {
                var checkbox = $(this);
                jQuery.post('<?php echo $this->user_index_url . '/set-status'; ?>', {
                    'user_id': checkbox.closest('tr').attr('data-id'),
                    'is_active': (checkbox.is(':checked') ? 1 : 0)
                });
            });
        });
    </script>
    <?php $this->addSnippet(ob_get_clean()); ?>
<?php endif; ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<?php if ($this->users) : ?>
<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('app.users'); ?>
                </h4>
                <?php if ($this->app->hasPermission('create_users')) : ?>
                <div class="tools">
                    <a href="<?php echo $this->user_index_url . '/create'; ?>"
                       data-tooltip="<?php echo $this->text->get('user.add'); ?>">
                        <i class="plus icon"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="content">
                <table class="ui compact celled table users">
                    <thead class="full-width">
                    <tr>
                        <th><?php echo $this->text->get('user.username'); ?></th>
                        <th><?php echo $this->text->get('user.name'); ?></th>
                        <th><?php echo $this->text->get('user.email'); ?></th>
                        <th><?php echo $this->text->get('user.registration_time'); ?></th>
                        <?php if ($this->app->hasPermission('edit_users')) : ?>
                            <th><?php echo $this->text->get('user.active'); ?></th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->users as $user) : ?>
                        <tr data-id="<?php echo $user['id']; ?>">
                            <td class="link">
                                <a href="<?php echo $this->user_view_url . '/' . $user['id']; ?>">
                                    <?php echo $user['username']; ?>
                                </a>
                            </td>
                            <td><?php echo $user['full_name']; ?></td>
                            <td class="link">
                                <a href="mailto:<?php echo $user['email']; ?>">
                                    <?php echo $user['email']; ?>
                                </a>
                            </td>
                            <td><?php echo $user['registration_time']; ?></td>
                            <?php if ($this->app->hasPermission('edit_users')) : ?>
                                <td class="collapsing center aligned">
                                    <div class="ui fitted slider checkbox">
                                        <input type="checkbox"<?php
                                        if ($user['is_active']) { echo ' checked'; }?>>
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
