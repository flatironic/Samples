<?php
/**
 * Races Model
 *
 * Contains methods used for race list views and search
 *
 */
class Races_model extends CI_Model {

    public function __construct() {
        parent::__construct ();
    }

    public function get_races($state_id = 'all', $sport_id = 'all', $type_id = 'all', $sub_type_id = 'all', $month = 0, $year = 0, $keywords = '', $zip = '', $radius = '', $future_only = "true", $order = 'DESC', $start, $limit) {

        $future_only = ($future_only == "true") ? TRUE : FALSE;

        if ($zip){
            $this->db->select ( '*' )->from ( 'pr_zipcode' )->where ( 'zipcode', $zip );
            $result = $this->db->get ();
            $zipData = $result->first_row ();
            //if (!$zipData)
            //    return;
            $lat = $zipData->lat;
            $lon = $zipData->lon;
            if ( !$lat ) $lat = 0;
            if ( !$lon ) $lon = 0;
            if ( !$radius ) $radius = 100;

            $query = $this->db->query ( "SELECT zipcode FROM pr_zipcode WHERE (POW((69.1*(lon-\"$lon\")*cos($lat/57.3)),\"2\")+POW((69.1*(lat-\"$lat\")),\"2\"))<($radius*$radius) " );
            if ($query->num_rows () > 0) {
                foreach ( $query->result () as $row ) {
                    $zipcodes [] = $row->zipcode;
                }
            }
        }
        //subquery for max stage date after event group by:
        $this->db->select('*')->from('pr_stages')->order_by('date', 'desc');
        $subquery = $this->db->_compile_select();
        $this->db->_reset_select();

        $this->db->select ( '*, pr_races.id as id, s.name as stagename, pr_races.name as racename, pr_sports.*, pr_sports.name as sport_name, pr_races_events.event_id as event_id, pr_states.abbv as state_abbv, pr_sub_types.sub_type as sub_type, s.lat as lat, s.lon as lon, pr_zipcode.lat as zip_lat, pr_zipcode.lon as zip_lon, pr_types.name as type_name, pr_image_filenames.filename as filename' )->from ( 'pr_races' );
        $this->db->join ( 'pr_races_events', 'pr_races_events.race_id = pr_races.id' );
        $this->db->join ( 'pr_events', 'pr_races_events.event_id = pr_events.id' );
        $this->db->join("($subquery) s",'s.race_id = pr_races.id');
        $this->db->join ( 'pr_states_races', 'pr_states_races.race_id = pr_races.id' );
        $this->db->join ( 'pr_states', 'pr_states.id=pr_states_races.state_id' );
        $this->db->join ( 'pr_types_stages', 'pr_types_stages.stage_id = s.id' );
        $this->db->join ( 'pr_types', 'pr_types_stages.type_id = pr_types.id' );
        $this->db->join ( 'pr_sports', 'pr_sports.id = pr_types.sport_id' );
        $this->db->join ( 'pr_zipcode_stages', 'pr_zipcode_stages.stage_id = s.id');
        $this->db->join ( 'pr_zipcode', 'pr_zipcode.id = pr_zipcode_stages.zipcode_id');
        $this->db->join ( 'pr_sub_types_stages', 'pr_sub_types_stages.stage_id = s.id' );
        $this->db->join ( 'pr_sub_types', 'pr_sub_types_stages.sub_type_id = pr_sub_types.id' );
        $this->db->join ( 'pr_image_filenames', 'pr_events.header_image_id = pr_image_filenames.image_id', 'left');

        $keywords = mysql_real_escape_string ($keywords);

        if ( strtotime($keywords) ) {
            $date = date ( "Y-m-d", strtotime($keywords) );
            $this->db->where ( "s.date", $date );
        }

        if ($sport_id != 'all' && $sport_id != '') {
            $this->db->where ( 'pr_sports.id', $sport_id );
        }

        if ($state_id != 'all' && $state_id) {
            $this->db->where ( 'pr_states_races.state_id', $state_id );
        }

        if ($type_id != 'all' && $type_id) {
            $this->db->where ( 'pr_types_stages.type_id', $type_id );
        }

        if ($sub_type_id != 'all' && $sub_type_id) {
            $this->db->where ( 'pr_sub_types_stages.sub_type_id', $sub_type_id );
        }

        if ($month != 0) {
            $this->db->where ( 'MONTH(s.date)', $month );
        }

        if ($year != 0) {
            $this->db->where ( 'YEAR(s.date)', $year );
        }

        if ($keywords != '') {
            $where = "(pr_races.id > 0";
            if ($keywords != '') {
                $where .= " AND (pr_events.name LIKE '%$keywords%')";
            }

            if ( strtotime($keywords) ) {
                $date = date ( "Y-m-d", strtotime($keywords) );
            }

            $where .= ")";

            $this->db->where ( $where );
        }

        if($future_only) {
            $this->db->where ( 's.date >= CURRENT_DATE()' );
        }

        if ($zipcodes)
            $this->db->where_in ( 'pr_zipcode.zipcode', $zipcodes );

        $this->db->order_by ( 's.date ' . $order . ', pr_races.name asc');


        if ( isset($limit) && isset($start) ) {
            $this->db->limit ($limit, $start);
        }

        $this->db->group_by('pr_events.id');
        $results = $this->db->get ();
        return $results;

    }

}

/* End of file races_model.php */
/* Location: ./system/application/models/races_model.php */