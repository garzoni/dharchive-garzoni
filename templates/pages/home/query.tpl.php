
<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="section-heading row">
    <div class="sixteen wide column">
        <h2 class="title">Garzoni Data</h2>
    </div>
</div>
<div class="row">
    <div class="sixteen wide column">
        <div class="ui stacked segment">
            <p>The Garzoni dataset contains information about venetian apprenticeship contracts extracted from the <a href="http://garzoni.hypotheses.org/238">Accordi dei Garzoni</a>. The annotation phase is still on-going and the data set currently contains about half of the 55,000 contracts of the archival source (28,000 contracts as of September 2017). New contracts are annotated on a daily basis, and automatically added to the RDF graph. </p>
        </div>
    </div>
</div>

<div class="section-heading row">
    <div class="sixteen wide column">
        <h2 class="title">Accessing Full Data</h2>
    </div>
</div>
<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment">

            <h4>SPARQL endpoint</h4>
            <p>Data is accessible via a sparql endpoint, either from the bottom of this page (powered by <a href="http://doc.yasgui.org/#">YASGUI</a>), or from  <a href="https://sparql.dhlab.epfl.ch/sparql">sparql.dhlab.epfl.ch</a>.</p>

            <p>We provide below several sets of pre-defined queries considering the dataset from different angles. When clicking on a question, the corresponding query is automatically inserted in the sparql editor at the bottom of the page.</p>

            <h4>RDF dump</h4>
            <p>When complete, the full RDF graph will be available for download.</p>

            <h4>Linked data front end</h4>
            <p>One can browse the RDF graph thanks to a linked data front end (powered by <a href="http://lodview.it/">LodView</a>), which also perform URI dereferencing. The root of the Garzoni graph is <a href="http://data.dhlab.epfl.ch/garzoni/garzoniDataset.html">here</a>.</p>
        </div>
    </div>
</div>

<div class="section-heading row">
    <div class="sixteen wide column">
        <h2 class="title">Queries</h2>
    </div>
