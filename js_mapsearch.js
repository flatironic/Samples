//mapsearch : events & listings

function getUrlParam(param) {
    var query = location.hash.replace('#!search', '');
    var vars = query.split('/');
    for (var i = 0; i < vars.length; i++) {

        if (decodeURIComponent(vars[i]) == param) {
            return decodeURIComponent(vars[i + 1]);
        }
    }
}

function panMapByAddress(geocoder, address, map) {
    geocoder.geocode({ 'address': address}, function (results, status) {
        if (status == google.maps.GeocoderStatus.OK && map) {
            map.panTo(results[0].geometry.location);
        }
    });
}

function setMapCenter() {
    var map = this.map;
    var geocoder = new google.maps.Geocoder();
    //var zipCode = getUrlParam('postal_code');
    var zipCode = $('#postalcode').val();

    if (zipCode)
        panMapByAddress(geocoder, zipCode, map);
}

function setMapZoom(radius, map) {
    if (!map)
        map = this.map;
    radius = radius ? radius : $('#radius').val();
    //google.maps.event.clearListeners(map, 'zoom_changed');
    google.maps.event.removeListener(zoomListener);
    map.setZoom(Math.round(15 - Math.log(radius) / Math.LN2));
    zoomListener = google.maps.event.addListener(map, 'zoom_changed', function () {
        setTimeout(radius_change(map), 500);
    });
}

function stringToRESTObject() {
    var params = location.hash.replace('#!search', '');
    var f = params.split("/");
    var oREST = new Object();
    var oKey, oValue;
    for (var i in f) {

        if (i % 2 === 0) {
            oValue = f[i];
            if (i > 1)
                oREST[oKey] = oValue;
        }
        else
            oKey = f[i];
    }
    return oREST;
}

function objectToRESTString(obj) {
    var str = '/';
    for (var p in obj) {
        if (p && obj.hasOwnProperty(p)) {
            str += p + '/' + obj[p] + '/';
        }
    }
    return str;
}

function geoLocate() {
    var map = this.map;
    var postal_code = zipCenter;
    //this.EventListView
    var geoServiceUrl = '//www.telize.com/geoip/';
    if (userIP == '127.0.0.1')
        userIP = '71.56.239.49';
    if (userIP && userIP != '0.0.0.0')
        geoServiceUrl += userIP;


    $.ajax({
        url: geoServiceUrl,
        timeout: 1000,
        success: function (response) {
            geoSuccess = true;
            if (response && response.postal_code && response.region) {
                //response.zipcode = response.postal_code;
                response.region_name = response.region;
                postal_code = response.postal_code
            }
        },
        error: function (x,t,m) {
            if(t==="timeout"){
                console.log('geo timout');
            }
            else{
                console.log('geo failure');
            }
            postal_code = 80302;
        },
        async: false //callback solution would be better...
    }, "jsonp");

    return postal_code;
}

function getSearchParams() {
    var oRest = stringToRESTObject();

    //oRest.page = !oRest.page || postal_code != oRest.postal_code || radius != oRest.radius ? oRest.page : '1';
    oRest.page = oRest.page ? oRest.page : '1';

    if (PageView == 'sales') {
        oRest.radius = oRest.radius ? oRest.radius : '1000';
        oRest.future_only = 'false';
    }
    else {
        oRest.radius = oRest.radius ? oRest.radius : '100';
        oRest.future_only = oRest.future_only ? oRest.future_only : 'true';
    }
    oRest.postal_code = oRest.postal_code ? oRest.postal_code : geoLocate();
    if (oRest.postal_code == zipCenter) //short cut
        oRest.radius = '1200';
    if (postal_code != oRest.postal_code)
        oRest.page = 1;
    //set for comparison
    postal_code = oRest.postal_code;
    radius = oRest.radius;
    var params = objectToRESTString(oRest);
    return params;
}

function radius_change(map) {
    var bounds = map.getBounds();
    var center = bounds.getCenter();
    var ne = bounds.getNorthEast();
    // r = radius of the earth in statute miles
    var r = 3963.0;
    // Convert lat or lng from decimal degrees into radians (divide by 57.2958)
    var lat1 = center.lat() / 57.2958;
    var lon1 = center.lng() / 57.2958;
    var lat2 = ne.lat() / 57.2958;
    var lon2 = ne.lng() / 57.2958;

    // distance = circle radius from center to Northeast corner of bounds
    var distance = r * Math.acos(Math.sin(lat1) * Math.sin(lat2) + Math.cos(lat1) * Math.cos(lat2) * Math.cos(lon2 - lon1));
    var oREST = stringToRESTObject();
    if (distance) {
        oREST.radius = Math.round(distance);
        var sREST = objectToRESTString(oREST);
        router.navigate('!search' + sREST, {trigger: true});
    }
}

