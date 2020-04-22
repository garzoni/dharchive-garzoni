<?php
use Application\Core\Type\Table;

$htmlOptions = [
    'table_attributes' => ['class' => 'ui compact celled table'],
    'column_name_format' => 'title_case',
];

$filters = [
    'contracts' => '
        name,group,type,dynamic,multiple_values,searchable,allow_additions,dependent
        Registered After,Contract,date,no,no,yes,no,yes
        Registered Before,Contract,date,no,no,yes,no,yes
        On Multiple Pages,Contract,dropdown,no,no,no,no,no
        With Margin,Contract,dropdown,no,no,no,no,no
        With Extra Information,Contract,dropdown,no,no,no,no,no
        Extra Information,Contract,text,-,-,-,-,-
        Role,Person Mention,dropdown,no,yes,no,no,no
        Gender,Person Mention,dropdown,no,no,no,no,no
        Age,Person Mention,dropdown,yes,yes,yes,yes,no
        Name,Person Mention,dropdown,yes,yes,yes,yes,no
        Entity,Person Mention,dropdown,yes,no,yes,no,no
        Profession - Standard Form,Person Mention,dropdown,yes,yes,yes,yes,no
        Profession - Occupation,Person Mention,dropdown,yes,yes,yes,yes,no
        Profession - Category,Person Mention,dropdown,yes,no,yes,no,yes
        Profession - Subcategory,Person Mention,dropdown,yes,no,yes,no,yes
        Profession - Material,Person Mention,dropdown,yes,yes,yes,no,no
        Profession - Product,Person Mention,dropdown,yes,yes,yes,no,no
        Place of Origin - Historical Name,Person Mention,dropdown,yes,yes,yes,yes,no
        Place of Origin - Contemporary Name,Person Mention,dropdown,yes,yes,yes,yes,no
        Place of Origin - Sestiere,Person Mention,dropdown,no,no,no,no,yes
        Place of Origin - Parish,Person Mention,dropdown,yes,no,yes,no,yes
        Place of Origin - Province,Person Mention,dropdown,yes,yes,yes,no,no
        Place of Origin - Country,Person Mention,dropdown,yes,yes,yes,no,no
        With Extra Information,Person Mention,dropdown,no,no,no,no,no
        Extra Information,Person Mention,text,-,-,-,-,-
        Insignia,Workshop,dropdown,yes,yes,yes,yes,no
        Site,Workshop,dropdown,yes,yes,yes,yes,no
        Sestiere,Workshop,dropdown,no,no,no,no,yes
        Parish,Workshop,dropdown,yes,no,yes,no,yes
        Type,Event,dropdown,no,yes,no,no,no
        After,Event,date,no,no,yes,no,yes
        Before,Event,date,no,no,yes,no,yes
        Duration - Years,Event,text,-,-,-,-,-
        Duration - Months,Event,text,-,-,-,-,-
        Duration - Days,Event,text,-,-,-,-,-
        With Extra Information,Event,dropdown,no,no,no,no,no
        Extra Information,Event,text,-,-,-,-,-
        Type,Hosting Condition,dropdown,no,yes,no,no,no
        Paid in Goods,Hosting Condition,dropdown,no,no,no,no,no
        Paid By,Hosting Condition,dropdown,no,yes,no,no,no
        Periodization,Hosting Condition,dropdown,no,yes,no,no,no
        Specific Period,Hosting Condition,text,-,-,-,-,-
        Application Rule,Hosting Condition,dropdown,no,no,no,no,no
        Type of Clothing,Hosting Condition,dropdown,yes,yes,yes,yes,no
        With Extra Information,Hosting Condition,dropdown,no,no,no,no,no
        Extra Information,Hosting Condition,text,-,-,-,-,-
        Type,Financial Condition,dropdown,no,yes,no,no,no
        Paid in Goods,Financial Condition,dropdown,no,no,no,no,no
        Paid By,Financial Condition,dropdown,no,yes,no,no,no
        Periodization,Financial Condition,dropdown,no,yes,no,no,no
        Specific Period,Financial Condition,text,-,-,-,-,-
        Partial Amount,Financial Condition,text,-,-,-,-,-
        Total Amount,Financial Condition,text,-,-,-,-,-
        Currency Unit,Financial Condition,dropdown,no,yes,no,no,no
        Money Information,Financial Condition,dropdown,yes,yes,yes,yes,no
        With Extra Information,Financial Condition,dropdown,no,no,no,no,no
        Extra Information,Financial Condition,text,-,-,-,-,-
    ',
];

