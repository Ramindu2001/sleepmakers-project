$(document).ready(function(){
//initialize
// time duration
var duration_value = 1;
var chart;
var donut_chart;
var line_chart;
$("#btn_dur_day").css('background-color', 'dodgerblue');
$("#btn_dur_day").css('color', 'white');

//load bar chart
ChartSelector(duration_value);

//load revenue donut
paymethodDonut();

//load purchase Line
PurchaseLine();

// time duration
$("#btn_dur_day").click(function(){
    $(this).css('background-color', 'dodgerblue');
    $(this).css('color', 'white');

    $("#btn_dur_week").css('background-color', 'white');
    $("#btn_dur_week").css('color', 'black');

    $("#btn_dur_month").css('background-color', 'white');
    $("#btn_dur_month").css('color', 'black');

    duration_value = 1;
    ChartSelector(duration_value);
});

$("#btn_dur_week").click(function(){
    $(this).css('background-color', 'dodgerblue');
    $(this).css('color', 'white');

    $("#btn_dur_day").css('background-color', 'white');
    $("#btn_dur_day").css('color', 'black');

    $("#btn_dur_month").css('background-color', 'white');
    $("#btn_dur_month").css('color', 'black');

    duration_value = 2;
    ChartSelector(duration_value);
});

$("#btn_dur_month").click(function(){
    $(this).css('background-color', 'dodgerblue');
    $(this).css('color', 'white');

    $("#btn_dur_day").css('background-color', 'white');
    $("#btn_dur_day").css('color', 'black');

    $("#btn_dur_week").css('background-color', 'white');
    $("#btn_dur_week").css('color', 'black');

    duration_value = 1;
    ChartSelector(duration_value);
});

//cmb changed
$("#cmb_chart_type").change(function(){
    ChartSelector(duration_value);
});

//Donut refresh
$("#btn_refresh_donut").click(function(){
    paymethodDonut();
});//refresh donut

//line chart refresh
$("#btn_refresh_line").click(function(){
    PurchaseLine();
});//refresh line chart

//============================= Functions =============================//
function ChartSelector(duration_value)
{
    var chart_type = $("#cmb_chart_type").val();
     switch (chart_type) {
        case '0':
            grossSaleChart(duration_value);  
        break;
        case '1':
            numberSaleChart(duration_value);  
        break;
     }//switch

}//chart selector

function grossSaleChart(duration_value)
{
    var date_range = 1;
    switch (duration_value) {
        case 1:
            date_range = 7;    
        break;
        case 2:
            date_range = 14;    
        break;
        case 3:
            date_range = 30;
        break;
    }
    //initial autoload
    $.get("../AJAX/Charts/getGrossSale.php", {
        date_range: date_range
        }, function(data){
        var grossSale = [];
        var calDate = [];
    
        const Obj = JSON.parse(data);
    
        for(var i=0; i<Obj.length; i++)
        {
            calDate[i] = Obj[i].EffectiveDate;
            grossSale[i] = Obj[i].GrossSale;
        }//assign values to arrays
    
        var bar_chart = {
        chart: {
            type: "bar",
            height: 345,
            offsetX: -15,
            toolbar: { show: true },
            foreColor: "#adb0bb",
            fontFamily: 'inherit',
            sparkline: { enabled: false },
            },
        
        series: [
            {name: "Gross Sale:", data:grossSale},
        ],
    
        xaxis:{
            type: "category",
            categories: calDate,
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

        if(chart)
            {
                chart.destroy();
            }
        
        chart = new ApexCharts(document.querySelector("#bar_chart"), bar_chart);
        chart.render();
        
    });//get gross sales
}//gross sale chart

function numberSaleChart(duration_value)
{
    var date_range = 7;
    switch (duration_value) {
        case 1:
            date_range = 7;    
        break;
        case 2:
            date_range = 14;    
        break;
        case 3:
            date_range = 30;    
        break;
    }
    //initial autoload
    $.get("../AJAX/Charts/getNumberSale.php", {
        date_range: date_range
        }, function(data){
        var numberSale = [];
        var calDate = [];
    
        const Obj = JSON.parse(data);
    
        for(var i=0; i<Obj.length; i++)
        {
            calDate[i] = Obj[i].EffectiveDate;
            numberSale[i] = Obj[i].numberSale;
        }//assign values to arrays
    
        var bar_chart = {
        chart: {
            type: "bar",
            height: 345,
            offsetX: -15,
            toolbar: { show: true },
            foreColor: "#adb0bb",
            fontFamily: 'inherit',
            sparkline: { enabled: false },
            },
        
        series: [
            {name: "Bill Count:", data:numberSale},
        ],
    
        xaxis:{
            type: "category",
            categories: calDate,
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
        
        };//barchart

        if(chart)
        {
            chart.destroy();
        }
        
        chart = new ApexCharts(document.querySelector("#bar_chart"), bar_chart);
        chart.render();
        
    });//get gross sales
}//number sale 

function fastMovingItems(duration_value)
{
    var date_range = 7;
    switch (duration_value) {
        case '1':
            date_range = 7;    
        break;
        case '2':
            date_range = 14;    
        break;
        case '3':
            date_range = 30;    
        break;
    }//switch

    $.get("../AJAX/Charts/getFastMoving.php", {
        date_range: date_range
    }, function(data){
        var soldQty = [];
        var itemName = [];

        const Obj = JSON.parse(data);
    
        for(var i=0; i<Obj.length; i++)
        {
            soldQty[i] = Obj[i].TotalSaleQty;
            itemName[i] = Obj[i].ItemName;
        }//assign values to arrays

        var bar_chart = {
            chart: {
                type: "bar",
                height: 345,
                offsetX: -15,
                toolbar: { show: true },
                foreColor: "#adb0bb",
                fontFamily: 'inherit',
                sparkline: { enabled: false },
                },
            
            series: [
                {name: "Sold Qty", data:soldQty},
            ],
        
            xaxis:{
                type: "category",
                categories: itemName,
            },
        
            plotOptions: {
                bar: {
                  horizontal: false,
                  columnWidth: "25%",
                  borderRadius: [6],
                  borderRadiusApplication: 'end',
                  borderRadiusWhenStacked: 'all'
                },
              },
        
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
            
            };//barchart
    
            if(chart)
            {
                chart.destroy();
            }
            
            chart = new ApexCharts(document.querySelector("#bar_chart"), bar_chart);
            chart.render();
    });//get fast items
}//fast movie items

function paymethodDonut()
{
    $.get("../AJAX/Charts/getTransaction.php", function(data){
        var paymethods = [];
        var transferAmount = [];
        var colorCode = [];

        const Obj = JSON.parse(data);

        for(var i=0; i<Obj.length; i++)
        {
            paymethods[i] = Obj[i].paymethod;
            transferAmount[i] = parseFloat(Obj[i].transfer_amount);
            colorCode[i] = Obj[i].paymethod_color;
        }//assign values to arrays

        var revenue_chart = {
            series: transferAmount,
            chart:{
                type: 'donut',
            },
            plotOptions:{
                pie:{
                    donut:{
                        size: "75%",
                        labels:{
                            show:false,
                            total:{
                                show: true,
                                showAlways: true
                            }
                        }
                    }
                }
            },// plotOptions
            //label
            labels: paymethods,
            //color
            fill:{colors: colorCode},
            //legend
            legend:{show: false },
            //data label
            dataLabels: { enabled: false },
        };//revenue chart

        if(donut_chart)
        {
            donut_chart.destroy();
        }
        
        donut_chart = new ApexCharts(document.querySelector("#donut_chart"), revenue_chart);
        donut_chart.render();

    });
}//paymethod donut

function PurchaseLine()
{
    $.get("../AJAX/Charts/getPurchase.php", function(data){
        //alert(data);
        var calDays = [];
        var purchaseAmount = [];

        const Obj = JSON.parse(data);

        for(var i=0; i<Obj.length; i++)
        {
            calDays[i] = Obj[i].EffectiveDate;
            purchaseAmount[i] = parseFloat(Obj[i].TotalPurchasePrice);
        }//assign values to arrays

        var purchase_chart = {
            chart: {
                id: "sparkline3",
                type: "area",
                height: 80,
                sparkline: {
                  enabled: true,
                },
                group: "sparklines",
                fontFamily: "Plus Jakarta Sans', sans-serif",
                foreColor: "#adb0bb",
              },
            series: [
                {
                    name: "Purchase",
                    color: "#49BEFF",
                    data: purchaseAmount,
                },
            ],
            stroke: {
                curve: "smooth",
                width: 2,
            },
            fill: {
                colors: ["#f3feff"],
                type: "solid",
                opacity: 0.05,
            },
            markers: {
                size: 0,
            },
            tooltip: {
                theme: "dark",
                fixed: {
                  enabled: true,
                  position: "right",
                },
                x: {
                  show: false,
                },
              },
        };//purchase chart

        if(line_chart)
        {
            line_chart.destroy();
        }
        
        line_chart = new ApexCharts(document.querySelector("#line_chart"), purchase_chart);
        line_chart.render();
    });//get purchase
}//purchase line

});//jquery dashboard