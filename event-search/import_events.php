<?php

define('ENDPOINT', 'runsignup.com/rest/');
define('PROTOCOL', 'https');
define('API_KEY', 'wwnGbjn6cu1590XnXjt1Dd1zDc3wMrL9');
define('API_SECRET', 'NYFP7TLaeC5esTrg2L8wPZVi2PFE6hEJ');

class Import extends ST_Controller {

    public function __construct() {
        parent::ST_Controller ();

        $this->load->model ( 'races_model', 'races_model' );
        $this->load->model ( 'race_model', 'race_model' );
        $this->load->model ( 'photos_model', 'photos_model' );
        $this->load->library('RunSignupRestClient');
        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
    }

    function eventbrite(){

        if ($this->data['authorized_user']->access_level_id < 2){
            redirect('/user/login?ref=/' . uri_string() . '&view=page');
            die;
        }

        $get_results = $this->input->get ( 'get_results', TRUE );
        if ($get_results){
            $new_events_only = $this->input->get ( 'new_events_only', TRUE );
            $future_events_only = $this->input->get ( 'future_events_only', TRUE );
            $q = urldecode($this->input->get ( 'name', TRUE ));
            $page = $this->input->get ( 'page', TRUE );
            $since_id = $this->input->get ( 'since_id', TRUE );
            $date_created_range_start = $this->input->get ( 'created_range_start', TRUE );
            $date_created_range_end = $this->input->get ( 'created_range_end', TRUE );
            $date_modified_range_start = $this->input->get ( 'modified_range_start', TRUE );
            $date_modified_range_end = $this->input->get ( 'modified_range_end', TRUE );
            $categories = urldecode($this->input->get ( 'categories', TRUE ));
            $origin_id = $this->input->get ( 'origin_id', TRUE );

            $endpoint = 'https://www.eventbriteapi.com/v3/';
            $uri = "/events/search?";
            $uri .= "venue.country=US";
            $uri .= "&amp;tracking_code=getevent";
            $uri .= $q ? "&amp;q=" . $q : "";
            $uri .= $page ? "&amp;page=" . $page : "";
            $uri .= $categories ? "&amp;categories=" . $categories : "";
            $uri .= $since_id ? "&amp;since_id=" . $since_id : "";
            $uri .= $date_created_range_start ? "&amp;date_created.range_start=" . $date_created_range_start . "T00:00:00Z" : "";
            $uri .= $date_created_range_end ? "&amp;date_created.range_end=" . $date_created_range_end . "T00:00:00Z" : "";
            $uri .= $date_modified_range_start ? "&amp;date_modified.range_start=" . $date_modified_range_start . "T00:00:00Z" : "";
            $uri .= $date_modified_range_end ? "&amp;date_modified.range_end=" . $date_modified_range_end . "T00:00:00Z" : "";

            // Construct the full URL.
            $request_url = $endpoint . $uri;
            $options = array(
                'http' => array(
                    'method' => 'GET',
                    'header'=> "Authorization: Bearer LRHL3GXSMVAAYLQLEQIG"// . $this->token
                )
            );
            // Call the URL and get the data.
            $resp = file_get_contents($request_url, false, stream_context_create($options));
            $resp  = json_decode($resp);
            $add_origin_ids = $this->input->post ( 'add_origin_id', TRUE );
            $new_events_only = $this->input->get ( 'new_events_only', TRUE );
            $race_count = 0;
            $message = "";

            foreach($resp->events as $index => $race){
                $race_id = "";
                $event_id = "";
                if (!$race->id)
                    continue;

                if ($race->online_event)
                    continue;

                if ($race_exists = $this->race_model->check_race_exists_external( $race->id )){
                    $race_id = $race_exists->race_id;
                    $event_id = $race_exists->event_id;
                }

                $race_detail = "";
                $race_detail .= "<input type='checkbox' name='add_origin_id[" . $race->id . "]' id='add_origin_id" . $race->id . "'> &nbsp;";
                $race_detail .= $race->id . "&nbsp;";
                if ($race_id && $event_id){
                    $race_detail .= "<a target='_blank' href='". $this->config->item ( 'base_url' ) . event_link($event_id, $race->name->text) . "'>". $this->config->item ( 'base_url' ) . event_link($event_id, $race->name->text) . "</a>";
                }
                else{
                    $race_detail .= $race->name->text;
                }
                $race_detail .= " : " . $race->start->local . "<br/>";
                $race_detail .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Created On:" . $race->created . " - Last Modified:" . $race->changed;
                // $race_detail .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email: <input value='" . $race["contactEmailAdr"] . "' style='width:500px;' type='input' placeholder='promoter email' name='promoter_email_" . $race->id . "' id='promoter_email" . $race->id . "'>";
                $race_detail .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Logo:&nbsp;  <input value='" . $race->logo_url ."' style='width:500px;' type='input' placeholder='race logo' name='race_logo_" . $race->id . "' id='race_logo" . $race->id . "'> ";
                $race_detail .= "<hr>";

                $this->data['race_details'][] = $race_detail;

                if ($add_origin_ids[$race->id]){
                    $message .= $this->add_update_eventbrite_event($race, $race_id, $event_id);
                }
                $race_count++;
            }
            if ($race_count == 0)
                $message = "No races with those filters, Brah!";
            $this->data ['message'] = $message;

            $this->data ['pagination'] = $resp->pagination;
            $this->data ['add_origin_ids'] = $add_origin_ids;
        }
        $this->data ['import_name'] = "Eventbrite";
        $this->data['cc_data'] = $this->render ( 'administrator/import', TRUE );
        $this->render ( 'administrator/wrapper' );
    }

