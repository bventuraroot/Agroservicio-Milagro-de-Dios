/**
 * Dashboard CRM
 */

'use strict';
(function () {
  let cardColor, labelColor, shadeColor, legendColor, borderColor;
  if (isDarkStyle) {
    cardColor = config.colors_dark.cardColor;
    labelColor = config.colors_dark.textMuted;
    legendColor = config.colors_dark.bodyColor;
    borderColor = config.colors_dark.borderColor;
    shadeColor = 'dark';
  } else {
    cardColor = config.colors.cardColor;
    labelColor = config.colors.textMuted;
    legendColor = config.colors.bodyColor;
    borderColor = config.colors.borderColor;
    shadeColor = '';
  }

  // Sales last year Area Chart
  // --------------------------------------------------------------------
  const salesLastYearEl = document.querySelector('#salesLastYear');
  if (salesLastYearEl) {
    const salesLastYearConfig = {
      chart: {
        height: 78,
        type: 'area',
        parentHeightOffset: 0,
        toolbar: { show: false },
        sparkline: { enabled: true }
      },
      markers: { colors: 'transparent', strokeColors: 'transparent' },
      grid: { show: false },
      colors: ['#28c76f'],
      fill: {
        type: 'gradient',
        gradient: { shade: 'light', shadeIntensity: 0.8, opacityFrom: 0.6, opacityTo: 0.25 }
      },
      dataLabels: { enabled: false },
      stroke: { width: 2, curve: 'smooth' },
      series: [{ data: window.ventasUltimoAno || [] }],
      xaxis: { show: true, lines: { show: false }, labels: { show: false }, stroke: { width: 0 }, axisBorder: { show: false } },
      yaxis: { stroke: { width: 0 }, show: false },
      tooltip: { enabled: false }
    };
    new ApexCharts(salesLastYearEl, salesLastYearConfig).render();
  }

  // Sessions Last Month - Staked Bar Chart
  // --------------------------------------------------------------------
  const sessionsLastMonthEl = document.querySelector('#sessionsLastMonth');
  if (sessionsLastMonthEl) {
    const ventasUltimoMes = window.ventasUltimoMes || [];
    const sessionsLastMonthConfig = {
      chart: {
        type: 'bar',
        height: 78,
        parentHeightOffset: 0,
        stacked: false,
        toolbar: { show: false }
      },
      series: [{ name: 'Ventas', data: ventasUltimoMes }],
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '30%',
          barHeight: '100%',
          borderRadius: 5,
          startingShape: 'rounded',
          endingShape: 'rounded'
        }
      },
      dataLabels: { enabled: false },
      tooltip: { enabled: false },
      stroke: { curve: 'smooth', width: 1, lineCap: 'round', colors: ['#fff'] },
      legend: { show: false },
      colors: ['#696cff'],
      grid: { show: false, padding: { top: -41, right: -10, left: -8, bottom: -22 } },
      xaxis: {
        categories: Array.from({length: ventasUltimoMes.length}, (_, i) => (i+1).toString()),
        labels: { show: false },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: { show: false }
    };
    new ApexCharts(sessionsLastMonthEl, sessionsLastMonthConfig).render();
  }

  // Revenue Growth Chart
  // --------------------------------------------------------------------
  const revenueGrowthEl = document.querySelector('#revenueGrowth');
  if (revenueGrowthEl) {
    const revenueGrowthConfig = {
      chart: {
        height: 80,
        type: 'line',
        parentHeightOffset: 0,
        toolbar: { show: false },
        sparkline: { enabled: true }
      },
      series: [{ data: window.ventasUltimaSemana || [] }],
      stroke: { width: 2, curve: 'smooth' },
      colors: ['#28c76f'],
      grid: { show: false, padding: { top: -10, right: -10, left: -10, bottom: -10 } },
      tooltip: { enabled: false },
      xaxis: { show: false, lines: { show: false } },
      yaxis: { show: false }
    };
    new ApexCharts(revenueGrowthEl, revenueGrowthConfig).render();
  }

  // Earning Reports Tabs Function
  function EarningReportsBarChart(arrayData, highlightData) {
    const basicColor = config.colors_label.primary,
      highlightColor = config.colors.primary;
    var colorArr = [];

    for (let i = 0; i < arrayData.length; i++) {
      if (i === highlightData) {
        colorArr.push(highlightColor);
      } else {
        colorArr.push(basicColor);
      }
    }

    const earningReportBarChartOpt = {
      chart: {
        height: 258,
        parentHeightOffset: 0,
        type: 'bar',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          columnWidth: '32%',
          startingShape: 'rounded',
          borderRadius: 7,
          distributed: true,
          dataLabels: {
            position: 'top'
          }
        }
      },
      grid: {
        show: false,
        padding: {
          top: 0,
          bottom: 0,
          left: -10,
          right: -10
        }
      },
      colors: colorArr,
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return val + 'k';
        },
        offsetY: -25,
        style: {
          fontSize: '15px',
          colors: [legendColor],
          fontWeight: '600',
          fontFamily: 'Public Sans'
        }
      },
      series: [
        {
          data: arrayData
        }
      ],
      legend: {
        show: false
      },
      tooltip: {
        enabled: false
      },
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
        axisBorder: {
          show: true,
          color: borderColor
        },
        axisTicks: {
          show: false
        },
        labels: {
          style: {
            colors: labelColor,
            fontSize: '13px',
            fontFamily: 'Public Sans'
          }
        }
      },
      yaxis: {
        labels: {
          offsetX: -15,
          formatter: function (val) {
            return '$' + parseInt(val / 1) + 'k';
          },
          style: {
            fontSize: '13px',
            colors: labelColor,
            fontFamily: 'Public Sans'
          },
          min: 0,
          max: 60000,
          tickAmount: 6
        }
      },
      responsive: [
        {
          breakpoint: 1441,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '41%'
              }
            }
          }
        },
        {
          breakpoint: 590,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '61%',
                borderRadius: 5
              }
            },
            yaxis: {
              labels: {
                show: false
              }
            },
            grid: {
              padding: {
                right: 0,
                left: -20
              }
            },
            dataLabels: {
              style: {
                fontSize: '12px',
                fontWeight: '400'
              }
            }
          }
        }
      ]
    };
    return earningReportBarChartOpt;
  }
  var chartJson = 'earning-reports-charts.json';
  // Earning Chart JSON data
  var earningReportsChart = $.ajax({
    url: assetsPath + 'json/' + chartJson, //? Use your own search api instead
    dataType: 'json',
    async: false
  }).responseJSON;

  // Earning Reports Tabs Orders
  // --------------------------------------------------------------------
  const earningReportsTabsOrdersEl = document.querySelector('#earningReportsTabsOrders');
  if (earningReportsTabsOrdersEl) {
    const meses = Object.keys(window.ventasPorMes || {});
    const datos = Object.values(window.ventasPorMes || {});
    const earningReportsOrdersConfig = {
      chart: { height: 200, type: 'bar', parentHeightOffset: 0, toolbar: { show: false } },
      series: [{ name: 'Ventas', data: datos }],
      xaxis: { categories: meses, labels: { style: { colors: '#a1acb8' } } },
      yaxis: { labels: { style: { colors: '#a1acb8' } } },
      colors: ['#696cff'],
      plotOptions: { bar: { borderRadius: 8, columnWidth: '60%' } },
      grid: { borderColor: '#a1acb8', strokeDashArray: 6 },
      legend: { show: false }
    };
    new ApexCharts(earningReportsTabsOrdersEl, earningReportsOrdersConfig).render();
  }
  // Earning Reports Tabs Sales
  // --------------------------------------------------------------------
  const earningReportsTabsSalesEl = document.querySelector('#earningReportsTabsSales');
  if (earningReportsTabsSalesEl) {
    const meses = Object.keys(window.ventasPorMes || {});
    const datos = Object.values(window.ventasPorMes || {});
    const earningReportsSalesConfig = {
      chart: { height: 200, type: 'bar', parentHeightOffset: 0, toolbar: { show: false } },
      series: [{ name: 'Ventas', data: datos }],
      xaxis: { categories: meses, labels: { style: { colors: '#a1acb8' } } },
      yaxis: { labels: { style: { colors: '#a1acb8' } } },
      colors: ['#28c76f'],
      plotOptions: { bar: { borderRadius: 8, columnWidth: '60%' } },
      grid: { borderColor: '#a1acb8', strokeDashArray: 6 },
      legend: { show: false }
    };
    new ApexCharts(earningReportsTabsSalesEl, earningReportsSalesConfig).render();
  }
  // Earning Reports Tabs Profit
  // --------------------------------------------------------------------
  const earningReportsTabsProfitEl = document.querySelector('#earningReportsTabsProfit');
  if (earningReportsTabsProfitEl) {
    const meses = Object.keys(window.ventasPorMes || {});
    const datos = Object.values(window.ventasPorMes || {}).map(val => val * 0.3);
    const earningReportsProfitConfig = {
      chart: { height: 200, type: 'area', parentHeightOffset: 0, toolbar: { show: false } },
      series: [{ name: 'Beneficios', data: datos }],
      xaxis: { categories: meses, labels: { style: { colors: '#a1acb8' } } },
      yaxis: { labels: { style: { colors: '#a1acb8' } } },
      colors: ['#ff6b6b'],
      fill: { type: 'gradient', gradient: { shade: 'light', shadeIntensity: 0.8, opacityFrom: 0.6, opacityTo: 0.25 } },
      stroke: { curve: 'smooth', width: 2 },
      grid: { borderColor: '#a1acb8', strokeDashArray: 6 },
      legend: { show: false }
    };
    new ApexCharts(earningReportsTabsProfitEl, earningReportsProfitConfig).render();
  }
  // Earning Reports Tabs Income
  // --------------------------------------------------------------------
  const earningReportsTabsIncomeEl = document.querySelector('#earningReportsTabsIncome');
  if (earningReportsTabsIncomeEl) {
    const meses = Object.keys(window.ventasPorMes || {});
    const datos = Object.values(window.ventasPorMes || {});
    const earningReportsIncomeConfig = {
      chart: { height: 200, type: 'donut', parentHeightOffset: 0, toolbar: { show: false } },
      series: datos,
      labels: meses,
      colors: ['#696cff', '#28c76f', '#ff6b6b', '#ffc107', '#17a2b8', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d', '#343a40', '#007bff'],
      plotOptions: {
        pie: {
          donut: {
            size: '75%',
            labels: {
              show: true,
              name: { show: true, fontSize: '12px', fontFamily: 'Public Sans' },
              value: { show: true, fontSize: '12px', fontFamily: 'Public Sans' }
            }
          }
        }
      },
      legend: { show: false }
    };
    new ApexCharts(earningReportsTabsIncomeEl, earningReportsIncomeConfig).render();
  }

  // Sales Last 6 Months - Radar Chart
  // --------------------------------------------------------------------
  const salesLastMonthEl = document.querySelector('#salesLastMonth'),
    salesLastMonthConfig = {
      series: [
        {
          name: 'Sales',
          data: [32, 27, 27, 30, 25, 25]
        },
        {
          name: 'Visits',
          data: [25, 35, 20, 20, 20, 20]
        }
      ],
      chart: {
        height: 340,
        type: 'radar',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        radar: {
          polygons: {
            strokeColors: borderColor,
            connectorColors: borderColor
          }
        }
      },
      stroke: {
        show: false,
        width: 0
      },
      legend: {
        show: true,
        fontSize: '13px',
        position: 'bottom',
        labels: {
          colors: legendColor,
          useSeriesColors: false
        },
        markers: {
          height: 10,
          width: 10,
          offsetX: -3
        },
        itemMargin: {
          horizontal: 10
        },
        onItemHover: {
          highlightDataSeries: false
        }
      },
      colors: [config.colors.primary, config.colors.info],
      fill: {
        opacity: [1, 0.85]
      },
      markers: {
        size: 0
      },
      grid: {
        show: false,
        padding: {
          top: 0,
          bottom: -5
        }
      },
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        labels: {
          show: true,
          style: {
            colors: [labelColor, labelColor, labelColor, labelColor, labelColor, labelColor],
            fontSize: '13px',
            fontFamily: 'Public Sans'
          }
        }
      },
      yaxis: {
        show: false,
        min: 0,
        max: 40,
        tickAmount: 4
      },
      responsive: [
        {
          breakpoint: 769,
          options: {
            chart: {
              height: 400
            }
          }
        }
      ]
    };
  if (typeof salesLastMonthEl !== undefined && salesLastMonthEl !== null) {
    const salesLastMonth = new ApexCharts(salesLastMonthEl, salesLastMonthConfig);
    salesLastMonth.render();
  }

  // Progress Chart
  // --------------------------------------------------------------------
  // Radial bar chart functions
  function radialBarChart(color, value) {
    const radialBarChartOpt = {
      chart: {
        height: 53,
        width: 43,
        type: 'radialBar'
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '33%'
          },
          dataLabels: {
            show: false
          },
          track: {
            background: config.colors_label.secondary
          }
        }
      },
      stroke: {
        lineCap: 'round'
      },
      colors: [color],
      grid: {
        padding: {
          top: -15,
          bottom: -15,
          left: -5,
          right: -15
        }
      },
      series: [value],
      labels: ['Progress']
    };
    return radialBarChartOpt;
  }
  // All progress chart
  const chartProgressList = document.querySelectorAll('.chart-progress');
  if (chartProgressList) {
    chartProgressList.forEach(function (chartProgressEl) {
      const color = config.colors[chartProgressEl.dataset.color],
        series = chartProgressEl.dataset.series;
      const optionsBundle = radialBarChart(color, series);
      const chart = new ApexCharts(chartProgressEl, optionsBundle);
      chart.render();
    });
  }

  // Project Status - Line Chart
  // --------------------------------------------------------------------
  const projectStatusEl = document.querySelector('#projectStatusChart'),
    projectStatusConfig = {
      chart: {
        height: 240,
        type: 'area',
        toolbar: false
      },
      markers: {
        strokeColor: 'transparent'
      },
      series: [
        {
          data: [2000, 2000, 4000, 4000, 3050, 3050, 2000, 2000, 3050, 3050, 4700, 4700, 2750, 2750, 5700, 5700]
        }
      ],
      dataLabels: {
        enabled: false
      },
      grid: {
        show: false,
        padding: {
          left: -10,
          right: -5
        }
      },
      stroke: {
        width: 3,
        curve: 'straight'
      },
      colors: [config.colors.warning],
      fill: {
        type: 'gradient',
        gradient: {
          opacityFrom: 0.6,
          opacityTo: 0.15,
          stops: [0, 95, 100]
        }
      },
      xaxis: {
        labels: {
          show: false
        },
        axisBorder: {
          show: false
        },
        axisTicks: {
          show: false
        },
        lines: {
          show: false
        }
      },
      yaxis: {
        labels: {
          show: false
        },
        min: 1000,
        max: 6000,
        tickAmount: 5
      },
      tooltip: {
        enabled: false
      }
    };
  if (typeof projectStatusEl !== undefined && projectStatusEl !== null) {
    const projectStatus = new ApexCharts(projectStatusEl, projectStatusConfig);
    projectStatus.render();
  }
})();
