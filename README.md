# Samples
Code Examples - Included are samples for event searching. These are merely samples that are meant demonstrate coding style and approach.

Here is a little info on each one:

##event-search

1. page_events - html for the search page includes a handlebars template - uses bootstrap.

2. js_events - the search uses a module design and the js manages the form display, api calls and renders the results using the handlebars template, the search takes advantage of a 3rd party geolocation ip api that will lookup location based on the user's ip address and do an event query based on zipcode radius. If the ip address fails, then the initial search will default to a date ordered search.

3. api_events - restful, will send back json - other sites use this api with jsonp, mostly for rosters and such.

4. model_events - codeigniter query to get events.

5. import_events - this is a tool that will target api's of event registration sites and map their event properties into the database. Each imported event will then have it's own event webpage with a link to their registration for the purpose of the affiliate relationship.

##backbone-googlemap

1. page_mapsearch - html with bootstrap elements and handlebars templates, responsive design. This app includes event sales functionality that will display events for sale for logged in users with an additional view with event sales details.

2. js_mapsearch - backbone app with data binding between map and event listings, uses RESTful api requests.