    private function add_update_eventbrite_event($race, $race_id = null, $event_id = null)
    {
        $race_logo = $this->input->post ( 'race_logo_' . $race->id, TRUE );
        $event = new stdClass();
        $event->name = trim($race->name->text);
        if ($race->description->html){
            $event->notes = str_ireplace ( '<img' , '<img class="img-responsive"', $race->description->html  );
        }
        $race_data = new stdClass();
        $race_data->origin_id = $race->id;
        $race_data->origin = "eventbrite";
        if ($race_logo)
            $race_data->origin_logo = $race_logo;
        //if ($promoter_email)
        //    $race_data->promoter_email = $promoter_email;

        $race_data->name = date("Y", strtotime($race->start->local)) . " " . $race->name->text;
        $race_data->promoter_name = "EventBrite";
        $race_data->createdby_user_id = "88396";
        $race_data->ownedby_user_id = "88396";
        $race_data->editedby_user_id = "88396";
        $race_data->user_id = "88396";

        //foreach($race->stages as $stage){
        $stage = new stdClass();

        $stage->name = trim($race->name->text);
        $stage->date = date("Y-m-d", strtotime($race->start->local));
        //$stage->notes = $revent["details"];
        $stage->address = $race->venue->address->address_1;
        if ($race->venue->address->city)
            $stage->city = $race->venue->address->city;
        else
            $stage->city = "Somewhere";
        $stage->lat = $race->venue->address->latitude;
        $stage->lng = $race->venue->address->longitude;
        $stage->state_id = $this->race_model->get_state_id($race->venue->address->region);
        if ($race->venue->address->postal_code)
            $stage->zipcode_id = $this->race_model->get_zipcode_id($race->venue->address->postal_code);
        else
            $stage->zipcode_id = 0;
        $stage->country_code = $race->venue->address->country;
        $stage->links = array('registration' => $race->url);

        if ($race->subcategory->name == "Running")
            $stage->type_id = "41";
        elseif ($race->subcategory->name == "Mountain Biking")
            $stage->type_id = "1";
        elseif ($race->subcategory->name == "Cycling")
            $stage->type_id = "2";
        elseif ($race->subcategory->name == "Walking")
            $stage->type_id = "40";
        else
            $stage->type_id = "15";

        $stage->sub_type_id = "30";
        $stage_data [] = $stage;
        //}

        //do we have event and race ids? - if so then update
        if ($race_id && $event_id){
            $event->id = $event_id;
            $race_data->id = $race_id;
        }

        $data = array();
        $data ['event_info'] = $event;
        $data ['race_data'] = $race_data;
        $data ['stage_data'] = $stage_data;

        //echo "<pre>";print_r($data);echo $race_id ."|". $event_id; echo "<pre>";die;

        if ($race_id && $event_id){
            $data ['race_data']->id = $race_id;
            $data ['event_info']->event_id = $event_id;
            //remove the stages and readd them in the update - we don't know the stage ids to update...
            $this->race_model->clear_stages ( $race_id );
            $this->race_model->update_event ( $data );
            $this->race_model->update ( $data );
            $header_image_id = $this->race_model->get_event ( $event_id )->header_image_id;
            if ($header_image_id){
                $image =  $this->photos_model->get_image ( $header_image_id, $image_size = "custom" );
                $this->photos_model->update_image_filename($image->image_filename_id, $race_logo);
            }
        }
        else{
            $event_id = $this->race_model->add_event ( $data );
            $race_id = $this->race_model->add ( $data, $event_id );
            $image_id = $this->photos_model->add_image_event_external($event_id, $race_logo);
        }

        if (isset($race_id) && isset($event_id))
            return "Added: " . $event->name . " | <a target='_blank' href='". $this->config->item ( 'base_url' ) . event_link($event_id, $event->name) . "'>". $this->config->item ( 'base_url' ) . event_link($event_id, $event->name) . "</a><br/>";
    }

