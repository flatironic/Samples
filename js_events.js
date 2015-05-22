if (typeof PR === "undefined") var PR = {};

PR = {
    api : {
        "url" : ""
    },
    user : {
        "first" : "",
        "last" : "",
        "screenname" : "",
        "email" : ""
    },
    userOptions : {
        "lastEvent" : ""
    },
    loading : '<div class="loading"><span class="glyphicon glyphicon-refresh spin"></span> Loading...</div>',
    getSearchParams: function(variable) {
        var query = location.hash.replace('#!search?', '');
        var vars = query.split('&');
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            if (decodeURIComponent(pair[0]) == variable) {
                return decodeURIComponent(pair[1]);
            }
        }
    },
    manageFormDisplay: function(view) {
        if (!view)
            view = 'keyword';
        $('.no-results').hide();

        if (view == 'postal'){
            $('#complete-search').hide();
            $('#postal-search').show();
            $('#search-type').text("Location");
        }
        else if (view == 'keyword'){
            $('#postal-search').hide();
            $('#complete-search').hide();
            $('#search-type').text("Find Events:");
        }
        else if (view == 'open'){
            $('#search-type').text("Open Reg:");
        }
        else{
            $('#postal-search').show();
            $('#complete-search').show();
            $('#search-type').text("Complete");
        }
    },
    isNumber: function  (o) {
        return ! isNaN (o-0) && o != null;
    },
    handleAPIRequestUrl: function (request){
        var requestURL = request;
        if (Config.apiKey)
            requestURL += '?X-API-KEY=' + Config.apiKey;
        return requestURL;
    },
    getDateFromDateString: function (date) {
        var split = date.split('-');
        return new Date(split[0],split[1]-1,split[2]);
    },
    sortByDate: function (a, b) {
        return new Date(b.date).getTime() - new Date(a.date).getTime();
    },
    searchClear: function(){
        $('.no-results').remove();
        $('.notification').empty();
    },
    searchEvents: function (url, view){
        PR.searchClear();
        PR.api.url = url;
        PR.displayEventList(PR.api.url, view);
        $('#loading').html(PR.loading);
    },
    zipSearch: function (zip, radius) {
        PR.searchEvents('/api/races/zipcodes/zipcode/' + zip + '/radius/' + radius);
    },
    keywordSearch: function (keywords, future_only) {
        PR.searchEvents('/api/races/keywords/keyword/' + encodeURIComponent(keywords) + '/future_only/' + future_only);
    },
    filterSearch: function (advanced, state_id, sport_id, month, year) {
        PR.searchEvents('/api/races/events/state_id/' + state_id + '/sport_id/' + sport_id + '/month/' + month + '/year/' + year);
    },
    openSearch: function () {
        PR.searchEvents('/api/races/registration_events', 'registerEvents');
    },
    futureSearch: function () {
        PR.searchEvents('/api/races/events/future_only/true/start/0/limit/30');
    },
    eventSearch: function (advanced, state_id, sport_id, type_id, month, year, keywords, postal_code, radius, future_only ) {
        //PR.searchEvents('/api/races/events/state_id/' + state_id + '/sport_id/' + sport_id + '/month/' + month + '/year/' + year + '/keywords/' + encodeURIComponent(keywords) + '/zipcode/' + zip + '/radius/' + radius + '/future_only/' + future_only);
        var query = "";
        query += (state_id) ? '/state_id/' + state_id : '';
        query += (sport_id) ? '/sport_id/' + sport_id : '';
        query += (type_id) ? '/type_id/' + type_id : '';
        query += (month) ? '/month/' + month : '';
        query += (year) ? '/year/' + year : '';
        query += (keywords) ? '/keywords/' + encodeURIComponent(keywords) : '';
        query += (postal_code) ? '/postal_code/' + postal_code : '';
        query += (radius) ? '/radius/' + radius : '';
        query += (future_only) ? '/future_only/' + future_only : '';
        PR.searchEvents('/api/races/events' + query);
    },
    displayEventList: function (url, view) {
        var eventsUrl = url + '/start/' + 0 + '/limit/' + Config.eventResultsLimit;
        //clean/hide elements until results:
        var elEventList = $('#display-event-list');
        elEventList.html("");
        var showMore = $("#showMore");
        showMore.hide();

        $.getJSON(PR.handleAPIRequestUrl(eventsUrl),function(data){
            //wrap into a new object for template
            var eventItems = PR.formatEventItems(data);
            var template;
            var showMoreBtn = true;
            PR.numResults = data.length;
            if (view == 'registerEvents'){
                showMoreBtn = false;
            }
            template = Handlebars.compile( $("#template-event-list").html() );
            if(eventItems.events.length == 0)
                delete eventItems.events;
            elEventList.append(template(eventItems));
            $('#loading').html('');
            if ($(".no-results").length > 0){
                if (location.hash.indexOf('future_only=true')){
                    var futureUrl = location.hash.replace("future_only=true", "future_only=false");
                    $(".no-results").append(' |  <a href="' + futureUrl + '">Include past events</a>');
                }
            }
            showMore.off(); //unbind more results
            if (data.length >= Config.eventResultsLimit && showMoreBtn != false)
            {
                showMore.show();
                $("#showMore span.ui-btn-text").text("Show More");
                showMore.attr('attr', String(Config.eventResultsLimit));
                showMore.on('click', function(){
                    $('#loading').html(PR.loading);
                    $("#showMore span.ui-btn-text").text("Loading...");
                    var rangeStart = showMore.attr('attr');
                    eventsUrl = url + '/start/' + rangeStart + '/limit/' + Config.eventResultsLimit;
                    PR.appendToEventList(eventsUrl);
                    showMore.attr('attr', parseInt(rangeStart) + Config.eventResultsLimit);
                });
            }
        });
    },
    formatEventItems: function (data) {
        var eventItems = new Object();
        eventItems.events = data;
        var lastDate;
        $.each(eventItems.events, function (key, value) {
            if (eventItems.events[key].racename)
                eventItems.events[key].racelink = eventLink(eventItems.events[key].racename, eventItems.events[key].city, eventItems.events[key].state);
            if (lastDate != value.date) {
                lastDate = value.date;
                //var formatDate = new Date(lastDate).toDateString();
                var formatDate = PR.getDateFromDateString(lastDate).toDateString();
                eventItems.events[key].new_date = formatDate;
            }
            else {
                lastDate = value.date;
            }
        });
        return eventItems;
    },
    appendToEventList: function (url) {
        var elEventList = $('#display-event-list');
        $("#showMore").html('<span class="glyphicon glyphicon-refresh spin"></span> Loading...');
            $.getJSON(PR.handleAPIRequestUrl(url),function(data){
                if (data.length < Config.eventResultsLimit)
                    $("#showMore").hide();
                var eventItems = PR.formatEventItems(data);
                var template = Handlebars.compile( $("#template-event-list").html() );
                elEventList.append(template(eventItems));
                
                $("#showMore").html("Show More");
            });
            $('#loading').html('');
    },
    getEvents: function (){
        var future_only = true;
        var keywords = null;
        var radius = 100;
        var formView = 'keyword';

        if ($("#postal-search").is(":visible"))
            formView = 'postal';
        if ($("#complete-search").is(":visible"))
            formView = 'complete';

        PR.manageFormDisplay(formView);

        var keywords = $('#keywords').val() ? $('#keywords').val() : '';
        var radius = $('#radius').val() ? $('#radius').val() : '';
        var postalCode = $('#postalcode').val() ? $('#postalcode').val() : '';
        var stateId = $('#state').val() ? $('#state').val() : '';
        var sportId = $('#sport').val() ? $('#sport').val() : '';
        var typeId = $('#type').val() ? $('#type').val() : '';
        var month = $('#month').val() ? $('#month').val() : '';
        var year = $('#year').val() ? $('#year').val() : '';
        var futureOnly = true;
        if ($('#pastEvents:checkbox:checked').val() == 1)
            futureOnly = false;

        var params = "";
        params += formView ? 'form_view=' + formView : '';
        params += keywords ? '&keywords=' + encodeURIComponent(keywords) : '';
        params += '&future_only=' + futureOnly;
        if (formView == 'postal' || formView == 'complete') {
            params += postalCode ? '&postal_code=' + postalCode : '';
            params += radius ? '&radius=' + radius : '';
        }
        if (formView == 'complete') {
            params += stateId ? '&state_id=' + stateId : '';
            params += sportId ? '&sport_id=' + sportId : '';
            params += typeId ? '&type_id=' + typeId : '';
            params += month ? '&month=' + month : '';
            params += year ? '&year=' + year : '';
        }

        if ($('#pr-search').attr('action'))
            window.location = $('#pr-search').attr('action') + '#!search?' + params;
        else
            window.location.hash = '!search?' + params;
    }
}


