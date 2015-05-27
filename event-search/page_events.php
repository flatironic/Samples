
<form id="pr-search" role="search" method="get" class="form-inline" role="form">

    <div class="row">
        <div class="col-lg-6">
            <div class="notification"></div>
        </div>
    </div>

    <div id="event-search">
        <div class="row" id="keyword-search">
            <div class="col-lg-6">
                <div class="input-group">
                    <div class="input-group-btn dropdown">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span id="search-type">Find Events</span> <span class="caret"></span></button>
                        <ul class="dropdown-menu">
                            <li role="presentation" class="dropdown-header">Search by:</li>
                            <li><a href="javascript:void(0)" class="keyword-search">Keywords</a></li>
                            <li><a href="javascript:void(0)" class="postal-search">Location</a></li>
                            <li><a href="javascript:void(0)" class="complete-search">Complete Search</a></li>
                            <li style="border-bottom:1px dashed #888;margin:5px 0 5px 0;height:1px;">&nbsp;</li>
                            <li role="presentation" class="dropdown-header">Display Events:</li>
                            <li><a href="javascript:void(0)" class="open-search" id="open-search">Open GetEvent Registration</a></li>
                        </ul>
                    </div>
                    <input type="text" class="form-control" name="keywords" id="keywords" placeholder="Search Events by Keyword">
                    <div class="input-group-btn">
                        <button type="submit" class="search-submit btn btn-pr" id="search-submit">Search</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="postal-search" style="display: none">
            <div class="col-lg-12">
                <div class="form-group" style="margin: 2px 5px 3px 0;">
                    <label class="sr-only" for="postalcode">Postal Code</label>
                    <input type="text" class="form-control" placeholder="Zip/Postal Code" name="postalcode" id="postalcode" value="" type="number" />
                </div>

                <div class="form-group">
                    <div class="input-group" style="margin-left: -4px;">
                        <label class="sr-only" for="radiusBox">Radius</label>
                        <span class="input-group-addon">Radius:</span>
                        <input style="max-width: 200px" placeholder="50" type="text" class="form-control" name="radius" id="radius" type="number" />
                        <span class="input-group-addon">miles</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" id="complete-search" style="display: none">
            <div class="col-lg-12">
                <div class="form-group">
                    <label class="sr-only" for="state">State</label>
                    <select name="state" id="state" placeholder="State..." class="form-control">
                        <option value="">State...</option>
                        <option value="1">Alaska</option>
                        <option value="2">Alabama</option>
                        <option value="4">Arizona</option>
                        <option value="5">Arkansas</option>
                        <option value="6">California</option>
                        <option value="7">Colorado</option>
                        <option value="8">Connecticut</option>
                        <option value="9">Delaware</option>
                        <option value="10">Washington D.C.</option>
                        <option value="12">Florida</option>
                        <option value="13">Georgia</option>
                        <option value="15">Hawaii</option>
                        <option value="16">Idaho</option>
                        <option value="17">Illinois</option>
                        <option value="18">Indiana</option>
                        <option value="19">Iowa</option>
                        <option value="20">Kansas</option>
                        <option value="21">Kentucky</option>
                        <option value="22">Louisiana</option>
                        <option value="23">Maine</option>
                        <option value="25">Maryland</option>
                        <option value="26">Massachusetts</option>
                        <option value="27">Michigan</option>
                        <option value="28">Minnesota</option>
                        <option value="29">Mississippi</option>
                        <option value="30">Missouri</option>
                        <option value="31">Montana</option>
                        <option value="32">Nebraska</option>
                        <option value="33">Nevada</option>
                        <option value="34">New Hampshire</option>
                        <option value="35">New Jersey</option>
                        <option value="36">New Mexico</option>
                        <option value="37">New York</option>
                        <option value="38">North Carolina</option>
                        <option value="39">North Dakota</option>
                        <option value="41">Ohio</option>
                        <option value="42">Oklahoma</option>
                        <option value="43">Oregon</option>
                        <option value="45">Pennsylvania</option>
                        <option value="47">Rhode Island</option>
                        <option value="48">South Carolina</option>
                        <option value="49">South Dakota</option>
                        <option value="50">Tennessee</option>
                        <option value="51">Texas</option>
                        <option value="52">Utah</option>
                        <option value="53">Vermont</option>
                        <option value="55">Virginia</option>
                        <option value="56">Washington</option>
                        <option value="57">West Virginia</option>
                        <option value="58">Wisconsin</option>
                        <option value="59">Wyoming</option>
                    </select>
                </div>
                <div class="form-group">
                    <select name="sport" id="sport" placeholder="Sport..." class="form-control">
                        <option value="">Sport...</option>
                        <option value="1">Cycling</option>
                        <option value="3">Running</option>
                        <option value="4">Multisport</option>
                        <option value="2">Winter</option>
                    </select>
                    <select name="type" id="type" placeholder="Sport Type..." class="form-control">
                        <option value="">Sport Type...</option>
                        <option value="">-- Running Events --</option>
                        <option value="12">10k</option>
                        <option value="13">5k</option>
                        <option value="14">Marathon</option>
                        <option value="22">Half Marathon</option>
                        <option value="7">Ultra</option>
                        <option value="8">Trail</option>
                        <option value="11">Relay</option>
                        <option value="15">Other</option>
                        <option value="">-- Cycling Events --</option>
                        <option value="1">Mountain Bike</option>
                        <option value="2">Road</option>
                        <option value="3">Cyclocross</option>
                        <option value="4">Track</option>
                        <option value="5">Rides</option>
                        <option value="">-- Winter Events --</option>
                        <option value="6">Cross Country Ski</option>
                        <option value="9">Snowshoe</option>
                        <option value="10">Ski Mountaineering</option>
                        <option value="">-- Other Events --</option>
                        <option value="15">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <fieldset>
                        <select name="month" id="month" data-theme="a" placeholder="Month..." class="form-control">
                            <option value="">Month...</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                </div>
                <div class="form-group">
                    <select name="year" id="year" placeholder="Year..." class="form-control">
                        <option value="">Year...</option>
                        <option value="<?php echo date("Y", strtotime("+1 year"));?>"><?= date("Y", strtotime("+1 year"));?></option>
                        <option selected="selected" value="<?= date("Y");?>"><?= date("Y");?></option>
                        <option value="<?php echo date("Y", strtotime("-1 year"));?>"><?= date("Y", strtotime("-1 year"));?></option>
                        <option value="<?php echo date("Y", strtotime("-2 year"));?>"><?= date("Y", strtotime("-2 year"));?></option>
                    </select>
                    </fieldset>
                </div>
            </div>
        </div>
        &nbsp;<input type="checkbox" id="pastEvents" name="pastEvents" value="1"/> Include Past Events
    </div>
