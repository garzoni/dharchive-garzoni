<!DOCTYPE html>

<html lang="<?php echo $this->request->getLanguage(); ?>">

<head>

<title>
    <?php
        echo isset($this->page_title)
            ? ($this->page_title . ' | ' . $this->config->app_name)
            : $this->config->app_name;
    ?>
</title>

<meta charset="<?php echo $this->config->charset; ?>">
<?php if(isset($this->page_description)) : ?>
    <meta name="description" content="<?php echo $this->page_description; ?>">
<?php endif; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="shortcut icon" href="<?php
    echo $this->app->getImageUrl('favicon.png'); ?>"/>

<?php $this->insertStyleSheets(); ?>

<?php $this->insertScripts('head'); ?>

<?php $this->insertSnippets('head'); ?>

</head>

<body>

<?php $this->insertSnippets('before'); ?>
