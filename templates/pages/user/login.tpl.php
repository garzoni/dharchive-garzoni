<?php ob_start(); ?>

<style>
    body {
        background-image: url('/assets/img/ughi-pianta.jpg');
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center center;
    }
    #login-form-container {
        height: 100%;
        margin: 0;
        background-color: rgba(0, 0, 0, 0.5);
    }
    #login-form-container .ui.divider {
        color: white;
    }
    h1 {
        font-size: 4em;
        font-weight: normal;
        margin-bottom: 1em;
        color: white;
    }
</style>

<?php $this->addSnippet(ob_get_clean(), 'head'); ?>

<?php $this->include('components/page_begin.tpl.php'); ?>

<div class="ui middle aligned center aligned grid" id="login-form-container">
    <div class="column" style="max-width: 450px;">
        <h1>Garzoni</h1>
        <form class="ui large form" method="post" action="<?php echo $this->form_handler ?>">
            <input type="hidden" name="action" value="login" />
            <div class="ui stacked segment">
                <div class="field">
                    <div class="ui left icon input">
                        <i class="user icon"></i>
                        <input name="login-uid" placeholder="<?php
                               echo $this->text->get('home.label.login_uid');
                               ?>">
                    </div>
                </div>
                <div class="field">
                    <div class="ui left icon input">
                        <i class="lock icon"></i>
                        <input type="password" name="password"
                               placeholder="<?php
                               echo $this->text->get('home.label.password');
                               ?>">
                    </div>
                </div>
                <div class="ui fluid large grey submit button">
                    <?php echo $this->text->get('home.button.log_in'); ?>
                </div>
            </div>
            <div class="ui error message"></div>
            <div class="ui horizontal divider">
                Or
            </div>
            <div id="guest-login-button" class="ui labeled icon button">
                Continue as a Guest
                <i class="user outline icon"></i>
            </div>
        </form>
    </div>
</div>

<?php $this->insertScripts('body'); ?>

<script>
    $(document).ready(function() {
        var guestLoginButton = $('#guest-login-button'),
            errorMessages = JSON.parse('<?php echo json_encode($this->session->getMessages('error'));?>'),
            loginForm = $('.ui.form');
        guestLoginButton.on('click', function () {
            loginForm.find('input[name="login-uid"]').val('guest');
            loginForm.find('input[name="password"]').val('q87I2AXRpt4I');
            loginForm.submit();
        });
        loginForm.form({
            fields: {
                email: {
                    identifier: 'login-uid',
                    rules: [{
                        type: 'empty',
                        prompt: 'Please, enter your username or e-mail.'
                    }]
                },
                password: {
                    identifier: 'password',
                    rules: [{
                        type: 'empty',
                        prompt: 'Please, enter your password.'
                    }]
                }
            }
        });
        if (errorMessages.length) {
            loginForm.addClass('error');
            loginForm.form('add errors', errorMessages)
        }
    });
</script>

<?php $this->include('components/page_end.tpl.php'); ?>
