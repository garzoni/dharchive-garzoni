
<div class="ui dropdown item language-menu" tabindex="0">
    <i class="<?php echo $this->request->getLanguage(); ?> flag"></i>
    <?php echo $this->language_menu[$this->request->getLanguage()]['title']; ?>
    <i class="dropdown icon"></i>
    <div class="menu transition hidden" tabindex="-1">
        <?php foreach ($this->language_menu as $code => $properties) : ?>
            <?php if ($code === $this->request->getLanguage()) continue; ?>
            <a class="item" href="<?php echo $properties['url']; ?>">
                <i class="<?php echo $code; ?> flag"></i>
                <?php echo $properties['title']; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
