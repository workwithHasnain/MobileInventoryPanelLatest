/**
 * GSMArena to DeviceArena Importer - Popup Script
 * 
 * Scrapes device specs from GSMArena pages and generates PostgreSQL INSERT
 * statements or imports directly to DeviceArena via the PHP endpoint.
 */

// ======================================================================
// 1. SCRAPING FUNCTION (Injected into the GSMArena page)
// ======================================================================

/**
 * This function runs inside the GSMArena page context via chrome.scripting.executeScript.
 * It reads the DOM and returns a structured object with all device data.
 */
function scrapeGSMArenaPage() {
    const result = {
        fullName: '',
        brand: '',
        modelName: '',
        mainImage: '',
        dataSpecs: {},
        sections: {},
        alsoKnownAs: ''
    };

    // --- Device name from h1 ---
    const modelEl = document.querySelector('[data-spec="modelname"]');
    if (modelEl) {
        result.fullName = modelEl.textContent.trim();
    } else {
        result.fullName = (document.title || '').replace(/\s*-\s*Full phone specifications.*$/i, '').trim();
    }

    // --- Also known as ---
    const commentEl = document.querySelector('[data-spec="comment"]');
    if (commentEl) {
        result.alsoKnownAs = commentEl.textContent.replace(/^Also known as\s*/i, '').trim();
    }

    // --- Brand (first word) ---
    const nameParts = result.fullName.split(' ');
    result.brand = nameParts[0] || '';
    result.modelName = nameParts.slice(1).join(' ') || result.fullName;

    // --- Main image ---
    const mainImgEl = document.querySelector('.specs-photo-main img');
    if (mainImgEl) {
        result.mainImage = mainImgEl.src;
    }

    // --- Collect ALL data-spec attribute values ---
    document.querySelectorAll('[data-spec]').forEach(el => {
        const key = el.getAttribute('data-spec');
        // Use innerHTML processing for multi-line values
        let html = el.innerHTML;
        html = html.replace(/<br\s*\/?>/gi, '\n');
        html = html.replace(/<sup>/gi, '').replace(/<\/sup>/gi, '');
        html = html.replace(/<[^>]+>/g, '');
        // Decode entities
        const tmp = document.createElement('textarea');
        tmp.innerHTML = html;
        result.dataSpecs[key] = tmp.value.trim();
    });

    // --- Parse spec tables from #specs-list ---
    const specsList = document.getElementById('specs-list');
    if (specsList) {
        const tables = specsList.querySelectorAll('table');
        for (const table of tables) {
            const th = table.querySelector('th');
            if (!th) continue;

            const sectionName = th.textContent.trim();
            const rows = [];

            const trs = table.querySelectorAll('tr');
            for (const tr of trs) {
                const ttlCell = tr.querySelector('td.ttl');
                const nfoCell = tr.querySelector('td.nfo');
                if (!ttlCell && !nfoCell) continue;

                // Field name: get text from <a> tag or directly
                let fieldName = '';
                if (ttlCell) {
                    const link = ttlCell.querySelector('a');
                    fieldName = (link ? link.textContent : ttlCell.textContent).trim();
                    // Handle &nbsp; (becomes \u00a0)
                    if (fieldName === '\u00a0' || fieldName === '') fieldName = '';
                }

                // Field value: handle <br> tags, decode entities
                let fieldValue = '';
                if (nfoCell) {
                    let html = nfoCell.innerHTML;
                    html = html.replace(/<br\s*\/?>/gi, '\n');
                    html = html.replace(/<sup>/gi, '').replace(/<\/sup>/gi, '');
                    html = html.replace(/<[^>]+>/g, '');
                    const tmp = document.createElement('textarea');
                    tmp.innerHTML = html;
                    fieldValue = tmp.value.trim();
                }

                if (fieldName || fieldValue) {
                    rows.push({ field: fieldName, description: fieldValue });
                }
            }

            if (rows.length > 0) {
                result.sections[sectionName] = rows;
            }
        }
    }

    return result;
}


// ======================================================================
// 2. DATA EXTRACTION (Maps GSMArena data to DeviceArena fields)
// ======================================================================

/**
 * GSMArena section name → DeviceArena DB column mapping.
 * GSMArena uses different section names than our DB columns.
 */
