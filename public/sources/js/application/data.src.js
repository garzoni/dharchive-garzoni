
$(document).ready(function() {
    var yasqe = $('#yasqe'),
        yasr = $("#yasr");
    if (yasqe.length && yasr.length) {
        $.yasqe = YASQE(yasqe[0], {
            sparql: {
                endpoint: 'https://sparql.dhlab.epfl.ch/sparql',
                showQueryButton: true
            },
            createShareLink: null
        });

        YASR.plugins.table.defaults.datatable.lengthMenu = [
            [10, 25, 50, -1],
            [10, 25, 50, 'All']
        ];

        /*
        YASR.plugins.table.defaults.datatable.language = {
            paginate: {
                next: '<i class="angle double right icon">',
                previous: '<i class="angle double left icon">'
            }
        };
        */

        YASR.plugins.table.defaults.datatable.dom = '<fl<t>ip>';

        $.yasr = YASR(yasr[0], {
            outputPlugins: ['error', 'boolean', 'rawResponse', 'table'], // 'gchart', 'pivot'
            output: 'table',
            useGoogleCharts: false,
            getUsedPrefixes: $.yasqe.getPrefixesFromQuery
        });

        $.yasqe.options.sparql.handlers.success = function(data, textStatus, xhr) {
            $.yasr.setResponse({response: data, contentType: xhr.getResponseHeader("Content-Type")});
        };
        $.yasqe.options.sparql.handlers.error = function(xhr, textStatus, errorThrown) {
            var exceptionMsg = textStatus + " (response status code " + xhr.status + ")";
            if (errorThrown && errorThrown.length) exceptionMsg += ": " + errorThrown;
            $.yasr.setResponse({exception: exceptionMsg});
        };
    }
});

function loadQuery (queryname) {
    var url = 'https://raw.githubusercontent.com/garzoni/garzoni-sparql-api/master/' + queryname + '.rq';
    $.ajax(url, {
        dataType: 'text',
        success: function (data) {
            $.yasqe.setValue(data);
            $.yasqe.query();
        }
    });
}
