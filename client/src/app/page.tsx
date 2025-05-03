'use client'
import Image from "next/image";
import { useEffect,useState } from "react";
import { useQuery } from "@tanstack/react-query";
import fetchWeather from "./lib/api";
import  Input  from 'rippleui';

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

  const [city, setCity] = useState('Nairobi');
  const [data, setData] = useState<WeatherData | null>(null);

  const [unit, setUnit] = useState('celsius');
  const [loading, setLoading] = useState(false);
  


  useEffect(() => {
    fetchWeather(city,setData,setLoading);
  }, []);

  const handleSearch = () => {
    fetchWeather(city,setData,setLoading);
  };

  const toggleUnit = () => {
    setUnit(unit === 'celsius' ? 'fahrenheit' : 'celsius');
  };

  const getTemp = (tempObj:any) => `${tempObj[unit]} °${unit === 'celsius' ? 'C' : 'F'}`;

  return (
    <div className="p-4 max-w-5xl mx-auto text-gray-800">
      <div className="flex items-center gap-2 mb-4">
        <input
          placeholder="Search city..."
          value={city}
          onChange={(e) => setCity(e.target.value)}
          className="flex-grow"
        />
        <button onClick={handleSearch} className="btn btn-primary">Go</button>
        <button onClick={toggleUnit} className="btn btn-outline">°C / °F</button>
      </div>

      {loading && <p>Loading...</p>}

      {data && (
        <div>
          <div className="flex items-center gap-4 mb-4">
            <img src={`https://openweathermap.org/img/wn/${data.current.icon}@2x.png`} alt="icon" className="w-16 h-16" />
            <div>
              <h2 className="text-3xl font-bold">{getTemp(data.current.temperature)}</h2>
              <p className="capitalize">{data.current.description}</p>
              <p className="text-sm text-gray-500">{new Date(data.current.date).toDateString()} — {data.location}</p>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-4 mb-4">
            {data.forecast.map((day, index) => (
              <div key={index} className="card p-4 bg-white shadow rounded-xl">
                <h3 className="font-bold">{new Date(day.date).toDateString()}</h3>
                <img src={`https://openweathermap.org/img/wn/${day.icon}@2x.png`} alt="forecast icon" className="w-12 h-12 mx-auto" />
                <p>{getTemp(day.temp_min)} - {getTemp(day.temp_max)}</p>
                <p className="capitalize text-sm">{day.description}</p>
              </div>
            ))}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="card p-4 bg-white shadow rounded-xl">
              <h4 className="text-gray-500 text-sm">Wind Status</h4>
              <p className="text-xl font-bold">{data.current.wind_speed} km/h</p>
            </div>
            <div className="card p-4 bg-white shadow rounded-xl">
              <h4 className="text-gray-500 text-sm">Humidity</h4>
              <p className="text-xl font-bold">{data.current.humidity}%</p>
              <progress className="progress progress-info w-full" value={data.current.humidity} max="100"></progress>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