$(function () {
    $('#filters-wrap .dropdown-menu').on("click", function (event) {
        event.stopPropagation();
    });
});

var Router = Backbone.Router.extend({
    initialize: function () {
        this.postal_code = '';
        this.radius = '';
        this.rest_string = '';
    },
    routes: {
        "!search*query": "search",
        "!listing/*query": "listing",
        '*path': 'default_route'
    },
    search: function (query) {
        var oRest = stringToRESTObject();
        $("#loading").show();
        $("#indicator").show();
        //setTimeout(function(){
        //only allow more results if we using the same map zip and radius:
        if (oRest.page > 1) {
            Events.fetch({remove: false}).done(function () {
                $("#loading").hide();
            });
        }
        else if (this.rest_string != objectToRESTString(oRest)) {
            if ($("#pan_search").is(':checked')){
                Events.fetch().done(function (data) {
                    $("#loading").hide();
                    App.show_map();
                    if (data.length === 0) {
                        $('#no_results').show();
                    }
                    else {
                        $('#no_results').hide();
                    }
                });
            }
            else{
                $("#loading").hide();
            }
        }
        this.rest_string = objectToRESTString(oRest);
        $("#postalcode").val(oRest.postal_code);
        $('#radius').val(oRest.radius);
        setTimeout(function () {
            $("#loading").hide();
        }, 3000);
        ga('send', 'event', "map", "map search", this.rest_string);
    },
    listing: function (query) {
        var event_sales_id = query;
        if (typeof userId !== 'undefined' && userId) {
            var permission = new Permission();
            permission.fetch({
                success: function () {
                    if (permission.get("user_id")) {
                        if (event_sales_id) {
                            var sale_detail = new EventSaleDetail({id: event_sales_id});
                            sale_detail.fetch({
                                success: function () {
                                    var sale_detail_view = new EventSalesDetailView({model: sale_detail});
                                    App.show_sale_details();
                                    var label = 'eventId:' + sale_detail.get('event_id') + ',userId:' + userId;
                                    ga('send', 'event', "listings", "listing view", label);
                                }
                            });
                        }
                    }
                    else {
                        window.location = "/listings";
                    }
                }
            });
        }
        else {
            window.location = "/listings";
        }
    },
    default_route: function () {
        var params = getSearchParams();
        var url = '/api/races/events' + params;
        ga('send', 'event', "map", "map search page", params);
        window.location.replace('#!search' + params);
        return url;
    }

});

var Permission = Backbone.Model.extend({
    initialize: function () {
    },
    urlRoot: '/api/listings/permission',
    clear: function () {
        this.destroy();
    }
});

var Event = Backbone.Model.extend({
    initialize: function () {
    },
    urlRoot: '/api/races/',
    clear: function () {
        this.destroy();
    }
});

var EventList = Backbone.Collection.extend({
    model: Event,
    url: function () {
        var params = getSearchParams();
        var url = '/api/races/events' + params;
        if (PageView == "sales")
            url = '/api/races/events/sale/1' + params;
        if (params)
            router.navigate('!search' + params, {trigger: true});
        return url;
    },
    sort_order: 'date',
    by_date: function(date) {
        var filtered = this.filter(function(Event) {
            return Event.get("date") === date;
        });
        return filtered.length;
    },
    comparator: function (Event) {
        if (this.sort_order === 'name') {
            return Event.get('eventname');
        }
        else {
            if (PageView == "sales")
                return -Event.get('date');
            else
                return Event.get('date');
        }
    }
});

var EventDetail = Backbone.Model.extend({
    //urlRoot:  '/api/race/event/eventid/'
    initialize: function (options) {
        this.options = options;
    },
    urlRoot: function () {
        var url = '/api/race/event/eventid/'
        return url;
    }
});

var EventDetails = Backbone.Collection.extend({
    model: EventDetail
});

var EventDetailView = Backbone.View.extend({
    el: $("#event_details"),
    initialize: function (detail) {
        this.model.on('sync', function () {
            this.render();
        }, this);
    },
    events: {
        //'click a.detail': 'show_event_detail'
    },
    render: function () {
        var template = _.template($("#event_detail_template").html(), this.model.toJSON());
        this.$el.html(template);
        return this;
    }
});

