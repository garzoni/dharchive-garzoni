/********* CONFIG *******************/

CONTRACT_URL = 'https://garzoni.dhlab.epfl.ch/contract/view/';

// WARNING : keep this in sync with main.py !
START_DATE = 1550;
END_DATE = 1700;
STEP_DATE = 25;

//COLORS
var gradient = tinygradient([
    {color: '#4444', pos: 0},
    {color: '#5689', pos: 0.1},
    {color: '#A86B', pos: 0.2},
    {color: '#DA4D', pos: 0.5},
    {color: '#F44F', pos: 1}
  ]);
var gradientColors = gradient.rgb(101);

/********* GLOBAL VARIABLES *******************/

var filterActive = false; // is date filter active
var year = START_DATE; // current year on slider
var range = year + '-' + (year + STEP_DATE); // current range on slider

/********* SETUP MAP *******************/

var map;

$(function() {
    var filter  = $('#filter'),
        filterToggle = filter.find('.ui.checkbox'),
        filterRange = filter.find('.ui.range');
    map = new ol.Map({
        target: 'map',
        layers: [
            backgroundLayer,
            originsLayer,
            parishesLayer,
            heatmapLayer,
            linksLayer
        ],
        view: new ol.View({
            center: ol.proj.fromLonLat([12.33,45.4375]),
            zoom: 6
        }),
        controls: []
    });
    map.addInteraction(originHoverInteraction);
    map.addInteraction(parishHoverInteraction);

    map.on('click', mapClick);

    filterToggle.checkbox({
        fireOnInit: true,
        onChange: function() {
            filterToggleChange(filterToggle.checkbox('is checked'))
        }
    });

    filterRange.range({
        min: START_DATE,
        max: END_DATE-STEP_DATE,
        step: STEP_DATE,
        start: year,
        onChange: filterRangeChange
    });
});


/********* SETUP LAYERS ****************/

var backgroundLayer = new ol.layer.Tile({
    source: new ol.source.XYZ({
        // TODO : add attribution !
        // url: 'https://1.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png'
        url: 'https://1.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}.png'
        // url: 'https://1.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png'
        // url: 'https://1.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}.png'
    })
});
var originsLayer = new ol.layer.Vector({
    source: new ol.source.Vector({
        url: 'data/origins.geojson',
        format: new ol.format.GeoJSON()
    }),
    style: originsMainStyle,
    minResolution: 50
});
var parishesLayer = new ol.layer.Vector({
    source: new ol.source.Vector({
        url: 'data/parishes.geojson',
        format: new ol.format.GeoJSON()
    }),
    style: parishMainStyle,
    maxResolution: 50
});
var heatmapLayer = new ol.layer.Heatmap({
    source: new ol.source.Vector({
      url: 'data/heatmap.geojson',
      format: new ol.format.GeoJSON()
    }),
    blur: 8,
    radius: 4,
    // gradient: ['#444', '#00f', '#ff0', '#f00'],
    visible: false,
    weight: heightMapWeight,
    // weight: function(f){return f.get('count')/2.0}
});
var linksLayer = new ol.layer.Vector({
    source: new ol.source.Vector({
        url: 'data/links.geojson',
        format: new ol.format.GeoJSON()
    }),
    style: arrowStyle,
    visible: false
    // maxResolution: 50
});

/********* MAP INTERACTIONS *************/

var parishHoverInteraction = new ol.interaction.Select({
    condition: ol.events.condition.pointerMove,
    style: parishHoverStyle,
    layers:[parishesLayer]  //Setting layers to be hovered
});
var originHoverInteraction = new ol.interaction.Select({
    condition: ol.events.condition.pointerMove,
    style: originsHoverStyle,
    layers:[originsLayer]  //Setting layers to be hovered
});

function mapClick(event){
    var box = $('#infobox'),
        title = box.find('.header > .title'),
        label = box.find('.header > .label'),
        content = box.children('.content').eq(0),
        footer = box.children('.footer').eq(0),
        found = false;
    map.forEachFeatureAtPixel(event.pixel, function(feature) {
        var featureId = feature.get('id'),
            contractCount = feature.get('apprentices_count') || feature.get('workshops_count') || '0';
        box.css('display', 'flex');
        title.html('');
        label.html('');
        content.html('<div class="ui active inline loader"></div>');
        footer.html('');

        if(!found && featureId) {
            map.getView().fit(feature.getGeometry(), {duration: 250});
            title.html(feature.get('name'));
            label.html(feature.get('type'));
            footer.html(contractCount + ' Contracts');
            content.html('');
            $.ajax({
                url: 'data/details/' + featureId + '.geojson',
                dataType: 'json',
                success: function (data) {
                    var features = data['features'].slice(0, 100);
                    $('#infobox .js_apprentices_list').empty();
                    $.each(features, function( index, item ) {
                        var html = $($('#tpl-contract-summary').html());
                        html.find('.contract .date').html(item['properties']['contract_date'] || '&mdash;');
                        html.find('.contract .link').attr('href', CONTRACT_URL + item['properties']['contract_id']);
                        html.find('.master').html(item['properties']['master_name'] || '&mdash;');
                        html.find('.apprentice').html(item['properties']['apprentice_name'] || '&mdash;');
                        content.append(html);
                    });
                }
            });
            found = true;
            box.css('display', 'flex');
        }
    });
    if (!found) {
        box.css('display', 'none');
    }
}

