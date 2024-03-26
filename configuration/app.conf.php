<?php

namespace Application;

use Application\Core\Configuration\Repository;

return new Repository(call_user_func(function()
{
    // Basic configuration
    $settings = [
        'app_name'      => 'Garzoni',
        'app_version'   => '2.0',
        'protocol'      => 'https',
        'domain'        => 'garzoni.org',
        'timezone'      => 'Europe/Zurich',
        'charset'       => 'utf-8',
        'owner'         => 'Garzoni Project',
    ];

    // Environment
    switch (ENVIRONMENT) {
        case 'development':
            $settings['domain'] = 'dev.garzoni.org';
            $settings['env'] = [
                'display_errors'        => true,
                'error_reporting'       => E_ALL,
                'load_compiled_assets'  => false,
            ];
            // Database
            $settings['db'] = [
                'driver'    => 'pgsql',
                'host'      => 'localhost',
                'port'      => 5432,
                'database'  => '',
                'username'  => '',
                'password'  => '',
            ];
            break;
        case 'production':
            $settings['env'] = [
                'display_errors'        => false,
                'error_reporting'       => E_ALL,
                'load_compiled_assets'  => true,
            ];
            // Database
            $settings['db'] = [
                'driver'    => 'pgsql',
                'host'      => 'localhost',
                'port'      => 5432,
                'database'  => '',
                'username'  => '',
                'password'  => '',
            ];
            break;
        default:
            trigger_error('Undefined environment', E_USER_ERROR);
    }

    // Database
    $settings['db']['dsn'] = $settings['db']['driver'] . ':'
        . 'host=' . $settings['db']['host'] . ';'
        . 'port=' . $settings['db']['port'] . ';'
        . 'dbname=' . $settings['db']['database'];

    // Logging
    $settings['log'] = [
        'date_format' => 'Y-m-d H:i:s',
    ];

    // Request
    $settings['request'] = [
        'language'      => 'en',
        'module'        => 'main',
        'controller'    => 'home',
        'action'        => 'index',
        'force_www'     => false,
    ];

    // Cache
    $settings['cache'] = [
        'lifetime' => 3600, // seconds
        'prefix' => $settings['domain'],
    ];

    // Session
    $settings['session'] = [
        'handler' => [
            'table'                 => 'agent_sessions',
            'id_column'             => 'id',
            'data_column'           => 'data',
            'start_column'          => 'start_time',
            'last_access_column'    => 'last_access_time',
            'requests_column'       => 'requests',
        ],
        'options' => [
            'auto_start'                => 0,
            'cache_limiter'             => 'nocache',
            'cookie_domain'             => '.' . $settings['domain'],
            'cookie_lifetime'           => 3600 * 24, // seconds
            'cookie_path'               => '/',
            'gc_maxlifetime'            => 3600 * 24, // seconds
            'gc_probability'            => 5,
            'gc_divisor'                => 50,
            'hash_bits_per_character'   => 5, // 32-character string
            'hash_function'             => 1, // sha-1
            'name'                      => 'sid',
            'save_handler'              => 'user',
            'use_cookies'               => 1,
        ],
    ];

    // Directories
    $settings['dir'] = [
        'root'      => ROOT_DIR,
        'app'       => ROOT_DIR . 'application/',
        'config'    => ROOT_DIR . 'configuration/',
        'db'        => ROOT_DIR . 'database/',
        'public'    => ROOT_DIR . 'public/',
        'scripts'   => ROOT_DIR . 'scripts/',
        'backup'    => ROOT_DIR . 'storage/backup/',
        'cache'     => ROOT_DIR . 'storage/cache/',
        'logs'      => ROOT_DIR . 'storage/logs/',
        'sessions'  => ROOT_DIR . 'storage/sessions/',
        'temp'      => ROOT_DIR . 'storage/temp/',
        'templates' => ROOT_DIR . 'templates/',
        'lang'      => ROOT_DIR . 'translations/',
        'vendor'    => ROOT_DIR . 'vendor/',
    ];

    // Files
    $configDir = $settings['dir']['config'];
    $settings['files'] = [
        'compiled_assets'   => $configDir . 'assets/compiled.conf.php',
        'asset_sources'     => $configDir . 'assets/sources.conf.php',
        'mime_types'        => $configDir . 'mime_types.conf.php',
        'routes'            => $configDir . 'routes.conf.php',
    ];

    // URLs
    $baseUrl = $settings['protocol'] . '://'
        . ($settings['request']['force_www'] ? 'www.' : '')
        . $settings['domain'] . '/';

    $settings['url'] = [
        'base'      => $baseUrl,
        'errors'    => $baseUrl . 'errors/',
        'assets'    => $baseUrl . 'assets/',
        'img'       => $baseUrl . 'assets/img/',
        'fonts'     => $baseUrl . 'assets/fonts/',
        'src'       => $baseUrl . 'sources/',
    ];

    // Languages
    $settings['languages'] = [
        'en' => [
            'name'              => 'English',
            'locale'            => 'en_US',
        ],
    ];

    // Modules
    $settings['modules'] = [
        'main' => [],
        'iiif' => [],
        'api' => [],
    ];

    // IIIF APIs
    $settings['iiif'] = [
        'image' => [
            'protocol'      => 'http://iiif.io/api/image',
            'context'       => 'http://iiif.io/api/image/2/context.json',
            'profile'       => 'http://iiif.io/api/image/2/profiles/level2.json',
            'server'        => [
                'url'       => 'https://images.center/iiif',
                'dir'       => '/usr/local/share/images',
                'cache_dir' => '/var/cache/loris2',
            ]
        ],
        'pres'  => [
            'context'   => 'http://iiif.io/api/presentation/2/context.json',
            'base_url'  => $baseUrl . 'iiif/pres/',
        ],
    ];

    // Annotations
    $settings['annotation'] = [
        'rules' => [
            'transcriptions' => [
                'types' => [
                    'dhc:Transcription',
                ],
                'allow_multiple' => true,
            ],
            'mentions' => [
                'types' => [
                    'grz:ContractMention',
                    'grz:EventMention',
                    'grz:PersonMention',
                    'grz:HostingConditionMention',
                    'grz:FinancialConditionMention',
                    'grz:ProfessionMention',
                    'grz:NumberMention',
                ],
                'allow_multiple' => true,
            ],
        ],
    ];

    return $settings;
}));

// -- End of file
