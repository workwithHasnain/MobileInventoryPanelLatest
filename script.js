var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});


function toggleMenu() {
    let menu = document.getElementById('leftMenu');
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'block';
    }
}


// let lastScrollTop = 0;
// const navbar = document.getElementById("navbar");

// window.addEventListener("scroll", function () {
//     let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

//     if (scrollTop > lastScrollTop) {
//         // Scroll Down - hide navbar
//         navbar.style.transform = "translateY(-100%)";
//     } else {
//         // Scroll Up - show navbar
//         navbar.style.transform = "translateY(0)";
//     }

//     lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
// });
var prevScrollpos = window.pageYOffset;
window.onscroll = function () {
    var currentScrollPos = window.pageYOffset;
    if (prevScrollpos > currentScrollPos) {
        document.getElementById("navbar").style.top = "0";
    } else {
        document.getElementById("navbar").style.top = "-130px";
    }
    prevScrollpos = currentScrollPos;
}

// Live search: attach to header input inside .central form in navbar (desktop only)
document.addEventListener('DOMContentLoaded', function () {
    const searchContainer = document.querySelector('.central');
    if (!searchContainer) return;

    const input = searchContainer.querySelector('input[type="text"]');
    if (!input) return;

    // Create dropdown container
    const dropdown = document.createElement('div');
    dropdown.className = 'live-search-dropdown';
    dropdown.style.position = 'absolute';
    dropdown.style.top = (searchContainer.getBoundingClientRect().height + 4) + 'px';
    dropdown.style.left = '0';
    dropdown.style.right = '0';
    dropdown.style.background = '#fff';
    dropdown.style.border = '1px solid #ddd';
    dropdown.style.borderTop = 'none';
    dropdown.style.zIndex = '2000';
    dropdown.style.maxHeight = '360px';
    dropdown.style.overflowY = 'auto';
    dropdown.style.display = 'none';
    dropdown.style.boxShadow = '0 6px 12px rgba(0,0,0,.15)';
    dropdown.style.padding = '4px 0';

    // Ensure parent is positioned for absolute child
    const parent = searchContainer;
    parent.style.position = 'relative';
    parent.appendChild(dropdown);

    let debounceTimer = null;
    let controller = null;

    function clearResults() {
        dropdown.innerHTML = '';
        dropdown.style.display = 'none';
    }

    function renderResults(items) {
        dropdown.innerHTML = '';
        if (!items || items.length === 0) {
            const empty = document.createElement('div');
            empty.textContent = 'No results';
            empty.style.padding = '8px 12px';
            empty.style.color = '#666';
            dropdown.appendChild(empty);
            dropdown.style.display = 'block';
            return;
        }

        items.forEach(function (item) {
            const row = document.createElement('div');
            row.className = 'live-search-item';
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.gap = '8px';
            row.style.padding = '5px 10px';
            row.style.cursor = 'pointer';
            row.style.height = '50px';
            row.style.width = '50px';

            row.addEventListener('mouseover', function () { row.style.background = '#f5f5f5'; });
            row.addEventListener('mouseout', function () { row.style.background = '#fff'; });

            if (item.image) {
                const img = document.createElement('img');
                const baseURL = window.baseURL || '/';
                // Check if image is relative path and prepend baseURL
                img.src = (item.image.startsWith('/') || item.image.startsWith('http')) ? item.image : baseURL + item.image;
                img.alt = item.title || '';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '3px';
                row.appendChild(img);
            } else {
                const placeholder = document.createElement('div');
                placeholder.style.width = '32px';
                placeholder.style.height = '32px';
                placeholder.style.background = '#eee';
                placeholder.style.borderRadius = '3px';
                row.appendChild(placeholder);
            }

            const text = document.createElement('div');
            const label = document.createElement('div');
            label.textContent = item.title || '';
            label.style.fontSize = '13px';
            label.style.fontWeight = '600';
            label.style.color = '#222';

            const meta = document.createElement('div');
            meta.textContent = item.type === 'post' ? 'Post' : 'Device';
            meta.style.fontSize = '11px';
            meta.style.color = '#888';

            text.appendChild(label);
            text.appendChild(meta);
            row.appendChild(text);

            row.addEventListener('click', function () {
                if (item.url) {
                    window.location.href = item.url;
                } else if (item.type === 'post' && item.slug) {
                    window.location.href = 'post.php?slug=' + encodeURIComponent(item.slug);
                } else if (item.type === 'device' && item.slug) {
                    window.location.href = 'device.php?slug=' + encodeURIComponent(item.slug);
                }
            });

            dropdown.appendChild(row);
        });

        dropdown.style.display = 'block';
    }

    function doSearch(query) {
        if (controller) controller.abort();
        controller = new AbortController();
        const baseURL = window.baseURL;
        const url = baseURL + 'search.php?q=' + encodeURIComponent(query) + '&limit=8';
        fetch(url, { signal: controller.signal })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                renderResults((data && data.results) ? data.results : []);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return; // ignore
                clearResults();
            });
    }

    input.addEventListener('input', function () {
        const val = input.value.trim();
        if (val.length === 0) {
            clearResults();
            return;
        }
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { doSearch(val); }, 200);
    });

    // Hide when clicking outside
    document.addEventListener('click', function (e) {
        if (!parent.contains(e.target)) {
            clearResults();
        }
    });

    // Keyboard interactions
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            clearResults();
        }
    });
});

