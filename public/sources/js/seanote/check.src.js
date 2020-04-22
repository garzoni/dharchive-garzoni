(function(dep) {
    'use strict';

    var acceptHigherVersion = false,
        errorMessage = '',
        i, l, r;

    function parseVersionString(s) {
        var version = [],
            versionComponents = s.split('.'),
            i;
        for (i = 0; i < 3; i++) {
            version.push(parseInt(versionComponents[i], 10) || 0);
        }
        return version;
    }

    for (i = 0; i < dep.length; i++) {
        acceptHigherVersion = (dep[i].requiredVersion.slice(-1) === '+');
        errorMessage = 'Seanote requires ' + dep[i].name + ' ' + dep[i].requiredVersion;
        if (acceptHigherVersion) {
            errorMessage = errorMessage.slice(0, -1) + ' or higher';
        }
        if (!dep[i].loadedVersion || (!acceptHigherVersion
            && (dep[i].loadedVersion !== dep[i].requiredVersion))) {
            throw new Error(errorMessage);
        } else {
            l = parseVersionString(dep[i].loadedVersion);
            r = parseVersionString(dep[i].requiredVersion);
            if ((l[0] < r[0]) || ((l[0] === r[0]) && (l[1] < r[1]))
                || ((l[0] === r[0]) && (l[1] === r[1]) && (l[2] < r[2]))) {
                throw new Error(errorMessage);
            }
        }
    }
}([ // Dependencies
    {
        name: 'jQuery',
        requiredVersion: '2.1.1+',
        loadedVersion: jQuery ? jQuery.fn.jquery : null
    },
    {
        name: 'OpenSeadragon',
        requiredVersion: '2.0.0+',
        loadedVersion: OpenSeadragon ? OpenSeadragon.version.versionStr : null
    }
]));
