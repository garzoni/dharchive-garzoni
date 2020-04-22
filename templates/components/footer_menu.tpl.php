
<div class="ui centered stackable inverted five column grid">
    <?php foreach ($this->footer_menu as $item) : ?>
        <div class="column">
            <h4 class="ui inverted header">
                <?php if (isset($item['icon'])) : ?>
                    <i class="<?php echo $item['icon']; ?> icon"></i>
                <?php endif; ?>
                <?php echo $this->text->get($item['title']); ?>
            </h4>
            <?php if (isset($item['items'])) : ?>
                <div class="ui inverted link list">
                    <?php foreach ($item['items'] as $subitem) : ?>
                        <?php
                            if (empty($subitem)) {
                                continue;
                            }
                            $class = 'item';
                            if (isset($subitem['active'])
                                && ($subitem['active'] === true)) {
                                $class .= ' active';
                            }
                            $target = '';
                            if (isset($subitem['external'])
                                && ($subitem['external'] === true)) {
                                $target = 'target="_blank"';
                            }
                        ?>
                        <a href="<?php echo $subitem['url']; ?>"
                           class="<?php echo $class; ?>" <?php echo $target; ?>>
                            <?php echo $this->text->get($subitem['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
