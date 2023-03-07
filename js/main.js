//Set Default data in the widget
jQuery(document).ready(function(){
  filter_data('last_7_days');
});

// Get data from API with Selected Filter
function filter_data(val){
  jQuery.ajax({
    type: "get",
    dataType: "json",
    url: graph_api_end_point .resturl+'/'+val,
    success: function(data){
      render_data(data.data);
    },
    error: function (error) {
      render_data([]);
    }
  });
}

// ReactJS render data to graph
function render_data(data){
  const renderLineChart = /*#__PURE__*/ React.createElement(
    Recharts.LineChart,
    
    {
      width: 430,
      height: 300,
      data: data,
      margin:{ top: 5, right: 20, bottom: 5, left: 0  },
      
    },
    React.createElement(Recharts.XAxis , {
      dataKey: 'name',
    }),
    React.createElement(Recharts.YAxis ),
    React.createElement(Recharts.Tooltip ),
    React.createElement(Recharts.Line, {
      XAxis:{dataKey:'name'},
      type: 'monotone',
      dataKey: 'line',
      stroke: '#8884d8',
      strokeWidth:2
    }),
    React.createElement(Recharts.Line, {
      XAxis:{dataKey:'name'},
      type: 'monotone',
      dataKey: 'line_two',
      stroke: '#82ca9d',
      strokeWidth:2
    }),
  );
  ReactDOM.render(renderLineChart, document.getElementById('dashboard_chart'));
}