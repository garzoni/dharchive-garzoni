<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui padded text container segment">
            <p>The <em>Accordi dei Garzoni</em> is a document series from the State Archives of Venice which originates from the activity of the <a href='http://garzoni.hypotheses.org/454'> Giustizia Vecchia</a> magistracy. By the enforcement of laws from the 13th and 14th centuries, this judicial authority was in charge of registering apprenticeship contracts with the aim, among others, of protecting young people while they were trained and/or providing domestic services. If all masters and guilds did not always comply with the legislation – although regularly reiterated –, the result of this regulation is that information for much of apprenticeship arrangements got centralized, today reflected in an exceptionally dense and complete archival series. </p>
            <p>The <em>Accordi</em> comprises 32 registers which are, for the most part, in a very well-preserved state. The first register (n°151) starts on June, 9th 1575 and the last (n°182) ends on May, 20th 1772. Despite some gaps at the beginning and at the end of the 17th c., the coverage of the bound period is pretty good and contract records add up to ca. 55,000. Registers contains 3 to 6 records per page, each amounting to a small paragraph of 6 to 10 lines preceded by the date. An additional note would sometimes appear in the margin, indicating a correction or modification made to the contract a posteriori. Apprenticeship enrolments were registered by several officers, resulting in very different handwritings. Deciphering such writings is nowadays restricted to experimented paleographers, specialists of Venetian dialect and familiar with the numerous abbreviations used by the scribes. </p>
            <h3>From Documents to Structured Data</h3>
            <p>Our starting point was tens of thousands of Garzoni contracts and our finishing line an information system to support historians to make sense of all the information they contain. Starting from digitized documents, processes were incrementally organized into the following steps, where information is gradually built up:</p>
            <ol start=''>
                <li>data modelling – or how to formalize what we are interested in</li>
                <li>data acquisition – or how to extract information, via transcription and annotation, according to the data model</li>
                <li>data normalisation – or how to gather variants under same representations (location and profession names)</li>
                <li>data validation – or how to detect and correct potential mistakes</li>
                <li>data exploitation – or how to enable search and exploration</li>
            </ol>
            <h3>Garzoni Contracts</h3>
            <p>The vast majority of contracts were recorded following the same pattern, that is to say documenting the same elements of information. Each contract, always about 8 to 10 lines long, exposes the essentials of the apprenticeship agreement, with the following elements:</p>
            <ul>
                <li>the identity of the involved persons, with the Apprentice, the Master, and potentially a Guarantor. Information such as the age and the geographical origins of the apprentice, or the residence of the master or apprentice are often mentioned</li>
                <li>the profession to be taught and the workshop where the learning takes place</li>
                <li>the diverse terms of the contracts, with the salary, the mention of possible advantages in kind, the contract duration, and at times other details</li>
            </ul>
            <p>For more details about garzoni contract&#39;s content, see <a href="/data-model">Garzoni Data Model</a>.</p>
            <p>Overall, the regularity of the provided information is a major advantage: it allows a systematic study of the contracts and greatly eases the definition of a data mode.</p>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
