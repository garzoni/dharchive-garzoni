<!DOCTYPE html>

<html lang="<?php echo $this->page_language; ?>">
<?php $this->include('components/head.tpl.php'); ?>
<body>

<?php $this->include('components/navigation_bar.tpl.php'); ?>

<div id="main-content" class="ui page grid">
    <div class="row">
        <div class="sixteen wide column">
            <h1><?php echo $this->text->get('app.help'); ?></h1>
        </div>
    </div>
    <div class="row">
        <div class="sixteen wide column">
            <div class="ui list">
            <?php foreach ($this->docs as $url => $title) : ?>
                <div class="item">
                    <i class="circle thin icon"></i>
                    <div class="content">
                        <a href="<?php echo $url; ?>"><?php echo $title; ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->include('components/footer.tpl.php'); ?>

</body>
</html>
