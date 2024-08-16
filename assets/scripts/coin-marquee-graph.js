function ucwp_load_graph(display_canva, data, line_color = 'rgba(255, 99, 132, 0.8)') {
	let ctx = document.getElementById(display_canva);
	if (ctx == null) {
		return;
	}
	var myChart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ucwp_get_last_n_days_date_as_array(data.length),
			datasets: [{
				label: "Price",
				data: data,
				backgroundColor: 'rgba(255, 99, 132, 0.2)',
				borderColor: line_color,
				borderWidth: 1,
			// 	remove the point on the graph
				pointRadius: 0,
				pointHitRadius: 0,
				pointHoverRadius: 0,
				pointHoverBorderWidth: 0,
				pointBorderWidth: 0,
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					display: false // Hide the legend
				}
			},
			scales: {
				x: {
					display: false, // Hide the x-axis
				},
				y: {
					display: false // Hide the y-axis
				}
			}
		}
	});
}


function ucwp_get_last_n_days_date_as_array(number_of_days){
	var dates = [];
	for (var i = (number_of_days-1); i >= 0; i--) {
		var d = new Date();
		d.setDate(d.getDate() - i);
		dates.push(d.toLocaleDateString());
	}
	return dates;
}

