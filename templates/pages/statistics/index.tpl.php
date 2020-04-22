
<!DOCTYPE html>

<html lang="<?php echo $this->page_language; ?>">
<?php $this->include('components/head.tpl.php'); ?>
<body>

<?php $this->include('components/navigation_bar.tpl.php'); ?>

<div id="main-content" class="ui page grid">
    <div class="row">
        <div class="sixteen wide column">
            <h2><?php echo $this->text->get('statistics.collections'); ?></h2>
        </div>
    </div>
    <div class="row">
        <div class="sixteen wide column">
            <?php if ($this->type === 'imported_documents') : ?>
            <h3><?php echo $this->text->get('statistics.imported_documents'); ?></h3>
            <div class="ui segment chart" id="imported-documents-chart"></div>
            <?php endif; ?>
            <?php if ($this->type === 'imported_pages') : ?>
            <h3><?php echo $this->text->get('statistics.imported_pages'); ?></h3>
            <div class="ui segment chart" id="imported-pages-chart"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->include('components/footer.tpl.php'); ?>

<script>
    var tauBrewer = tauCharts.api.colorBrewers.get('tauBrewer');

    <?php if ($this->type === 'imported_documents') : ?>
    new tauCharts.Chart({
        type: 'horizontal-stacked-bar',
        x: 'documents',
        y: 'type',
        color: 'type',
        guide: {
            x: {label: {text: 'documents'}},
            y: {label: {text: 'type'}},
            color: {label: {text: 'Type'}},
            showGridLines: 'y'
        },
        data: JSON.parse('<?php
            echo $this->collections->project(
                ['code', 'type', 'documents']
            )->toJson();
            ?>'),
        plugins: [
            <?php if ($this->app->hasPermission('export_statistics')) : ?>
            tauCharts.api.plugins.get('exportTo')({
                cssPaths:['<?php echo $this->chart_css_url; ?>']
            }),
            <?php endif; ?>
            tauCharts.api.plugins.get('legend')(),
            tauCharts.api.plugins.get('tooltip')()
        ]
    }).renderTo('#imported-documents-chart');
    <?php endif; ?>

    <?php if ($this->type === 'imported_pages') : ?>
    new tauCharts.Chart({
        type: 'horizontal-stacked-bar',
        x: 'pages',
        y: 'type',
        color: 'type',
        guide: {
            x: {label: {text: 'pages'}},
            y: {label: {text: 'type'}},
            color: {label: {text: 'Type'}},
            showGridLines: 'y'
        },
        data: JSON.parse('<?php
            echo $this->collections->project(
                ['code', 'type', 'pages']
            )->toJson();
            ?>'),
        plugins: [
            <?php if ($this->app->hasPermission('export_statistics')) : ?>
            tauCharts.api.plugins.get('exportTo')({
                cssPaths:['<?php echo $this->chart_css_url; ?>']
            }),
            <?php endif; ?>
            tauCharts.api.plugins.get('legend')(),
            tauCharts.api.plugins.get('tooltip')()
        ]
    }).renderTo('#imported-pages-chart');
    <?php endif; ?>
</script>

</body>
</html>