foreach ($filters as &$table) {
    $getValues = function ($data) {
        $data = trim($data);
        return str_getcsv($data);
    };
    $records = explode("\n", trim($table));
    $headerRow = $getValues(array_shift($records));
    $dataRows = [];
    foreach ($records as $record) {
        $values = $getValues($record);
        $dataRow = [];
        foreach ($headerRow as $index => $column) {
            $dataRow[$column] = $values[$index] ?? null;
        }
        $dataRows[] = $dataRow;
    }
    $table = new Table($dataRows);
}

?>

<?php ob_start(); ?>

<style>
    .ui.table th:nth-child(n+4),
    .ui.table td:nth-child(n+4) {
        text-align: center;
    }
</style>

<?php $this->addSnippet(ob_get_clean(), 'head'); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui padded text container segment">
            <p>This entry point allows to explore Garzoni data via fine-grained property filters.</p>
            <p>Faceted search is availble on three data-set perspectives:</p>
            <ol>
                <li><strong>Contracts</strong>, to explore apprenticship contracts based on multiple properties of their mentions and entities</li>
                <li><strong>Person Mentions</strong>, to explore solely Person Mentions and their related Person Entities. This can be useful to explore person mentions names and to understand they were related to entities.</li>
                <li><strong>Persons</strong> (entities), to explore Person Entities</li>
            </ol>
            <p>In practice, the main search is the Contract one, the two others are additional.</p>
            <p>In (1) the basic unit of search is the Contract. When combined, filters act at the level of contract which means that two Person Mention filters, with one specifying the role Master and a profession <em>p</em>, and the other specifying the Role Apprentice and a gender <em>g</em>, will retrieve Contracts that contain both a Master with profession <em>p</em> and an apprentice with gender <em>g</em>.</p>
            <p>For a demonstration of the Faceted Search, you can watch this video:</p>
            <iframe width="640" height="360" src="https://tube.switch.ch/embed/8775fd07" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>

            <h3>Fuzzy and Exact Match</h3>
            <p>For searchable filters, i.e. when it is possible to type in to look for a specific value or to add one, fuzzy and exact matches are supported:</p>
            <p><strong>Exact</strong>: by typing the string normally.</p>
            <p><strong>Fuzzy</strong>: by prepending the string with a <strong>tilde</strong> (<code>~</code>).</p>
            <p>The fuzzy search pattern may contain two types of placeholders:</p>
            <ul>
                <li><strong>underscore</strong> (<code>_</code>): stands for (matches) any single character</li>
                <li><strong>percent sign</strong> (<code>%</code>): matches any sequence of zero or more characters</li>
            </ul>

            <h3>Summary of Filters</h3>
            <p><strong>Dynamic</strong>: whether the possible values to filter on are dynamically loaded or not.</p>
            <p><strong>Multiple Values</strong>: whether the filter supports multiple values or not. The default logical operator is OR.</p>
            <p><strong>Searchable</strong>: whether the possible values of the filter can be searched by typing in or not.</p>
            <p><strong>Allow Additions</strong>: whether it is possible to search with a value which is not dynamically proposed.</p>
            <p><strong>Dependent</strong>: whether the possible values are dependent from the ones of another filter.</p>

            <?php echo $filters['contracts']->toHtml($htmlOptions); ?>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
