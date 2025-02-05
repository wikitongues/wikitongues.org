<?php
// Define the special characters and the base letters, then merge them.
$special_chars = ['!', '’', '‡', '|', 'ǂ'];
$base_letters = range('A', 'Z');
$letters = array_merge($base_letters, $special_chars);
?>

<div id="language-list-container">
	<h2 class="title">Language Index</h2>
    <nav id="language-nav">
			<?php foreach ($letters as $letter): ?>
				<a class="nav-btn loading" href="<?php echo esc_attr("#letter-" . $letter); ?>" title="Languages starting with <?php echo esc_attr($letter); ?>" data-letter="<?php echo esc_attr($letter); ?>">
					<?php echo esc_html($letter); ?>
				</a>
			<?php endforeach; ?>
    </nav>
    <div id="language-list"></div>

	<div id="loading-spinner" class="hidden">
		<div class="spinner"></div>
		<p>Loading more languages...</p>
	</div>
</div>

<script>
// The LazyLanguageLoader class handles sequential loading and supports chunked loading for heavy letters.
class LazyLanguageLoader {
	constructor(options) {
		this.languageList = document.getElementById(options.languageListId);
		this.loadingSpinner = document.getElementById(options.loadingSpinnerId);
		this.navButtons = document.querySelectorAll(options.navButtonSelector);
		this.letters = Array.from(this.navButtons)
			.map(btn => btn.getAttribute("data-letter").trim())
			.filter(letter => letter !== "");
		this.loadedLetters = new Set();
		this.remainingLetters = [...this.letters];
		this.pendingResponses = {};
		this.viewportHeight = window.innerHeight;
		this.loading = false;
		this.initialLoadComplete = false;

		this.init();
	}

	init() {
		this.remainingLetters
			.reduce((promiseChain, letter) => {
				return promiseChain.then(() => {
					return this.loadLetterWithPromise(letter);
				});
			}, Promise.resolve())
	}

	removeNavLoading(letter) {
		const navButton = document.querySelector(`#language-nav [data-letter="${letter}"]`);
		if (navButton) {
			navButton.classList.remove('loading');
		}
	}

	loadLetter(letter) {
		// if (letter === 'A') {
		// 	return this.loadLetterChunks(letter);
		// }

		if (!letter || letter === " " || this.loadedLetters.has(letter)) return Promise.resolve();
		this.loadedLetters.add(letter);
		this.createLetterContainer(letter);
		this.showLoader();

		return fetch(`../wp-content/themes/blankslate-child/modules/language-index-ajax.php?start_letter=${encodeURIComponent(letter)}`)
			.then(response => response.json())
			.then(data => {
				if (!Array.isArray(data) || data.length === 0) {
					console.warn(`No data found for: ${letter}`);
					return;
				}
				this.pendingResponses[letter] = data;
				this.insertData(letter);
			})
			.catch(error => {
				console.error(`Error loading languages for ${letter}:`, error);
				this.loadedLetters.delete(letter); // Allow retry
			})
			.finally(() => {
				this.hideLoader();
				this.loading = false;
				this.removeNavLoading(letter);
			});
	}

	// Append a chunk of data to the existing letter container.
	// appendDataToLetter(letter, data) {
	// 	let group = document.getElementById(`letter-${letter}`);
	// 	let columns = group.querySelector(".language-columns");
	// 	columns.innerHTML += data.map(lang => `
	// 		<a href="${lang.permalink}" class="language-item ${lang.has_speakers ? 'has-speakers' : ''}" title="${lang.has_speakers ? `${lang.iso} resources available` : ''}">
	// 			<span>${lang.iso}</span>
	// 			<p>${lang.standard_name}</p>
	// 		</a>
	// 	`).join("");
	// }

	// Show a loader (spinner) inside the letter container.
	showLoaderForLetter(letter) {
		let group = document.getElementById(`letter-${letter}`);
		let loader = group.querySelector(".language-loader");
		if (loader) {
			loader.style.display = "flex";
		} else {
			loader = document.createElement("div");
			loader.className = "language-loader";
			loader.innerHTML = `<div class="spinner"></div>`;
			group.appendChild(loader);
		}
	}

	// Hide the loader inside the letter container.
	hideLoaderForLetter(letter) {
		let group = document.getElementById(`letter-${letter}`);
		let loader = group.querySelector(".language-loader");
		if (loader) {
			loader.style.display = "none";
		}
	}

