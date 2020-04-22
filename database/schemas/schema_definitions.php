<?php

$def = [
    '@reUuid' => '^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
    '@reQName' => '^[a-z]{1,10}:[A-Za-z]{1}[A-Za-z0-9(),_.]+$',
    '@reToken' => '^[a-z]{1}[a-z0-9_-]+$',
    '@reMimeType' => '^([a-z-]{4,12}/[a-z0-9.+-]+)$',
    '@reLang' => '^[a-z]{2,3}(-([A-Z]{1}[a-z]{3}))?(-([A-Z]{2}|[0-9]{3}))?$',
    '@reDate' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
    '@reDateTime' => '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$',
];

foreach($def as $key => $value) {
    $def[$key] = json_encode($value);
}

// --------------------
// Functions
// --------------------

/**
 * Generates a JSON schema for an object.
 *
 * @param string $object
 * @param string $label
 * @param string $otherProperties
 * @return string
 */
function genSchema(
    string $object,
    string $label,
    string $otherProperties = ''
): string {
    $otherProperties = trim($otherProperties);
    $schema = '{"label": ' . $label . ', ' . $object;
    if (!empty($otherProperties)) {
        $schema .= ', ' . $otherProperties;
    }
    $schema .= '}';
    return $schema;
}

/**
 * Generates a JSON schema for an array.
 *
 * @param string $object
 * @param string $label
 * @param bool $allowSingle
 * @param int $minItems
 * @param int $maxItems
 * @param string $otherProperties
 * @return string
 */
function genArraySchema(
    string $object,
    string $label,
    bool $allowSingle = false,
    int $minItems = 1,
    int $maxItems = 0,
    string $otherProperties = ''
): string {
    $otherProperties = trim($otherProperties);
    $schema = '{"label": ' . $label
        . ', "type": "array", "items": ' . $object;
    if ($minItems > 0) {
        $schema .= ', "minItems": ' . $minItems;
    }
    if ($maxItems > 0) {
        $schema .= ', "maxItems": ' . $maxItems;
    }
    if (!empty($otherProperties)) {
        $schema .= ', ' . $otherProperties;
    }
    $schema .= '}';
    if ($allowSingle) {
        return '"oneOf": [' . $object . ', ' . $schema . ']';
    } else {
        return $schema;
    }
}

// --------------------
// Numbers
// --------------------

$def['@integer:'] = '
    "type": "number",
    "multipleOf": 1.0
';

$def['@width'] = genSchema(
    $def['@integer:'],
    '{"en": "Width"}'
);
$def['@height'] = genSchema(
    $def['@integer:'],
    '{"en": "Height"}'
);
$def['@xValue'] = genSchema(
    $def['@integer:'],
    '{"en": "X Value"}'
);
$def['@yValue'] = genSchema(
    $def['@integer:'],
    '{"en": "Y Value"}'
);

$def['@boundingBox'] = '
    {
        "label": {
            "en": "Bounding Box"
        },
        "type": "object",
        "properties": {
            "w": ' . $def['@width'] . ',
            "h": ' . $def['@height'] . ',
            "x": ' . $def['@xValue'] . ',
            "y": ' . $def['@yValue'] . '
        },
        "additionalProperties": false,
        "required": ["w", "h", "x", "y"]
    }
';

// --------------------
// Plain Strings
// --------------------

$def['@string:'] = '
    "type": "string",
    "minLength": 1
';

$def['@label'] = genSchema(
    $def['@string:'],
    '{"en": "Label"}'
);
$def['@name'] = genSchema(
    $def['@string:'],
    '{"en": "Name"}'
);
$def['@content'] = genSchema(
    $def['@string:'],
    '{"en": "Content"}'
);
$def['@description'] = genSchema(
    $def['@string:'],
    '{"en": "Description"}'
);
$def['@attribution'] = genSchema(
    $def['@string:'],
    '{"en": "Attribution"}'
);

// --------------------
// Multilingual Strings
// --------------------

$def['@multilingual:'] = '
    "oneOf": [
        {
            ' . $def['@string:'] . '
        },
        {
            "type": "object",
            "patternProperties": {
                ' . $def['@reLang'] . ': {
                    ' . $def['@string:'] . '
                }
            },
            "additionalProperties": false
        }
    ]
';

$def['@multilingualLabel'] = genSchema(
    $def['@multilingual:'],
    '{"en": "Label"}'
);
$def['@multilingualName'] = genSchema(
    $def['@multilingual:'],
    '{"en": "Name"}'
);
$def['@multilingualContent'] = genSchema(
    $def['@multilingual:'],
    '{"en": "Content"}'
);
$def['@multilingualDescription'] = genSchema(
    $def['@multilingual:'],
    '{"en": "Description"}'
);
$def['@multilingualAttribution'] = genSchema(
    $def['@multilingual:'],
    '{"en": "Attribution"}'
);

// --------------------
// Tokens
// --------------------

