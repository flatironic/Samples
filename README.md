# Samples
Code Examples - Included are samples for event searching. These are merely samples that are meant demonstrate coding style and approach.

Here is a little info on each one:

1. page - html for the search page includes a handlebars template - uses bootstrap.

2. javascript - the search uses a module design and the js manages the form display, api calls and renders the results using the handlebars template, the search takes advantage of a 3rd party geolocation ip api that will lookup location based on the user's ip address and do an event query based on zipcode radius. If the ip address fails, then the initial search will default to a date ordered search.

3. api - restful, will send back json - other sites use this api with jsonp, mostly for rosters and such.

4. model - codeigniter query to get events.

5. import - this is a tool that will target api's of event registration sites and map their event properties into the database. Each imported event will then have it's own event webpage with a link to their registration for the purpose of the affiliate relationship.
