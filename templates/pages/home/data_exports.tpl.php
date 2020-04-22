<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui padded text container segment">
            <p>Garzoni data can be exported in different formats and from different places in the GUI.</p>

            <h3>How are exports organized?</h3>
            <p>Garzoni data is highly relational and cannot be exported all at once in a flat representation. Instead, data export is divided in several sub-sets:</p>

            <h4>Contracts</h4>
            <p>This export contains the core of the data. The basic unit is the Contract, which gather mentions of various types and their entities. Each mention has a unique ID. In practice, the most important IDs are the ones of the Contract, to connect different mentions related to the same contract, and the one of the Person Mentions, to connect Person Mentions with Person Entities.</p>

            <h4>Person Mentions</h4>
            <p>This is an extra subset for convenience. Person Mentions information is already contained in the Contracts export.</p>

            <h4>Persons</h4>
            <p>This export contains data related to Person Entities. It is meant to be used in combination with the Contract export in order to know to which entities the person mentioned in contracts refer to.</p>

            <h4>Person Relationships</h4>
            <p>This export contains Person Entities and their relationships. It is meant to be used in relation with Contract and Person Entities export.</p>

            <h4>Locations</h4>
            <p>This export contains the normalised and classified location transcripts.</p>

            <h4>Professions</h4>
            <p>This export contains the normalised profession transcripts.</p>

            <h4>Profession Categories</h4>
            <p>This export contains the classified profession standard forms.</p>

            <p>These exports are available in <strong>Excel</strong>, <strong>ODS</strong> and <strong>JSON</strong> formats.</p>

            <h3>Where can data be exported from?</h3>
            <p>All data-sets can be fully exported from the <a href="/data/download/">Download</a> page.</p>
            <p>Sub-collections selected via search can also be exported from the search result page, either a few selected items, either the full result set.</p>

            <h3>Other exports</h3>

            <h4>IIIF Image and Presentation API</h4>
            <p>Available <a href='https://garzoni.dhlab.epfl.ch/iiif/pres/collection/top'>here</a>.</p>

            <h4>RDF Dump</h4>
            <p>Coming soon.</p>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
