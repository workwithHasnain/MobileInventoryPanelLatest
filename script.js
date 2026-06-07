document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});


function toggleMenu() {
    let menu = document.getElementById('leftMenu');
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'block';
    }
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
    let currentQuery = '';
    let currentOffset = 0;

    function clearResults() {
        dropdown.innerHTML = '';
        dropdown.style.display = 'none';
    }

    function renderResults(items, isAppend = false) {
        if (!isAppend) {
            dropdown.innerHTML = '';
        } else {
            const loadMoreBtn = dropdown.querySelector('.load-more-btn');
            if (loadMoreBtn) loadMoreBtn.remove();
        }

        if (!items || items.length === 0) {
            if (!isAppend) {
                const empty = document.createElement('div');
                empty.textContent = 'No results';
                empty.style.padding = '8px 12px';
                empty.style.color = '#666';
                dropdown.appendChild(empty);
                dropdown.style.display = 'block';
            }
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
            } else if (item.type !== 'brand') {
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
            meta.textContent = item.type === 'post' ? 'Post' : (item.type === 'brand' ? 'Brand' : 'Device');
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
                } else if (item.type === 'brand' && item.slug) {
                    window.location.href = 'brand.php?slug=' + encodeURIComponent(item.slug);
                } else if (item.type === 'device' && item.slug) {
                    window.location.href = 'device.php?slug=' + encodeURIComponent(item.slug);
                }
            });

            dropdown.appendChild(row);
        });

        if (items.length >= 50) {
            const loadMoreBtn = document.createElement('div');
            loadMoreBtn.className = 'load-more-btn';
            loadMoreBtn.textContent = 'Load More';
            loadMoreBtn.style.padding = '8px 12px';
            loadMoreBtn.style.textAlign = 'center';
            loadMoreBtn.style.color = '#007bff';
            loadMoreBtn.style.cursor = 'pointer';
            loadMoreBtn.style.fontWeight = '600';
            loadMoreBtn.style.borderTop = '1px solid #eee';
            
            loadMoreBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                currentOffset += 50;
                doSearch(currentQuery, true);
            });
            dropdown.appendChild(loadMoreBtn);
        }

        dropdown.style.display = 'block';
    }

    function doSearch(query, isLoadMore = false) {
        if (!isLoadMore) {
            currentOffset = 0;
            currentQuery = query;
            if (controller) controller.abort();
            controller = new AbortController();
            
            dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;"><i class="fa fa-spinner fa-spin me-2"></i>Searching...</div>';
            dropdown.style.display = 'block';
        } else {
            const loadMoreBtn = dropdown.querySelector('.load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Loading...';
                loadMoreBtn.style.pointerEvents = 'none';
                loadMoreBtn.style.opacity = '0.7';
            }
        }
        const baseURL = window.baseURL;
        const url = baseURL + 'search.php?q=' + encodeURIComponent(currentQuery) + '&limit=50&offset=' + currentOffset;
        fetch(url, { signal: controller.signal })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                renderResults((data && data.results) ? data.results : [], isLoadMore);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return; // ignore
                if (!isLoadMore) clearResults();
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
let mobileCurrentQuery = '';
let mobileCurrentOffset = 0;

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

function performMobileSearch(query, isLoadMore) {
    var resultsContainer = document.getElementById('mobileSearchResults');
    if (!query || !query.trim()) {
        resultsContainer.innerHTML = '';
        return;
    }
    mobileCurrentQuery = query.trim();
    if (mobileSearchController) mobileSearchController.abort();
    mobileSearchController = new AbortController();
    resultsContainer.innerHTML = '<div style="padding:20px;text-align:center;color:#888"><i class="fa fa-spinner fa-spin me-2"></i>Searching...</div>';

    var baseURL = window.baseURL || '/';
    var url = baseURL + 'handlers/search.php?q=' + encodeURIComponent(mobileCurrentQuery);

    fetch(url, { signal: mobileSearchController.signal })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            // Flatten categories with type labels
            var flat = [];
            (data.devices || []).forEach(function (i) { flat.push(Object.assign({}, i, { _type: 'Device' })); });
            (data.reviews || []).forEach(function (i) { flat.push(Object.assign({}, i, { _type: 'Review' })); });
            (data.news    || []).forEach(function (i) { flat.push(Object.assign({}, i, { _type: 'News'   })); });
            (data.posts   || []).forEach(function (i) { flat.push(Object.assign({}, i, { _type: 'Post'   })); });
            renderMobileSearchResults(flat);
        })
        .catch(function (err) {
            if (err && err.name !== 'AbortError') {
                resultsContainer.innerHTML = '<div class="mobile-search-empty">Search failed. Please try again.</div>';
            }
        });
}

