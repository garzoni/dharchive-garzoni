
<div id="pagelet-header" class="ui top fixed inverted menu">
    <div class="item main-menu-toggler">
        <i class="icon content"></i>
    </div>
    <a class="header item" href="<?php echo $this->app->getUrl('module'); ?>">
        <?php echo $this->config->app_name; ?>
    </a>
    <div class="right menu">
        <?php
        // $this->include('components/language_menu.tpl.php');
        if ($this->session->isAuthenticated()) :
            $this->include('components/user_menu.tpl.php');
        endif;
        ?>
    </div>
</div>
