
<?php
    $i = 0;
    $n = count($this->breadcrumbs);
    if ($n > 0) :
?>
    <div class="ui small breadcrumb">
        <?php foreach ($this->breadcrumbs as $item) : ?>
            <?php
                $i++;
                if (empty($item)) {
                    continue;
                }
            ?>
            <?php if ($item['active'] || empty($item['url'])) : ?>
                <div class="section<?php if ($item['active']) echo ' active'; ?>">
                    <?php echo $this->text->get($item['title']); ?>
                </div>
            <?php else : ?>
                <a class="section" href="<?php echo $item['url']; ?>">
                    <?php echo $this->text->get($item['title']); ?>
                </a>
            <?php endif; ?>
            <?php if ($i < $n) : ?>
                <i class="right angle icon divider"></i>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php
    endif;
    unset($i, $n);
?>
