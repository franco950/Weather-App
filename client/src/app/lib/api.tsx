const API_BASE_URL = 'http://127.0.0.1:8000/api/weather?city='; 
const fetchWeather = async (queryParam:string,setData:any,setLoading:any) => {
  try {
    setLoading(true);
    const res = await fetch(`${API_BASE_URL}${queryParam}`);
    if (!res.ok) throw new Error(`${res}`);
    const result = await res.json();
    setData(result);
  } catch (err) {
    console.error('Error fetching weather:', err);
  } finally {
    setLoading(false);
  }
};
export default fetchWeather