	// Create a container for a specific letter if one doesn’t exist.
	createLetterContainer(letter) {
		let group = document.getElementById(`letter-${letter}`);
		if (!group) {
			group = document.createElement("div");
			group.id = `letter-${letter}`;
			group.classList.add("language-group");
			group.innerHTML = `
				<h2>${letter}</h2>
				<div class="language-loader">
					<div class="spinner"></div>
				</div>
				<div class="language-columns"></div>
			`;
			const letterIndex = this.letters.indexOf(letter);
			let inserted = false;

			for (let i = letterIndex + 1; i < this.letters.length; i++) {
				const nextLetter = this.letters[i];
				const nextGroup = document.getElementById(`letter-${nextLetter}`);
				if (nextGroup) {
					this.languageList.insertBefore(group, nextGroup);
					inserted = true;
					break;
				}
			}

			if (!inserted) {
				for (let i = letterIndex - 1; i >= 0; i--) {
					const prevLetter = this.letters[i];
					const prevGroup = document.getElementById(`letter-${prevLetter}`);
					if (prevGroup) {
						if (prevGroup.nextSibling) {
							this.languageList.insertBefore(group, prevGroup.nextSibling);
						} else {
							this.languageList.appendChild(group);
						}
						inserted = true;
						break;
					}
				}
			}

			if (!inserted) {
				this.languageList.appendChild(group);
			}
		}
	}

	// Insert data into a letter’s container (used by the standard non-chunked load).
	insertData(letter) {
		let group = document.getElementById(`letter-${letter}`);
		let columns = group.querySelector(".language-columns");
		columns.innerHTML = this.pendingResponses[letter].map(lang => `
			<a href="${lang.permalink}" class="language-item ${lang.has_speakers ? 'has-speakers' : ''}" title="${lang.has_speakers ? `${lang.iso} resources available` : ''}">
				<span>${lang.iso}</span>
				<p>${lang.standard_name}</p>
			</a>
		`).join("");

		const loader = group.querySelector(".language-loader");
		if (loader) {
			loader.remove();
		}
		delete this.pendingResponses[letter];
	}

	checkScroll() {
		if (!this.initialLoadComplete || this.loading || this.remainingLetters.length === 0) return;

		let lastItem = this.languageList.lastElementChild;
		if (lastItem && lastItem.getBoundingClientRect().bottom < window.innerHeight + 1000) {
			this.loading = true;
			let nextLetter = this.remainingLetters.shift();
			this.loadLetter(nextLetter);
		}
	}

	loadInitialLetters() {
		if (this.remainingLetters.length === 0) return;
		let viewportHeight = window.innerHeight;
		let totalLoadedHeight = this.languageList.clientHeight;
		if (this.remainingLetters.includes("A")) {
			let index = this.remainingLetters.indexOf("A");
			this.remainingLetters.splice(index, 1);
			this.loadLetterWithPromise("A").then(() => {
				this.loadNextBatch(viewportHeight, totalLoadedHeight);
			});
		} else {
			this.loadNextBatch(viewportHeight, totalLoadedHeight);
		}
	}

	loadNextBatch(viewportHeight, totalLoadedHeight) {
		if (this.remainingLetters.length === 0) {
			this.initialLoadComplete = true;
			return;
		}

		let letter = this.remainingLetters.shift();
		let beforeLoadHeight = this.languageList.clientHeight;

		this.loadLetterWithPromise(letter).then(() => {
			let afterLoadHeight = this.languageList.clientHeight;
			totalLoadedHeight += (afterLoadHeight - beforeLoadHeight) || this.viewportHeight / 2;

			if (totalLoadedHeight < viewportHeight * 2) {
				this.loadNextBatch(viewportHeight, totalLoadedHeight);
			} else {
				setTimeout(() => {
					this.initialLoadComplete = true;
				}, 500);
			}
		});
	}

	// Wrap loadLetter in a promise for sequential chaining.
	loadLetterWithPromise(letter) {
		return new Promise((resolve) => {
			this.loadLetter(letter);
			let checkLoaded = setInterval(() => {
				if (this.loadedLetters.has(letter)) {
					clearInterval(checkLoaded);
					resolve();
				}
			}, 100);
		});
	}

	showLoader() {
		this.loadingSpinner.style.display = "flex";
	}

	hideLoader() {
		this.loadingSpinner.style.display = "none";
	}
}

