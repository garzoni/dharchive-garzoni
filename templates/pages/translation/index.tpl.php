<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('app.translations'); ?>
                </h4>
            </div>
            <div class="content">
                <div class="ui divided relaxed list">
                <?php foreach($this->languages as $code => $name): ?>
                    <div class="item">
                        <i class="<?php echo $code; ?> flag"></i>
                        <a href="<?php echo $this->app->getUrl('controller', 'view/' . $code) ?>">
                            <?php echo $name; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
