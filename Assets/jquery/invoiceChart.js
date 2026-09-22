$(document).ready(function(){

var bar_chart;

//=============================== Today Sale ============================//
$("#btn_open_todaysale").click(function(){
    $("#today_sale_modal").modal('toggle');

    //sale bar chart
    SalesBarChart();
});

$("#btn_refresh_bar").click(function(){

    //sale bar chart
    SalesBarChart();
});//refresh bar chart

//=============================== Function ===============================//
function SalesBarChart()
{
    $.get("../AJAX/Charts/getTodaySale.php", function(data){
        // alert(data);
        // console.log(data);
        var time = [];
        var sale = [];

        const Obj = JSON.parse(data);

        for(var i=0; i<Obj.length; i++)
        {
            time[i] = Obj[i].time;
            sale[i] = parseFloat(Obj[i].sale);
        }//assign values to arrays

        var sale_chart = {
            chart: {
                type: "bar",
                height: 220,
                offsetX: -15,
                toolbar: { show: true },
                foreColor: "#adb0bb",
                fontFamily: 'inherit',
                sparkline: { enabled: false },
                },
            
            series: [
                {name: "Gross Sale:", data:sale},
            ],
        
            xaxis:{
                type: "category",
                categories: time,
            },
        
            plotOptions: {
                bar: {
                  horizontal: false,
                  columnWidth: "35%",
                  borderRadius: [6],
                  borderRadiusApplication: 'end',
                  borderRadiusWhenStacked: 'all'
                },
              },
    
              markers: { size: 0 },
        
              grid: {
                borderColor: "rgba(0,0,0,0.1)",
                strokeDashArray: 3,
                xaxis: {
                  lines: {
                    show: false,
                  },
                },
              },
        
              dataLabels: {
                enabled: false,
              },
          
          
              legend: {
                show: false,
              },
    
              stroke: {
                show: true,
                width: 3,
                lineCap: "butt",
                colors: ["transparent"],
              },
    
              responsive: [
                {
                  breakpoint: 600,
                  options: {
                    plotOptions: {
                      bar: {
                        borderRadius: 3,
                      }
                    },
                  }
                }
              ]
    
            };//barchart

        if(bar_chart)
        {
            bar_chart.destroy();
        }

        bar_chart = new ApexCharts(document.querySelector("#line_chart"), sale_chart);
        bar_chart.render();
    });
}//sale bar chart

});//invoice chart js