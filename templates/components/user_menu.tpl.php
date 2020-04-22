
<div class="ui dropdown item user-menu" tabindex="0">
    <i class="user icon"></i>
    <i class="dropdown icon"></i>
    <div class="menu transition hidden" tabindex="-1">
        <div class="header">
            <?php echo $this->session->get('auth_user')->get('full_name'); ?>
        </div>
        <?php foreach ($this->user_menu as $item) : ?>
            <?php
                if (is_null($item)) :
            ?>
                <div class="divider"></div>
            <?php
                    continue;
                endif;
            ?>
            <a class="item" href="<?php echo $item['url']; ?>">
                <?php if (isset($item['icon'])) : ?>
                    <i class="<?php echo $item['icon']; ?> icon"></i>
                <?php endif; ?>
                <?php echo $this->text->get($item['title']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
