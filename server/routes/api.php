<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
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
        //check for no result, empty response and incomplete city match
        if (empty($geoData) || empty($geoData[0])|| strcasecmp($geoData[0]['name'], $city) !== 0) {
            return ['error' => true, 
                    'message' => 'No location found for the given input'];
        }

        $lat = $geoData[0]['lat'];
        $lon = $geoData[0]['lon'];
  
        return [$lat,$lon];
    }
//get city name using the coordinates
function getCityFromCoordinates(float $lat, float $lon): ?string {
    $apiKey = env('OPENWEATHER_API_KEY');

    $response = Http::get('http://api.openweathermap.org/geo/1.0/reverse', [
        'lat' => $lat,
        'lon' => $lon,
        'limit' => 1,
        'appid' => $apiKey,
    ]);

    if (!$response->successful()) {
        return null;
    }

    $data = $response->json();

    if (!empty($data[0]['name'])) {
        return $data[0]['name']; 
    }

    return null;
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
//stores a cache for each city for 10 minutes ...since the data only changes every 10 minutes
function getCachedWeather($lat,$lon, string $city, int $minutes = 10) {

    $cacheKey = 'weather_' . md5($city . implode(',', [$lat,$lon]));
    return Cache::remember($cacheKey, now()->addMinutes($minutes), function () use ($lat,$lon) {
        return getWeather($lat,$lon);
    });
}

Route::get('/weather', function () {
    //extracting current latitude and longitude
    $lat = request()->query('lat');
    $lon = request()->query('lon');


    //default city is Nairobi if unable to get city from params or coordinates
    $city = Str::title(request()->query('city'));
    if (!$city && $lat && $lon){
        $city=getCityFromCoordinates($lat,$lon);
    }
    
    //getting coordinates for city if there are no coordinates, default city is Nairobi
    $city = $city ?: 'Nairobi';
    if ($city&& (!$lat || !$lon)) {
        $coordinates=getCoordinates($city);
        
        if (!empty( $coordinates['error'])) {
            return response()->json([
                'error' =>  $coordinates['message'] ?? 'Unknown error',
                'details' =>  $coordinates['body'] ?? null,
            ],  $coordinates['status'] ?? 500);
        }
        
        [$lat, $lon] = $coordinates;
        
    }
    
    //getting weather data for coordinates and caching it
    $data = getCachedWeather($lat,$lon,$city);

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
