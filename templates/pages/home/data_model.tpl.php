<?php ob_start(); ?>

<style>
    .col-property {
        width: 20%;
    }
    .col-type {
        width: 15%;
    }
    .col-format {
        width: 15%;
    }
    .col-comment {
        width: 50%;
    }
</style>

<?php $this->addSnippet(ob_get_clean(), 'head'); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui padded text container segment">
            <p>The primary unit of information is an <strong>image segment</strong> featuring an apprenticeship contract. Internally, at the level of the DHCanvas annotation model, an image segement represents the link between the primary source and all its derived annotations. At the level of Garzoni model, it is the object wich gather all annotations related to one contract. Image segment objects are not manipulated in the exploration interface, but it is good to be aware of this base unit.</p>
            <p>On each image segment, various annotations representing objects from the Garzoni model pile up, according to the mention and entity layers described in <a href="/data-acquisition">Data Acquisition</a>.</p>
            <h3>Mentions and Entities</h3>
            <p>The Garzoni model features the following <strong>mentions</strong>:</p>
            <ul>
                <li><strong>Contract</strong> (mention), with properties related to the apprenticeship contract</li>
                <li><strong>Event</strong> (mention), with properties related to different kind of events happening during a contract</li>
                <li><strong>Person</strong> (mention), with properties related to different persons mentioned in a contract</li>
                <li><strong>Financial Condition</strong> (mention), with properties related to the contract salary</li>
                <li><strong>Hosting Condition</strong> (mention), with properties related to the &#39;care&#39; of the apprentice</li>
            </ul>
            <p>These five types of mentions are created as mention annotations on one image segment, which &#39;glues&#39; them together (we know they belong together because they are on the same image segment). They represent the factual information present in an historical document contract.</p>
            <p>On top of these mentions, there exists one type of <strong>entity</strong>:</p>
            <ul>
                <li><strong>Person</strong> (entity), representing the disambiguated (as far as possible) entity to which refer several person mentions</li>
            </ul>
            <h4>Properties of mentions and entity annotations:</h4>
            <h5>Contract Mention</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Date</td>
                        <td>String</td>
                        <td>YYYY-MM-DD</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>On Multiple Pages</td>
                        <td>Boolean</td>
                        <td>&nbsp;</td>
                        <td>Indicates whether the contract spans multiple pages.</td>
                    </tr>
                    <tr>
                        <td>Has Margin</td>
                        <td>Boolean</td>
                        <td>&nbsp;</td>
                        <td>Indicates whether there is an additional note in the margin of the contract.</td>
                    </tr>
                    <tr>
                        <td>Additional Information</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Any specific information the annotator deemed relevant to add.</td>
                    </tr>
                    <tr>
                        <td>Related Contract</td>
                        <td>String</td>
                        <td>UUID</td>
                        <td>Rare case of a contrat referring to a previous contrat.</td>
                    </tr>
                    <tr>
                        <td>MediaWiki Reference</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Legacy contract id from project pre-study, not visible in the interface.</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h5>Person Mention</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Semantic tag</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Apprentice, Master, Guarantor, Other</td>
                    </tr>
                    <tr>
                        <td>Name</td>
                        <td>Object</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Gender</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Female, Male</td>
                    </tr>
                    <tr>
                        <td>Age</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Geographical origin</td>
                        <td>Object</td>
                        <td>&nbsp;</td>
                        <td>See Location object</td>
                    </tr>
                    <tr>
                        <td>Professions</td>
                        <td>Array of Objects</td>
                        <td>&nbsp;</td>
                        <td>See Profession object</td>
                    </tr>
                    <tr>
                        <td>Workshop</td>
                        <td>Object</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Charge</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>See Location object</td>
                    </tr>
                    <tr>
                        <td>Charge location</td>
                        <td>Object</td>
                        <td>&nbsp;</td>
                        <td>See Location object</td>
                    </tr>
                    <tr>
                        <td>Residence</td>
                        <td>Object</td>
                        <td>&nbsp;</td>
                        <td>See Location object</td>
                    </tr>
                    <tr>
                        <td>Entity</td>
                        <td>Object</td>
                        <td>Entity</td>
                        <td>Link towards a Person entity</td>
                    </tr>
                    <tr>
                        <td>Additional information</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Any specific information the annotator deemed relevant to add.</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h5>Event Mention</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Semantic tag</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Apprenticeship, Breach of Contract, Flee</td>
                    </tr>
                    <tr>
                        <td>Start date</td>
                        <td>String</td>
                        <td>YYYY-MM-DD</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>End date</td>
                        <td>String</td>
                        <td>YYYY-MM-DD</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Duration</td>
                        <td>&nbsp;</td>
                        <td>Object</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Denunciation date</td>
                        <td>String</td>
                        <td>YYYY-MM-DD</td>
                        <td>For Event of type &#39;Flee&#39; only</td>
                    </tr>
                    <tr>
                        <td>Additional information</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Any specific information the annotator deemed relevant to add.</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h5>Hosting Condition Mention</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Semantic Tag</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Accomodation, Clothing, Generic Expenses, Personal Care</td>
                    </tr>
                    <tr>
                        <td>Paid in Goods</td>
                        <td>Boolean</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Paid By</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Apprentice, Master, Other</td>
                    </tr>
                    <tr>
                        <td>Application Rule</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Sano, Sano et infermo </td>
                    </tr>
                    <tr>
                        <td>Periodization</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Daily, Monthly, Weekly, Semestral, Annual, Whole Period</td>
                    </tr>
                    <tr>
                        <td>Specific Period</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Type of Clothing</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Additional information</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Any specific information the annotator deemed relevant to add.</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h5>Financial Condition Mention</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Semantic Tag</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Single Salary, Progressive Salary, Pledge, Other Salary</td>
                    </tr>
                    <tr>
                        <td>Paid in Goods</td>
                        <td>Boolean</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Paid By</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Apprentice, Master, Other</td>
                    </tr>
                    <tr>
                        <td>Currency Unit</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Ducati, Lire, Soldi, Other</td>
                    </tr>
                    <tr>
                        <td>Money Information</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Indicated if not covered by the Currency Unit values.</td>
                    </tr>
                    <tr>
                        <td>Periodization</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: Daily, Monthly, Weekly, Semestral, Annual, Whole Period</td>
                    </tr>
                    <tr>
                        <td>Specific Period</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Partial Amount</td>
                        <td>Integer</td>
                        <td>&nbsp;</td>
                        <td>For Progressive Salary</td>
                    </tr>
                    <tr>
                        <td>Total Amount</td>
                        <td>Integer</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Additional information</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Any specific information the annotator deemed relevant to add.</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h5>Person Entity</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Name</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>A name which should be unique.</td>
                    </tr>
                    <tr>
                        <td>Relationships</td>
                        <td>Array of Objects</td>
                        <td>&nbsp;</td>
                        <td>See Relation object.</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h4>Properties of other objects used as mention or entity property values:</h4>
            <h5>Name Object</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Forename</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Surname</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Patronymic 1</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Name of the father.</td>
                    </tr>
                    <tr>
                        <td>Quodam 1</td>
                        <td>Boolean</td>
                        <td>&nbsp;</td>
                        <td>&quot;di&quot; if the relative is still alive, &quot;quondam&quot; otherwise.</td>
                    </tr>
                    <tr>
                        <td>Patronymic 2</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Name of the grandfather</td>
                    </tr>
                    <tr>
                        <td>Quodam 2</td>
                        <td>Boolean</td>
                        <td>&nbsp;</td>
                        <td>&quot;di&quot; if the relative is still alive, &quot;quondam&quot; otherwise.</td>
                    </tr>
                    <tr>
                        <td>Collective name</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <p>N.B.: Based on these appellation components, person mentions names are recomposed in a &#39;person label&#39;.</p>
            <h5>Profession Object</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Transcript</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Literal transcription</td>
                    </tr>
                    <tr>
                        <td>Standard form</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>The standard form was not added during the annotation phase, but at a later stage, after the work on profession normalisation.</td>
                    </tr>
                    <tr>
                        <td>Works for</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>In somes cases, indicates with whom a person was working.</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h5>Location Object</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Transcript</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Literal transcription</td>
                    </tr>
                    <tr>
                        <td>Standard Form</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Sestiere</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: among list of Venice sestiere</td>
                    </tr>
                    <tr>
                        <td>Parish</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: among list of Venice parishes</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h5>Workshop Object</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Insignia</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>The name of the workshop</td>
                    </tr>
                    <tr>
                        <td>Site</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>An indication of where the workshop is located, if any.</td>
                    </tr>
                    <tr>
                        <td>Sestiere</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: among list of Venice sestiere</td>
                    </tr>
                    <tr>
                        <td>Parish</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values: among list of Venice parishes</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h5>Person Relation Object</h5>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th class="col-property">Property</th>
                        <th class="col-type">Type</th>
                        <th class="col-format">Format</th>
                        <th class="col-comment">Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>RelationType</td>
                        <td>String</td>
                        <td>Qualified name</td>
                        <td>Possible values among a set of family relationships.</td>
                    </tr>
                    <tr>
                        <td>Person</td>
                        <td>String</td>
                        <td>&nbsp;</td>
                        <td>Name of the related Person Entity</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h3>Additionnal Information</h3>
            <p>Besides the information collected via annotation with DHCanvas, additional normalisation steps occurred for profession and location names.</p>
            <h4>Profession Normalisation</h4>
            <p>Profession transcripts were lexically normalised (normalisation) and semantically classified (classification).</p>
            <p>Lexical normalisation consisted in adding, for each profession transcript:</p>
            <ul>
                <li>a standard form, i.e. a regular surface form which normalizes linguistic variants, in Venetian</li>
                <li>an occupation name, i.e. a kind of more precise appellation or profession naming, in modern Italian</li>
            </ul>
            <p>Classification consisted in adding, for each standard form:</p>
            <ul>
                <li>a hierarchical classification with three levels:
                    <ul>
                        <li>level 0, about the economic sector</li>
                        <li>level 1, about the field of activity</li>
                        <li>level 2, about the sub-field of activity</li>
                    </ul>
                </li>
                <li>semantic tags:
                    <ul>
                        <li>product, that specifies the final product of the craft</li>
                        <li>material, that specifies the material used</li>
                    </ul>
                </li>
            </ul>
            <h4>Location Normalisation</h4>
            <p>Location transcripts from the Geographical Origins property of Person Mentions were lexically normalised, and classified.</p>
            <p>Lexical normalisation consisted in adding, for each location transcript:</p>
            <ul>
                <li>a standard form, i.e. a regular surface form which normalizes linguistic variants, in Venetian</li>
                <li>a name, in modern Italian</li>
            </ul>
            <p>Classification consisted in adding, for each location standard form:</p>
            <ul>
                <li>a parish and sestiere, if relevant and not already present</li>
                <li>a type, between the following values: populated place, region, country, Venetian area</li>
                <li>a country, for those location names outside Italy</li>
                <li>a province, for Italian location names</li>
            </ul>
            <h3>Where to Find What</h3>
            <h4>Faceted Search</h4>
            <p>Possible filters offer most of the annotation properties. Additional information about profession and location are seamlessly incorporated in the search, although they are not formally part of contracts.</p>
            <h4>Download</h4>
            <p>The Download section provides data exports which partially reflects the Garzoni physical database. In these exports, additional information about Professions, Locations, as well as Person Relationships are separated from the Contract export. Visit the <a href="/data/download">Download</a> section for more information.</p>
            <h3>Terminology</h3>
            <p><strong>Qualified Name</strong>: an unambiguous name that specifies an object, see this <a href='https://en.wikipedia.org/wiki/Fully_qualified_name'>Wikipedia article</a> . </p>
            <p><strong>UUID</strong>: Universally Unique Identifier, see this <a href='https://en.wikipedia.org/wiki/Universally_unique_identifier'>Wikipedia article</a> . </p>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
