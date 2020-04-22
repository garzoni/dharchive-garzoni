<?php ob_start(); ?>

<style>
    .col-name {
        width: 64%;
    }
    .col-file {
        width: 12%;
        text-align: center !important;
    }
    .col-file img {
        display: inline-block !important;
    }
</style>

<?php $this->addSnippet(ob_get_clean(), 'head'); ?>

<?php

$fileFormats = [
    'xlsx' => 'icons/xlsx.svg',
    'ods' => 'icons/ods.svg',
    'json' => 'icons/json.svg',
];
$datasets = [
    'Contracts' => 'contract/export',
    'Person Mentions' => 'person-mention/export',
    'Persons' => 'person/export',
    'Person Relationships' => 'person/export-relationships',
    'Locations' => 'location/export',
    'Professions' => 'profession/export',
    'Profession Categories' => 'profession-category/export',
];

?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="eight wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">Datasets</h4>
            </div>
            <div class="content">
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-name">Name</th>
                        <?php foreach ($fileFormats as $fileFormat => $iconUrl) : ?>
                        <th class="col-file"><?php echo strtoupper($fileFormat); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($datasets as $datasetName => $dataUrl) : ?>
                    <tr>
                        <td class="col-name">
                            <?php echo $datasetName; ?>
                        </td>
                        <?php foreach ($fileFormats as $fileFormat => $iconUrl) : ?>
                        <td class="col-file">
                            <a href="<?php echo $this->app->getUrl('module', $dataUrl . '?format=' . $fileFormat); ?>">
                                <img class="ui mini image" alt="<?php echo $fileFormat; ?>"
                                     src="<?php echo $this->app->getImageUrl($iconUrl); ?>">
                            </a>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
