import './App.css';
import Select from './Select';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { useEffect, useState } from 'react';

function App(props) {
  const [title, setTitle] = useState([]);
  const fetchData = (fordays) => {
    return fetch(props.endpont+'/'+fordays,{ 
        headers: {
          'content-type': 'application/json',
          'X-WP-Nonce': props.nonce
        }
    })
    .then((response) => response.json())
    .then((data) => setTitle(data.data));
  }

  useEffect(() => {
    fetchData('last_7_day');
  },[])

  function onChange(event){
    fetchData(event.target.value);
  }

  return (
    <div className="App">
      <Select onChange={onChange} />
      <div className='chart-container'>
        <ResponsiveContainer width="100%" height="100%">
          <LineChart
            width={430}
            height={300}
            data={title}
            margin={{
              top: 5,
              right: 30,
              left: 0,
              bottom: 5,
            }}
          >
            <XAxis dataKey="name" />
            <YAxis/>
            <Tooltip />
            <Legend />
            <Line type="monotone" dataKey="line" strokeWidth='2' stroke="#8884d8" activeDot={{ r: 8 }} />
            <Line type="monotone" dataKey="line_two" stroke="#82ca9d" />
          </LineChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}

export default App;
