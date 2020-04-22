
<?php $this->include('components/page_begin.tpl.php'); ?>

<?php $this->include('components/header.tpl.php'); ?>

<div id="main-container">

    <?php $this->include('components/main_menu.tpl.php'); ?>

    <div id="main-content">
        <div class="ui stackable grid">

            <?php if (isset($this->page_title)) : ?>
            <div class="page-heading row">
                <div class="sixteen wide column">
                    <h1 class="title">
                        <?php echo $this->page_title; ?>
                    </h1>
                    <?php $this->include('components/breadcrumbs.tpl.php'); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if($this->session->hasMessages()) : ?>
            <div class="row">
                <div class="sixteen wide column">
                    <div class="ui segment box">
                        <div class="header">
                            <h4 class="title">
                                <?php echo $this->text->get('app.system_message'); ?>
                            </h4>
                            <div class="tools">
                                <i class="close icon link"></i>
                            </div>
                        </div>
                        <div class="content">
                            <?php $this->include('components/messages.tpl.php'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