const SECTION_MAP = {
    'Network': 'network',
    'Launch': 'launch',
    'Body': 'body',
    'Display': 'display',
    'Platform': 'hardware',    // GSMArena calls it "Platform", we call it "hardware"
    'Memory': 'memory',
    'Main Camera': 'main_camera',
    'Selfie camera': 'selfie_camera',
    'Sound': 'multimedia',  // GSMArena calls it "Sound", we call it "multimedia"
    'Comms': 'connectivity', // GSMArena calls it "Comms", we call it "connectivity"
    'Features': 'features',
    'Battery': 'battery',
    'Misc': 'general_info' // GSMArena calls it "Misc", we call it "general_info"
};

/**
 * Extract structured device data from raw scraped data.
 * Parses individual fields and maps grouped specs to DB columns.
 */
function extractDeviceData(scraped) {
    const ds = scraped.dataSpecs;
    const data = {
        brand: scraped.brand,
        name: scraped.modelName,
        fullName: scraped.fullName,
        mainImage: scraped.mainImage,
        year: null,
        releaseDate: null,
        availability: null,
        price: null,
        priceRaw: null,
        weight: null,
        thickness: null,
        os: null,
        storage: null,
        cardSlot: null,
        displaySize: null,
        displayResolution: null,
        mainCameraResolution: null,
        mainCameraVideo: null,
        ram: null,
        chipsetName: null,
        batteryCapacity: null,
        wiredCharging: null,
        wirelessCharging: null,
        slug: null,
        metaTitle: null,
        metaDesc: null,
        groupedSpecs: {}
    };

    // --- Year ---
    if (ds.year) {
        const m = ds.year.match(/(\d{4})/);
        data.year = m ? parseInt(m[1]) : null;
    }

    // --- Release Date ---
    if (ds.status) {
        const m = ds.status.match(/Released\s+(\d{4}),?\s+(\w+)\s+(\d{1,2})/i);
        if (m) {
            const months = {
                January: 1, February: 2, March: 3, April: 4, May: 5, June: 6,
                July: 7, August: 8, September: 9, October: 10, November: 11, December: 12
            };
            const mon = months[m[2]];
            if (mon) {
                data.releaseDate = `${m[1]}-${String(mon).padStart(2, '0')}-${m[3].padStart(2, '0')}`;
            }
        }
    }

    // --- Availability ---
    if (ds.status) {
        const s = ds.status.toLowerCase();
        if (s.includes('available')) data.availability = 'Available';
        else if (s.includes('coming soon')) data.availability = 'Coming Soon';
        else if (s.includes('discontinued')) data.availability = 'Discontinued';
        else if (s.includes('rumored') || s.includes('rumoured')) data.availability = 'Rumored';
    }

    // --- Price ---
    if (ds.price) {
        data.priceRaw = ds.price;
        const m = ds.price.match(/([\d,]+(?:\.\d+)?)/);
        data.price = m ? parseFloat(m[1].replace(',', '')) : null;
    }

    // --- Weight ---
    if (ds.weight) {
        const m = ds.weight.match(/([\d.]+)\s*g/);
        data.weight = m ? m[1] : null;
    }

    // --- Thickness (3rd dimension from dimensions) ---
    if (ds.dimensions) {
        const m = ds.dimensions.match(/([\d.]+)\s*x\s*([\d.]+)\s*x\s*([\d.]+)\s*mm/);
        data.thickness = m ? m[3] : null;
    }

    // --- OS ---
    data.os = ds.os || null;

    // --- Storage ---
    if (ds.internalmemory) {
        const m = ds.internalmemory.match(/(\d+\s*(?:GB|TB))/i);
        data.storage = m ? m[1] : null;
    }

    // --- Card slot ---
    if (ds.memoryslot) {
        data.cardSlot = ds.memoryslot;
    }

    // --- Display size ---
    if (ds.displaysize) {
        const m = ds.displaysize.match(/([\d.]+)\s*inch/i);
        data.displaySize = m ? m[1] : null;
    }

    // --- Display resolution ---
    if (ds.displayresolution) {
        const m = ds.displayresolution.match(/(\d+\s*x\s*\d+)/);
        data.displayResolution = m ? m[1] : null;
    }

    // --- Main camera resolution ---
    if (ds.cam1modules) {
        const m = ds.cam1modules.match(/(\d+)\s*MP/i);
        data.mainCameraResolution = m ? m[1] + ' MP' : null;
    }

    // --- Main camera video ---
    data.mainCameraVideo = ds.cam1video || null;

    // --- RAM ---
    if (ds.internalmemory) {
        const ramMatches = [...ds.internalmemory.matchAll(/(\d+)\s*GB\s*RAM/gi)];
        if (ramMatches.length > 0) {
            const vals = ramMatches.map(m => parseInt(m[1]));
            const unique = [...new Set(vals)].sort((a, b) => a - b);
            data.ram = unique.join('/') + 'GB';
        }
    }

    // --- Chipset ---
    data.chipsetName = ds.chipset || null;

    // --- Battery capacity ---
    if (ds.batdescription1) {
        const m = ds.batdescription1.match(/(\d+)\s*mAh/i);
        data.batteryCapacity = m ? m[1] : null;
    }

    // --- Charging (parse from Battery section) ---
    const batterySection = scraped.sections['Battery'];
    if (batterySection) {
        for (const row of batterySection) {
            const desc = row.description || '';
            const lines = desc.split('\n');
            for (const line of lines) {
                // Match wired charging (exclude "reverse")
                if (/wired/i.test(line) && !/reverse/i.test(line)) {
                    const wm = line.match(/(\d+)\s*W/i);
                    if (wm) data.wiredCharging = wm[1] + 'W';
                }
                // Match wireless charging (exclude "reverse")
                if (/wireless/i.test(line) && !/reverse/i.test(line)) {
                    const wm = line.match(/(\d+)\s*W/i);
                    if (wm) data.wirelessCharging = wm[1] + 'W';
                }
            }
        }
    }

    // --- Slug ---
    data.slug = (data.brand + '-' + data.name)
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');

    // --- Meta title ---
    data.metaTitle = `${data.fullName} - Full Specifications | DeviceArena`;

    // --- Meta description ---
    const metaParts = [];
    if (data.displaySize) metaParts.push(`${data.displaySize}" display`);
    if (data.chipsetName) metaParts.push(data.chipsetName);
    if (data.batteryCapacity) metaParts.push(`${data.batteryCapacity} mAh battery`);
    if (data.storage) metaParts.push(`${data.storage} storage`);
    if (data.ram) metaParts.push(`${data.ram} RAM`);
    data.metaDesc = `${data.fullName} specifications. ${metaParts.join(', ')}.`;

    // --- Map grouped spec sections ---
    for (const [gsmSection, dbColumn] of Object.entries(SECTION_MAP)) {
        if (scraped.sections[gsmSection]) {
            data.groupedSpecs[dbColumn] = scraped.sections[gsmSection];
        }
    }

    return data;
}


