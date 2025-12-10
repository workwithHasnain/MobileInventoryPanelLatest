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
            row.style.padding = '6px 10px';
            row.style.cursor = 'pointer';

            row.addEventListener('mouseover', function () { row.style.background = '#f5f5f5'; });
            row.addEventListener('mouseout', function () { row.style.background = '#fff'; });

            if (item.image) {
                const img = document.createElement('img');
                img.src = item.image;
                img.alt = item.title || '';
                img.style.width = '32px';
                img.style.height = '32px';
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
                } else if (item.type === 'device' && item.id) {
                    window.location.href = 'device.php?id=' + encodeURIComponent(item.id);
                }
            });

            dropdown.appendChild(row);
        });

        dropdown.style.display = 'block';
    }

    function doSearch(query) {
        if (controller) controller.abort();
        controller = new AbortController();
        const url = 'search.php?q=' + encodeURIComponent(query) + '&limit=8';
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