$def['@uri:'] = '
    "type": "string",
    "format": "uri",
    "options": {
        "hidden": true
    }
';

$def['@uri'] = genSchema(
    $def['@uri:'],
    '{"en": "URI"}'
);

$def['@uuid:'] = '
    "type": "string",
    "pattern": ' . $def['@reUuid'] . '
';

$def['@uuid'] = genSchema(
    $def['@uuid:'],
    '{"en": "UUID"}'
);

$def['@code:'] = '
    "type": "string",
    "pattern": ' . $def['@reToken'] . '
';

$def['@code'] = genSchema(
    $def['@code:'],
    '{"en": "Code"}'
);

$def['@qualifiedName:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "options": {
        "hidden": true
    }
';

$def['@qualifiedName'] = genSchema(
    $def['@qualifiedName:'],
    '{"en": "Qualified Name"}'
);

$def['@date:'] = '
    "type": "string",
    "pattern": ' . $def['@reDate'] . ',
    "placeholder": {
        "en": "YYYY-MM-DD"
    }
';

$def['@date'] = genSchema(
    $def['@date:'],
    '{"en": "Date"}'
);

$def['@timestamp:'] = '
    "type": "string",
    "pattern": ' . $def['@reDateTime'] . ',
    "placeholder": {
        "en": "YYYY-MM-DD hh:mm:ss"
    }
';

$def['@timestamp'] = genSchema(
    $def['@timestamp:'],
    '{"en": "Timestamp"}'
);

$def['@fileFormat:'] = '
    "type": "string",
    "pattern": ' . $def['@reMimeType'] . ',
    "options": {
        "hidden": true
    }
';

$def['@fileFormat'] = genSchema(
    $def['@fileFormat:'],
    '{"en": "File Format"}'
);

// --------------------
// Lists
// --------------------

$def['@language:'] = '
    "type": "string",
    "pattern": ' . $def['@reLang'] . ',
	"_choiceList": {
        "listQName": "dhc:Language",
        "keyProperty": "relative_id"
    }
';

$def['@language'] = genSchema(
    $def['@language:'],
    '{"en": "Language"}'
);
$def['@languageTag'] = genSchema(
    $def['@language:'],
    '{"en": "Language Tag"}'
);

$def['@style:'] = '
    "type": "string",
    "pattern": ' . $def['@reToken'] . ',
	"_choiceList": {
        "listQName": "dhc:Style",
        "keyProperty": "relative_id"
    }
';

$def['@style'] = genSchema(
    $def['@style:'],
    '{"en": "Style"}'
);

$def['@motivation:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "oa:Motivation",
        "keyProperty": "qualified_name"
    }
';

$def['@motivation'] = genSchema(
    $def['@motivation:'],
    '{"en": "Motivation"}'
);

$def['@viewingDirection:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "sc:ViewingDirection",
        "keyProperty": "qualified_name"
    }
';

$def['@viewingDirection'] = genSchema(
    $def['@viewingDirection:'],
    '{"en": "Viewing Direction"}'
);

$def['@viewingHint:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "sc:ViewingHint",
        "keyProperty": "qualified_name"
    }
';

$def['@viewingHint'] = genSchema(
    $def['@viewingHint:'],
    '{"en": "Viewing Hint"}'
);

$def['@certainty:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "dhc:CertaintyList",
        "keyProperty": "qualified_name"
    }
';

$def['@certainty'] = genSchema(
    $def['@certainty:'],
    '{"en": "Certainty"}'
);

// --------------------
// IIIF Schemas
// --------------------

$def['@iiifLink:'] = '
    "type": "object",
    "properties": {
        "@id": ' . $def['@uri'] . ',
        "profile": ' . genSchema($def['@uri:'], '{"en": "Profile URI"}') . ',
        "label": ' . $def['@multilingualLabel'] . ',
        "format": ' . $def['@fileFormat'] . '
    },
    "additionalProperties": false
';

$def['@iiifService:'] = '
    "type": "object",
    "properties": {
        "@context": ' . genSchema($def['@uri:'], '{"en": "Context URI"}') . ',
        "@id": ' . $def['@uri'] . ',
        "profile": ' . genSchema($def['@uri:'], '{"en": "Profile URI"}') . '
    },
    "additionalProperties": true,
    "required": ["@context", "@id", "profile"]
';

$def['@iiifService'] = genSchema(
    $def['@iiifService:'],
    '{"en": "Service"}'
);
$def['@iiifService[]'] = genArraySchema(
    $def['@iiifService'],
    '{"en": "Services"}'
);

$def['@iiifImage:'] = '
    "oneOf": [
        ' . $def['@uri'] . ',
        ' . $def['@iiifService'] . '
    ]
';

$def['@iiifThumbnail'] = genSchema(
    $def['@iiifImage:'],
    '{"en": "Thumbnail"}'
);
$def['@iiifLogo'] = genSchema(
    $def['@iiifImage:'],
    '{"en": "Logo"}'
);