// ------------------------------
// DOMContentLoaded: Initialize Everything
// ------------------------------
document.addEventListener("DOMContentLoaded", function () {
	new LazyLanguageLoader({
		languageListId: "language-list",
		loadingSpinnerId: "loading-spinner",
		navButtonSelector: "#language-nav .nav-btn"
	});

	// ========================================
	// Fixed Nav: Make the nav fixed when it reaches the top.
	// Prevent page jump by inserting a placeholder of equal height.
	// ========================================
	const nav = document.getElementById("language-nav");
	let navOffsetTop = nav.offsetTop;

	// Get the original nav offset and height.
	const offsetElements = [
		document.querySelector("#wpadminbar"),
		document.querySelector(".wt_header")
	];
	const searchElements = [
		document.querySelector('.wt_banner--searchbar'),
		document.querySelector('#language-list-container > h2')
	];

	function calculateTotalOffsetHeight(elements) {
		let total = 0;
		elements.forEach(el => { total += (el ? el.offsetHeight : 0); });
		return total;
	}

	let totalOffsetHeight = calculateTotalOffsetHeight(offsetElements);
	let totalSearchHeight = calculateTotalOffsetHeight(searchElements);

	let navOffset = navOffsetTop - totalOffsetHeight;

	const navHeight = nav.offsetHeight;
	const navPlaceholder = document.createElement("div");
	navPlaceholder.className = "nav-placeholder";
	navPlaceholder.style.height = `${navHeight}px`;

	// ------------------------------
	// Window Resize: Watch the window size for height changes.
	// ------------------------------
	window.addEventListener("resize", function() {
		navOffsetTop = nav.offsetTop;
		navOffset = navOffsetTop - totalOffsetHeight;
	});

	window.addEventListener("scroll", function() {
		if (window.pageYOffset >= navOffset) {
			if (!nav.parentNode.contains(navPlaceholder)) {
				nav.parentNode.insertBefore(navPlaceholder, nav);
			}
			nav.classList.add("fixed");
			nav.style.top = `${totalOffsetHeight - 1}px`;
		} else {
			if (nav.parentNode.contains(navPlaceholder)) {
				nav.parentNode.removeChild(navPlaceholder);
			}
			nav.classList.remove("fixed");
		}
	});

	// ------------------------------
	// ResizeObserver: Watch the search bar for height changes.
	// ------------------------------
	const searchbar = document.querySelector('.wt_banner--searchbar');

	function updateNavOffset() {
		totalSearchHeight = calculateTotalOffsetHeight(searchElements);
		navOffsetTop = nav.offsetTop;
		navOffset = navOffsetTop - totalOffsetHeight;
	}

	if (window.ResizeObserver && searchbar) {
		const resizeObserver = new ResizeObserver(entries => {
			for (let entry of entries) {
				updateNavOffset();
			}
		});
		resizeObserver.observe(searchbar);
	} else {
		console.warn("ResizeObserver is not supported; consider a fallback method.");
	}

	// ========================================
	// Active Nav Anchor: Highlight the nav button for the active letter.
	// Rather than choosing the first group with a positive rect.top,
	// we choose the group that has scrolled past a threshold
	// and pick the one with the highest top value among those.
	// ========================================
	window.addEventListener("scroll", function() {
		let activeLetter = null;
		let highestTop = -Infinity;
		document.querySelectorAll(".language-group").forEach(group => {
			const rect = group.getBoundingClientRect();
			let threshold = totalOffsetHeight + 48 + 16; // 48px for the nav, 16px for the margin
			if (rect.top <= threshold && rect.top > highestTop) {
				highestTop = rect.top;
				activeLetter = group.id.replace("letter-", "");
			}
		});
		// Fallback: if no group qualifies, use the first group.
		if (!activeLetter) {
			const firstGroup = document.querySelector(".language-group");
			if (firstGroup) {
				activeLetter = firstGroup.id.replace("letter-", "");
			}
		}
		// Remove active class from all nav buttons.
		document.querySelectorAll("#language-nav .nav-btn").forEach(btn => btn.classList.remove("active"));
		// Add active class to the nav button corresponding to the active letter.
		if (activeLetter) {
			const navButton = document.querySelector(`#language-nav [data-letter="${activeLetter}"]`);
			if (navButton) navButton.classList.add("active");
		}
	});
});
</script>
