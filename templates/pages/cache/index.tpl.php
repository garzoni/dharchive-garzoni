<?php

$htmlOptions = [
    'table_attributes' => ['class' => 'ui compact celled table'],
    'include_table_header' => false,
];

?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('cache.general_information'); ?>
                </h4>
            </div>
            <div class="content">
                <?php echo $this->general_info->toHtml($htmlOptions); ?>
            </div>
        </div>
    </div>
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('cache.cache_information'); ?>
                </h4>
            </div>
            <div class="content">
                <?php echo $this->cache_info->toHtml($htmlOptions); ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$this->cached_items->isEmpty()) : ?>
<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('cache.cached_items'); ?>
                </h4>
                <?php if ($this->app->hasPermission('delete_cache')) : ?>
                <div class="tools">
                    <div class="ui dropdown">
                        <i class="ellipsis vertical icon link"></i>
                        <div class="menu">
                            <a class="item" href="<?php
                                echo $this->app->getUrl('controller', 'delete'); ?>">
                                <?php echo $this->text->get('cache.flush_cache'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="content">
                <table class="<?php echo $htmlOptions['table_attributes']['class']; ?> definition">
                    <thead class="full-width">
                    <tr>
                        <?php if ($this->app->hasPermission('delete_cache')) : ?>
                            <th>&nbsp;</th>
                        <?php endif; ?>
                        <th><?php echo $this->text->get('cache.cached_item'); ?></th>
                        <th class="center aligned">
                            <?php echo $this->text->get('cache.memory_size'); ?>
                        </th>
                        <th class="center aligned">
                            <?php echo $this->text->get('cache.hits'); ?>
                        </th>
                        <th><?php echo $this->text->get('cache.creation_time'); ?></th>
                        <th><?php echo $this->text->get('cache.last_access_time'); ?></th>
                        <th><?php echo $this->text->get('cache.expiration_time'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->cached_items->toArray() as $item) : ?>
                        <?php
                        $viewUrl = $this->app->getUrl('controller', 'view/' . $item['key']);
                        $deleteUrl = $this->app->getUrl('controller', 'delete/' . $item['key']);
                        ?>
                        <tr>
                            <?php if ($this->app->hasPermission('delete_cache')) : ?>
                                <td class="collapsing center aligned">
                                    <a class="action delete" href="<?php echo $deleteUrl; ?>">
                                        <i class="remove icon"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
                            <td class="link">
                                <a href="<?php echo $viewUrl; ?>">
                                    <?php echo $item['key']; ?>
                                </a>
                            </td>
                            <td class="right aligned">
                                <?php echo $item['memory_size']; ?>
                            </td>
                            <td class="right aligned">
                                <?php echo $item['hit_count']; ?>
                            </td>
                            <td><?php echo $item['creation_time']; ?></td>
                            <td><?php echo $item['last_access_time']; ?></td>
                            <td><?php echo $item['expiration_time']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->include('layouts/default/end.tpl.php'); ?>
