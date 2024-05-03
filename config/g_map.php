<?php

return array(
    'map_link' => array(
        'google_map' => array(
            'url' => 'http://d1lkqsdr30qepu.cloudfront.net/production/place/default_400_600.png',
            'width' => 400,
            'height' => 600,
            'bg_color' => '#ebebeb',
        )
    ),
    "map_dimensions" => array(
        "size_400_600" => array(
            "width" => 400,
            "height" => 600,
        ),
    ),
    'render_map_gateway_url' => env("RENDER_MAP_GATEWAY_URL"),
    "map_default_bg_color" => "#ebebeb",
    "icon_base_url" => "http://d1lkqsdr30qepu.cloudfront.net/production/place-icon/home/",
    "item_icon_base_url" => "http://d1lkqsdr30qepu.cloudfront.net/production/place-icon/detail/",
    "source_icon_base_url" => "http://d1lkqsdr30qepu.cloudfront.net/production/source-icon/detail/",
    "imdb_site_base_url" => "https://www.imdb.com/title/",
    "fayvo_share_base_url" => "https://web.fayvo.com/f?",
    "youtube_site_base_url" => "https://www.youtube.com/watch?v=",
    "default_icon_name" => "default-1",
    "icons_mapping" => array(
        "" => "default",
        "meal_delivery" => "food_and_drinks_places",
        "meal_takeaway" => "food_and_drinks_places",
        "cafe" => "food_and_drinks_places",
        "bar" => "food_and_drinks_places",
        "bakery" => "food_and_drinks_places",
        "food" => "food_and_drinks_places",
        "delivery" => "food_and_drinks_places",
        "coffee" => "food_and_drinks_places",
        "restaurant" => "food_and_drinks_places",
        "restaurants" => "food_and_drinks_places",
        "supermarket" => "utilities_places",
        "movie_rental" => "utilities_places",
        "store" => "utilities_places",
        "beauty_salon" => "utilities_places",
        "real_estate_agency" => "utilities_places",
        "liquor_store" => "utilities_places",
        "pet_store" => "utilities_places",
        "laundry" => "utilities_places",
        "shoe_store" => "utilities_places",
        "jewelry_store" => "utilities_places",
        "department_store" => "utilities_places",
        "home_goods_store" => "utilities_places",
        "hardware_store" => "utilities_places",
        "convenience_store" => "utilities_places",
        "grocery_or_supermarket" => "utilities_places",
        "shopping_mall" => "utilities_places",
        "furniture_store" => "utilities_places",
        "florist" => "utilities_places",
        "clothing_store" => "utilities_places",
        "dry_cleaning" => "utilities_places",
        "bicycle_store" => "utilities_places",
        "gas_stations" => "utilities_places",
        "drugstore" => "utilities_places",
        "parking" => "utilities_places",
        "electronics_store" => "utilities_places",
        "electrician" => "utilities_places",
        "car_wash" => "utilities_places",
        "car_repair" => "utilities_places",
        "car_rental" => "utilities_places",
        "car_dealer" => "utilities_places",
        "book_store" => "utilities_places",
        "banks" => "utilities_places",
        "atm" => "utilities_places",
        "veterinary_care" => "services_places",
        "painter" => "services_places",
        "travel_agency" => "services_places",
        "transit_station" => "services_places",
        "train_station" => "services_places",
        "taxi_stand" => "services_places",
        "storage" => "services_places",
        "airport" => "services_places",
        "moving_company" => "services_places",
        "accounting" => "services_places",
        "insurance_agency" => "services_places",
        "light_rail_station" => "services_places",
        "lawyer" => "services_places",
        "locksmith" => "services_places",
        "dentist" => "services_places",
        "funeral_home" => "services_places",
        "gym" => "services_places",
        "plumber" => "services_places",
        "physiotherapist" => "services_places",
        "spa" => "services_places",
        "hair_care" => "services_places",
        "rv_park" => "services_places",
        "bus_station" => "services_places",
        "lodging" => "services_places",
        "roofing_contractor" => "services_places",
        "hotels" => "services_places",
        "subway_station" => "services_places",
        "pharmacies" => "services_places",
        "hospital" => "services_places",
        "health" => "services_places",
        "general_contractor" => "services_places",
        "finance" => "services_places",
        "local_government_office" => "government_holdings_places",
        "court_house" => "government_holdings_places",
        "police" => "government_holdings_places",
        "embassy" => "government_holdings_places",
        "city_hall" => "government_holdings_places",
        "cemetery" => "government_holdings_places",
        "post_office" => "government_holdings_places",
        "fire_station" => "government_holdings_places",
        "hindu_temple" => "religious_places_places",
        "church" => "religious_places_places",
        "synagogue" => "religious_places_places",
        "mosque" => "religious_places_places",
        "place_of_worship" => "religious_places_places",
        "school" => "educational_institutes_places",
        "primary_school" => "educational_institutes_places",
        "secondary_school" => "educational_institutes_places",
        "university" => "educational_institutes_places",
        "libraries" => "educational_institutes_places",
        "night_club" => "things_to_do_places",
        "park" => "things_to_do_places",
        "stadium" => "things_to_do_places",
        "museum" => "things_to_do_places",
        "movie_theater" => "things_to_do_places",
        "casino" => "things_to_do_places",
        "campground" => "things_to_do_places",
        "bowling_alley" => "things_to_do_places",
        "art_gallery" => "things_to_do_places",
        "tourist_attraction" => "things_to_do_places",
        "amusement_parks" => "things_to_do_places",
        "zoo" => "things_to_do_places",
        "administrative_area_level_1" => "government_holdings_places",
        "administrative_area_level_2" => "government_holdings_places",
        "administrative_area_level_3" => "government_holdings_places",
        "administrative_area_level_4" => "government_holdings_places",
        "administrative_area_level_5" => "government_holdings_places",
        "town_square" => "government_holdings_places",
        "postal_town" => "government_holdings_places",
        "postal_code_suffix" => "government_holdings_places",
        "postal_code_prefix" => "government_holdings_places",
        "postal_code" => "government_holdings_places",
        "post_box" => "government_holdings_places",
        "point_of_interest" => "government_holdings_places",
        "archipelago" => "regions_places",
        "colloquial_area" => "regions_places",
        "continent" => "regions_places",
        "country" => "regions_places",
        "natural_feature" => "regions_places",
        "plus_code" => "regions_places",
        "floor" => "other_places",
        "geocode" => "other_places",
        "intersection" => "other_places",
        "establishment" => "other_places",
        "neighborhood" => "other_places",
        "room" => "other_places",
        "premise" => "other_places",
        "route" => "other_places",
        "street_address" => "other_places",
        "street_number" => "other_places",
        "subpremise" => "other_places",
        "locality" => "default-1",
        "doctor" => "services_places",
        "sublocality" => "other_places",
        "sublocality_level_1" => "other_places",
        "sublocality_level_2" => "other_places",
        "sublocality_level_3" => "other_places",
        "sublocality_level_4" => "other_places",
        "sublocality_level_5" => "other_places"
    ),
    "scheme_url" => array(
        'ios' => array(
//            'youtube' => "https://www.youtube.com/watch?v=",
            'youtube' => "youtube://",
            'imdb' => "imdb:///title/",
//            'itunes' => "music://geo.itunes.apple.com/us/albums/",
            'itunes' => "music://",
            'ibook' => "itms-books://",
            'google' => "googlemaps://",
            'web' => "",
            'anghami' => "anghami://play.anghami.com/song/",
        ),
        'android' => array(
            'youtube' => "youtube://",
            'imdb' => "imdb:///title/",
            'itunes' => "music://",
            'ibook' => "itms-books://",
            'google' => "googlemaps://",
            'web' => "",
            'anghami' => "anghami://play.anghami.com/song/",
        ),
    ),
    "api_share_base_url_local" => "https://web-staging.fayvo.com/",
    "api_share_base_url_staging" => "https://web-staging.fayvo.com/",
    "api_share_base_url_production" => "https://www.fayvo.com/",
    'api_share_prefix' => array(
        'food' => 'place/?id=',
        'place' => 'place/?id=',
        'location' => 'place/?id=',
        'movie' => 'movie-tv/?id=',
        'tv' => 'movie-tv/?id=',
        'television' => 'movie-tv/?id=',
        'music' => 'music-post/?id=',
        'book' => 'book/?id=',
        'video' => 'video/?id=',
        'url' => 'web/?id=',
        'game' => 'game/?id=',
        'profile' => 'profile/?id=',
        'media' => 'media-post/?id=',
        'box' => 'box-view/?id=',
    )
);