var EventSaleDetail = Backbone.Model.extend({
    initialize: function (options) {
        this.options = options;

    },
    urlRoot: function () {
        var url = '/api/listings/event/id/'
        return url;
    }
});

var EventSaleDetails = Backbone.Collection.extend({
    model: EventSaleDetail
});

var EventSalesDetailView = Backbone.View.extend({
    el: $("#event_sales_details"),
    initialize: function (detail) {
        this.model.on('sync', function () {
            this.render();
        }, this);
    },
    events: {
        //'click a.detail': 'show_event_detail'
    },
    render: function () {
        // this.$el.html(this.model.get('name'));
        var template = _.template($("#event_sales_detail_template").html(), this.model.toJSON());
        this.$el.html(template).on("click", ".btn-modal-contact", function (e) {
            createModal(this);
        });
    }
});

var EventListItemView = Backbone.View.extend({
    initialize: function (options) {
        this.marker_view = options.marker_view; //retain instance of google marker
        this.model.on('remove', this.remove, this); //subscribe to remove events on model
        this.render();
    },
    events: {
        'mouseover li.event-list-item': 'show_event_info',
        'mouseout li.event-list-item': 'hide_event_info',
        'click a.more_information': 'get_event_details',
        'click a.sale_information': 'show_sale_details'
    },
    get_event_details: function (e) {
        var event_id = $(e.currentTarget).data("event_id");
        var detail = new EventDetail({id: event_id});
        detail.fetch();
        var detail_view = new EventDetailView({model: detail});
        App.show_event_details();
    },
    show_sale_details: function (e) {
        App.show_sale_details();
    },
    show_event_info: function () {
        this.marker_view.show_event_info.call(this.marker_view.marker);
    },
    hide_event_info: function () {
        this.marker_view.hide_event_info.call(this.marker_view.marker);
    },
    render: function () {
        var sportId = this.model.get('sport_id');
        var sportType = getSportId();

        function getSportId() {
            switch (parseInt(sportId)) {
                case 1:
                    return "cycling"
                    break;
                case 2:
                    return "winter"
                    break;
                case 3:
                    return "running"
                    break;
                case 4:
                    return "multisport"
                    break;
                default:
                    return "other"
            }
        }

        this.model.set({sportType: sportType});
        this.model.set({img: this.model.get('filename') ? this.model.get('filename') : '/assets/img/icon-' + sportType + '.png'});
//        var index = Events.indexOf(this.model);
//        var modelPrior = Events.at(index - 1);
//        if (!modelPrior || (modelPrior && this.model.get('date') != modelPrior.get('date'))){
//            this.model.set({dateHeader: this.model.get('date')});
//        }
        var template = _.template($("#event_list").html(), this.model.toJSON());
        this.$el.html(template);
        return this;
    },

    remove: function () {
        this.$el.html('');
    }
});

