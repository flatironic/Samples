<?php defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/REST_Controller.php';

class Races extends REST_Controller
{

    function events_get()
    {
        $this->load->model ( 'races_model', 'races_model' );
        $this->load->model ( 'listings_model', 'listings_model' );

        $state_id = $this->get('state_id', true);
        $sport_id = $this->get('sport_id', true);
        $type_id = $this->get('type_id', true);
        $sub_type = $this->get('sub_type', true);
        $year = $this->get('year', true);
        $month = $this->get('month', true);
        $future_only = $this->get('future_only', true);
        $start = $this->get('start', true);
        $limit = $this->get('limit', true);
        $page  = $this->get('page', true);
        $results_per_page = $this->get('results_per_page', true);
        $keywords = $this->get('keywords', true);
        $zipcode = $this->get('postal_code', true);
        $radius = $this->get('radius', true);

        if ($page){
            $limit = $results_per_page ? $results_per_page : '100';
            $start = ($page - 1) * $limit;
        }

        if( $this->get('sale', true )){
            $model = "listings_model";
            $sort = "DESC";
        }
        else{
            $model = "races_model";
            $sort = $future_only == "true" ? "ASC" : "DESC";
        }

        $races = $this->{$model}->get_races($state_id, $sport_id, $type_id, $sub_type, $month, $year, $keywords, $zipcode, $radius, $future_only, $sort, $start, $limit );

        $this->eventListResponse($races);
    }



    function registration_events_get()
    {
        $this->load->model ( 'registration_events_model', 'registration_events' );
        $this->load->model ( 'races_model', 'rlist' );
        $events = $this->registration_events->get_events(TRUE);
        $race_ids = array();

        if ($events && $events->result()) {
            foreach ($events->result() as $race) {
                array_push($race_ids, $race->race_id);
            }
            $races = $this->rlist->get_races_by_raceids($race_ids);
            $this->eventListResponse($races);
        }
    }

    public function eventListResponse($races)
    {


        if ($races && $result = $races->result()) {
            //check for pr events
            $this->load->model ( 'events_model', 'events_model' );
            $this->load->model ( 'registration_events_model', 'reglist' );
            $this->load->model ( 'stage_model', 'stage_model' );
            foreach ($result as $key) {
                $race_ids[] = $key->race_id;
                $event_ids[] = $key->event_id;
            }
            $reg_races = $this->reglist->get_events_by_races( $race_ids );

            foreach ($reg_races->result() as $key) {
                $is_open = $this->reglist->is_open($key->id);
                if ($is_open)
                    $reg_race_ids[] = $key->race_id;
            }
            $e_id = null;
            foreach ($result as $key) {
                //if a duplicate event
                if ($key->event_id == $e_id){
                    continue;
                }
                $e_id = $key->event_id;
                //echo '<pre>';
                //print_r($key);
                //echo '</pre>'
                $r = new stdClass();
                $r->id = $key->race_id;
                $r->race_id = $key->race_id;
                $r->event_id = $key->event_id;
                $r->stage_id = $key->stage_id;
                $reg_links = $this->stage_model->get_links( $r->stage_id );
                if ($reg_links){
                    $reg_links = $reg_links->result();
                    foreach($reg_links as $links){
                        if ($links->name == 'registration'){
                            $r->aff_name = $this->affiliateName($links->link);
                            $r->aff_reg_link = "/races/reg_link?stage_id=" . $r->stage_id . "&affiliate=" . $r->aff_name . "&stage=" . urlencode($key->stagename);
                        }
                    }
                }
                $r->sport_id = $key->sport_id;
                $r->date = $key->date;
                $r->address = $key->address;
                $r->city = $key->city;
                $r->state = $key->abbv;
                $r->type = $key->type_name;
                if ($key->sub_type != 'None')
                    $r->sub_type = $key->sub_type;
                $r->stagename = $key->stagename;
                $event = $this->events_model->get_event_by_race($r->race_id);
                $r->eventname = $event->name;
                $r->racename = $key->racename;
                if (in_array($r->race_id, $reg_race_ids)) {
                    $r->pr_reg = true;
                }
                $r->filename = $key->filename;
                $r->lon = $key->lon;
                $r->lat = $key->lat;
                $r->zip_lon = $key->zip_lon;
                $r->zip_lat = $key->zip_lat;
                if ($key->event_sales_id){
                    $r->event_sales_id = $key->event_sales_id;
                    $r->event_sales_description = $key->description;
                    $r->event_price = $key->event_price;
                }

                $racesAPI[] = $r;
            }
        }

        if ($racesAPI) {
            $this->response($racesAPI, 200); // 200 being the HTTP response code
        } else {
            $this->response("", 200); //handle no results in js
        }
    }
}
