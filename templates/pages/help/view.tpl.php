<!DOCTYPE html>

<html lang="<?php echo $this->page_language; ?>">
<?php $this->include('components/head.tpl.php'); ?>

<body>

<?php $this->include('components/navigation_bar.tpl.php'); ?>

<div id="main-content" class="ui page grid">
    <div class="row">
        <div class="sixteen wide column docs">
            <?php echo $this->content; ?>
        </div>
    </div>
</div>

<?php $this->include('components/footer.tpl.php'); ?>

</body>
</html>
