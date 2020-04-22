<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('user.new'); ?>
                </h4>
            </div>
            <div class="content">
                <form class="ui form user" method="post">
                    <div class="required field">
                        <label><?php echo $this->text->get('user.username'); ?></label>
                        <input name="username" value="<?php
                            echo $this->submitted_data['username'] ?? ''; ?>">
                    </div>
                    <div class="required field">
                        <label><?php echo $this->text->get('user.email'); ?></label>
                        <input name="email" value="<?php
                        echo $this->submitted_data['email'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('user.first_name'); ?></label>
                        <input name="first_name" value="<?php
                        echo $this->submitted_data['first_name'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('user.last_name'); ?></label>
                        <input name="last_name" value="<?php
                        echo $this->submitted_data['last_name'] ?? ''; ?>">
                    </div>
                    <div class="required field">
                        <label><?php echo $this->text->get('user.password'); ?></label>
                        <input type="password" name="password" value="<?php
                        echo $this->submitted_data['password'] ?? ''; ?>">
                    </div>
                    <div class="required field">
                        <label><?php echo $this->text->get('user.password_repeat'); ?></label>
                        <input type="password" name="password_repeat" value="<?php
                        echo $this->submitted_data['password_repeat'] ?? ''; ?>">
                    </div>
                    <button class="ui primary button">
                        <?php echo $this->text->get('app.create'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('app.suggestion'); ?>
                </h4>
                <div class="tools">
                    <i class="close icon link"></i>
                </div>
            </div>
            <div class="content">
                <?php $this->include('components/password_generator.tpl.php'); ?>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
