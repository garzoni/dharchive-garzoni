<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui padded text container segment">
            <h3>Entry Points </h3>
            <p>Garzoni data can be explored via different entry points requiring different levels of digital literacy.</p>
            <ol start=''>
                <li>
                    <p><strong>Faceted search</strong>: available via a Graphical User Interface (GUI), allows fine-grained selection of contracts and requires basic knowledge of Garzoni Data Model.</p>
                </li>
                <li>
                    <p><strong>Full-text search</strong>: available via a GUI, allows selection of contracts and requires basic knowledge of Garzoni Data Model.</p>
                </li>
                <li>
                    <p><strong>SPARQL endpoint</strong>: allows large scale exploration of the data-set , requires basic knowledge of RDF, intermediate knowledge of sparql, and knowledge of the Garzoni RDF Model.</p>
                </li>
                <li>
                    <strong><a href="/data/download">Data dumps</a></strong>:
                    <ul>
                        <li><strong>Excel format</strong>: allows custom exploration of the data-set or a sub-collection of it, requires basic knowledge of Garzoni Data Model and of Excel functionalities (e.g. Pivot Table and VLOOKUP) or <a href='https://www.knime.com/knime-software/knime-analytics-platform'>KNIME Analytics Platform</a></li>
                        <li><strong>JSON format</strong>: allows custom, large-scale exploration of the data-set or a sub-collection of it, requires basic knowledge of Garzoni Data Model and of a scripting language (e.g. Python) or KNIME</li>
                        <li><strong>RDF dump</strong>: : allows custom, large-scale exploration of the data-set or a sub-collection of it, requires basic knowledge of Garzoni Data Model, of RDF and of a scripting language (e.g. Python)</li>
                    </ul>
                </li>
            </ol>
            <h3>What Should I Use?</h3>
            <p><strong>I am a humanist with limited digital skills</strong></p>
            <p>It is recommended to start with the explanations about <a href="/data-acquisition">Data Acquisition</a> and <a href="/data-model">Garzoni Data Model</a>. For exploration, best is to use the Faceted search GUI to select a sub-collection of interest. Finally, for custom study of a sub-collection according to a specific historical question, it is recommended to export data in Excel and to use KNIME, as illustrated in the <a href="/workflows">Recipes</a> videos.</p>
            <p><strong>I am a data scientist or a humanist with intermediate digital skills</strong></p>
            <p>As above it is recommended to read about Data Acquisition and Garzoni Data Model. For Data Exploration, it is possible to skip the GUI and to rely on JSON export, either programmatically, either via KNIME.</p>
            <h3>Whom Should I Contact if I Have Questions?</h3>
            <p>For questions related to the historical source and profession and location normalisation: <a href='mailto:valentina.sapienza@unive.it'>Valentina Sapienza</a> and <a href='mailto:anna.bellavitis@univ-rouen.fr'>Anna Bellavitis</a>.</p>
            <p>For questions related to data management, acquisition and exploration: <a href='mailto:maud.ehrmann@epfl.ch'>Maud Ehrmann</a>.</p>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
