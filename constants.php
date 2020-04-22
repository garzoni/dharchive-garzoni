<?php

namespace Application;

const ENVIRONMENT = 'production';

// Namespaces and Directories
const NAMESPACE_SEPARATOR = '\\';
const ROOT_DIR = __DIR__ . DIRECTORY_SEPARATOR;
const APP_DIR = ROOT_DIR . 'application';

// Date and Time
const DATE_APP = 'Y-m-d';
const TIME_APP = 'H:i:s';
const DATETIME_APP = DATE_APP . ' ' . TIME_APP;

// Regular Expressions
const REGEX_EMAIL = '^[a-z0-9_.+-]+@[a-z0-9-]+\.[a-z0-9-.]+$';
const REGEX_LANG = '^[a-z]{2,3}(-([A-Z]{1}[a-z]{3}))?(-([A-Z]{2}|[0-9]{3}))?$';
const REGEX_QNAME = '^[a-z]{1,10}:[A-Za-z]{1}[A-Za-z0-9(),_.]+$';
const REGEX_UUID = '^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$';

// Application Settings
const MEMORY_LIMIT = '512M';
const VALUE_LIST_LIMIT = 50;

// -- End of file