$(document).ready(function() {
    var hash = location.hash;
    if (typeof hideGeo == 'undefined')
        hideGeo = false;


    if (!hash){
        //var geoServiceUrl = '//freegeoip.net/json/';
       var geoServiceUrl = '//www.telize.com/geoip/';
       var geoSuccess = false;
       if (userIP == '127.0.0.1')
           userIP = '71.56.239.49';
       if (userIP && userIP != '0.0.0.0')
           geoServiceUrl += userIP;

       //give the geoip service a chance, otherwise... let's do a query that works
       var goGeo = true;
       setTimeout(function(){
           if(!geoSuccess) {goGeo = false;PR.futureSearch();$('#geo-header').html('<h4>Upcoming Events:</h4>');}
       }, 2000); // 2 sec

       if (goGeo){
           var geoService = $.get(geoServiceUrl, function(response) {
               var zipcode,city,region_name,searchTitle;
               geoSuccess = true;
               //if telize then we modify the response to accommodate their api
               if (geoServiceUrl.indexOf('telize') > -1){
                   if (response && response.postal_code && response.region){
                       response.zipcode = response.postal_code;
                       response.region_name = response.region;
                   }
               }

               function doGeoSearch() {
                   PR.zipSearch(zipcode, 500);
                   searchTitle = '<h4>Events around ' + city + ', ' + region_name + ':</h4>';
               }

               if (response && response.zipcode){//did we get a response from the api service?
                   //console.log(response);
                   zipcode = response.zipcode;
                   city = response.city;
                   region_name = response.region_name;
                   doGeoSearch();
               }
               else if (!hideGeo){ //try google's geolocation
                   if (navigator.geolocation) {
                       navigator.geolocation.getCurrentPosition(function (position) {
                           getZipFromReverseGeoCode(position);
                       }, function () { //user did not enable geolocation
                           PR.futureSearch(); //does not fire for firefox if "not now" selected...
                       });
                   } else {
                       //geolocation not supported
                       PR.futureSearch();
                   }
                   function getZipFromReverseGeoCode(position) {
                       var geocoder = new google.maps.Geocoder();
                       var latLng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                       //$('.notification').append(' geocoder: ' + geocoder.length);
                       geocoder.geocode({'latLng':latLng}, function (results, status) {
                           if (status == google.maps.GeocoderStatus.OK) {
                               if (results[1]) {
                                   //$("#zip").val(results[1].postal_code)
                                   var address = results[0].address_components;
                                   zipcode = address[address.length - 1].long_name;
                                   city = address[address.length - 5].long_name;
                                   region_name = address[address.length - 3].long_name;
                                   $("#zip").val(zipcode);
                                   doGeoSearch();
                               }
                           } else {
                               alert("Geocoder failed due to: " + status);
                           }
                       });
                   }
               }
               else{ //get events by date
                   PR.futureSearch();
               }
               if (!searchTitle)
                   searchTitle = '<h4>Upcoming Events:</h4>';
               $('#geo-header').html(searchTitle);
           }, "jsonp");
       }
       //.fail(function() {
       //    alert( "do a future search" );
       //});
   }

    //using jquery.ba-hashchange.js for page history/state
    $(window).hashchange( function(){
        $('#geo-header').html('');
        var formView = 'keyword';

        if (PR.getSearchParams('form_view')){
            var formView = PR.getSearchParams('form_view') ? PR.getSearchParams('form_view') : 'complete';
            var stateId = PR.getSearchParams('state_id');
            var sportId = PR.getSearchParams('sport_id');
            var typeId = PR.getSearchParams('type_id');
            var month = PR.getSearchParams('month');
            var year = PR.getSearchParams('year');
            var zip = PR.getSearchParams('postal_code');
            var radius = PR.getSearchParams('radius');
            var keywords = PR.getSearchParams('keywords');
            var future_only = PR.getSearchParams('future_only') ? PR.getSearchParams('future_only') : 'true';
            PR.eventSearch(true, stateId, sportId, typeId, month, year, keywords, zip, radius, future_only);
            $('#state').val(stateId);
            $('#sport').val(sportId);
            $('#month').val(month);
            $('#year').val(year);
        }
        else if (PR.getSearchParams('open_events')){
            formView = 'open';
            PR.openSearch();
        }
        PR.manageFormDisplay(formView);

        //google analytics page view if hash changed:
        ga('send', 'pageview', {
            'page': location.pathname + location.search  + location.hash
        });
    });
    // Since the event is only triggered when the hash changes, we need to trigger
    // the event now to handle the hash the page may have loaded with.
    $(window).hashchange();

    $('.postal-search').on("click", function(){
        PR.manageFormDisplay('postal');
    });
    $('.keyword-search').on("click", function(){
        PR.manageFormDisplay('keyword');
    });
    $('.complete-search').on("click", function(){
        PR.manageFormDisplay('complete');
    });
    $('#open-search').on("click", function(){
        window.location.hash = '!search?open_events=true';
        PR.manageFormDisplay('open');
    });


    $('#search-submit').on("click", function(event){
        event.preventDefault();
        PR.getEvents();
    });

    jQuery('#searchBox').keypress(function(e) {
        if (e.keyCode == 13 && !e.shiftKey) {
            PR.getEvents();
        }
    });



});

