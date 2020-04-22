<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui padded text container segment">
            <p>Garzoni data was collected thanks to the annotation work of experimented palaeographers based on a we-based transcription and annotation interface named <em>DHCanvas</em>.</p>
            <h3>DHCanvas</h3>
            <p>The DHLAB developed a generic web-based application for the visualization, transcription and annotation of historical documents, named <em>DHCanvas</em>. Its overall goal is to allow users to collaboratively transcribe and semantically annotate documents, thereby producing information which can be stored, processed, retrieved and exchanged. Some of the key features of DHCanvas are:</p>
            <ul>
                <li>preservation of the link between the annotation (already an interpretation) and the source (an image segment)</li>
                <li>capacity to handle concurrent annotations</li>
                <li>full compatibility with representation standards for digital resources (<a href='http://iiif.io/'>IIIF</a>, Web Annotation Data Model)</li>
                <li>generic design based on meta objects and JSON schemas which allow to use the interface for other sources</li>
            </ul>
            <p>Even though application scenarios involving image annotation can be diverse, DHCanvas focuses on annotation of images of textual documents. In this scenario, the user reads a page, selects an image segment (usually corresponding to a segment of text) and is being offered the possibility to transcribe and annotate the textual chunk imaged by that segment. The annotation of a segment involves several sub-annotations which encode various types of information. In a document annotation context, information can be regarded as belonging to three different levels:</p>
            <ul>
                <li>resource level — what relates to a digital resource/canvas, e.g. an image segment representing the name of a person</li>
                <li>document level — what relates to textual content, e.g. an annotation representing the mention of a person</li>
                <li>entity level — what relates to concepts and entities of the world, e.g. an annotation representing the entity a mention refers to</li>
            </ul>
            <p>In concrete terms, these levels are reflected by the annotation workflow offered to the user in DHCanvas.</p>
            <p>At <strong>level (1)</strong>, the user <em>selects</em> (or draw) a <em>*segment</em> of the image and <em>transcribes</em> it. In the Garzoni context, transcription capacities were not used. </p>
            <p>At <strong>level (2)</strong>, the user <em>describes</em> what the text is about, that is to say creates <em><strong>mention annotations</strong></em> gathering <em>local</em> information about the concept or the entity <em>mentioned</em> in the current segment. A Mention is of a certain type (Person, Event, Contract) and consists of a tag (a kind of sub-category which specifies the role of the mentioned entity within the local context) and a series of attributes. </p>
            <p>Finally, at <strong>level (3)</strong>, the user <em>identifies</em> to which real-world entity the mention refers to, that is to say creates an <em><strong>entity annotation</strong></em> meant to identify and to gather information about the real-world concept or entity, independently from the local/textual context. The information associated to the entity consists of a name, which should be unique, and possibly a (family) relationship towards another entity. In this way, information about an entity is progressively assembled by collecting elements attached to related mentions. </p>
            <p>The annotation part of the inteface is not accessible to the public, you can however discover the annotation workflow through this video:</p>
            <iframe width="640" height="360" src="https://tube.switch.ch/embed/f9d3909c" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