// Mobile Search Modal Logic
let mobileSearchDebounceTimer = null;
let mobileSearchController = null;

function openMobileSearch(event) {
    event.preventDefault();
    const modal = document.getElementById('mobileSearchModal');
    const input = document.getElementById('mobileSearchInput');

    modal.style.display = 'flex';
    modal.classList.add('open');

    // Lock body scroll
    document.body.style.overflow = 'hidden';

    // Focus input
    input.focus();

    // Clear previous results
    document.getElementById('mobileSearchResults').innerHTML = '';
}

function closeMobileSearch() {
    const modal = document.getElementById('mobileSearchModal');
    modal.style.display = 'none';
    modal.classList.remove('open');

    // Unlock body scroll
    document.body.style.overflow = '';

    // Clear results
    document.getElementById('mobileSearchResults').innerHTML = '';
    document.getElementById('mobileSearchInput').value = '';

    // Abort any pending requests
    if (mobileSearchController) {
        mobileSearchController.abort();
        mobileSearchController = null;
    }
}

function performMobileSearch(query) {
    const resultsContainer = document.getElementById('mobileSearchResults');

    if (!query.trim()) {
        resultsContainer.innerHTML = '';
        return;
    }

    // Cancel previous request
    if (mobileSearchController) {
        mobileSearchController.abort();
    }

    mobileSearchController = new AbortController();

    const baseURL = window.baseURL || '/';
    const url = baseURL + `search.php?q=${encodeURIComponent(query)}&limit=15`;

    fetch(url, {
        signal: mobileSearchController.signal
    })
        .then(response => response.json())
        .then(data => {
            renderMobileSearchResults(data.results || []);
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                console.error('Search error:', err);
            }
        });
}

function renderMobileSearchResults(items) {
    const resultsContainer = document.getElementById('mobileSearchResults');

    if (!items || items.length === 0) {
        resultsContainer.innerHTML = '<div class="mobile-search-empty">No results found</div>';
        return;
    }

    resultsContainer.innerHTML = '';

    items.forEach(item => {
        const link = document.createElement('a');
        link.href = item.url;
        link.className = 'mobile-search-result-item';

        const hasImage = item.image && item.image.trim();

        if (hasImage) {
            const img = document.createElement('img');
            const baseURL = window.baseURL;
            // Check if image is relative path and prepend baseURL
            img.src = (item.image.startsWith('/') || item.image.startsWith('http')) ? item.image : baseURL + item.image;
            img.alt = item.title;
            img.className = 'mobile-search-result-image';
            img.onerror = function () {
                this.style.display = 'none';
            };
            link.appendChild(img);
        } else {
            const placeholder = document.createElement('div');
            placeholder.className = 'mobile-search-result-image';
            placeholder.style.background = '#e0e0e0';
            link.appendChild(placeholder);
        }

        const content = document.createElement('div');
        content.className = 'mobile-search-result-content';

        const title = document.createElement('p');
        title.className = 'mobile-search-result-title';
        title.textContent = item.title;
        content.appendChild(title);

        const type = document.createElement('p');
        type.className = 'mobile-search-result-type';
        type.textContent = item.type === 'post' ? 'Article' : 'Device';
        content.appendChild(type);

        link.appendChild(content);
        resultsContainer.appendChild(link);
    });
}

// Mobile search event listeners
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('mobileSearchModal');
    const input = document.getElementById('mobileSearchInput');
    const closeBtn = document.getElementById('mobileSearchClose');
    const backdrop = document.querySelector('.mobile-search-backdrop');

    if (!modal || !input || !closeBtn) return;

    // Close button
    closeBtn.addEventListener('click', closeMobileSearch);

    // Close on backdrop click
    backdrop.addEventListener('click', closeMobileSearch);

    // Search input with debounce
    input.addEventListener('input', function () {
        clearTimeout(mobileSearchDebounceTimer);
        const query = this.value.trim();

        mobileSearchDebounceTimer = setTimeout(() => {
            performMobileSearch(query);
        }, 200);
    });

    // Close on Escape
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMobileSearch();
        }
    });

    // Prevent closing when clicking inside the container
    document.querySelector('.mobile-search-container').addEventListener('click', function (e) {
        if (e.target === this) {
            closeMobileSearch();
        }
    });
});