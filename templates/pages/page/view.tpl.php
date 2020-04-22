<?php ob_start(); ?>

<script>

    var clipboard;

    function showPersonInfo(item) {
        var personName = item.text(),
            personId = item.attr('data-value'),
            requestUrl = '<?php echo $this->homepage; ?>/person/get-data/'
                + personId + '/' + encodeURIComponent(personName).replace(/%20/g, '+');
        if (item.hasClass('info')) {
            return;
        }
        $.ajax({
            dataType: 'json',
            url: requestUrl,
            success: function(data) {
                var mentions = data.mentions,
                    mentionCount = mentions.length,
                    maxDisplayed = 10,
                    separator = ' &#8226 ',
                    htmlContent = '<p>',
                    header, description, i;
                if (data.url) {
                    htmlContent += '<a href="' + data.url + '" target="_blank">'
                        + data.name + '</a>';
                } else {
                    htmlContent += data.name;
                }
                htmlContent += '</p>';
                if (mentionCount) {
                    htmlContent += '<div class="ui tiny divided list">';
                    for (i = 0; i < mentionCount; i++) {
                        if (mentions[i].url) {
                            header = '<a href="' + mentions[i].url + '" '
                                + 'target="_blank">' + mentions[i].date + '</a>';
                        } else {
                            header = mentions[i].date;
                        }
                        description = mentions[i].role;
                        if (mentions[i].profession) {
                            description += separator + mentions[i].profession;
                        }
                        if (mentions[i].insigna) {
                            description += separator + mentions[i].insigna;
                        }
                        if (mentions[i].parish) {
                            description += separator + mentions[i].parish;
                        }
                        if (mentions[i].geoOrigin) {
                            description += separator + mentions[i].geoOrigin;
                        }
                        htmlContent += '<div class="item"><i class="calendar icon"></i>'
                            + '<div class="content">'
                            + '<div class="header">' + header + '</div>'
                            + '<div class="description">' + description + '</div>'
                            + '</div></div>';
                        if (i >= (maxDisplayed - 1)) {
                            break;
                        }
                    }
                    if (mentionCount > maxDisplayed) {
                        htmlContent += '<div class="item"><i class="plus icon"></i> '
                            + (mentionCount - maxDisplayed) + ' other mentions</div>';
                    }
                    htmlContent += '</div>';
                } else {
                    htmlContent += '<i class="warning sign icon"></i> ';
                    htmlContent += 'No data found.';
                }
                item.find('.icon').popup({
                    on: 'hover',
                    hoverable: true,
                    exclusive: true,
                    html: htmlContent,
                    position: 'left center',
                    hideOnScroll: false,
                    context: $('#seanote')
                });
                item.addClass('info');
                item.find('.icon').trigger('mouseover');
            }
        });
    }

    function execPersonMentionHooks(ee) {
        var professions = ee.editor.getEditor('root.professions'),
            geoOrigin = ee.editor.getEditor('root.geoOrigin'),
            entities = ee.node.find('.entity-annotations .entities'),
            dropdown = entities.find('.ui.dropdown'),
            observer;
        if (professions.getValue().length === 0) {
            professions.addRow();
        }
        $(geoOrigin.toggle_button).on('click', function() {
            if (geoOrigin.collapsed === false) {
                $(geoOrigin.editors.transcript.input).focus();
            }
        });
        entities.find('.add-entity').on('click', function() {
            clipboard = jQuery.trim(entities.find('input.search').val());
        });
        dropdown.dropdown('setting', 'onHide', function() {
            return $('#seanote').children('.ui.popup').length === 0;
        });
        observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                var item, icon;
                for (var i = 0; i < mutation.addedNodes.length; i++) {
                    item = $(mutation.addedNodes[i]);
                    if (!item.hasClass('item')) {
                        return;
                    }
                    item.prepend('<i class="user icon"></i>');
                    item.find('.icon').on('mouseover', {item: item}, function(event) {
                        showPersonInfo(event.data.item);
                    });
                }
            });
        });

        observer.observe(
            dropdown.find('.menu').get(0),
            {childList: true}
        );
    }

    function execPersonHooks(ee) {
        var relationships = ee.editor.getEditor('root.relationships');
        if (relationships.getValue().length === 0) {
            relationships.addRow();
        }
        if (clipboard) {
            ee.editor.getEditor('root.name').setValue(clipboard);
            clipboard = '';
        }
    }

    $(document).ready(function() {
        var $seanote = $('#seanote'),
            $snMenu = $('#seanote-menu'),
            $snToolbar = $('#seanote-toolbar'),
            $snControls = $('#seanote-controls'),
            $snNavigator = $('#seanote-navigator'),
            $snAnnotator = $('#seanote-annotator'),
            $snFilmstrip = $('#seanote-filmstrip'),
            tooltipOptions = {
                position: 'top center',
                variation: 'tiny inverted',
                context: $seanote
            };

        // Dropdowns
        $snMenu.find('.dropdown').dropdown({
            on: 'hover',
            action: 'hide',
            direction: 'downward'
        });
        $snControls.find('.dropdown').dropdown({
            direction: 'upward',
            onShow: function() {
                $(this).popup('hide');
            }
        });

        // Tooltips
        $snFilmstrip.find('.tipped').popup(tooltipOptions);
        $snNavigator.find('.tipped').popup(tooltipOptions);
        $snControls.find('.tipped').popup(tooltipOptions);
        $snAnnotator.find('.tipped').popup(tooltipOptions);
        $snToolbar.find('.tipped').popup($.extend({}, tooltipOptions, {
            position: 'right center'
        }));

        // Tabs
        $snAnnotator.find('.tabular.menu .item').tab({
            'onLoad': function(tabPath) {
                $snAnnotator.find('.tab-actions .button').hide();
                $snAnnotator.find('.tab-actions .button[data-tab="' + tabPath + '"]').show();
            }
        });

        $snAnnotator.find('.dropdown').dropdown({
            on: 'hover',
            direction: 'upward',
            action: 'hide'
        });
    });

    window.onload = function() {
        JSONEditor.defaults.default_language = '<?php echo $this->request->getLanguage(); ?>';
        JSONEditor.defaults.fallback_language = 'en';

        var viewer = Seanote({
            viewer: {
                language: '<?php echo $this->request->getLanguage(); ?>',
                sources: {
                    idxEntities: '<?php echo $this->entity_index_url; ?>',
                    idxAnnotations: '<?php echo $this->annotation_index_url; ?>',
                    idxSegments: '<?php echo $this->segment_index_url; ?>',
                    segments: '<?php echo $this->canvas_segments_url; ?>',
                    image: '<?php echo $this->canvas_image; ?>',
                    entityTypeUrl: '<?php echo $this->entity_type_url; ?>',
                    valueListUrl: '<?php echo $this->value_list_url; ?>'
                }
                <?php if (!$this->app->hasPermission('create_annotations')) : ?>
                ,
                components: {
                    policy: 'disable',
                    list: ['annotator', 'entityCreator']
                }
                <?php endif; ?>
            },
            <?php if (!$this->app->hasPermission('create_annotations')) : ?>
            toolbar: {
                buttons: {
                    policy: 'disable',
                    list: [
                        'createSegment',
                        'resizeSegment',
                        'deleteSegments',
                        'groupSegments',
                        'ungroupSegments',
                        'mergeSegments',
                        'unmergeSegments'
                    ]
                }
            },
            <?php endif; ?>
            canvas: {
                currentDocumentId: '<?php echo $this->document->get('id'); ?>',
                currentPageId: '<?php echo $this->canvas_code; ?>'
            },
            annotator: {
                transcriptionLanguage: 'en'
            }
        }, {}); // window.text['<?php echo $this->request->getLanguage(); ?>']

        <?php if(!empty($this->highlights)) : ?>

        viewer.subscribe('exec:loadObjects:end', function() {
            var objects = JSON.parse('<?php
                    echo json_encode($this->highlights);
                    ?>'),
                i;
            if ($.isArray(objects)) {
                for (i = 0; i < objects.length; i++) {
                    viewer.canvas.selectObject(objects[i]);
                }
            }
        });

        <?php endif; ?>

        viewer.subscribe('exec:initEntityEditor:start', function(event, ee) {
            switch (ee.entity.type) {
                case 'grz:EventMention':
                    ee.addDefault('tag', '556fc076-5f71-4702-a04e-27e2a3600db4', 'Apprenticeship');
                    break;
                case 'grz:PersonMention':
                    ee.addDefault('tag', 'fb3f72eb-b1ae-4b95-81c8-8a5eb245e5fd', 'Apprentice');
                    break;
                case 'grz:HostingConditionMention':
                    ee.addDefault('tag', '58706497-16ab-4337-b2c7-7db6e3f56c46', 'Accommodation');
                    break;
                case 'grz:FinancialConditionMention':
                    ee.addDefault('tag', '17c352f1-bd3c-4a27-8bae-15004508fafb', 'Single Salary');
                    break;
            }
        });

        viewer.subscribe('exec:initEntityEditor:end', function(event, ee) {
            // console.log(ee);
            switch (ee.entity.type) {
                case 'grz:PersonMention':
                    execPersonMentionHooks(ee);
                    break;
                case 'grz:Person':
                    execPersonHooks(ee);
                    break;
            }
        });
    };

</script>

<?php $this->addSnippet(ob_get_clean()); ?>

<?php $this->include('components/page_begin.tpl.php'); ?>

<div id="seanote">
	<?php $this->include('components/seanote/menu.tpl.php'); ?>
	<div id="seanote-viewer" class="seanote-viewer">
		<div id="openseadragon-viewer" class="openseadragon-viewer"></div>
		<?php $this->include('components/seanote/toolbar.tpl.php'); ?>
		<?php $this->include('components/seanote/filmstrip.tpl.php'); ?>
		<?php $this->include('components/seanote/navigator.tpl.php'); ?>
        <?php if ($this->app->hasPermission('create_annotations')) : ?>
		<?php $this->include('components/seanote/annotator.tpl.php'); ?>
		<?php $this->include('components/seanote/entity_editor.tpl.php'); ?>
        <?php endif; ?>
		<?php $this->include('components/seanote/modal.tpl.php'); ?>
	</div>
	<?php $this->include('components/seanote/controls.tpl.php'); ?>
</div>

<?php $this->include('components/page_end.tpl.php'); ?>