function renderMobileSearchResults(items) {
    var resultsContainer = document.getElementById('mobileSearchResults');

    if (!items || items.length === 0) {
        resultsContainer.innerHTML = '<div class="mobile-search-empty">No results found</div>';
        return;
    }
    resultsContainer.innerHTML = '';

    var baseURL = window.baseURL || '/';

    items.forEach(function (item) {
        var link = document.createElement('a');
        link.href = item.url || '#';
        link.className = 'mobile-search-result-item';

        // Thumbnail
        var imgSrc = item.image ? item.image.trim() : '';
        if (imgSrc && !imgSrc.startsWith('http') && !imgSrc.startsWith('/')) {
            imgSrc = baseURL + imgSrc;
        }
        if (imgSrc) {
            var img = document.createElement('img');
            img.src = imgSrc;
            img.alt = item.title || '';
            img.className = 'mobile-search-result-image';
            img.onerror = function () { this.style.display = 'none'; };
            link.appendChild(img);
        } else {
            var placeholder = document.createElement('div');
            placeholder.className = 'mobile-search-result-image';
            link.appendChild(placeholder);
        }

        var content = document.createElement('div');
        content.className = 'mobile-search-result-content';

        var title = document.createElement('p');
        title.className = 'mobile-search-result-title';
        title.textContent = item.title || '';
        content.appendChild(title);

        var typeLabel = document.createElement('p');
        typeLabel.className = 'mobile-search-result-type';
        typeLabel.textContent = item._type || '';
        content.appendChild(typeLabel);

        link.appendChild(content);
        resultsContainer.appendChild(link);
    });
}

// Mobile search event listeners
document.addEventListener('DOMContentLoaded', function () {
    var modal    = document.getElementById('mobileSearchModal');
    var input    = document.getElementById('mobileSearchInput');
    var closeBtn = document.getElementById('mobileSearchClose');

    if (!modal || !input || !closeBtn) return;

    closeBtn.addEventListener('click', closeMobileSearch);

    input.addEventListener('input', function () {
        clearTimeout(mobileSearchDebounceTimer);
        var query = this.value.trim();
        mobileSearchDebounceTimer = setTimeout(function () {
            performMobileSearch(query);
        }, 260);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMobileSearch();
    });
});