    private function activeRequest(){

        $future_events_only = $this->input->get ( 'future_events_only', TRUE );
        $exclude_children = $this->input->get ( 'exclude_children', TRUE );
        $asset_guid = $this->input->get ( 'asset_guid', TRUE );
        $name = $this->input->get ( 'name', TRUE );
        $results_per_page = $this->input->get ( 'results_per_page', TRUE );
        $current_page = $this->input->get ( 'page', TRUE );
        $start_event_date = $this->input->get ( 'start_event_date', TRUE );
        $end_event_date = $this->input->get ( 'end_event_date', TRUE );
        $topic_name = $this->input->get ( 'topic_name', TRUE );

        if ($future_events_only)
            $range_date = date("Y-m-d") . '..';
        if ($start_event_date && !$end_event_date)
            $range_date = $start_event_date . '..';
        if ($end_event_date && !$start_event_date)
            $range_date =  '..' . $end_event_date;
        if ($start_event_date && $end_event_date)
            $range_date = $start_event_date . '..' . $end_event_date;

        if ($name)
            $name = str_replace(" ", "%20", $name);

        $origin_id = $this->input->get ( 'origin_id', TRUE );
        if ($origin_id)
            $urlPrefix = 'race/' . $origin_id;

        $ch = curl_init();

        $url = "http://api.amp.active.com/v2/search?";
        $url .= "api_key=3nqjztsat62fxy6vpuxjpy5e";
        $url .= $asset_guid ? "&amp;asset.assetGuid=" . $asset_guid : "";
        $url .= $name ? "&amp;query=" . $name : "";
        $url .= $results_per_page ? "&amp;per_page=" . $results_per_page : "";
        $url .= $current_page ? "&amp;current_page=" . $current_page : "";
        $url .= $range_date ? "&amp;start_date=" . $range_date : "";
        $url .= $exclude_children ? "&amp;exclude_children=true" : "";
        $url .= "&amp;category=event";
        $url .= "&amp;registerable_only=true";

        $url .= $topic_name ? "&amp;topic_name=" . str_replace(",","%20OR%20topic_name=",strtolower($topic_name)) : "";
        //echo $url;die;
        // Try to get url with curl
        $data = null;
        $httpMethod = 'GET';
        $parse = true;
        $postParams = "query=running&category=event&start_date=2013-07-04..&near=San%20Diego,CA,US&radius=50&api_key=3nqjztsat62fxy6vpuxjpy5e";
        if ($ch)
        {
            // Set up curl options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // For debugging
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

            // OAuth headers
            $headers = array();

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // Determine HTTP method
            if ($httpMethod == 'GET')
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
            else if ($httpMethod == 'POST')
            {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
            }
            // DELETE request
            else if ($httpMethod == 'DELETE')
            {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($postParams))
                {
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
                }
            }

            // Make request
            $data = curl_exec($ch);

            // Store header debugging info
            $debugData['requestHeaders'] = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        }

