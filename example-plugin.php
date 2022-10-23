<?php
/**
 * Plugin name: EGDB - Example Plugin
 * Plugin URI: http://appdets.com
 * Description: Micro Eloquent for WordPress RAW SQL Queries
 * Version: 1.0
 * Author: Appdets
 * Author URI: http://appdets.com
 * License: GPL2
 */


define('EGDB_EXAMPLE_PLUGIN', __FILE__); 

# Exit if accessed directly
defined('ABSPATH') or die('Direct Script not Allowed');

require_once __DIR__ . '/egdb/Model.php'; 

require_once __DIR__ . '/example-models/Table.php';
require_once __DIR__ . '/example-models/Reservation.php';

final class EGDB_Example_Plugin {

    # constructor
    function __construct()
    {
        add_action( 'rest_api_init', array($this, 'register_rest_api') );
    }

    # register rest api
    public function register_rest_api()
    {
        register_rest_route( 'egdb/v1', '/tables', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tables'),
        ) );

        register_rest_route( 'egdb/v1', '/reservations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reservations'),
        ) );
    }

    # get tables
    public function get_tables()
    {
        $result = Table::first();
        return new WP_REST_Response( ['reservations' => $result->data()], 200 );
    }

    # get reservations
    public function get_reservations()
    {
        $result = Reservation::first();
        return new WP_REST_Response( $result, 200 );
    }
}

new EGDB_Example_Plugin();