<?php
session_start();
require_once 'functions/functions.php';
require_once 'functions/database.php';

function search_get($data)
{
    $logged_in_user = user\get_logged_in_user();
    $user_id = NULL;
    if ($logged_in_user)
        $user_id = $logged_in_user['id'];

    // Get all trips near the route
    $trips = database\get_trips_near_on(
        $data['route'],
        $data['departure'],
        $user_id
    );

    if ($trips == NULL)
        $trips = array();

    // Filter trips based on gender and women-only search
    $filtered_trips = array();
    foreach ($trips as $trip) {
        if ($user_id && $trip['driver']['id'] == $user_id) {
            continue;
        }
        if (isset($data['women_only']) && $data['women_only']) {
            if ($trip['women_only'] == 1) {
                $filtered_trips[] = $trip;
            }
        } else {
            if ($user_id) {
                $user = user\get_user($user_id);
                if ($user && $user['gender'] == 0) {  
                    $filtered_trips[] = $trip;  
                } else if ($trip['women_only'] == 0) {  
                    $filtered_trips[] = $trip;
                }
            } else if ($trip['women_only'] == 0) { 
                $filtered_trips[] = $trip;
            }
        }
    }

    $trips_found = array("trips" => $filtered_trips);
    functions\json_respond('OK', 'Searched!', $trips_found);
}

function request_post($data)
{
    $logged_in_user = user\get_logged_in_user();
    if (!$logged_in_user)
        return functions\json_respond('ERROR', 'Login Required!');

    $request_data = array(
        "user_id" => $logged_in_user['id'],
        "trip_id" => $data['trip_id'],
        "message" => $data['message']
    );

    if (database\request_ride($request_data))
        return functions\json_respond('OK', 'Request Sent!');
    else
        return functions\json_respond('ERROR', 'Unable to request ride');
}

if ($_GET) {
    search_get(json_decode($_GET['data'], true));
} elseif ($_POST) {
    request_post(json_decode($_POST['data'], true));
}