// ══════════════════════════════════════════════════
//  DESKTOP MEGA-DROPDOWN SEARCH
// ══════════════════════════════════════════════════
(function () {
    var searchInput     = null;
    var searchDropdown  = null;
    var searchWrap      = null;
    var clearBtn        = null;
    var searchTimer     = null;
    var searchCtrl      = null;
    var lastQuery       = '';

    function getBase() { return window.baseURL || '/'; }

    // ── Open / Close ──────────────────────────────
    function openDropdown() {
        if (!searchDropdown) return;
        searchDropdown.style.display = 'block';
        searchWrap.classList.add('da-search-active');
        searchInput.setAttribute('aria-expanded', 'true');
    }
    function closeDropdown() {
        if (!searchDropdown) return;
        searchDropdown.style.display = 'none';
        searchWrap.classList.remove('da-search-active');
        searchInput.setAttribute('aria-expanded', 'false');
    }
    function showHint() {
        openDropdown();
        document.getElementById('da-search-dropdown-inner').innerHTML =
            '<div class="da-search-hint"><i class="fa fa-magnifying-glass"></i><span>Start typing to search devices, reviews, news &amp; posts</span></div>';
    }
    function showLoading() {
        openDropdown();
        document.getElementById('da-search-dropdown-inner').innerHTML =
            '<div class="da-search-loading"><i class="fa fa-spinner fa-spin"></i><span>Searching...</span></div>';
    }

    // ── Helpers ───────────────────────────────────
    function resolveImg(img) {
        if (!img || !img.trim()) return '';
        if (img.startsWith('http') || img.startsWith('/')) return img;
        return getBase() + img;
    }
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function iconFor(type) {
        return {
            devices: '<i class="fa fa-mobile-screen da-thumb-icon"></i>',
            reviews: '<i class="fa fa-star-half-stroke da-thumb-icon"></i>',
            news:    '<i class="fa fa-newspaper da-thumb-icon"></i>',
            posts:   '<i class="fa fa-file-lines da-thumb-icon"></i>',
        }[type] || '<i class="fa fa-file da-thumb-icon"></i>';
    }

    // ── Build a single result card ────────────────
    function buildCard(item, type) {
        var a = document.createElement('a');
        a.href = item.url || '#';
        a.className = 'da-search-result-card';

        var thumb = document.createElement('div');
        thumb.className = 'da-search-result-thumb' + (type === 'devices' ? ' device-thumb' : '');
        var imgSrc = resolveImg(item.image);
        if (imgSrc) {
            var img = document.createElement('img');
            img.src = imgSrc; img.alt = item.title || ''; img.loading = 'lazy';
            img.onerror = function () { thumb.innerHTML = iconFor(type); };
            thumb.appendChild(img);
        } else {
            thumb.innerHTML = iconFor(type);
        }
        a.appendChild(thumb);

        var text = document.createElement('div');
        text.className = 'da-search-result-text';
        var title = document.createElement('p');
        title.className = 'da-search-result-title';
        title.textContent = item.title || '';
        text.appendChild(title);
        var meta = document.createElement('p');
        meta.className = 'da-search-result-meta';
        if (type === 'devices') {
            meta.textContent = item.brand || 'Device';
        } else {
            meta.textContent = (item.desc && item.desc.trim())
                ? item.desc.substring(0, 58) + (item.desc.length > 58 ? '…' : '')
                : ({ reviews: 'Review', news: 'News', posts: 'Post' }[type] || '');
        }
        text.appendChild(meta);
        a.appendChild(text);

        var arrow = document.createElement('i');
        arrow.className = 'fa fa-chevron-right da-search-result-arrow';
        a.appendChild(arrow);
        return a;
    }

    // ── "Find more" button (the 16th slot) ─────
    function buildFindMoreBtn(col, type, nextOffset) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'da-search-find-more';
        btn.innerHTML = '<i class="fa fa-rotate-right"></i><span>Find more</span><i class="fa fa-chevron-down"></i>';
        btn.addEventListener('click', function (e) {
            e.stopPropagation(); // Prevent closing dropdown due to DOM removal bubbling
            loadMoreForColumn(col, type, nextOffset, btn);
        });
        return btn;
    }

    // ── Per-column load-more ──────────────────────
    function loadMoreForColumn(col, type, nextOffset, btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i><span>Loading...</span>';

        var url = getBase() + 'handlers/search.php'
            + '?q='      + encodeURIComponent(lastQuery)
            + '&type='   + encodeURIComponent(type)
            + '&offset=' + nextOffset;

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var items = data.items || [];

                // Edge case: 0 items returned — initial batch was already the full set
                if (items.length === 0) {
                    btn.remove();
                    // Update badge to drop the '+' suffix
                    var headEl = col.querySelector('.da-search-col-head');
                    var badge  = headEl && headEl.querySelector('.da-col-count');
                    if (badge) {
                        var cards = col.querySelectorAll('.da-search-result-card').length;
                        badge.textContent = '(' + cards + ')';
                    }
                    updateFooterCount();
                    return;
                }

                btn.remove();
                items.forEach(function (item) {
                    col.appendChild(buildCard(item, type));
                });

                // Chain: if exactly 15 came back, there may be yet more
                if (data.has_more) {
                    col.appendChild(buildFindMoreBtn(col, type, nextOffset + 15));
                }

                // Update column count badge
                var head  = col.querySelector('.da-search-col-head');
                var badge = head && head.querySelector('.da-col-count');
                if (badge) {
                    var cards = col.querySelectorAll('.da-search-result-card').length;
                    // '+' suffix = more may exist; no '+' = all shown
                    badge.textContent = data.has_more ? '(' + cards + '+)' : '(' + cards + ')';
                }
                updateFooterCount();
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-exclamation-triangle"></i><span>Retry</span>';
            });
    }

    // ── Live footer count ─────────────────────────
    function updateFooterCount() {
        var inner = document.getElementById('da-search-dropdown-inner');
        if (!inner) return;
        var total  = inner.querySelectorAll('.da-search-result-card').length;
        var footer = inner.querySelector('.da-search-dropdown-footer .da-footer-count');
        if (footer) footer.textContent = total + ' result' + (total !== 1 ? 's' : '') + ' shown';
    }

    // ── Build a full column ───────────────────────
    function buildCol(items, type, label, iconClass, colorClass) {
        var col = document.createElement('div');
        col.className = 'da-search-col';

        var head = document.createElement('div');
        head.className = 'da-search-col-head ' + colorClass;
        head.innerHTML = '<i class="' + iconClass + '"></i>'
            + '<span>' + label + '</span>'
            + '<span class="da-col-count" style="margin-left:auto;opacity:0.65;font-weight:700;">'
            + (items.length ? '(' + items.length + '+)' : '') + '</span>';
        col.appendChild(head);

        if (!items || items.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'da-search-col-empty';
            empty.textContent = 'No results';
            col.appendChild(empty);
        } else {
            items.forEach(function (item) { col.appendChild(buildCard(item, type)); });
            // Always show "Find 15 more" — initial load brings only 4, more always likely.
            // Pass items.length as starting offset for the next fetch.
            col.appendChild(buildFindMoreBtn(col, type, items.length));
        }
        return col;
    }

    // ── Render full results ───────────────────────
    function renderResults(data, query) {
        var inner = document.getElementById('da-search-dropdown-inner');
        if (!inner) return;

        var total = (data.devices || []).length + (data.reviews || []).length
                  + (data.news    || []).length + (data.posts   || []).length;

        if (total === 0) {
            inner.innerHTML = '<div class="da-search-no-results">'
                + '<i class="fa fa-face-frown-open"></i>'
                + '<strong>No results for &ldquo;' + escHtml(query) + '&rdquo;</strong>'
                + '<span>Try a different keyword or browse a category</span></div>';
            return;
        }

        inner.innerHTML = '';
        var grid = document.createElement('div');
        grid.className = 'da-search-cols';
        grid.appendChild(buildCol(data.devices || [], 'devices', 'Devices', 'fa fa-mobile-screen',    'devices'));
        grid.appendChild(buildCol(data.reviews || [], 'reviews', 'Reviews', 'fa fa-star-half-stroke', 'reviews'));
        grid.appendChild(buildCol(data.news    || [], 'news',    'News',    'fa fa-newspaper',        'news'));
        grid.appendChild(buildCol(data.posts   || [], 'posts',   'Posts',   'fa fa-file-lines',       'posts'));
        inner.appendChild(grid);

        var footer = document.createElement('div');
        footer.className = 'da-search-dropdown-footer';
        footer.innerHTML = '<span class="da-footer-count">' + total + ' result' + (total !== 1 ? 's' : '') + ' shown</span>'
            + '<span><kbd>Esc</kbd> to close</span>';
        inner.appendChild(footer);
    }

    // ── Fetch ─────────────────────────────────────
    function doSearch(query) {
        if (searchCtrl) searchCtrl.abort();
        searchCtrl = new AbortController();
        lastQuery  = query;
        showLoading();
        fetch(getBase() + 'handlers/search.php?q=' + encodeURIComponent(query), { signal: searchCtrl.signal })
            .then(function (r) { return r.json(); })
            .then(function (data) { renderResults(data, query); openDropdown(); })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                var inner = document.getElementById('da-search-dropdown-inner');
                if (inner) inner.innerHTML = '<div class="da-search-no-results"><i class="fa fa-circle-exclamation"></i><span>Search failed. Please try again.</span></div>';
            });
    }

    // ── Init ──────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        searchInput    = document.getElementById('da-desktop-search-input');
        searchDropdown = document.getElementById('da-search-dropdown');
        searchWrap     = document.getElementById('da-search-wrap');
        clearBtn       = document.getElementById('da-search-clear-btn');
        if (!searchInput || !searchDropdown) return;

        searchInput.addEventListener('input', function () {
            var q = this.value.trim();
            if (clearBtn) clearBtn.style.display = q.length ? 'flex' : 'none';
            clearTimeout(searchTimer);
            // Open immediately on first character
            if (q.length === 0) { if (searchCtrl) searchCtrl.abort(); showHint(); return; }
            // Show loading state right away so the panel opens instantly
            showLoading();
            searchTimer = setTimeout(function () { doSearch(q); }, 150);
        });

        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 2) openDropdown(); else showHint();
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                clearBtn.style.display = 'none';
                if (searchCtrl) searchCtrl.abort();
                showHint();
                searchInput.focus();
            });
        }

        var submitBtn = document.querySelector('.da-search-submit-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                var q = searchInput.value.trim();
                if (q.length >= 2) doSearch(q);
            });
        }

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeDropdown(); searchInput.blur(); }
        });

        // Click outside → close
        document.addEventListener('click', function (e) {
            if (searchWrap) {
                var path = e.composedPath ? e.composedPath() : [];
                // Check if target is outside searchWrap, AND isn't in the event path (handles DOM removal)
                if (!searchWrap.contains(e.target) && path.indexOf(searchWrap) === -1) {
                    closeDropdown();
                }
            }
        });
    });
})();