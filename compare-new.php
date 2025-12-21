<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mobile Compare Section</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="table-wrapper">

    <style>
      .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .compare-section {
        padding: 15px;
        background: #fff;
        overflow-x: scroll;
        min-width: 150%;
        width: 100%;
        border-collapse: collapse;
      }

      .compare-section h2 {
        font-size: 22px;
        margin-bottom: 5px;
      }

      .phone-img {
        width: 100%;
        height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .phone-img img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
      }

      .sub-title {
        color: #777;
        font-size: 13px;
        margin-bottom: 15px;
      }

      .compare-box {
        display: flex;
        gap: 10px;
      }

      .compare-column {
        background: #fff;
        border: 1px solid #ddd;
        padding: 10px;
        width: 50%;
      }

      .compare-column label {
        font-size: 12px;
        color: #666;
        display: block;
        margin-bottom: 5px;
      }

      .compare-column input {
        width: 100%;
        padding: 6px;
        border: 1px solid #aaa;
        margin-bottom: 10px;
      }

      .compare-column h3 {
        font-size: 16px;
        margin-bottom: 10px;
      }

      .btn1-button {
        background: #EEEEEE;
        color: black;
        border: none;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        text-align: left;
      }

      .actions {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        flex-direction: column;
        justify-content: center;
        gap: 5px;
        padding-top: 15px;
        align-items: center;
        width: 100%;
      }

      .actions a {
        text-decoration: none;
        color: #000;
        font-size: 13px;
        width: 100%;
        display: flex;
        justify-content: center;
      }

      .storage {
        font-size: 13px;
        color: #555;
      }

      .price {
        color: red;
        font-weight: bold;
        margin: 5px 0;
      }

      .brand {
        color: #0057b8;
        font-weight: bold;
        font-size: 14px;
      }

      .empty {
        background: #fafafa;
      }
    </style>

    <section class="compare-section">
      <h2>Compare</h2>
      <p class="sub-title">SPECIFICATIONS</p>

      <div class="compare-box">

        <div class="compare-column">
          <label>COMPARE WITH</label>
          <input type="text" placeholder="Search">

          <h3>Samsung Galaxy A56</h3>

          <div class="phone-img">
            <img src="https://fdn2.gsmarena.com/vv/bigpic/oneplus-15.jpg" alt="Samsung Galaxy A56">
          </div>

          <div class="actions">
            <a href="#" class="btn1-button" style="display:flex;align-items:center;gap:6px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3" />
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
              </svg>
              <span style="font-weight:600;">REVIEW</span>
            </a>
            <a href="#" class="btn1-button" style="display:flex;align-items:center;gap:6px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="7" y="2" width="10" height="20" rx="2" />
                <circle cx="12" cy="18" r="1" />
              </svg>
              <span style="font-weight:600;">SPECS</span>
            </a>
          </div>

          <p class="storage">128GB 8GB RAM</p>
          <p class="price">$499.99</p>
          <p class="brand">SAMSUNG</p>
        </div>
        <div class="compare-column">
          <label>COMPARE WITH</label>
          <input type="text" placeholder="Search">

          <h3>Samsung Galaxy A56</h3>

          <div class="phone-img">
            <img src="https://fdn2.gsmarena.com/vv/bigpic/oneplus-15.jpg" alt="Samsung Galaxy A56">
          </div>

          <div class="actions">
            <a href="#" class="btn1-button" style="display:flex;align-items:center;gap:6px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3" />
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
              </svg>
              <span style="font-weight:600;">REVIEW</span>
            </a>
            <a href="#" class="btn1-button" style="display:flex;align-items:center;gap:6px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="7" y="2" width="10" height="20" rx="2" />
                <circle cx="12" cy="18" r="1" />
              </svg>
              <span style="font-weight:600;">SPECS</span>
            </a>
          </div>

          <p class="storage">128GB 8GB RAM</p>
          <p class="price">$499.99</p>
          <p class="brand">SAMSUNG</p>
        </div>
        <div class="compare-column">
          <label>COMPARE WITH</label>
          <input type="text" placeholder="Search">

          <h3>Samsung Galaxy A56</h3>

          <div class="phone-img">
            <img src="https://fdn2.gsmarena.com/vv/bigpic/oneplus-15.jpg" alt="Samsung Galaxy A56">
          </div>

          <div class="actions">
            <a href="#" class="btn1-button" style="display:flex;align-items:center;gap:6px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3" />
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
              </svg>
              <span style="font-weight:600;">REVIEW</span>
            </a>
            <a href="#" class="btn1-button" style="display:flex;align-items:center;gap:6px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="7" y="2" width="10" height="20" rx="2" />
                <circle cx="12" cy="18" r="1" />
              </svg>
              <span style="font-weight:600;">SPECS</span>
            </a>
          </div>

          <p class="storage">128GB 8GB RAM</p>
          <p class="price">$499.99</p>
          <p class="brand">SAMSUNG</p>
        </div>


      </div>
    </section>

  </div>



</body>

</html>