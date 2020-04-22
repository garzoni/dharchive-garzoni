<?php

namespace Application;

use Application\Core\Configuration\Repository;

return new Repository([
    'translations' => ['translation', 'index'],
    'permissions' => ['permission', 'index'],
    'roles' => ['role', 'index'],
    'users' => ['user', 'index'],
    'profile' => ['user', 'view'],
    'settings' => ['user', 'update'],
    'login' => ['user', 'login'],
    'logout' => ['user', 'logout'],
    'collections' => ['collection', 'index'],
    'documents' => ['document', 'index'],
    'contracts' => ['contract', 'index'],
    'persons' => ['person', 'index'],
    'person-mentions' => ['person-mention', 'index'],
    'search' => ['annotation', 'search'],
    'entity-types' => ['entity-type', 'index'],
    'pres' => ['presentation', 'export'],
    'map' => ['home', 'page', ['map']],
    'query' => ['home', 'page', ['query']],
    'about-project' => ['home', 'page', ['about-project']],
    'historical-source' => ['home', 'page', ['historical-source']],
    'data-acquisition' => ['home', 'page', ['data-acquisition']],
    'data-model' => ['home', 'page', ['data-model']],
    'terms-of-use' => ['home', 'page', ['terms-of-use']],
    'data-exploration' => ['home', 'page', ['data-exploration']],
    'faceted-search' => ['home', 'page', ['faceted-search']],
    'full-text-search' => ['home', 'page', ['full-text-search']],
    'sparql' => ['home', 'page', ['sparql']],
    'data-exports' => ['home', 'page', ['data-exports']],
    'workflows' => ['home', 'page', ['workflows']],
    'faq' => ['home', 'page', ['faq']],
]);

// -- End of file
