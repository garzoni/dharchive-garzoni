<?php

namespace Application;

?>

<!DOCTYPE html>

<html lang="<?php echo $this->page_language; ?>">
<?php $this->include('components/head.tpl.php'); ?>
<body>

<?php $this->include('components/navigation_bar.tpl.php'); ?>

<div id="main-content" class="ui page grid">
    <div class="row">
        <div class="sixteen wide column">
            <h1><?php echo ($this->action === 'create')
                    ? $this->text->get('collection.add')
                    : $this->text->get('collection.update'); ?></h1>
        </div>
    </div>
    <?php if ($this->session->hasMessages()) : ?>
    <div class="row">
        <div class="sixteen wide column">
            <?php $this->include('components/messages.tpl.php'); ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="row">
        <div class="six wide column">
            <div class="ui segment">
                <form class="ui form user" method="post">
                    <div class="field <?php if ($this->action === 'update') echo ' disabled'; ?>">
                        <label><?php echo $this->text->get('collection.code'); ?></label>
                        <input type="text" name="code" value="<?php
                            echo $this->submitted_data['code'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('collection.description'); ?></label>
                        <input type="text" name="description" value="<?php
                        echo $this->submitted_data['description'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('collection.type'); ?></label>
                        <input type="text" name="type" value="<?php
                        echo $this->submitted_data['type'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('collection.material'); ?></label>
                        <input type="text" name="material" value="<?php
                        echo $this->submitted_data['material'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('collection.start_date'); ?></label>
                        <input type="text" name="start_date" value="<?php
                        echo $this->submitted_data['start_date'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('collection.end_date'); ?></label>
                        <input type="text" name="end_date" value="<?php
                        echo $this->submitted_data['end_date'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('collection.page_count'); ?></label>
                        <input type="text" name="page_count" value="<?php
                        echo $this->submitted_data['page_count'] ?? ''; ?>">
                    </div>
                    <div class="field">
                        <label><?php echo $this->text->get('collection.geo_origin'); ?></label>
                        <input type="text" name="geo_origin" value="<?php
                        echo $this->submitted_data['geo_origin'] ?? ''; ?>">
                    </div>
                    <button class="ui primary button" type="submit">
                        <?php echo $this->text->get('app.save'); ?>
                    </button>
                </form>
            </div>
        </div>
        <div class="six wide column"></div>
    </div>
</div>

<?php $this->include('components/footer.tpl.php'); ?>

</body>
</html>
