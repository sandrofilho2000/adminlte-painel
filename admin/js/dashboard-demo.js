(function ($) {
  'use strict';

  const salesCanvas = document.getElementById('sales-chart');
  if (salesCanvas && window.Chart) {
    new Chart(salesCanvas, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [
          {
            label: 'Digital Goods',
            data: [28, 42, 38, 56, 72, 68, 86],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.15)',
            fill: true,
            tension: 0.35,
            pointRadius: 3
          },
          {
            label: 'Physical Goods',
            data: [18, 26, 34, 30, 48, 52, 61],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'bottom'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.06)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  }

  const browserCanvas = document.getElementById('browser-chart');
  if (browserCanvas && window.Chart) {
    new Chart(browserCanvas, {
      type: 'doughnut',
      data: {
        labels: ['Chrome', 'Firefox', 'Safari', 'Edge', 'Other'],
        datasets: [
          {
            data: [48, 18, 16, 12, 6],
            backgroundColor: ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6c757d']
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }

  const $map = $('#world-map-markers');
  if ($map.length && $.fn.vectorMap) {
    $map.vectorMap({
      map: 'world_en',
      backgroundColor: 'transparent',
      borderColor: '#f4f4f4',
      borderOpacity: 0.8,
      borderWidth: 1,
      color: '#d2d6de',
      enableZoom: true,
      hoverColor: '#17a2b8',
      hoverOpacity: 0.7,
      normalizeFunction: 'polynomial',
      selectedColor: '#007bff',
      showTooltip: true,
      values: {
        us: 8390,
        br: 5120,
        gb: 2910,
        de: 2460,
        in: 1980,
        au: 940
      },
      scaleColors: ['#c8e6c9', '#28a745'],
      onLabelShow: function (event, label, code) {
        const values = {
          us: '8,390 visits',
          br: '5,120 visits',
          gb: '2,910 visits',
          de: '2,460 visits',
          in: '1,980 visits',
          au: '940 visits'
        };

        if (values[code]) {
          label.text(label.text() + ': ' + values[code]);
        }
      }
    });
  }
})(jQuery);
