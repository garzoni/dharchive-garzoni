
<!DOCTYPE html>

<html lang="<?php echo $this->page_language; ?>">
<?php $this->include('components/head.tpl.php'); ?>
<body>

<?php $this->include('components/navigation_bar.tpl.php'); ?>

<style type="text/css">
    .chart {
        width: 100%;
        height: 400px;
        margin: 0;
        padding: 0;
    }
</style>

<div id="main-content" class="ui page grid">
    <div class="row">
        <div class="sixteen wide column">
            <h1><?php echo $this->text->get('app.dashboard'); ?></h1>
        </div>
    </div>
    <div class="row">
        <div class="sixteen wide column">
            <h2><?php echo $this->text->get('statistics.collections'); ?></h2>
        </div>
    </div>
    <div class="row">
        <div class="sixteen wide column">
            <h4><?php echo $this->text->get('statistics.imported_documents'); ?></h4>
            <div class="chart" id="imported-documents-chart"></div>
            <h4><?php echo $this->text->get('statistics.imported_pages'); ?></h4>
            <div class="chart" id="imported-pages-chart"></div>
        </div>
    </div>
    <div class="row">
        <div class="sixteen wide column">
            <h2><?php echo $this->text->get('statistics.annotations'); ?></h2>
        </div>
    </div>
    <div class="row">
        <div class="sixteen wide column">
            <h4><?php echo $this->text->get('statistics.by_type'); ?></h4>
            <div class="chart" id="imported-documents-chart"></div>
            <h4><?php echo $this->text->get('statistics.by_creator'); ?></h4>
            <div class="chart" id="imported-pages-chart"></div>
        </div>
    </div>
</div>

<?php $this->include('components/footer.tpl.php'); ?>

<script>
    var tauBrewer = tauCharts.api.colorBrewers.get('tauBrewer');
    new tauCharts.Chart({
        type: 'stacked-bar',
        x: 'type',
        y: 'documents',
        color: 'type',
        guide: {
            x: {label: {text: 'type'}},
            y: {label: {text: 'documents'}},
            color: {label: {text: 'Type'}}
        },
        data: JSON.parse('<?php
            echo $this->collections->project(
                ['code', 'type', 'documents']
            )->toJson();
            ?>'),
        plugins: [
            tauCharts.api.plugins.get('legend')(),
            tauCharts.api.plugins.get('tooltip')()
        ]
    }).renderTo('#imported-documents-chart');

    new tauCharts.Chart({
        type: 'stacked-bar',
        x: 'type',
        y: 'pages',
        color: 'type',
        guide: {
            x: {label: {text: 'type'}},
            y: {label: {text: 'pages'}},
            color: {label: {text: 'Type'}}
        },
        data: JSON.parse('<?php
            echo $this->collections->project(
                ['code', 'type', 'pages']
            )->toJson();
            ?>'),
        plugins: [
            tauCharts.api.plugins.get('legend')(),
            tauCharts.api.plugins.get('tooltip')()
        ]
    }).renderTo('#imported-pages-chart');
</script>

</body>
</html>
