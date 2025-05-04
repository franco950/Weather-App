type FetchParams = {
  city?: string;
  lat?: number;
  lon?: number;
};

export default async function fetchWeather(
  { city, lat, lon }: FetchParams,
  setData: Function,
  setLoading: Function,
  setError: Function
) {
  try {
    setLoading(true);
    setError("");

    const query = city
      ? `city=${encodeURIComponent(city)}`
      : `lat=${lat}&lon=${lon}`;

    const res = await fetch(`http://127.0.0.1:8000/api/weather?${query}`);
    if (!res.ok) {
      console.log(res)
      throw new Error("Failed to fetch weather data.");
    }
    const weather = await res.json();
    setData(weather);
  } catch (err: any) {
    setError(err.message || "An error occurred.");
    setData(null);
  } finally {
    setLoading(false);
  }
}
