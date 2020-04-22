<?php

use function Application\createText as _;

?>

<!DOCTYPE html>

<html lang="<?php echo $this->page_language; ?>">
<?php $this->include('components/head.tpl.php'); ?>
<body>

<?php $this->include('components/navigation_bar.tpl.php'); ?>

<div id="main-content" class="ui page grid">
    <div class="row">
        <div class="sixteen wide column">
            <h1>
                <?php echo $this->text->get('app.collections'); ?>
                <a href="<?php echo $this->collection_index_url . '/export';?>" style="float: right;"
                   data-tooltip="<?php echo $this->text->get('app.export'); ?>">
                    <i class="external icon"></i>
                </a>
            </h1>
            <?php $this->include('components/messages.tpl.php'); ?>
        </div>
    </div>

    <?php if ($this->collections) : ?>

    <div class="row">
        <div class="sixteen wide column">
            <table class="ui compact celled definition table collections">
                <thead class="full-width">
                <tr>
                    <?php if ($this->app->hasPermission('edit_documents')) : ?>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    <?php endif; ?>
                    <th class="center aligned"><?php echo $this->text->get('collection.code'); ?></th>
                    <th class="four wide"><?php echo $this->text->get('collection.description'); ?></th>
                    <th><?php echo $this->text->get('collection.type'); ?></th>
                    <th><?php echo $this->text->get('collection.material'); ?></th>
                    <th class="right aligned"><?php echo $this->text->get('collection.documents'); ?></th>
                    <th class="right aligned"><?php echo $this->text->get('collection.pages'); ?></th>
                    <th><?php echo $this->text->get('collection.start_date'); ?></th>
                    <th><?php echo $this->text->get('collection.end_date'); ?></th>
                    <th><?php echo $this->text->get('collection.geo_origin'); ?></th>
                </tr>
                </thead>
                <tbody>
            <?php foreach ($this->collections as $collection) :
                $properties = json_decode($collection['properties'], true);
                $metadata = $properties['metadata'] ?? [];
                $statistics = $this->statistics->get($collection['code']);
                $importedPageCount = (int) ($statistics['pages'] ?? 0);
                $pageCount = (int) ($metadata['pageCount'] ?? 0);

                $annotationInfo = '';
                $annotationStats = $this->annotation_stats->{$collection['code']} ?? [];
                if (!empty($annotationStats)) {
                    $annotationInfo = '<h3>' . $this->text->get('app.annotations') . '</h3>'
                        . '<ul class=\'ui list\'>'
                        . '<li>' . $this->text->get('app.transcriptions') . ': ' . $annotationStats['transcriptions'] . '</li>'
                        . '<li>' . $this->text->get('app.mentions') . ': ' . $annotationStats['mentions'] . '</li>'
                        . '<li>' . $this->text->get('app.tags') . ': ' . $annotationStats['tags'] . '</li>'
                        . '<li>' . $this->text->get('app.identifications') . ': ' . $annotationStats['identifications'] . '</li>'
                        . '</ul>';
                }
                ?>

                <tr>
                    <?php if ($this->app->hasPermission('edit_documents')) : ?>
                    <th class="center aligned">
                        <a href="<?php echo $this->collection_index_url . '/update/' . $collection['id'];?>"
                           data-tooltip="<?php echo $this->text->get('app.edit'); ?>">
                            <i class="edit icon"></i>
                        </a>
                    </th>
                    <th class="center aligned">
                        <?php if (!isset($statistics['documents'])) : ?>
                            <span class="action delete" data-tooltip="<?php echo $this->text->get('app.delete'); ?>">
                                <i class="remove link icon" data-id="<?php echo $collection['id']; ?>"></i>
                            </span>
                        <?php elseif (!empty($annotationInfo)) : ?>
                            <span class="annotation-info" data-html="<?php echo $annotationInfo; ?>">
                                <i class="at icon"></i>
                            </span>
                        <?php else : ?>
                            &nbsp;
                        <?php endif; ?>
                    </th>
                    <?php endif; ?>
                    <td class="center aligned"><?php echo $collection['code']; ?></td>
                    <td><?php echo _($properties['description'] ?? '–')->htmlEncode(); ?></td>
                    <td><?php echo _($metadata['type'] ?? '–')->htmlEncode(); ?></td>
                    <td><?php echo _($metadata['material'] ?? '–')->htmlEncode(); ?></td>
                    <td class="link right aligned">
                        <a href="<?php echo $this->document_list_url
                            . '?filter-collection=' . $collection['code']; ?>">
                            <?php echo $statistics['documents'] ?? 0; ?>
                        </a>
                    </td>
                <?php if (($pageCount > 0) && ($pageCount != $importedPageCount)) : ?>
                    <td class="right aligned warning">
                        <?php echo $importedPageCount; ?>
                        <span data-tooltip="<?php echo 'Metadata Page Count: ' . $pageCount; ?>">
                            <i class="attention icon"></i>
                        </span>
                    </td>
                <?php else : ?>
                    <td class="right aligned">
                        <?php echo $importedPageCount; ?>
                    </td>
                <?php endif; ?>
                    <td><?php echo _($metadata['startDate'] ?? '–')->htmlEncode(); ?></td>
                    <td><?php echo _($metadata['endDate'] ?? '–')->htmlEncode(); ?></td>
                    <td><?php echo _($metadata['geoOrigin'] ?? '–')->htmlEncode(); ?></td>
                </tr>

            <?php endforeach; ?>
                </tbody>
                <?php if ($this->app->hasPermission('create_documents')) : ?>
                    <tfoot class="full-width">
                    <tr>
                        <th></th>
                        <th colspan="<?php echo $this->app->hasPermission('edit_documents') ? '10' : '8'; ?>">
                            <a href="<?php echo $this->collection_index_url . '/create'; ?>"
                               class="ui right floated small primary button">
                                <?php echo $this->text->get('collection.add'); ?>
                            </a>
                        </th>
                    </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="eight wide column">
            <h2><?php echo $this->text->get('collection.batch_import'); ?></h2>
            <form class="ui form" action="<?php echo $this->collection_index_url . '/import'; ?>"
                  method="post" enctype="multipart/form-data">
                <div class="field">
                    <label for="dataFile"><?php echo $this->text->get('collection.data_file'); ?></label>
                    <input type="file" name="dataFile" id="dataFile">
                </div>
                <button class="ui primary button" type="submit">
                    <?php echo $this->text->get('app.import'); ?>
                </button>
            </form>
        </div>
    </div>
</div>

<?php $this->include('components/footer.tpl.php'); ?>

<script>
    $(document).ready(function() {

        var table = $('.table.collections'),
            addCallbacks = function() {
                table.find('.annotation-info').popup();

                <?php if ($this->app->hasPermission('delete_documents')) : ?>

                table.find('.delete').on('click', function() {
                    jQuery.post('<?php echo $this->collection_index_url . '/delete';?>', {
                        'collection_id': $(this).find('i').attr('data-id')
                    }).done(function(response) {
                        if (response.status === true) {
                            location.reload();
                        }
                    });
                });

                <?php endif; ?>
            };

        table.DataTable({
            columns: [
                {orderable: false},
                {orderable: false},
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null
            ],
            lengthMenu: [
                [10, 25, 50],
                [10, 25, 50]
            ],
            order: [
                [2, 'asc']
            ],
            stateSave: true,
            stateDuration: 0,
            language: {
                url: '<?php echo $this->dtlang_url; ?>'
            },
            initComplete: function(settings, json) {
                addCallbacks();
            }
        });

        table.on('draw.dt', function() {
            addCallbacks();
        });
    });
</script>

</body>
</html>
