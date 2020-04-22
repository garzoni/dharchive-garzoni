<?php ob_start(); ?>

<style>
    th:first-child,
    th:nth-child(3) {
        width: 20%;
    }
</style>

<?php $this->addSnippet(ob_get_clean(), 'head'); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui padded text container segment">
            <p>The full text search functionality allows to search through annotations of Garzoni contracts and to retrieve corresponding documents. </p>
            <h3>Demonstration Video</h3>
            <iframe width="640" height="360" src="https://tube.switch.ch/embed/8775fd07" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>

            <h3>Quick Guide</h3>
            <h4>Indexed Targets</h4>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th>Object</th>
                        <th>Properties</th>
                        <th>Search Type</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Contract Mention</td>
                        <td>
                            <ul>
                                <li>date</li>
                            </ul>
                        </td>
                        <td>fuzzy match</td>
                    </tr>
                    <tr>
                        <td>Person Mention</td>
                        <td>
                            <ul>
                                <li>forename</li>
                                <li>surname</li>
                                <li>patronymic 1</li>
                                <li>patronymic 2</li>
                                <li>profession 1 transcript</li>
                                <li>profession 2 transcript</li>
                                <li>residence transcript</li>
                                <li>geo origin transcript</li>
                                <li>geo origin sestiere</li>
                                <li>geo origin parish</li>
                                <li>workshop insignia</li>
                                <li>details</li>
                            </ul>
                        </td>
                        <td>fuzzy match</td>
                    </tr>
                    <tr>
                        <td>Person Entity</td>
                        <td>
                            <ul>
                                <li>name</li>
                            </ul>
                        </td>
                        <td>fuzzy match</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h4>Operators</h4>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th>Logical Operator</th>
                        <th>Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>AND</td>
                        <td>logical AND</td>
                    </tr>
                    <tr>
                        <td>&amp;</td>
                        <td>logical AND for specifiers</td>
                    </tr>
                    <tr>
                        <td>OR</td>
                        <td>logical OR</td>
                    </tr>
                    <tr>
                        <td>|</td>
                        <td>logical OR for specifiers</td>
                    </tr>
                    <tr>
                        <td>-</td>
                        <td>logical NOT</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <figure>
                <table class="ui compact celled table">
                    <thead>
                    <tr>
                        <th>Specifier</th>
                        <th>Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>#</td>
                        <td>role</td>
                    </tr>
                    <tr>
                        <td>@</td>
                        <td>entity</td>
                    </tr>
                    </tbody>
                </table>
            </figure>
            <h3>Good to Know</h3>
            <h4>Diacritics &amp; Case Sensitivity</h4>
            <ul>
                <li>Search query is diacritic and case insensitive: searching <code>Domenico</code> is equivalent to <code>domenico</code>, and searching <code>Nicolò</code> is equivalent to <code>nicolo</code>. </li>
                <li>Parenthesis are automatically removed from the queries: <code>Giovanni (sartor)</code> is equivalent to <code>giovanni sartor</code>.</li>
            </ul>
            <h4>Token Search</h4>
            <p>By default the search is token-based. Each token must be separated with a space (<code>token1␣token2</code>). Internally, each space is replaced by the operator <code>AND</code>.</p>
            <ul>
                <li><code>giovanni</code> will return all segments (in our case contracts) which contains the string <em>giovanni</em>. </li>
                <li><code>giovanni di simone</code> is equivalent to searching <code>giovanni AND di AND simone</code>; it will return all segments which contains the string <em>giovanni</em>, <em>di</em> and <em>simone</em>. </li>
                <li>Contract registration dates are also indexed, therefore it is possible to search on this information. <code>1575-06-09</code> will return segments which contain the string <em>1575-06-09</em>. It is also possible to search for a year <code>1575</code> or a month <code>1575-06</code></li>
                <li>OR operator: If one wants to search for one token or another, the query <code>giovanni OR simone</code> will return segments which contains one, the other, or both.</li>
            </ul>
            <h4>Phrase Search</h4>
            <p>When using quotes, the query engine considers phrases and not tokens.</p>
            <ul>
                <li><code>&quot;antonio bortoli&quot;</code> will return segments which contain the full phrase, i.e. tokens which follow each other exactly as specified. Here it is good to have in mind the annotation text and the order of its components, as explained in the introduction above.</li>
                <li><code>&quot;antonio bortoli&quot; 1713</code> will return segments which contain both <em>antonio bortoli</em> and <em>1713</em>.</li>
            </ul>
            <h4>Exclusions</h4>
            <p>The minus sign can be used to exclude tokens or phrases which are not wanted. </p>
            <ul>
                <li><code>antonio -bortoli</code> will return segments which contain <code>antonio</code> but not <code>bortoli</code>.</li>
                <li><code>&quot;antonio bortoli&quot; -1713</code> will return segments which contain the phrase <em>antonio bortoli</em>, but not the string <em>1713</em>.</li>
            </ul>
            <h4>Specifying a Person Mention Role</h4>
            <p>The role of a person mention can be specified using the hashtag sign, i.e. <code>#role</code>. Role values can be: <code>apprentice</code>, <code>master</code>, <code>guarantor</code>, or <code>other</code>. Notice that there are is no space between the search term and the role specifier. Alternative roles can be listed, separated by a pipe character.</p>
            <ul>
                <li><code>"antonio bortoli" #master</code> will return segments which contain the name <em>"antonio bortoli"</em> and one or more persons tagged as masters and will generate a lot of results.</li>
                <li><code>"antonio bortoli"#master</code> will return segments which contain the name <em>"antonio bortoli"</em> and that same person has been tagged as a master.</li>
            </ul>
            <h4>Query Example</h4>
            <p>The query <code>"antonio bortoli"#master AND @fuin#apprentice|guarantor</code> will trigger a search for contracts with person mentions containing the name <em>"antonio bortoli"</em> and having the role <em>#master</em>, in addition to person mentions having the role of either <em>#apprentice</em> or <em>#guarantor</em> and referring to entities whose names contain the string <em>fuin</em>.</p>
            <h4>How Does It Work Internally?</h4>
            <p>Internally, the search is made against an index which contains the textual parts of the annotations (e.g. the string "Piero Fuin" in the field "name" of a Person Mention annotation) as well as tags (i.e. roles of person, types of events). Consequently, the search operates at two levels: </p>
            <ul>
                <li>at the lexical level, it allows to look for terms, as they appear in properties of annotations.</li>
                <li>at the semantic level, it allows to look for entity mentions with specific tags, and to retrieve mentions of entities.</li>
            </ul>
            <p>The index is built at the segement level (?)</p>
            <p>The current index does not include all property values, for some are not useful and/or difficult to manipulate in this search context (e.g. the Boolean value <code>hasMargin</code> or <code>onMultiplePages</code> of a contract). As for now, the following mention (contracts and persons) properties are contained in the index:</p>
            <p><strong>Indexation Example</strong></p>
            <p>Let’s consider an (incomplete) annotated segment with: one contract mention, two person mentions and two entities. Each mention has properties, filled in with values by annotators. These properties are defined according to the data model and are those which are manipulated during the annotation. They are not all instantiated. For our incomplete example, we will obtain the following annotations, tags and entities (simplified view):</p>
            <pre>