var EventListView = Backbone.View.extend({
    el: $("#events_wrapper"),
    initialize: function (options) {
        this.map = options.map;
        //this.listenTo(this.collection, 'add', this.added_event);
        this.listenTo(this.collection, 'sync', this.set_status);
        this.listenTo(this.collection, 'sync', this.sort_by_date);
        this.$el.fadeIn('500');
        this.list_header = $('#event-list-header');
        this.listing_info = $('#events_list_holder div#listing_info');
        this.list_login = $('#events_list_login');
        this.list_container = $('#events_list_holder ul', this.$el);
        this.render(this);
        this.set_status();
        if (PageView == "sales" && (typeof userId === 'undefined' || !userId)) {
            this.hideDetails = true;
            this.require_login();
            this.list_container.hide();
        }
        else {
            this.list_header.html("Events");
            this.hideDetails = false;
            this.list_container.show();
        }
        if (this.collection.length === 0 && Backbone.history.fragment.indexOf("listing") > 0) {
            //var list_view = new EventListView({model: Events, map: this.map});
            var template = _.template($("#event_sale_info").html());
            this.listing_info.html(template);
            this.list_header.html("Event for Sale");
        }
    },
    events: {
        'click #append_events': 'append_events',
        'click #sort_by_name': 'sort_by_name',
        'click #sort_by_date': 'sort_by_date'
    },
    append_events: function () {
        var oRest = stringToRESTObject();
        if (oRest.page)
            oRest.page = parseFloat(oRest.page) + 1;
        else
            oRest.page = 1;
        var params = objectToRESTString(oRest);
        router.navigate('!search' + params, {trigger: true});
    },
    sort_by_name: function () {
        Events.sort_order = 'name';
        Events.sort();
        $(this.list_container).empty();
        Events.each(this.added_event, this);
        $('.date-header').hide();
    },
    sort_by_date: function () {
        Events.sort_order = 'date';
        Events.sort();
        $(this.list_container).empty();
        Events.each(this.added_event, this);
        $('.date-header').show();
    },
    set_status: function () {
        this.listing_info.html('');
        if (PageView == "sales")
            this.list_header.html("Events for Sale");
        else
            this.list_header.html("Events");

        if (this.collection.length % 100 !== 0 || this.collection.length === 0) {
            this.$('#append_events').hide();
        }
        else {
            this.$('#append_events').show();
        }

        if (this.collection.length === 0) {
            this.$('#events_status').hide();
        }
        else {
            this.$('#events_status').show().html(this.collection.length + ' Results');
        }

        if (this.collection.length === 0 || this.collection.length < 5 || this.hideDetails) {
            this.$('#sort_events').hide();
        }
        else {
            this.$('#sort_events').show();
        }

        if (this.collection.length > 5)
            this.$el.css('overflow-y', 'scroll');
    },
    require_login: function (event) {
        $(this.list_login).html('<h3>Login Required <small><a href="buy-sell-events">More information</a></small></h3>To view events for sale, log in or create a free account.<br/><small>You must specify you are an event director on your account.</small><br/><br/>' +
            '<iframe style="width:100%;height:400px" id="login" src="/user/login?view=frame" frameborder="0"></iframe>');
    },
    added_event: function (event) {
        var marker_view = new EventMarkerView({ model: event, map: this.map });
        var item_view = new EventListItemView({ model: event, marker_view: marker_view });
        if (!this.hideDetails) {
            $(this.list_container).append(item_view.render().el);
        }
    },
    set_postal_code: function (map) {
        var bounds = map.getBounds();
        var center = bounds.getCenter();
        var geocoder = new google.maps.Geocoder();
        var latlng = new google.maps.LatLng(center.lat(), center.lng());
        geocoder.geocode({'latLng': latlng}, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                var result = results[0];
                //look for locality tag and administrative_area_level_1
                var postal_code = "";
                for (var i = 0, len = result.address_components.length; i < len; i++) {
                    var ac = result.address_components[i];
                    if (ac.types.indexOf("postal_code") >= 0) postal_code = ac.long_name;
                }
                if (postal_code) {
                    var oREST = stringToRESTObject();
                    oREST.postal_code = postal_code;
                    var sREST = objectToRESTString(oREST);
                    //window.location.hash = '!search' + sREST;
                    router.navigate('!search' + sREST, {trigger: true});
                }
            }
        });
    },
    render: function (self) {
        this.collection.each(this.added_event, this);
        if (self.map) {
            zoomListener = google.maps.event.addListener(self.map, 'zoom_changed', function () {
                setTimeout(radius_change(self.map), 1000); //not working?
            });
            google.maps.event.addListener(self.map, 'dragend', function () {
                setTimeout(self.set_postal_code(self.map), 10);
            });
        }
        return this;
    }
});

