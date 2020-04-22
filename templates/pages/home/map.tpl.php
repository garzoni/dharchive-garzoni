<?php $this->include('components/page_begin.tpl.php'); ?>

<div id="map" class="map"></div>

<div id="pane">
    <div id="filter" class="ui segment box">
        <div class="header">
            <h4 class="title">Time Period</h4>
            <div class="tools">
                <div class="ui slider checkbox">
                    <input name="public" type="checkbox">
                </div>
            </div>
        </div>
        <div class="content">
            <div class="ui range disabled"></div>
            <p class="selection"></p>
        </div>
    </div>
    <div id="infobox" class="ui segment box">
        <div class="header">
            <h4 class="title"></h4>
            <span class="ui horizontal basic label"></span>
        </div>
        <div class="content"></div>
        <div class="footer"></div>
    </div>
</div>

<div id="layer-selector">
    <div class="ui segment">
        <div class="ui small basic buttons">
            <button class="ui button active" onclick="zoomToEurope()" id="btn_zoom_europe">Apprentice Origins</button>
            <button class="ui button" onclick="zoomToHeatmap()" id="btn_zoom_heatmap">Apprentice Origins (Heatmap)</button>
            <button class="ui button" onclick="zoomToVenice()" id="btn_zoom_venice">Workshops</button>
        </div>
    </div>
</div>

<template id="tpl-contract-summary">
    <table class="ui fixed compact celled table contract-summary">
        <tbody>
        <tr>
            <td>Date</td>
            <td class="contract">
                <span class="date"></span>
                <a class="link" target="_blank" href="#">
                    <i class="ui newspaper outline icon"></i>
                </a>
            </td>
        </tr>
        <tr>
            <td>Master</td>
            <td class="master"></td>
        </tr>
        <tr>
            <td>Apprentice</td>
            <td class="apprentice"></td>
        </tr>
        </tbody>
    </table>
</template>

<?php $this->include('components/page_end.tpl.php'); ?>