$def['@iiifLicense'] = genSchema(
    $def['@iiifLink:'],
    '{"en": "License"}',
    '"required": ["@id"]'
);
$def['@iiifRelatedResource'] = genSchema(
    $def['@iiifLink:'],
    '{"en": "Related Resource"}',
    '"required": ["@id", "label", "format"]'
);
$def['@iiifRelatedResource[]'] = genArraySchema(
    $def['@iiifRelatedResource'],
    '{"en": "Related Resources"}'
);
$def['@iiifSeeAlso'] = genSchema(
    $def['@iiifLink:'],
    '{"en": "See Also"}',
    '"required": ["@id", "format"]'
);
$def['@iiifSeeAlso[]'] = genArraySchema(
    $def['@iiifSeeAlso'],
    '{"en": "See Also"}'
);
$def['@iiifManifestUuid'] = genSchema(
    $def['@uuid:'],
    '{"en": "Manifest UUID"}'
);
$def['@iiifCanvasCode'] = genSchema(
    $def['@code:'],
    '{"en": "Canvas Code"}'
);

// --------------------
// Garzoni Schemas
// --------------------

$def['@grzCurrencyUnit:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:CurrencyUnit",
        "keyProperty": "qualified_name",
        "labelProperty": "label"
    }
';

$def['@grzCurrencyUnit'] = genSchema(
    $def['@grzCurrencyUnit:'],
    '{"en": "Currency Unit"}'
);

$def['@grzSestriere:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:Sestriere",
        "keyProperty": "qualified_name",
        "labelProperty": "name"
    }
';

$def['@grzSestriere'] = genSchema(
    $def['@grzSestriere:'],
    '{"en": "Sestiere"}'
);

$def['@grzParish:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:Parish",
        "keyProperty": "qualified_name",
        "labelProperty": "name",
        "minCharacters": 2
    }
';

$def['@grzParish'] = genSchema(
    $def['@grzParish:'],
    '{"en": "Parish"}'
);

$def['@grzGender:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:GenderList",
        "keyProperty": "qualified_name"
    }
';

$def['@grzGender'] = genSchema(
    $def['@grzGender:'],
    '{"en": "Gender"}'
);

$def['@grzPayer:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:PayerList",
        "keyProperty": "qualified_name"
    }
';

$def['@grzPayer'] = genSchema(
    $def['@grzPayer:'],
    '{"en": "Payer"}'
);

$def['@grzPeriodization:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:PeriodizationList",
        "keyProperty": "qualified_name"
    }
';

$def['@grzPeriodization'] = genSchema(
    $def['@grzPeriodization:'],
    '{"en": "Periodization"}'
);

$def['@grzRoleType:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:RoleTypeList",
        "keyProperty": "qualified_name"
    }
';

$def['@grzRoleType'] = genSchema(
    $def['@grzRoleType:'],
    '{"en": "Role Type"}'
);

$def['@grzLocationType:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:LocationTypeList",
        "keyProperty": "qualified_name"
    }
';

$def['@grzLocationType'] = genSchema(
    $def['@grzLocationType:'],
    '{"en": "Location Type"}'
);

$def['@grzApplicationRule:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:ApplicationRuleList",
        "keyProperty": "qualified_name"
    }
';

$def['@grzApplicationRule'] = genSchema(
    $def['@grzApplicationRule:'],
    '{"en": "Application Rule"}'
);

$def['@grzTranscript'] = genSchema(
    $def['@string:'],
    '{"en": "Transcript"}'
);
$def['@grzStandardForm'] = genSchema(
    $def['@string:'],
    '{"en": "Standard Form"}'
);

$def['@grzLocation:'] = '
    "transcript": ' . $def['@grzTranscript'] . ',
    "standardForm": ' . $def['@grzStandardForm'] . ',
    "sestiere": ' . $def['@grzSestriere'] . ',
    "parish": ' . $def['@grzParish'] . '
';

$def['@grzPersonRelationType:'] = '
    "type": "string",
    "pattern": ' . $def['@reQName'] . ',
    "_choiceList": {
        "listQName": "grz:PersonRelationList",
        "keyProperty": "qualified_name"
    }
';

$def['@grzPersonRelationType'] = genSchema(
    $def['@grzPersonRelationType:'],
    '{"en": "Person Relation Type"}'
);

$def['@grzPerson:'] = '
    "type": "string",
    "pattern": ' . $def['@reUuid'] . ',
    "_choiceList": {
        "listQName": "grz:Person",
        "keyProperty": "id",
        "labelProperty": "name",
        "minCharacters": 2
    }
';

$def['@grzPerson'] = genSchema(
    $def['@grzPerson:'],
    '{"en": "Person"}'
);

// --------------------

$schemaVariables = [];

foreach($def as $key => $value) {
    $key = (substr($key, -1) == ':') ?
        '"' . rtrim($key, ':') . '": {}' : '"' . $key . '"';
    $schemaVariables[$key] = $value;
}

// -- End of file