var EventMarkerView = Backbone.View.extend({

    tagName: "li",
    initialize: function (options) {
        var self = this;
        if (PageView == "sales" && !userId)
            self.hideDetails = true;
        else
            self.hideDetails = false;
        self.model = options.model;
        self.model.on('remove', self.remove, self);

        self.map = options.map;
        //var pos = self.model.get('pos');
        var lat = self.model.get('lat');
        var lon = self.model.get('lon');
        var zip_lat = self.model.get('zip_lat');
        var zip_lon = self.model.get('zip_lon');
        var location, address;
        //zip_lon = -zip_lon; //oops... who did this to our data?
        if (lat && lat != "0.000000" && lon && lon != "0.000000") {
            location = new google.maps.LatLng(lat, lon);
        }
        else if (zip_lat && zip_lon) {
            location = new google.maps.LatLng(zip_lat, -zip_lon);
        }
        else {
            location = null
        }

        self.marker = null;
        self.marker = new google.maps.Marker({
            map: self.map,
            position: location,
            animation: google.maps.Animation.DROP,
            icon: new google.maps.MarkerImage(self.getIcon(self.model.get('sport_id'), self.model.get('event_sales_id')), null, null, null, new google.maps.Size(48, 48)),
            title: self.model.name,
            date: self.model.get('date'),
            logo: self.model.get('filename') ? self.model.get('filename') : '',
            eventname: self.model.get('eventname'),
            eventlink: '/races/event/' + self.model.get('event_id') + '/' + eventLink(self.model.get('eventname'), self.model.get('city'), self.model.get('state')),
            city: self.model.get('city'),
            state: self.model.get('state'),
            type: self.model.get('type'),
            sport_id: self.model.get('sport_id'),
            sale: self.model.get('event_sales_id'),
            id: self.model.get('event_id')
        });
        if (location == null && self.model.get('address') && self.model.get('city') && self.model.get('state')) {
            address = self.model.get('address') + " " + self.model.get('city') + ", " + self.model.get('state');
            geocoder = new google.maps.Geocoder();
            geocoder.geocode({ 'address': address}, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    var lat = results[0].geometry.location.lat();
                    var lng = results[0].geometry.location.lng();
                    location = new google.maps.LatLng(lat, lng);

                    self.marker.setPosition(location);
                }
            });
        }
        var template = _.template($("#map_marker_template").html(), self.marker);
        self.marker.infowindow = new google.maps.InfoWindow({
            content: template
        });
        if (!self.hideDetails)
            google.maps.event.addListener(self.marker, 'click', self.show_event_info);
    },
    show_event_detail: function () {
        this.hide_event_info();
        App.show_event_details();
    },
    getIcon: function (sport_id, sale) {
        var iconUrl = '/assets/img/icon-marker-run@2X.png';

        if (sale) {
            iconUrl = '/assets/img/icon-marker-sale@2X.png';
        }
        else {
            if (sport_id == '3')
                iconUrl = '/assets/img/icon-marker-run@2X.png';
            else if (sport_id == '1')
                iconUrl = '/assets/img/icon-marker-cycling@2X.png';
            else if (sport_id == '4')
                iconUrl = '/assets/img/icon-marker-multisport@2X.png';
            else if (sport_id == '2')
                iconUrl = '/assets/img/icon-marker-winter@2X.png';
            else
                iconUrl = '/assets/img/icon-marker-other@2X.png';
        }
        return iconUrl;
    },
    hide_event_info: function () {
        this.setIcon(this.getIcon(this.sport_id, this.sale));
        this.infowindow.close();
    },
    show_event_info: function () {
        this.setIcon(this.getIcon(this.sport_id, this.sale));
        this.infowindow.open(this.map, this);
    },
    render: function () {
    },
    remove: function () {
        this.marker.setMap(null);
        this.marker = null;
    }
});