/********* STYLES **********/

var mainStyleBase = new ol.style.Style({
    fill: new ol.style.Fill({
        color: 'rgba(255, 255, 255, 0.6)'
    }),
    stroke: new ol.style.Stroke({
        color: '#666',
        width: 1
    })
});
var hoverStyleBase = new ol.style.Style({
    fill: new ol.style.Fill({
        color: 'rgba(255, 255, 255, 0.6)'
    }),
    stroke: new ol.style.Stroke({
        color: '#666',
        width: 3
    }),
    text: new ol.style.Text({
        font: "18px Lato,'Helvetica Neue',Arial,Helvetica,sans-serif",
        fill: new ol.style.Fill({
            color: '#000'
        }),
    })
});

function _style(feature, resolution, mode, attribute, attr_max_val){

    if(filterActive){
        attribute = attribute+'_'+range
    }
    var c = feature.get(attribute);

    var start_year = feature.get('start_year');
    var end_year = feature.get('end_year');

    if( filterActive ){
        var meanyear = year+Math.round(STEP_DATE/2);
        if(start_year && start_year>meanyear){
            return null;
        }
        if(end_year && end_year<=meanyear){
            return null;
        }
    } else {
        if(end_year){
            return null;
        }
    }

    var base;
    if(mode=='normal'){
        base = mainStyleBase;
    } else {
        base = hoverStyleBase;
        base.getText().setText(feature.get('name')+"\n"+c+' contracts');
    }

    var gradientInt = Math.min(100,Math.max(0,Math.round(c/attr_max_val*100)));
    base.getFill().setColor( gradientColors[gradientInt].toString() );

    return base;
}

function originsMainStyle(feature, resolution){
    return _style(feature, resolution, "normal", "apprentices_count", 75)
}
function originsHoverStyle(feature, resolution){
    return _style(feature, resolution, "hover", "apprentices_count", 75)
}
function parishMainStyle(feature, resolution){
    return _style(feature, resolution, "normal", "workshops_count", 52)
}
function parishHoverStyle(feature, resolution){
    return _style(feature, resolution, "hover", "workshops_count", 52)
}

var arrowStyleBase = new ol.style.Style({
    stroke: new ol.style.Stroke({
        color: '#F80',
        width: 5
    })
});

function arrowStyle(feature, resolution){
    return arrowStyleBase;
}

function heightMapWeight(feature){
    var y = feature.get('year');
    if( filterActive ){
        if(y && y<year){
            return 0;
        }
        if(y && y>=year+STEP_DATE){
            return 0;
        }
    }

    return 1.0;

}


/********* GLOBAL INTERACTIONS **********/

function zoomToVenice(){
    $('#btn_zoom_europe').removeClass('active primary');
    $('#btn_zoom_heatmap').removeClass('active primary');
    $('#btn_zoom_venice').addClass('active primary');
    parishesLayer.setVisible(true);
    originsLayer.setVisible(true);
    heatmapLayer.setVisible(false);
    map.getView().animate({
        zoom: 15,
        center: ol.proj.fromLonLat([12.33,45.4375]),
        duration: 1000
    });
}

function zoomToEurope(){
    $('#btn_zoom_venice').removeClass('active primary');
    $('#btn_zoom_heatmap').removeClass('active primary');
    $('#btn_zoom_europe').addClass('active primary');
    parishesLayer.setVisible(true);
    originsLayer.setVisible(true);
    heatmapLayer.setVisible(false);
    map.getView().animate({
        zoom: 5,
        center: ol.proj.fromLonLat([12.33,45.4375]),
        duration: 1000
    });
}

function zoomToHeatmap(){
    $('#btn_zoom_venice').removeClass('active primary');
    $('#btn_zoom_europe').removeClass('active primary');
    $('#btn_zoom_heatmap').addClass('active primary');
    parishesLayer.setVisible(false);
    originsLayer.setVisible(false);
    heatmapLayer.setVisible(true);
    map.getView().animate({
        zoom: 5,
        center: ol.proj.fromLonLat([12.33,45.4375]),
        duration: 1000
    });
}

function filterRangeChange(value) {
    var filter  = $('#filter'),
        filterSelection = filter.find('.selection');
    year = value;
    range = value + '-' + (value + STEP_DATE);
    filterSelection.text(range);
    parishesLayer.getSource().changed();
    originsLayer.getSource().changed();
    heatmapLayer.getSource().changed();
    heatmapLayer.changed();
}

function filterToggleChange(value) {
    var filter  = $('#filter'),
        filterContent = filter.find('.content'),
        filterRange = filter.find('.ui.range');
    filterActive = value;
    if (filterActive) {
        filterContent.show();
        filterRange.removeClass('disabled');
    } else {
        filterContent.hide();
        filterRange.addClass('disabled');
    }
    parishesLayer.getSource().changed();
    originsLayer.getSource().changed();
}
