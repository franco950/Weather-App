'use client';
import { useEffect, useState } from "react";
import fetchWeather from "./lib/api";
import "./globals.css";

type WeatherData = {
  location: string;
  current: {
    date: string;
    temperature: {
      celsius: number;
      fahrenheit: number;
    };
    description: string;
    icon: string;
    humidity: number;
    wind_speed: number;
  };
  forecast: {
    date: string;
    temp_min: {
      celsius: number;
      fahrenheit: number;
    };
    temp_max: {
      celsius: number;
      fahrenheit: number;
    };
    description: string;
    icon: string;
  }[];
};

export default function WeatherApp() {
  const [city, setCity] = useState("Nairobi");
  const [data, setData] = useState<WeatherData | null>(null);
  const [unit, setUnit] = useState<"celsius" | "fahrenheit">("celsius");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const fetchByCity = () => {
    fetchWeather({ city }, setData, setLoading, setError);
  };

  const fetchByCoords = (lat: number, lon: number) => {
    fetchWeather({ lat, lon }, setData, setLoading, setError);
  };

  const handleGeolocation = () => {
    if (!navigator.geolocation) {
      setError("Geolocation is not supported by your browser.");
      return;
    }
    setError("");
    setLoading(true);
    navigator.geolocation.getCurrentPosition(
      (position) => {
        fetchByCoords(position.coords.latitude, position.coords.longitude);
      },
      () => {
        setError("Unable to retrieve location. Please allow access.");
        setLoading(false);
      }
    );
  };

  const toggleUnit = () => {
    setUnit(unit === "celsius" ? "fahrenheit" : "celsius");
  };

  const getTemp = (tempObj: any) =>
    `${tempObj[unit]} °${unit === "celsius" ? "C" : "F"}`;

  useEffect(() => {
    fetchByCity();
  }, []);

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-100 to-indigo-100 p-6">
    <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl p-6">
      <h1 className="text-2xl sm:text-3xl font-bold text-center mb-6 text-gray-700">
        Weather Forecast
      </h1> <div className="flex flex-col sm:flex-row items-center gap-3 mb-6">
          <input
            placeholder="Search city..."
            value={city}
            onChange={(e) => setCity(e.target.value)}
            className="w-full sm:w-auto flex-grow border border-gray-300 rounded-xl px-4 py-2 text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
          />
    <button
            onClick={fetchByCity}
            className="px-4 py-2 text-sm font-medium border-2 border-sky-500 bg-sky-100 text-sky-800 rounded-xl shadow-md hover:bg-sky-200 hover:scale-105 transition"
          >
            Go
          </button>
          <button
            onClick={toggleUnit}
            className="px-4 py-2 text-sm font-medium border-2 border-amber-500 bg-amber-100 text-amber-800 rounded-xl shadow-md hover:bg-amber-200 hover:scale-105 transition"
          >
            °C / °F
          </button>
          <button
            onClick={handleGeolocation}
            className="px-4 py-2 text-sm font-medium border-2 border-gray-400 bg-gray-100 text-gray-800 rounded-xl shadow-md hover:bg-gray-200 hover:scale-105 transition flex items-center"
          >
            Use My Location
            </button>
      </div>

      
      {loading && (
        <div className="flex justify-center items-center my-4">
          <div className="loading loading-spinner loading-lg text-primary" />
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div className="alert alert-error mb-4">
          <span>{error}</span>
        </div>
      )}

      {loading&&<p>loading...</p>}
      {data && !loading && (
        <>
          {/* Current Weather */}
          <div className="flex items-center gap-4 mb-6">
            <img
              src={`https://openweathermap.org/img/wn/${data.current.icon}@2x.png`}
              alt="icon"
              className="w-16 h-16"
            />
            <div>
              <h2 className="text-3xl font-bold">
                {getTemp(data.current.temperature)}
              </h2>
              <p className="capitalize">{data.current.description}</p>
              <p className="text-sm text-gray-500">
                {new Date(data.current.date).toDateString()} — {data.location}
              </p>
            </div>
          </div>

          {/* Forecast */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            {data.forecast.map((day, index) => (
              <div key={index} className="card bg-base-100 shadow-md p-4">
                <h3 className="font-semibold text-center text-sm mb-2">
                  {new Date(day.date).toDateString()}
                </h3>
                <img
                  src={`https://openweathermap.org/img/wn/${day.icon}@2x.png`}
                  alt="forecast icon"
                  className="w-12 h-12 mx-auto"
                />
                <p className="text-center text-sm">
                  {getTemp(day.temp_min)} - {getTemp(day.temp_max)}
                </p>
                <p className="text-center capitalize text-xs mt-1">
                  {day.description}
                </p>
              </div>
            ))}
          </div>

          {/* Additional Info */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="card bg-base-100 shadow-md p-4">
              <h4 className="text-sm text-gray-500">Wind Status</h4>
              <p className="text-xl font-bold">
                {data.current.wind_speed} km/h
              </p>
            </div>
            <div className="card bg-base-100 shadow-md p-4">
              <h4 className="text-sm text-gray-500">Humidity</h4>

              <p className="text-xl font-bold">{data.current.humidity}%</p>
              <progress
                className="progress progress-info w-full mt-2"
                value={data.current.humidity}
                max="100"
              ></progress>
            </div>
          </div>
        </>
      )}
    </div>
    </div>);
}
