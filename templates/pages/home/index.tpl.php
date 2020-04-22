
<?php ob_start(); ?>

<style>
    #main-content {
        background: white;
    }
    #hero {
        padding-top: 0;
    }
    #hero .column {
        padding: 0;
    }
    #about-project .column {
        padding: 1rem 8rem;
    }
    #about-project p {
        margin: 1rem 0;
        font-size: 1.15rem;
    }
    #institutions img {
        height: 80px;
    }
    #institutions .grid:last-child img {
        height: 60px;
    }
</style>

<?php $this->addSnippet(ob_get_clean(), 'head'); ?>

<?php $this->include('components/page_begin.tpl.php'); ?>

<?php $this->include('components/header.tpl.php'); ?>

<div id="main-container">

    <?php $this->include('components/main_menu.tpl.php'); ?>

    <div id="main-content">
        <div class="ui stackable grid">
            <div class="row" id="hero">
                <div class="sixteen wide column">
                    <div class="ui inverted vertical masthead center aligned segment">
                        <div class="ui text container">
                            <h1 class="ui inverted header">Apprenticeship in Venice</h1>
                            <h2>The <i>Accordi dei Garzoni</i> data from 1575 until 1772</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="about-project">
                <div class="sixteen wide column">
                    <p>
                        Who were these young &quot;garzoni&quot; hired as apprentices in Venice between the 16th and 18th centuries?
                        Which professions did they learn and under which conditions? Were they all entitled to a salary,
                        or sometimes only a pair of shoes with food and accommodation?
                        How long were they trained and with what prospect?
                    </p>
                    <p>
                        Possible answers to these questions — and many others — can certainly be found in the archival sources
                        of the Accordi dei Garzoni, kept in the State Archives of Venice. Thanks to the Garzoni project, led by
                        an interdisciplinary consortium supported by the Swiss National Science Foundation and
                        the French National Research Agency, it is now possible to explore 200 years of apprenticeship data.
                    </p>
                    <p>
                        To know more about data extraction and exploration, visit our
                        <a href="<?php echo $this->app->getUrl('module', 'help'); ?>">help page</a>, directly delve into
                        <a href="<?php echo $this->app->getUrl('module', 'contracts'); ?>">the data</a>,
                        or take a guided tour with <a href="https://tube.switch.ch/channels/4e66fcbe">these videos</a>.
                    </p>
                </div>
            </div>
            <div class="row" id="institutions">
                <div class="sixteen wide column">
                    <div class="ui divider"></div>
                    <div class="ui three column grid center aligned container">
                        <div class="column">
                            <a href="https://dhlab.epfl.ch/" target="_blank">
                                <img src="<?php echo $this->app->getImageUrl('logos/epfl.png'); ?>"
                                     srcset="<?php echo $this->app->getImageUrl('logos/epfl@2x.png'); ?> 2x"
                                     alt="EPFL Logo">
                            </a>
                            <p>
                                Digital Humanities Laboratory (DHLAB),<br>
                                Swiss Federal Institute of Technology in Lausanne
                            </p>
                        </div>
                        <div class="column">
                            <a href="http://grhis.univ-rouen.fr/grhis/" target="_blank">
                                <img src="<?php echo $this->app->getImageUrl('logos/grhis.png'); ?>"
                                     srcset="<?php echo $this->app->getImageUrl('logos/grhis@2x.png'); ?> 2x"
                                     alt="GRHis Logo">
                            </a>
                            <p>
                                Groupe de Recherche d’Histoire (GRHis),<br>
                                University of Rouen
                            </p>
                        </div>
                        <div class="column">
                            <a href="https://irhis.univ-lille.fr/" target="_blank">
                                <img src="<?php echo $this->app->getImageUrl('logos/irhis.png'); ?>"
                                     srcset="<?php echo $this->app->getImageUrl('logos/irhis@2x.png'); ?> 2x"
                                     alt="IRHiS Logo">
                            </a>
                            <p>
                                Institut de Recherche Historiques du Septentrion (IRHiS),<br>
                                University of Lille
                            </p>
                        </div>
                    </div>
                    <div class="ui divider"></div>
                    <div class="ui two column grid center aligned container">
                        <div class="column">
                            <a href="https://anr.fr/" target="_blank">
                                <img src="<?php echo $this->app->getImageUrl('logos/anr.png'); ?>"
                                     srcset="<?php echo $this->app->getImageUrl('logos/anr@2x.png'); ?> 2x"
                                     alt="ANR Logo">
                            </a>
                            <p>French National Research Agency</p>
                        </div>
                        <div class="column">
                            <a href="http://www.snf.ch/" target="_blank">
                                <img src="<?php echo $this->app->getImageUrl('logos/snf.png'); ?>"
                                     srcset="<?php echo $this->app->getImageUrl('logos/snf@2x.png'); ?> 2x"
                                     alt="SNF Logo">
                            </a>
                            <p>Swiss National Science Foundation</p>
                        </div>
                    </div>
                    <div class="ui divider"></div>
                    <div class="ui two column grid center aligned container">
                        <div class="column">
                            <a href="http://www.unive.it/" target="_blank">
                                <img src="<?php echo $this->app->getImageUrl('logos/unive.png'); ?>"
                                     srcset="<?php echo $this->app->getImageUrl('logos/unive@2x.png'); ?> 2x"
                                     alt="UNIVE Logo">
                            </a>
                            <p>Ca' Foscari University of Venice</p>
                        </div>
                        <div class="column">
                            <a href="http://www.archiviodistatovenezia.it/" target="_blank">
                                <img src="<?php echo $this->app->getImageUrl('logos/asve.png'); ?>"
                                     srcset="<?php echo $this->app->getImageUrl('logos/asve@2x.png'); ?> 2x"
                                     alt="ASVe Logo">
                            </a>
                            <p>State Archives of Venice</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->include('components/page_end.tpl.php'); ?>
