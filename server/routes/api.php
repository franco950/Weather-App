<?php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/weather', function () {
    //default city is nairobi if no parameters are sent
    $city = request()->query('city', 'Nairobi');
    $apiKey = env('OPENWEATHER_API_KEY');

    // getting coordinates for the city
    $geoResponse = Http::get('http://api.openweathermap.org/geo/1.0/direct', [
        'q' => $city,
        'limit' => 1,
        'appid' => $apiKey,
    ]);

    if (!$geoResponse->successful() || empty($geoResponse[0])) {
        return response()->json([
            'error' => 'Failed to fetch location data',
            'body' => $geoResponse->body(),
            'status' =>$geoResponse->status(),
        ], $geoResponse->status());
    }

    $lat = $geoResponse[0]['lat'];
    $lon = $geoResponse[0]['lon'];

    // getting weather using coordinates
    $weatherResponse = Http::get('https://api.openweathermap.org/data/2.5/weather', [
        'lat' => $lat,
        'lon' => $lon,
        'appid' => $apiKey,
        'units' => 'metric',
    ]);

    if ($weatherResponse->successful()) {
        return $weatherResponse->json();
    }

    return response()->json([
        'error' => 'Failed to fetch weather data',
        'body' => $weatherResponse->body(),
        'status' =>$weatherResponse->status(),
    ], $weatherResponse->status());
});
