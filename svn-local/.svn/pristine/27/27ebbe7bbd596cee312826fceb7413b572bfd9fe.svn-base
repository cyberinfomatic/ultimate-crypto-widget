(function(){
	//levenstein distance to find the similarity between two strings and sort the table based on that
	// Get the table and input element
	const tables_cnts = document.querySelectorAll('.ucwp-crypto-searchable-table');
	tables_cnts.forEach(table_cnt => {
		const tables = table_cnt.querySelectorAll('.ucwp-crypto-search-table');
		const inputs = table_cnt.querySelectorAll('.ucwp-crypto-search-input');

		inputs.forEach(input => {
			add_event_listner_for_search_input(input);
		});

		// Attach an event listener to the input field
		function add_event_listner_for_search_input(input) {
			input?.addEventListener('input', function () {
				// Get the search term
				const searchTerm = input.value.toLowerCase();

				// Get all table rows
				tables.forEach(table => {
					const rows = table.querySelectorAll('tbody tr');

					// Convert NodeList to array for easier manipulation
					const rowsArray = Array.from(rows);

					// Sort the rows based on the similarity of the 'data-tata' attribute value to the search term
					rowsArray.sort((rowA, rowB) => {
						const tataA = rowA.dataset.tata.toLowerCase();
						const tataB = rowB.dataset.tata.toLowerCase();

						// split both tata A and B with space and select the one with the lowest length
						const tataAArray = tataA.split('---');
						const tataBArray = tataB.split('---');

						// Calculate Levenshtein distance between the 'data-tata' values and the search term
						const distanceA = Math.min(...tataAArray.map(word => levenshteinDistance(word, searchTerm)));
						const distanceB = Math.min(...tataBArray.map(word => levenshteinDistance(word, searchTerm)));

						// Sort based on the calculated distances
						return distanceA - distanceB;
					});
					console.clear()

					console.log(rowsArray);

					// if search input is empty, sort the table based on the index
					if (searchTerm === '') {
						rowsArray.sort((rowA, rowB) => {
							const row1Count = parseInt(rowA.querySelector('td').textContent);
							const row2Count = parseInt(rowB.querySelector('td').textContent);
							return row1Count - row2Count;
						});
						console.log(rowsArray);
					}

					// Reorder the rows in the table
					rowsArray.forEach(row => {
						table.querySelector('tbody').appendChild(row);
					});
				});
			});
		}
	});
	// Function to calculate Levenshtein distance between two strings
	function levenshteinDistance(str1, str2) {
		const len1 = str1.length;
		const len2 = str2.length;
		const dp = Array.from(Array(len1 + 1), () => Array(len2 + 1).fill(0));

		for (let i = 0; i <= len1; i++) {
			for (let j = 0; j <= len2; j++) {
				if (i === 0) {
					dp[i][j] = j;
				} else if (j === 0) {
					dp[i][j] = i;
				} else if (str1[i - 1] === str2[j - 1]) {
					dp[i][j] = dp[i - 1][j - 1];
				} else {
					dp[i][j] = 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
				}
			}
		}

		return dp[len1][len2];
	}

})();