        $rest_data = json_decode($data, true);

        $this->data ['url'] = $url;
        return $rest_data;
    }

    public function active()
    {

        if ($this->data['authorized_user']->access_level_id < 2){
            redirect('/user/login?ref=/' . uri_string() . '&view=page');
            die;
        }

        $get_results = $this->input->get ( 'get_results', TRUE );
        if ($get_results){
            $new_events_only = $this->input->get ( 'new_events_only', TRUE );

            $resp = $this->activeRequest();

            $urlPrefix = 'races';

            if (!$resp)
                $message = "Request failed.\n";

            $add_origin_ids = $this->input->post ( 'add_origin_id', TRUE );
            $new_events_only = $this->input->get ( 'new_events_only', TRUE );

            $races = $resp['results'];
            $race_count = 0;
            foreach($races as $index => $race){
                //check if race exists - lookup origin_id
                $race_id = "";
                $event_id = "";
                if (!$race["assetGuid"])
                    continue;
                //let's not do virtual races
                if (!$race["place"]["postalCode"] || !is_numeric($race["place"]["postalCode"]))
                    continue;
                if (strpos(strtolower($race["assetName"]),"virtual") > -1)
                    continue;

                if ($race_exists = $this->race_model->check_race_exists_external( $race["assetGuid"] )){
                    if ($new_events_only)
                        continue;
                    $race_id = $race_exists->race_id;
                    $event_id = $race_exists->event_id;
                }

                if (strpos($race["logoUrlAdr"], "hotrace") > 0)
                    $race["logoUrlAdr"] = null;

                $race_detail = "";
                $race_detail .= "<input type='checkbox' name='add_origin_id[" . $race["assetGuid"] . "]' id='add_origin_id" . $race["assetGuid"] . "'> &nbsp;";
                $race_detail .= "<a href='?get_results=go&asset_guid=" . $race["assetGuid"] . "'>" . $race["assetGuid"] . "</a> ";
                if ($race_id && $event_id){
                    $race_detail .= "<a target='_blank' href='". $this->config->item ( 'base_url' ) . event_link($event_id, $race["assetName"]) . "'>". $this->config->item ( 'base_url' ) . event_link($event_id, $race["assetName"]) . "</a>";
                }
                else{
                    $race_detail .= $race["assetName"];
                }
                $race_detail .= " : " . $race["activityStartDate"] . "<br/>";
                $race_detail .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Created On:" . $race["createdDate"] . " - Last Modified:" . $race["modifiedDate"];
                $race_detail .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email: <input value='" . $race["contactEmailAdr"] . "' style='width:500px;' type='input' placeholder='promoter email' name='promoter_email_" . $race["assetGuid"] . "' id='promoter_email" . $race["assetGuid"] . "'>";
                $race_detail .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Logo:&nbsp;  <input value='" . $race["logoUrlAdr"] ."' style='width:500px;' type='input' placeholder='race logo' name='race_logo_" . $race["assetGuid"] . "' id='race_logo" . $race["assetGuid"] . "'> ";
                $race_detail .= "<hr>";
                $this->data['race_details'][] = $race_detail;

                if ($add_origin_ids[$race["assetGuid"]]){
                    $message .= $this->add_update_active_race($race, $race_id, $event_id);
                }
                $race_count++;
            }
            if ($race_count == 0)
                $message = "No races with those filters, Brah!";
        }

        $this->data ['import_name'] = "Active";
        $this->data ['message'] = $message;
        $this->data ['resp'] = $resp;
        $this->data ['races'] = $races;
        $this->data ['add_origin_ids'] = $add_origin_ids;
        $this->data['cc_data'] = $this->render ( 'administrator/import', TRUE );
        $this->render ( 'administrator/wrapper' );
    }

    /**
     * @param $race
     * @param $race_id
     * @param $event_id
     * @return string
     */
    private function add_update_active_race($race, $race_id = '', $event_id = '')
    {
        $event = new stdClass();
        $event->name = trim($race["assetName"]);
        if ($race["assetDescriptions"]) {
            foreach ($race["assetDescriptions"] as $description) {
                $event->notes .= $description["description"];
            }
        }
        $race_data = new stdClass();
        $race_data->origin_id = $race["assetGuid"];
        $race_data->origin = "active";
        if ($race['logoUrlAdr'] && strlen($race['logoUrlAdr']) > 5)
            $race_data->origin_logo = $race["logoUrlAdr"];
        if ($race['contactEmailAdr'])
            $race_data->promoter_email = $race["contactEmailAdr"];
        if ($race["activityStartDate"])
            $race_data->name = date("Y", strtotime($race["activityStartDate"])) . " " . $race["assetName"];
        else
            $race_data->name = $race["assetName"];
        $race_data->promoter_name = "Active";
        $race_data->createdby_user_id = "85468";
        $race_data->ownedby_user_id = "85468";
        $race_data->editedby_user_id = "85468";
        $race_data->user_id = "85468";

        foreach ($race["assetComponents"] as $aevent) {
            $stage = new stdClass();

            $stage->name = trim($aevent["assetName"]);
            if ($aevent["activityStartDate"])
                $stage->date = date("Y-m-d", strtotime($aevent["activityStartDate"]));
            else
                $stage->date = date("Y-m-d", strtotime($race["activityStartDate"]));;
            $stage->notes = "";

            $stage->address = $race["place"]["addressLine1Txt"];
            $stage->city = $race["place"]["cityName"];
            $stage->lat = $race["place"]["latitude"];
            $stage->lon = $race["place"]["longitude"];

            if ($race["place"]["postalCode"])
                $stage->state_id = $this->race_model->get_state_id($race["place"]["stateProvinceCode"]);
            if ($race["place"]["postalCode"])
                $stage->zipcode_id = $this->race_model->get_zipcode_id($race["place"]["postalCode"]);
            else
                $stage->zipcode_id = 0;
            //TO-DO ? - add lat long to stage
            //$race["place"]["longitude"]
            //$race["place"]["latitude"]
            $stage->country_code = $race["place"]["countryCode"];
            $stage->links = array('registration' => $race["registrationUrlAdr"]);

            $event_type = strtolower($race["assetTopics"]["topic"]["topicName"]);
            if ($event_type == 'Running')
                $stage->type_id = "41";
            elseif ($event_type == 'cycling')
                $stage->type_id = "2";
            elseif ($event_type == 'triathlon')
                $stage->type_id = "42";
            elseif ($event_type == 'duathlon')
                $stage->type_id = "21";
            else
                $stage->type_id = "15";

            $stage->sub_type_id = "30";
            $stage_data [] = $stage;

        }

        $data = array();
        $data ['event_info'] = $event;
        $data ['race_data'] = $race_data;
        $data ['stage_data'] = $stage_data;

        if ($race_id && $event_id) {
            //remove the stages and readd them in the update - we don't know the stage ids to update...
            $data ['race_data']->id = $race_id;
            $data ['event_info']->event_id = $event_id;
            $this->race_model->clear_stages($race_id);
            $this->race_model->update_event($data);
            $this->race_model->update($data);
            $header_image_id = $this->race_model->get_event($event_id)->header_image_id;
            if ($header_image_id && $race_data->origin_logo) {
                $image = $this->photos_model->get_image($header_image_id, $image_size = "custom");
                $this->photos_model->update_image_filename($image->image_filename_id, $race_data->origin_logo);
            }
            elseif($race_data->origin_logo){
                $image_id = $this->photos_model->add_image_event_external($event_id, $race_data->origin_logo);
            }
        } else {
            $event_id = $this->race_model->add_event($data);
            $race_id = $this->race_model->add($data, $event_id);
            if($race_data->origin_logo)
                $image_id = $this->photos_model->add_image_event_external($event_id, $race_data->origin_logo);
        }

        if (isset($race_id) && isset($event_id))
            return "Added: " . $event->name . " | <a target='_blank' href='". $this->config->item ( 'base_url' ) . event_link($event_id, $event->name) . "'>". $this->config->item ( 'base_url' ) . event_link($event_id, $event->name) . "</a><br/>";

    }
}