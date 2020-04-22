
<div id="main-menu" class="ui compact vertical inverted menu">
<?php foreach ($this->main_menu as $item) : ?>
    <?php if (empty($item)) continue; ?>
    <?php if (isset($item['items'])) : ?>
        <div class="item">
            <div class="title">
                <?php if (isset($item['icon'])) : ?>
                    <i class="<?php echo $item['icon']; ?> icon"></i>
                <?php endif; ?>
                <span><?php echo $this->text->get($item['title']); ?></span>
                <i class="angle left icon"></i>
            </div>
            <div class="content menu">
                <?php foreach ($item['items'] as $subitem) : ?>
                    <?php if (empty($subitem)) continue; ?>
                    <a class="item<?php if ($subitem['active']) echo ' current'; ?>"
                       href="<?php echo $subitem['url']; ?>">
                        <?php echo $this->text->get($subitem['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else : ?>
        <div class="item<?php if ($item['active']) echo ' current'; ?>">
            <a class="title" href="<?php echo $item['url']; ?>">
            <?php if (isset($item['icon'])) : ?>
                <i class="<?php echo $item['icon']; ?> icon"></i>
            <?php endif; ?>
                <span><?php echo $this->text->get($item['title']); ?></span>
            </a>
            <div class="menu"></div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>