var AppView = Backbone.View.extend({

    el: $("#mapsearch"),
    events: {
        'click #btn_map': 'show_map',
        'click #map_search': 'map_search'
    },
    show_event_details: function () {
        var self = this;
        var top = 0;
        var speed = 600;

        //set content position and fade in
        self.event_details.animate({top: (top) + 'px'}, speed, function () {
            self.event_details.fadeIn();
        });
        self.btn_map.fadeIn();
        self.event_sales_details.fadeOut();
        self.map_canvas.animate({height: (top) + 'px'}, speed);
    },
    show_sale_details: function () {
        var self = this;
        var top = 0;
        var speed = 600;
        self.event_details.fadeOut();
        //set content position and fade in
        self.event_sales_details.animate({top: (top) + 'px'}, speed, function () {
            self.event_sales_details.fadeIn();
        });
        self.btn_map.fadeIn();
        self.map_canvas.animate({height: (top) + 'px'}, speed);
    },
    show_map: function () {
        var self = this;
        var speed = 800;
        if (Events.length === 0 && Backbone.history.fragment.indexOf('listing') > -1) {
            location.href = '/listings';
        }
        else{
            self.event_details.fadeOut().html();
            self.event_sales_details.fadeOut().html();
            if ($(window).width() > 991) {
                self.map_canvas.animate({height: $(window).height() - 109}, speed, function () {
                    google.maps.event.trigger(self.map, 'resize');
                });
            }
            else {
                self.map_canvas.css({height: '360'}, speed);
            }
            self.btn_map.fadeOut();
        }
    },
    map_search: function (event) {
        if (event)
            event.preventDefault();

        if ($("#postalcode").val() == "") {
            alert("A postal code is required.");
            return;
        }
        //if radius or zip changed then reset map:
        if (this && $("#postalcode").val() && $("#radius").val()) {
            setMapCenter.call(this);
            setMapZoom.call(this);
        }
        var sale = false;
        var keywords = null;
        var radius = 100;
        var keywords = $('#keywords').val() ? $('#keywords').val() : '';
        var radius = $('#radius').val() ? $('#radius').val() : '100';
        var postal_code = $('#postalcode').val() ? $('#postalcode').val() : '';
        if (postal_code == zipCenter) {
            $('#postalcode').val('');
            $('#radius').val('');
        }
        var sport_id = $('#sport').val() ? $('#sport').val() : '';
        var type_id = $('#type').val() ? $('#type').val() : '';
        var month = $('#month').val() ? $('#month').val() : '';
        var year = $('#year').val() ? $('#year').val() : '';
        var page = '1';
        var future_only = true;
        if (PageView == "sales")
            future_only = false;
        if ($('#past_events:checkbox:checked').val() == 1)
            future_only = false;

        var params = "";
        params += keywords ? '/keywords/' + encodeURIComponent(keywords) : '';
        params += '/future_only/' + future_only;
        params += sale ? '/sale/' + sale : '';
        params += postal_code ? '/postal_code/' + postal_code : '';
        params += radius ? '/radius/' + radius : '';
        params += sport_id ? '/sport_id/' + sport_id : '';
        params += type_id ? '/type_id/' + type_id : '';
        params += month ? '/month/' + month : '';
        params += year ? '/year/' + year : '';
        params += '/page/' + page;
        router.navigate('!search' + params + '/', {trigger: true});
    },
    _initialize_map: function () {

        var center;
        var address = getUrlParam('postal_code');
        var self = this;
        var zoom = 5;
        var radius = getUrlParam('radius');
        if (radius) {
            zoom = Math.round(15 - Math.log(radius) / Math.LN2)
        }
        function getMap() {
            var styles = [
                {
                    elementType: "geometry",
                    stylers: [
                        { lightness: 10 },
                        { saturation: -10 }
                    ]
                }
            ];
            var mapOptions = {
                zoom: zoom,
                mapTypeId: google.maps.MapTypeId.TERRAIN,
                center: center,
                styles: styles
            };
            self.map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);
        }
        if (address) {
            geocoder = new google.maps.Geocoder();
            geocoder.geocode({ 'address': address}, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    var lat = results[0].geometry.location.lat();
                    var lng = results[0].geometry.location.lng();
                    center = new google.maps.LatLng(lat, lng);
                    getMap.call(this);
                }
            });
        }
        else {
            center = new google.maps.LatLng(40, -97);
            getMap.call(this);

        }
    },
    resizeWrapper: function () {
        if ($(window).width() < 992)
            $('#events-search-wrapper').removeClass('container-full').addClass('container');
        else
            $('#events-search-wrapper').addClass('container-full').removeClass('container');
    },
    resizeElements: function (headerHeight) {
        if ($(window).width() > 991) {
            $('#event_details, #events_wrapper, #event_sales_details').css("height", $(window).height() - this.headerHeight);
            if (this.map_canvas.height() > 0)
                this.map_canvas.css("height", $(window).height() - this.headerHeight);
        }
        else {
            this.map_canvas.css("height", "360");
        }
    },
    initialize: function () {
        var self = this;
        self.event_details = $('#event_details');
        self.event_sales_details = $('#event_sales_details');
        self.btn_map = $('#btn_map');
        self.map_canvas = $('#map_canvas');
        self.events_wrapper = $('#events_wrapper');
        self._initialize_map();
        //we need the map...
        var checkExist = setInterval(function () {
            if (self.map) {
                setTimeout(function () { //delay markers
                    var list_view = new EventListView({collection: Events, map: self.map});
                    setMapCenter.call(self);
                    setMapZoom.call(self);
                }, 800);
                clearInterval(checkExist);
            }
        }, 30); // check every 30ms
        self.headerHeight = 109;
        self.resizeElements(self.headerHeight);
        self.resizeWrapper();
        $(window).resize(function () {
            self.resizeElements(self.headerHeight);
            self.resizeWrapper();
        });
    }
});


var postal_code, radius, zoomListener;
var Events = new EventList();
var router = new Router();
var PageView = (typeof PageView === 'undefined') ? "mapsearch" : PageView;
var zipCenter = '66853';
var App = null;
$(function () {
    App = new AppView();
    Backbone.history.start();
});