// ======================================================================
// 3. SQL GENERATION
// ======================================================================

/** Escape a value for PostgreSQL single-quoted string */
function pgStr(val) {
    if (val === null || val === undefined || val === '') return 'NULL';
    return "'" + String(val).replace(/'/g, "''") + "'";
}

/** Format a numeric value for SQL */
function pgNum(val) {
    if (val === null || val === undefined || val === '') return 'NULL';
    const n = parseFloat(val);
    return isNaN(n) ? 'NULL' : n;
}

/** Format a PostgreSQL TEXT[] array */
function pgArray(arr) {
    if (!arr || arr.length === 0) return 'NULL';
    const items = arr.map(s => "'" + String(s).replace(/'/g, "''") + "'");
    return 'ARRAY[' + items.join(', ') + ']::TEXT[]';
}

/** Format a JSON value for PostgreSQL TEXT column */
function pgJson(obj) {
    if (!obj || (Array.isArray(obj) && obj.length === 0)) return 'NULL';
    const json = JSON.stringify(obj);
    return "'" + json.replace(/'/g, "''") + "'";
}

/**
 * Generate complete PostgreSQL INSERT statements for the device.
 * Includes brand upsert + device insert.
 */
function generateSQL(deviceData, imageUrls) {
    const mainImage = imageUrls.length > 0 ? imageUrls[0] : deviceData.mainImage;
    const allImages = imageUrls.length > 0 ? imageUrls : (deviceData.mainImage ? [deviceData.mainImage] : []);

    const specColumns = [
        'network', 'launch', 'body', 'display', 'hardware', 'memory',
        'main_camera', 'selfie_camera', 'multimedia', 'connectivity',
        'features', 'battery', 'general_info'
    ];

    let sql = '';
    sql += '-- ============================================================\n';
    sql += '-- GSMArena → DeviceArena Import\n';
    sql += `-- Device: ${deviceData.fullName}\n`;
    sql += `-- Generated: ${new Date().toISOString()}\n`;
    sql += '-- ============================================================\n\n';

    // Brand upsert
    sql += '-- Step 1: Ensure brand exists\n';
    sql += `INSERT INTO brands (name)\nVALUES (${pgStr(deviceData.brand)})\nON CONFLICT (name) DO NOTHING;\n\n`;

    // Device insert
    sql += '-- Step 2: Insert device\n';
    sql += 'INSERT INTO phones (\n';
    sql += '    release_date, name, brand_id, brand, year, availability, price,\n';
    sql += '    image, images,\n';
    sql += '    network, launch, body, display, hardware, memory,\n';
    sql += '    main_camera, selfie_camera, multimedia, connectivity, features, battery, general_info,\n';
    sql += '    weight, thickness, os, storage, card_slot,\n';
    sql += '    display_size, display_resolution, main_camera_resolution, main_camera_video,\n';
    sql += '    ram, chipset_name, battery_capacity, wired_charging, wireless_charging,\n';
    sql += '    slug, meta_title, meta_desc\n';
    sql += ') VALUES (\n';

    // Values
    const lines = [];
    lines.push(`    ${pgStr(deviceData.releaseDate)}`);          // release_date
    lines.push(`    ${pgStr(deviceData.name)}`);                 // name
    lines.push(`    (SELECT id FROM brands WHERE LOWER(name) = LOWER(${pgStr(deviceData.brand)}))`); // brand_id
    lines.push(`    ${pgStr(deviceData.brand)}`);                // brand
    lines.push(`    ${pgNum(deviceData.year)}`);                 // year
    lines.push(`    ${pgStr(deviceData.availability)}`);         // availability
    lines.push(`    ${pgNum(deviceData.price)}`);                // price
    lines.push(`    ${pgStr(mainImage)}`);                       // image
    lines.push(`    ${pgArray(allImages)}`);                     // images

    // Grouped spec columns
    for (const col of specColumns) {
        lines.push(`    ${pgJson(deviceData.groupedSpecs[col] || null)}`);
    }

    // Individual fields
    lines.push(`    ${pgStr(deviceData.weight)}`);               // weight
    lines.push(`    ${pgStr(deviceData.thickness)}`);            // thickness
    lines.push(`    ${pgStr(deviceData.os)}`);                   // os
    lines.push(`    ${pgStr(deviceData.storage)}`);              // storage
    lines.push(`    ${pgStr(deviceData.cardSlot)}`);             // card_slot
    lines.push(`    ${pgStr(deviceData.displaySize)}`);          // display_size
    lines.push(`    ${pgStr(deviceData.displayResolution)}`);    // display_resolution
    lines.push(`    ${pgStr(deviceData.mainCameraResolution)}`); // main_camera_resolution
    lines.push(`    ${pgStr(deviceData.mainCameraVideo)}`);      // main_camera_video
    lines.push(`    ${pgStr(deviceData.ram)}`);                  // ram
    lines.push(`    ${pgStr(deviceData.chipsetName)}`);          // chipset_name
    lines.push(`    ${pgStr(deviceData.batteryCapacity)}`);      // battery_capacity
    lines.push(`    ${pgStr(deviceData.wiredCharging)}`);        // wired_charging
    lines.push(`    ${pgStr(deviceData.wirelessCharging)}`);     // wireless_charging
    lines.push(`    ${pgStr(deviceData.slug)}`);                 // slug
    lines.push(`    ${pgStr(deviceData.metaTitle)}`);            // meta_title
    lines.push(`    ${pgStr(deviceData.metaDesc)}`);             // meta_desc

    sql += lines.join(',\n');
    sql += '\n);\n';

    return sql;
}


// ======================================================================
// 4. BUILD JSON PAYLOAD (for PHP import endpoint)
// ======================================================================

/**
 * Build the JSON payload matching the simpleAddDevice format.
 * Grouped specs are JSON-stringified since the DB stores them as TEXT.
 */
function buildImportPayload(deviceData, imageUrls) {
    const mainImage = imageUrls.length > 0 ? imageUrls[0] : deviceData.mainImage;
    const allImages = imageUrls.length > 0 ? imageUrls : (deviceData.mainImage ? [deviceData.mainImage] : []);

    const specColumns = [
        'network', 'launch', 'body', 'display', 'hardware', 'memory',
        'main_camera', 'selfie_camera', 'multimedia', 'connectivity',
        'features', 'battery', 'general_info'
    ];

    const payload = {
        name: deviceData.name,
        brand: deviceData.brand,
        year: deviceData.year,
        availability: deviceData.availability,
        price: deviceData.price,
        release_date: deviceData.releaseDate,
        image: mainImage || '',
        images: allImages,
        weight: deviceData.weight,
        thickness: deviceData.thickness,
        os: deviceData.os,
        storage: deviceData.storage,
        card_slot: deviceData.cardSlot,
        display_size: deviceData.displaySize,
        display_resolution: deviceData.displayResolution,
        main_camera_resolution: deviceData.mainCameraResolution,
        main_camera_video: deviceData.mainCameraVideo,
        ram: deviceData.ram,
        chipset_name: deviceData.chipsetName,
        battery_capacity: deviceData.batteryCapacity,
        wired_charging: deviceData.wiredCharging,
        wireless_charging: deviceData.wirelessCharging,
        slug: deviceData.slug,
        meta_title: deviceData.metaTitle,
        meta_desc: deviceData.metaDesc
    };

    // Grouped specs → JSON strings (matching how the form serializes them)
    for (const col of specColumns) {
        const specData = deviceData.groupedSpecs[col];
        payload[col] = specData && specData.length > 0 ? JSON.stringify(specData) : null;
    }

    return payload;
}


// ======================================================================
// 5. UI CONTROLLER
// ======================================================================

let currentDeviceData = null;

/** Set the status message in the header */
function setStatus(text, type = 'info') {
    const el = document.getElementById('status');
    el.textContent = text;
    el.className = 'status ' + type;
}

/** Show a result message below the buttons */
function showResult(text, type = 'success') {
    const el = document.getElementById('result-message');
    el.textContent = text;
    el.className = type;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 6000);
}

/** Get all selected image files from the file inputs */
function getImageFiles() {
    const inputs = document.querySelectorAll('.image-file-input');
    const files = [];
    inputs.forEach(inp => {
        if (inp.files && inp.files.length > 0) {
            files.push(inp.files[0]);
        }
    });
    return files;
}

/** Read current field values back into deviceData (user may have edited them) */
function syncFieldsToData() {
    if (!currentDeviceData) return;
    currentDeviceData.brand = document.getElementById('field-brand').value.trim();
    currentDeviceData.name = document.getElementById('field-name').value.trim();
    currentDeviceData.fullName = currentDeviceData.brand + ' ' + currentDeviceData.name;
    currentDeviceData.year = parseInt(document.getElementById('field-year').value) || null;
    currentDeviceData.availability = document.getElementById('field-availability').value || null;
    currentDeviceData.price = parseFloat(document.getElementById('field-price').value) || null;
    currentDeviceData.releaseDate = document.getElementById('field-release-date').value || null;

    // Regenerate slug from potentially edited brand/name
    currentDeviceData.slug = (currentDeviceData.brand + '-' + currentDeviceData.name)
        .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    currentDeviceData.metaTitle = `${currentDeviceData.fullName} - Full Specifications | DeviceArena`;
}

/** Populate the UI with extracted device data */
function populateUI(data) {
    currentDeviceData = data;

    // Fields
    document.getElementById('field-brand').value = data.brand || '';
    document.getElementById('field-name').value = data.name || '';
    document.getElementById('field-year').value = data.year || '';
    document.getElementById('field-availability').value = data.availability || '';
    document.getElementById('field-price').value = data.price || '';
    document.getElementById('field-release-date').value = data.releaseDate || '';

    if (data.priceRaw) {
        document.getElementById('price-note').textContent = `(${data.priceRaw})`;
    }

    // Stats
    document.getElementById('stat-display').textContent = data.displaySize ? `${data.displaySize}" ${data.displayResolution || ''}` : '—';
    document.getElementById('stat-camera').textContent = data.mainCameraResolution || '—';
    document.getElementById('stat-ram').textContent = data.ram || '—';
    document.getElementById('stat-battery').textContent = data.batteryCapacity ? `${data.batteryCapacity} mAh` : '—';
    document.getElementById('stat-chipset').textContent = data.chipsetName || '—';
    document.getElementById('stat-os').textContent = data.os ? (data.os.length > 30 ? data.os.substring(0, 30) + '...' : data.os) : '—';
    document.getElementById('stat-weight').textContent = data.weight ? `${data.weight}g` : '—';
    const chargingParts = [];
    if (data.wiredCharging) chargingParts.push(data.wiredCharging + ' wired');
    if (data.wirelessCharging) chargingParts.push(data.wirelessCharging + ' wireless');
    document.getElementById('stat-charging').textContent = chargingParts.length > 0 ? chargingParts.join(', ') : '—';
    document.getElementById('sections-count').textContent = Object.keys(data.groupedSpecs).length;

    // Main image preview from GSMArena
    if (data.mainImage) {
        const imgEl = document.getElementById('main-img');
        imgEl.src = data.mainImage;
        imgEl.style.display = 'block';
        document.getElementById('no-img-msg').style.display = 'none';
    }
}

/** Setup file input change listeners for preview and label updates */
function setupFileInputListeners() {
    const fileInputs = document.querySelectorAll('.image-file-input');
    fileInputs.forEach(input => {
        const row = input.closest('.image-file-row');
        const nameSpan = row.querySelector('.file-name');

        // Click on file-name span triggers the hidden file input
        nameSpan.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            if (input.files && input.files.length > 0) {
                const file = input.files[0];
                nameSpan.textContent = file.name;
                nameSpan.classList.add('has-file');
            } else {
                nameSpan.textContent = 'No file chosen';
                nameSpan.classList.remove('has-file');
            }
            updateImagePreviews();
        });
    });
}

/** Update the thumbnail preview grid from selected files */
function updateImagePreviews() {
    const container = document.getElementById('image-previews');
    container.innerHTML = '';
    const files = getImageFiles();
    files.forEach(file => {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        container.appendChild(img);
    });
}


// ======================================================================
// 6. INITIALIZATION
// ======================================================================

document.addEventListener('DOMContentLoaded', async () => {
    // --- Load saved settings ---
    try {
        const saved = await chrome.storage.local.get(['bridgeUrl', 'serverUrl', 'apiKey']);
        if (saved.bridgeUrl) document.getElementById('bridge-url').value = saved.bridgeUrl;
        if (saved.serverUrl) document.getElementById('server-url').value = saved.serverUrl;
        if (saved.apiKey) document.getElementById('api-key').value = saved.apiKey;
    } catch (e) { /* ignore */ }

    // --- Section toggles ---
    document.querySelectorAll('.section-title[data-toggle]').forEach(title => {
        title.addEventListener('click', () => {
            const bodyId = title.getAttribute('data-toggle');
            const body = document.getElementById(bodyId);
            if (body) {
                body.classList.toggle('hidden');
                title.classList.toggle('collapsed');
            }
        });
    });

    // --- Setup file input listeners ---
    setupFileInputListeners();

    // --- Generate SQL button ---
    document.getElementById('btn-generate-sql').addEventListener('click', async () => {
        if (!currentDeviceData) { showResult('No device data scraped yet.', 'error'); return; }
        syncFieldsToData();

        const imageFiles = getImageFiles();
        if (imageFiles.length === 0) {
            showResult('Please select at least one image file first.', 'error');
            return;
        }

        // SQL mode: use placeholder paths since actual upload paths are determined server-side
        const placeholderPaths = imageFiles.map((f, i) => `uploads/device_PENDING_${i + 1}_${f.name}`);
        const sql = generateSQL(currentDeviceData, placeholderPaths);

        const sqlSection = document.getElementById('sql-section');
        const sqlOutput = document.getElementById('sql-output');
        sqlOutput.value = '-- NOTE: This SQL uses placeholder image paths.\n'
            + '-- Use the "Import to DeviceArena" button instead to upload images automatically.\n'
            + '-- Or use the web form at import_device.php to upload images + paste data.\n\n'
            + sql;
        sqlSection.style.display = 'block';

        // Copy to clipboard
        try {
            await navigator.clipboard.writeText(sqlOutput.value);
            showResult('SQL generated and copied! Use Import button for image upload.', 'success');
        } catch (e) {
            showResult('SQL generated. Use Import button for actual image upload.', 'error');
        }
    });

    // --- Copy SQL button ---
    document.getElementById('btn-copy-sql').addEventListener('click', async () => {
        const sql = document.getElementById('sql-output').value;
        try {
            await navigator.clipboard.writeText(sql);
            showResult('SQL copied to clipboard!', 'success');
        } catch (e) {
            showResult('Failed to copy.', 'error');
        }
    });

    // --- Import to server button ---
    document.getElementById('btn-import').addEventListener('click', async () => {
        if (!currentDeviceData) { showResult('No device data scraped yet.', 'error'); return; }
        syncFieldsToData();

        const bridgeUrl = document.getElementById('bridge-url').value.trim().replace(/\/+$/, '');
        const serverUrl = document.getElementById('server-url').value.trim().replace(/\/+$/, '');
        const apiKey = document.getElementById('api-key').value.trim();

        if (!bridgeUrl) { showResult('Please set the local bridge URL.', 'error'); return; }
        if (!serverUrl) { showResult('Please set the remote server URL.', 'error'); return; }

        const imageFiles = getImageFiles();
        if (imageFiles.length === 0) {
            showResult('Please select at least one image file.', 'error');
            return;
        }

        // Save settings
        try {
            await chrome.storage.local.set({ bridgeUrl, serverUrl, apiKey });
        } catch (e) { /* ignore */ }

        // Build FormData with files + JSON device data
        const payload = buildImportPayload(currentDeviceData, []);
        const formData = new FormData();

        // Append all device data fields as a JSON string
        formData.append('device_data', JSON.stringify(payload));

        // Append image files
        imageFiles.forEach((file, i) => {
            formData.append('images[]', file, file.name);
        });

        const importBtn = document.getElementById('btn-import');
        importBtn.disabled = true;
        importBtn.textContent = '⏳ Uploading via Bridge...';

        try {
            // Send to LOCAL bridge, which forwards to remote server
            console.log('[DeviceArena] Sending to bridge:', bridgeUrl);
            console.log('[DeviceArena] Remote target:', serverUrl);

            const resp = await fetch(bridgeUrl, {
                method: 'POST',
                headers: {
                    'X-API-Key': apiKey,
                    'X-Remote-URL': serverUrl
                },
                body: formData
            });

            const text = await resp.text();
            console.log('[DeviceArena] Bridge response:', text);

            let result;
            try {
                result = JSON.parse(text);
            } catch (parseErr) {
                showResult('❌ Bridge returned invalid response: ' + text.substring(0, 300), 'error');
                return;
            }

            if (result.success) {
                showResult('✅ ' + (result.message || 'Device imported successfully!'), 'success');
            } else {
                let errMsg = result.error || 'Import failed.';
                if (result.details) errMsg += '\n\nDetails: ' + result.details.substring(0, 200);
                showResult('❌ ' + errMsg, 'error');
            }
        } catch (err) {
            console.error('[DeviceArena] Bridge error:', err);
            showResult('❌ Cannot reach local bridge: ' + err.message + '\n\nMake sure XAMPP/Apache is running.', 'error');
        } finally {
            importBtn.disabled = false;
            importBtn.textContent = '🚀 Import to DeviceArena';
        }
    });

    // --- Start scraping ---
    try {
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

        if (!tab || !tab.url || !tab.url.includes('gsmarena.com')) {
            setStatus('Not on GSMArena', 'error');
            document.getElementById('not-gsmarena').style.display = 'block';
            return;
        }

        setStatus('Scraping page...', 'info');

        const results = await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            func: scrapeGSMArenaPage
        });

        if (!results || !results[0] || !results[0].result) {
            setStatus('Scraping failed — no data returned', 'error');
            return;
        }

        const scraped = results[0].result;

        if (!scraped.fullName) {
            setStatus('Could not find device info on this page', 'error');
            document.getElementById('not-gsmarena').style.display = 'block';
            return;
        }

        const deviceData = extractDeviceData(scraped);
        populateUI(deviceData);

        document.getElementById('main-content').style.display = 'block';
        setStatus(`✅ ${deviceData.fullName} scraped`, 'success');

    } catch (err) {
        setStatus('Error: ' + err.message, 'error');
        console.error('Scraping error:', err);
    }
});