</form>
<div id="geo-header"></div>
<div id="loading"></div>
<ul id="display-event-list" class="ui-listview event-list"></ul>
<button type="button" class="btn btn-pr" id="showMore" style="display:none">Show More</button>


<script>
    var Config = {
        host: "<?= base_url(); ?>",
        mainHost: "<?= base_url(); ?>",
        eventResultsLimit: 30,
        lang: 'en',
        dataType: 'json',
        apiKey: ''//'4339fdf7cee9c9657342d6c67a98b2a4301f17a2',
    };

</script>
<script src="//maps.googleapis.com/maps/api/js?key=AIzaSyDN5jOr4W2g9i5yrPTv2XjN-5L64FP2Ze4&sensor=true&libraries=geometry" type="text/javascript"></script>
<script src="/assets/js/handlebars-v1.3.0.js" type="text/javascript"></script>
<script src="/assets/js/handlebars.app.js" type="text/javascript"></script>
<script src="/assets/js/jquery.ba-hashchange.js" type="text/javascript"></script>
<script src="/assets/js/validate.js" type="text/javascript"></script>
<script src="/assets/js/geo.js" type="text/javascript"></script>
<script src="/assets/js/search.js" type="text/javascript"></script>
<script src="/assets/js/google.events.js" type="text/javascript"></script>
<!-- ###################################-->
<!-- ######### templates  ##############-->
<!-- ###################################-->

<script id="template-event-list" type="text/x-handlebars-template">
    {{#events}}
    {{#if new_date}}
    <li class="date">{{new_date}}</li>
    {{else}}
    <li class="rule"></li>
    {{/if}}
    {{#if racename}}
    <li class="media {{#if pr_reg}}pr-reg{{/if}} event">

        <a class="listing-image pull-left" href="/races/event/{{event_id}}/{{urlEncode racename}}" attr="{{race_id}}" >

            {{#if filename}}
            <img class="media-object header img-responsive" src="{{filename}}" />
            {{else}}
            <img class="media-object icon img-responsive" src="{{iconDisplay sport_id}}" />
            {{/if}}
        </a>

        {{#if pr_reg}}
        <a data-label="{{eventname}}" data-category="register-getevent" class="getevent-registration-link pull-right btn btn-pr btn-list-register" href="/registration/register/{{race_id}}" attr="{{race_id}}">Register</a>
        {{else}}
        {{#if aff_reg_link}}
        <a data-label="{{eventname}}" data-category="register-{{aff_name}}" class="affiliate-registration-link pull-right btn btn-list-register btn-aff {{aff_name}}" target="_blank" href="{{aff_reg_link}}" attr="{{race_id}}"></a>
        {{/if}}
        {{/if}}

        <div class="media-body">
            <h2 class="media-heading">
                <a href="/races/event/{{event_id}}/{{urlEncode racelink}}" attr="{{race_id}}" class="event-link">{{{eventname}}}</a></h2>
            {{#ifCond racename eventname}}
            {{else}}
            {{#if racename}}
            <strong>{{racename}}</strong><br/>
            {{/if}}
            {{/ifCond}}
            {{#if city}}{{city}}{{/if}}{{#if state}}, {{state}}{{/if}}
            <br/>
            {{#if type}}
            Type: <span class="text-small">{{type}}</span>
            {{#if sub_type}}
            | <span class="text-small">{{sub_type}}</span>
            {{/if}}
            {{/if}}
        </div>
    </li>
    {{/if}}
    {{/events}}
    {{^events}}
    <div class="alert-message no-results"><span>Sorry, No Results!</span></div>
    {{/events}}
</script>