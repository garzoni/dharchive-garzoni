<div id="pagelet-footer" class="ui black inverted vertical segment">
    <div class="ui center aligned container">
        <?php if (!empty($this->footer_menu)): ?>
            <?php $this->include('components/footer_menu.tpl.php'); ?>
            <div class="ui inverted section divider"></div>
        <?php endif; ?>
        <div class="ui horizontal inverted small divided list">
            <div class="item">
                &copy; <?php echo date('Y'); ?>
                <?php echo $this->config->owner; ?>
            </div>
            <div class="item">
                <?php echo $this->config->app_name; ?> &bull;
                <?php echo $this->text->get('app.version'); ?>
                <?php echo $this->config->app_version; ?>
            </div>
        </div>
    </div>
</div>