Contract Mention:
    date = 1575-06-09; additionalDetails = none;
Person Mention 1:
    forename = Francesco; surname = De Garla; patronymic1 = none; patronymic2 = Zuane; geoOrigin = Venezia; profession1 = fabro;
Person Mention 2:
    forename = Zuane; surname = none; patronymic1 = none; patronymic2 = none; geoOrigin = none;
            </pre>
            <pre>
Person Entity 1:
    name = Francesco de Garla; tag = Apprentice;
Person Entity 2:
    name = Giovanni de Garla; tag = Apprentice;
            </pre>
            <p>From this, an indexed annotation text is built:</p>
            <pre>
1575-06-09
Francesco De Garla; Zuanne; Venezia; fabro; @"Francesco de Garla"; #apprentice
Zuane; @"Giovanni de Garla"; #apprentice
            </pre>
            <p>The information present in the indexed text annotation is always in the same order, i.e.:</p>
            <pre>
...
- contract date
- mention1 properties; entity1 name; tag
- mention2 properties; entity2 name; tag
...
            </pre>
            <p>During the indexation process, some parts which are more important for the search (i.e. entity name and date) are given higher weights than others.</p>
            <p>Please note that entity names sometimes contain a profession and/or a place (as a mean of disambiguation), e.g. <code>Giovanni (fabbro a San Barnaba)</code>. </p>
            <p>It is important to know about the indexing order when searching for phrases.</p>
            <h3>Search Result Display</h3>
            <p>Search results are presented in two panels: the lists of results on the right on a page bases, and a preview of documents with the highlighted contract on the left, which allow to quickly evaluate the relevance of an answer. </p>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
