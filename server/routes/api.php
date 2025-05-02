<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

//converts celcius to farenheight
function celsiusToFahrenheit(float $celsius): float {
    return ($celsius * 9 / 5) + 32;
}

// gets coordinates for a given city
function getCoordinates(string $city):array{
    $apiKey = config('services.openweather.key');
    
    $geoResponse = Http::get('http://api.openweathermap.org/geo/1.0/direct', [
        'q' => $city,
        'limit' => 1,
        'appid' => $apiKey,]);    
    
        //error handling for location call
        if (!$geoResponse->successful()) {
            return ['error' => true, 
                    'message' => 'Failed to fetch location data', 
                    'body' => $geoResponse->body(), 
                    'status' => $geoResponse->status()];
        }
        $geoData = $geoResponse->json();

        if (empty($geoData) || empty($geoData[0])) {
            return ['error' => true, 
                    'message' => 'No location found for the given city'];
        }

        $lat = $geoData[0]['lat'];
        $lon = $geoData[0]['lon'];
        return [$lat,$lon];
    }

// gets weather data using coordinates
function getWeather($lat,$lon){
    $apiKey = config('services.openweather.key');

    if (!$lat || !$lon) {
        return [
            'error' => true,
            'message' => 'Invalid coordinates'
        ];
    }
    $weatherResponse = Http::get('https://api.openweathermap.org/data/3.0/onecall', [
        'lat' => $lat,
        'lon' => $lon,
        'appid' => $apiKey,
        'units' => 'metric',
    ]);
    //error handling for weather call
    if (!$weatherResponse->successful()) {
        return response()->json([
            'error' => 'Failed to fetch weather data',
            'body' => $weatherResponse->body(),
            'status' =>$weatherResponse->status(),
        ], $weatherResponse->status());
    }
    $data = $weatherResponse->json();
    return $data;
}
//selects the relevant data and arranges it
function processData( $data,string $city):array{
    $current = $data['current'];
    $daily = array_slice($data['daily'], 1, 3); // next 3 days 
    $tempC = round($current['temp']);

   return [
    'location' => $city,
    'current' => [
        'date' => date('Y-m-d', $current['dt']),
        'temperature' => [
            'celsius' => $tempC = round($current['temp']),
            'fahrenheit' => round(celsiusToFahrenheit($tempC))
        ],
        'description' => $current['weather'][0]['description'] ?? '',
        'icon' => $current['weather'][0]['icon'] ?? '',
        'humidity' => $current['humidity'],
        'wind_speed' => $current['wind_speed'],
    ],
    'forecast' => array_map(function ($day) {
        $minC = round($day['temp']['min']);
        $maxC = round($day['temp']['max']);
        return [
            'date' => date('Y-m-d', $day['dt']),
            'temp_min' => [
                'celsius' => $minC,
                'fahrenheit' => round(celsiusToFahrenheit($minC))
            ],
            'temp_max' => [
                'celsius' => $maxC,
                'fahrenheit' => round(celsiusToFahrenheit($maxC))
            ],
            'description' => $day['weather'][0]['description'] ?? '',
            'icon' => $day['weather'][0]['icon'] ?? '',
        ];
    }, array_slice($daily, 1, 3)), 
];
}

Route::get('/weather', function () {
    //extracting current latitude and longitude
    $lat = request()->query('lat');
    $lon = request()->query('lon');

    //default city is Nairobi if no city parameters are sent
    $city = Str::title(request()->query('city', 'Nairobi'));

    //getting coordinates for city if there are no coordinates
    if (!$lat || !$lon) {
        $coordinates=getCoordinates($city);
        
        if (!empty( $coordinates['error'])) {
            return response()->json([
                'error' =>  $coordinates['message'] ?? 'Unknown error',
                'details' =>  $coordinates['body'] ?? null,
            ],  $coordinates['status'] ?? 500);
        }
        
        [$lat, $lon] = $coordinates;
        
    }
    //getting weather data for coordinates
    $data=getWeather($lat,$lon);

    if (!empty( $data['error'])) {
        return response()->json([
            'error' =>  $data['message'] ?? 'Unknown error',
            'details' =>  $data['body'] ?? null,
        ],  $data['status'] ?? 500);
    }

    //select and arrange relevant data 
    $result=processData($data,$city);

    return $result;    
});
