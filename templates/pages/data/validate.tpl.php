
<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">By Category</h4>
            </div>
            <div class="content">
                <div class="ui middle aligned relaxed divided list">
                    <div class="item">
                        <div class="content">
                            <a href="<?php echo $this->app->getUrl('action', 'pages'); ?>">Pages</a>
                        </div>
                    </div>
                    <div class="item">
                        <div class="content">
                            <a href="<?php echo $this->app->getUrl('action', 'segments'); ?>">Segments</a>
                        </div>
                    </div>
                    <div class="item">
                        <div class="content">
                            <a href="<?php echo $this->app->getUrl('action', 'contracts'); ?>">Contracts</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