</div>
<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment">
            <div class="ui accordion">
                <div class="title">
                    <i class="dropdown icon"></i> Documents
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('01_archive_01_distrib_contract_year');">What is the distribution of contracts per year?</a>
                        <li><a onclick="loadQuery('01_archive_02_distrib_contract_year_month');">What is the distribution of contracts per month for a certain year? </a>
                        <li><a onclick="loadQuery('01_archive_03_distrib_contract_year_month_day');">What is the distribution of contracts per day for a certain month/year?</a>
                        <li><a onclick="loadQuery('01_archive_04_avg_contract_year_month_day_absolute');">What is the average number of contracts per day ? (over the whole dataset)</a>
                        <li><a onclick="loadQuery('01_archive_05_distrib_contract_register');">What is the distribution of contracts per register?</a>
                        <li><a onclick="loadQuery('01_archive_06_nb_contract_withTW');">What is the total number of contracts between date x and date y?</a>
                        <li><a onclick="loadQuery('01_archive_07_all_contracts_before_year');">Give me all contracts before year x.</a>
                        <li><a onclick="loadQuery('01_archive_08_nb_contracts');">What is the total number of contracts?</a>
                        <li><a onclick="loadQuery('01_archive_09_avg_nb_contracts_page');">What is the average number of contracts per page?</a>
                        <li><a onclick="loadQuery('01_archive_10_distrib_contracts_page');">How many pages have how many contracts?</a>
                        <li><a onclick="loadQuery('01_archive_11_info_register');">Give me information about the registers.</a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Person mentions and entities
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">What is the total number of person mentions? </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">What is the total number of person entities? </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">How many person entities have how many mentions? </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">What is the average number of mentions per person entity? </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">How many person entities have more than x mentions? </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">Give me all mentions of person entity with id x. </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">Give me all mentions of person entity with name x. </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">Who are the 10 most mentioned person entities? </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">Get the top-x most mentioned person entities with time window. </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">Who are the person entities mentioned more than X time? </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">What is the distribution of roles of person entities mentioned more than X times? </a>
                        <li><a onclick="loadQuery('02_mentions_01_pm_total');">Get number of mentions per person entity (listing all entities) </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Gender
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('03_gender_01_nb_pe_with_genderInfo');">How many person entities have a gender information or not? </a>
                        <li><a onclick="loadQuery('03_gender_02_nb_pe_with_gender');">What is the total number of person entities with gender female/male? </a>
                        <li><a onclick="loadQuery('03_gender_03_women_distrib_per_year');">What is the distribution of woman person entities over time? </a>
                        <li><a onclick="loadQuery('03_gender_04_women_men_per_role');">What is the distribution of women/men per role? </a>
                        <li><a onclick="loadQuery('03_gender_05_gender_distrib_for_role_withTW');">What is the gender distribution for a given role and within a given time window (on person entities) ? </a>
                        <li><a onclick="loadQuery('03_gender_06_distrib_men_women_per_prof');">What is the distribution of women/men per profession? (needs profession categories) </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Roles
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('04_roles_01_role_distrib_per_pm');">What is the role distribution of person mentions? </a>
                        <li><a onclick="loadQuery('04_roles_02_nb_pe_with_role_x');">What is the total number of person entities having role x ? </a>
                        <li><a onclick="loadQuery('04_roles_03_nb_pe_with_role_x_withTW');">What is the total number of person entities having role x, with time window? </a>
                        <li><a onclick="loadQuery('04_roles_04_role_distrib_per_gender');">What is the role distribution per gender (on entities)? </a>
                        <li><a onclick="loadQuery('04_roles_05_role_distrib_overview');">Role distribution: Give me an overview of all and per gender </a>
                        <li><a onclick="loadQuery('04_roles_06_pe_with_doubleRole');">Give me all person entities who had 2 different roles (e.g. master \& apprentice). </a>
                        <li><a onclick="loadQuery('04_roles_07_nb_pe_with_doubleRole');">How many entities have different roles? </a>
                        <li><a onclick="loadQuery('04_roles_08_details_pe_with_doubleRole');">Give me the details (person/role/date) for person entities having both apprentice and master roles. </a>
                        <li><a onclick="loadQuery('04_roles_09_app_with_sameGuar_across_contracts');">Give me the apprentices who have the same guarantor in 2 different contracts. </a>
                        <li><a onclick="loadQuery('04_roles_10_nb_guar_per_contract_with_prof_x');">TO BE REVISED WITH PROFESSION THESAURUS - Number of guarantor per contract given a profession. </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Apprentices
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('05_app_01_nb_app_entities');">What is the total number of apprentice entities?  </a>
                        <li><a onclick="loadQuery('05_app_02_nb_app_entities_withTW');">What is the total number of apprentice entities, with time window?  </a>
                        <li><a onclick="loadQuery('05_app_03_nb_app_mentions');">What is the total number of apprentice mentions?  </a>
                        <li><a onclick="loadQuery('05_app_04_nb_app_mentions_withTW');">What is the total number of apprentice mentions, with time window?  </a>
                        <li><a onclick="loadQuery('05_app_05_distrib_app_per_year');">Get the number of apprentices (entity) over the years.  </a>
                        <li><a onclick="loadQuery('05_app_06_distrib_app_ages');">What is the distribution of apprentices per age (based on mentions)?  </a>
                        <li><a onclick="loadQuery('05_app_07_distrib_app_ages_withTW');">What is the distribution of apprentices per age (based on mentions), with time window?  </a>
                        <li><a onclick="loadQuery('05_app_08_distrib_app_ages_with_profX');">What is the distribution of apprentices per age (based on mentions), with a certain profession category?  </a>
                        <li><a onclick="loadQuery('05_app_09_distrib_app_ages_with_profX_withTW');">What is the distribution of apprentices per age (based on mentions), within a time window and with a certain profession category?  </a>
                        <li><a onclick="loadQuery('05_app_10_avg_app_age');">What is the average age of apprentices (all, for ages indicated in integers)?  </a>
                        <li><a onclick="loadQuery('05_app_11_avg_app_age_with_prof_x');">What is the average age of apprentices having profession category x?  </a>
                        <li><a onclick="loadQuery('05_app_12_avg_app_age_with_profX_withTW');">What is the average age of apprentices having profession category x, with time window?  </a>
                        <li><a onclick="loadQuery('05_app_13_app_with_several_mentions');">Get the apprentices who are mentioned in more than x contracts.  </a>
                        <li><a onclick="loadQuery('05_app_14_app_with_several_mentions_with_several_roles');">Who are the apprentices mentioned in more than 1 contract with different roles?  </a>
                        <li><a onclick="loadQuery('05_15_app_role_combinations');">Give me the possible apprentice combinations of roles with their frequency (currently not working, to be revised)  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Masters
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('06_masters_01_nb_master_entities');">What is the total number of masters?  </a>
                        <li><a onclick="loadQuery('06_masters_02_nb_masters_with_several_app');">How many masters have more than x apprentice?  </a>
                        <li><a onclick="loadQuery('06_masters_03_masters_with_several_app');">Get the list of masters having more than x apprentice.  </a>
                        <li><a onclick="loadQuery('06_masters_04_nb_masters_with_several_app_withTW');">Get the list of masters having more than x apprentice, with time window   </a>
                        <li><a onclick="loadQuery('06_masters_05_avg_nbApp_in_master_careers');">How many apprentice do masters have on average in their careers?  </a>
                        <li><a onclick="loadQuery('06_masters_06_avg_nbApp_in_master_careers_more_app');">How many apprentice do masters with more than one apprentice have on average in their careers?  </a>
                        <li><a onclick="loadQuery('06_masters_07_avg_nbApp_in_master_careers_with_prof_x_withTW');">How many apprentice do masters have on average in their careers, with time window and given a certain profession category?  </a>
                        <li><a onclick="loadQuery('06_masters_08_nbApp_per_master');">How many masters have how many apprentices?  </a>
                        <li><a onclick="loadQuery('06_masters_09_nbApp_per_master_withTW');">How many masters have how many apprentices, with time window ?  </a>
                        <li><a onclick="loadQuery('06_masters_10_app_timeline_for_master_with_URL_x');">Given a master with URL x, give the timeline of his students' enrolment.  </a>
                        <li><a onclick="loadQuery('06_masters_11_app_timeline_for_master_with_name_x');">Given a master with name x, give the timeline of his students' enrolment.  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Guarantors
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('07_guar_01_nbContracts_with_without_guar');">How many contracts have and have not a guarantor?  </a>
                        <li><a onclick="loadQuery('07_guar_02_contracts_distrib_per_nbGuar');">What is the contract distribution per number of guarantor?   </a>
                        <li><a onclick="loadQuery('07_guar_03_avg_nbApp_per_guar');">How many apprentices do guarantor have on average?  </a>
                        <li><a onclick="loadQuery('07_guar_04_avg_nbApp_per_guar_withTW');">How many apprentices do guarantor have on average, within a time window?  </a>
                        <li><a onclick="loadQuery('07_guar_05_avg_nbApp_per_guar_withTW_with_profX');">How many apprentices do guarantor have on average, within a time window and a specific profession category (TBU)?  </a>
                        <li><a onclick="loadQuery('07_guar_06_guar_with_most_app');">Give me the top x guarantors with the most apprentices.  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Salaries
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('08_salary_01_nbSal_progressive_notProgressive');">How many salaries are progressive vs. non-progressive?  </a>
                        <li><a onclick="loadQuery('08_salary_02_nbSal_inGood_inMoney');">How many salaries are in goods vs. in money?  </a>
                        <li><a onclick="loadQuery('08_salary_03_who_pays_sal');">Who pays the salary?  </a>
                        <li><a onclick="loadQuery('08_salary_04_prof_with_sal_paid_by_master_vs_app');">In which profession the salary is payed by the master, by the apprentice? (using profession standard forms)  </a>
                        <li><a onclick="loadQuery('08_salary_05_distrib_contract_per_finCond_type');">How many contracts have which type of financial conditions?  </a>
                        <li><a onclick="loadQuery('08_salary_06_distrib_contract_per_finCond_type_with_prof_x');">How many contracts have which type of financial conditions, given profession category x?  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Charges
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('09_charge_01_charges_with_freq');">What kind of charges is there and with which frequency?  </a>
                        <li><a onclick="loadQuery('09_charge_02_charges_with_loc');">How many charge have a location or not?  </a>
                        <li><a onclick="loadQuery('09_charge_03_charge_overview');">Overview of the locations of charges  </a>
                        <li><a onclick="loadQuery('09_charge_04_role_of_pers_with_charge');">What is the role of the persons having a charge?  </a>
                        <li><a onclick="loadQuery('09_charge_05_info_worksFor');">Give me information about ‘worksFor’ property.  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Professions
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('10_prof_01_distrib_nbProf_per_profCat');">How many profession mentions per profession category?  </a>
                        <li><a onclick="loadQuery('10_prof_02_nbPers_with_role_x_with_several_prof');">How many master/app/guar/other mentions have more than 1 profession?  </a>
                        <li><a onclick="loadQuery('10_prof_03_nbPers_several_prof');">How many person mentions have how many professions? (up to 7!)  </a>
                        <li><a onclick="loadQuery('10_prof_04_pers_several_prof');">Give me person mentions ordered according to their number of professions.  </a>
                        <li><a onclick="loadQuery('10_prof_05_nbContract_per_profCat');">What is the number of contracts per profession category? (considering master professions only)  </a>
                        <li><a onclick="loadQuery('10_prof_06_nbContract_per_profCat_withTW');">What is the number of contracts per profession category, with time window?  </a>
                        <li><a onclick="loadQuery('10_prof_07_app_withProf_withString');">Give me all apprentices, along with their related information, whose profession contains the string "servir" (could also be another string)  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Geographical aspects
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('13_geoVar_01_who_has_residence');">What is the role of the persons having the indication of a residence?  </a>
                        <li><a onclick="loadQuery('13_geoVar_02_who_has_geoOrig');">What is the role of the persons having the indication of a geographical origin?  </a>
                        <li><a onclick="loadQuery('13_geoVar_03_ple_plm');">Get all place entities, with their number of mentions.  </a>
                        <li><a onclick="loadQuery('13_geoVar_04_ple_plm_withParish');">Get all place entities having the indication of a parish, with their number of mentions.  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Person relations
                </div>
                <div class="content">
                    <p>To Do...</p>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Events
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('15_event_01_nb_event_per_type');">How many event mentions of which type are there ?  </a>
                        <li><a onclick="loadQuery('15_event_02_distrib_appship_per_duration');">How many apprenticeships have which duration (in number of months)?  </a>
                        <li><a onclick="loadQuery('15_event_03_appship_avg_duration');">What is the average duration of an apprenticeship?  </a>
                        <li><a onclick="loadQuery('15_event_04_appship_avg_duration_profCat');">What is the average duration of an apprenticeship per profession category ?  </a>
                        <li><a onclick="loadQuery('15_event_05_appship_avg_duration_profCatX');">What is the average duration of a contract in profession category X per year?  </a>
                        <li><a onclick="loadQuery('15_event_06_nb_flee_with_wo_denun');">How many flees are there, with and without denunciation date?  </a>
                        <li><a onclick="loadQuery('15_event_07_nb_flee_perYear');">How many flees are there per year ?  </a>
                        <li><a onclick="loadQuery('15_event_08_nb_breaches_perYear');">How many breaches of contract are there per year ?  </a>
                        <li><a onclick="loadQuery('15_event_09_day_contract');">On which days contracts are registered?  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Workshops
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('16_workshops_02_workshop_owner_roles');">Who has a workshop?  </a>
                        <li><a onclick="loadQuery('16_workshops_03_nb_master_with_wo_workshop');">How many masters are associated with a workshop or *not*?  </a>
                        <li><a onclick="loadQuery('16_workshops_04_nb_workshop_with_wo_place');">How many workshops have and have *not* the information of their places?  </a>
                        <li><a onclick="loadQuery('16_workshops_05_nb_workshop_with_wo_place_withTW');">Idem as 4), with time window filter.  </a>
                        <li><a onclick="loadQuery('16_workshops_06_allInfos_workshop');">Give me all workshop mentions, their location information, their insigna and the year they were mentioned.  </a>
                        <li><a onclick="loadQuery('16_workshops_07_distrib_workshop_perParish');">What is the distribution of workshops per parishes?  </a>
                        <li><a onclick="loadQuery('16_workshops_08_distrib_workshop_perSestiere');">What is the distribution of workshops per sestiere?  </a>
                        <li><a onclick="loadQuery('16_workshops_09_nb_workshop_withInsigna');">How many workshop have the indication if an insigna?  </a>
                        <li><a onclick="loadQuery('16_workshops_10_nb_workshop_mentions_perInsigna');">How many workshop mentions per insigna? (hint for workshop entities)  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Profession normalisation
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('11_profNorm_01_info_prof_mention_numbers');">Give me information on number of profession mentions: total, with or without transcript (TR, NoTR), standardForm (SF, NoSF), category (CAT, NoCat)  </a>
                        <li><a onclick="loadQuery('11_profNorm_02_tr_sf_cat_prof_mentions');">Display TR/SF/CAT of all profession mentions, grouped and alphabetically ordered by transcripts  </a>
                        <li><a onclick="loadQuery('11_profNorm_03_nb_profTrans_NOsf_NOcat');">Give me the number of unique transcripts that do not have SF nor CAT.  </a>
                        <li><a onclick="loadQuery('11_profNorm_04_profTrans_NOsf_NOcat');">Give me the list of unique transcripts that do not have SF nor CAT.  </a>
                        <li><a onclick="loadQuery('11_profNorm_05_diff_profTrans_of_master_and_app');">Give me the profession transcripts of Master and Apprentices when different (still duplicates, for unique across master and app profd, should do a uniq on the results)  </a>
                        <li><a onclick="loadQuery('11_profNorm_06_diff_profTrans_of_master_and_app_withDHCLink');">With link towards DHCanvas.  </a>
                        <li><a onclick="loadQuery('11_profNorm_07_profCat');">Get the list of profession categories (existing in dataset)  </a>
                        <li><a onclick="loadQuery('11_profNorm_08_prof_tr_sf_cat_suggestedSF_suggestedCat');">Get the list of transcripts (TR), standard forms (SF), profession categories, AND suggestedStandardForms and suggested profession categories  </a>
                    </ol>
                </div>
                <div class="title">
                    <i class="dropdown icon"></i> Location normalisation
                </div>
                <div class="content">
                    <ol>
                        <li><a onclick="loadQuery('12_geoNorm_01_loc_with_targets');">Get all locations, with indication of what they qualify.  </a>
                        <li><a onclick="loadQuery('12_geoNorm_02_loc_with_distinct_targets');">Get distinct locations, with indication of what they qualify.  </a>
                        <li><a onclick="loadQuery('12_geoNorm_03_loc_geoOrigins');">Get locations as object of grz-owl:geographicalOrigins (i.e. of apprentice mainly)  </a>
                        <li><a onclick="loadQuery('12_geoNorm_04_loc_geoOrigins_distinct');">Get locations as object of grz-owl:geographicalOrigins, with distinct lowercase transcripts and standard forms, and without place entity url.  </a>
                        <li><a onclick="loadQuery('12_geoNorm_05_loc_geoOrigins_distinct_noTrans');">Get locations as object of grz-owl:geographicalOrigins, with distinct lowercase standard forms and without transcripts (for place entity creation)  </a>
                        <li><a onclick="loadQuery('12_geoNorm_06_loc_residence');">Get locations as object of grz-owl:hasResidence (i.e. of masters mainly)  </a>
                        <li><a onclick="loadQuery('12_geoNorm_07_loc_residence_distinct');">Get locations as object of grz-owl:hasResidence (of masters mainly), with distinct lowercase transcripts and standard forms, and without place entity urls.  </a>
                        <li><a onclick="loadQuery('12_geoNorm_08_loc_location');">Get locations as object of grz-owl:hasLocation (of Workshop Mentions and Charge Mentions)  </a>
                        <li><a onclick="loadQuery('12_geoNorm_09_loc_location_distinct');">Get locations as object of grz-owl:hasLocation (of Workshop Mentions and Charge Mentions), with lowercase distinct transcripts and standard forms and without place entity urls.  </a>
                        <li><a onclick="loadQuery('12_geoNorm_10_geoOrigins_distinct_sf');">Get distinct standardforms from geoOrigins property.  </a>
                        <li><a onclick="loadQuery('12_geoNorm_11_residence_distinct_sf');">Get distinct standardforms from residence property.  </a>
                        <li><a onclick="loadQuery('12_geoNorm_12_location_distinct_sf');">Get distinct standardforms from location property.  </a>
                        <li><a onclick="loadQuery('12_geoNorm_13_parishes');">Get all parishes with their labels.  </a>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-heading row">
    <div class="sixteen wide column">
        <h2 class="title">Query Editor</h2>
    </div>
</div>
<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment">
            <div id="yasqe"></div>
        </div>
    </div>
</div>

<div class="section-heading row">
    <div class="sixteen wide column">
        <h2 class="title">Query Results</h2>
    </div>
</div>
<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment">
            <div id="yasr"></div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
