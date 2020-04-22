<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="six wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('role.new'); ?>
                </h4>
            </div>
            <div class="content">
                <form class="ui form role" method="post">
                    <div class="field">
                        <label for="role-code">
                            <?php echo $this->text->get('role.code'); ?>
                        </label>
                        <input name="code" id="role-code">
                    </div>
                    <button class="ui primary button">
                        <?php echo $this->text->get('app.create'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
