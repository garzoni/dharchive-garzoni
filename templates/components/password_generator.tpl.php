<?php ob_start(); ?>
    <script>
        $(document).ready(function() {
            $('#insert-password-button').click(function() {
                var form = $('.user.form'),
                    password = $('#generated-password').text();
                form.find('input[name="password"]').val(password);
                form.find('input[name="password_repeat"]').val(password);
            });
        });
    </script>
<?php $this->addSnippet(ob_get_clean()); ?>

<div class="ui message">
    <div class="header"><?php echo $this->text->get('user.generated_password'); ?></div>
    <p><?php echo $this->text->get('user.message.use_generated_password'); ?>
        <span id="generated-password" style="font-weight: bold;"><?php
            echo $this->random_password;
            ?></span>
    </p>
    <a class="ui basic button" id="insert-password-button">
        <span><?php echo $this->text->get('user.insert_password'); ?></span>
    </a>
</div>
