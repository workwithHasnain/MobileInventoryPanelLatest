<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">


<style>

/* MAIN CONTAINER */
.phone-section {
  width: 450px;
  padding: 20px;
  border: 1px solid #ddd;
  font-family: Arial, sans-serif;
  background: #fff;
}

/* TITLES */
.title {
  font-size: 26px;
  font-weight: 700;
  margin: 0 0 5px 0;
}

.sub-title {
  font-size: 13px;
  letter-spacing: 1px;
  color: #555;
  margin-bottom: 15px;
}

/* FLEX WRAPPER */
.phone-wrapper {
  display: flex;
  gap: 20px;
}

/* IMAGE */
.phone-image img {
  width: 170px;
  height: auto;
  border: 1px solid #ccc;
}

/* SPEC BOX */
.spec-box {
  width: 100%;
  background: #f7f7f7;
  padding: 15px;
  border-radius: 6px;
}

.spec-item {
  display: flex;
  gap: 12px;
  margin-bottom: 18px;
}

.spec-item .icon {
  font-size: 22px;
}

.spec-item p {
  margin: 0;
  font-size: 14px;
  color: #333;
}

.spec-item strong {
  font-size: 16px;
}

/* REVIEW BUTTON */
.review-btn { 
  background: #d50000;
  color: #fff;
  border: none;
  padding: 10px 18px;
  border-radius: 6px;
  font-size: 15px;
  cursor: pointer;
}

.review-btn:hover {
  background: #ba0000;
}

/* BOTTOM STATS */
.bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 15px;
}

.stats {
  display: flex;
  gap: 25px;
}

.stat {
  display: flex;
  align-items: center;
  gap: 5px;
}

.stat span {
  font-size: 20px;
}

.stat p {
  margin: 0;
  font-size: 14px;
  line-height: 15px;
}

.stat small {
  font-size: 11px;
  color: #555;
}


</style>

</head>
<body>
    
    <div class="phone-section">
  <h2 class="title">Samsung Galaxy S24 Ultra</h2>
  <p class="sub-title">SPECIFICATIONS</p>

  <div class="phone-wrapper">
    
    <!-- LEFT IMAGE -->
    <div class="phone-image">
      <img src="https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-s24-ultra-5g-sm-s928-stylus.jpg" alt="phone">
    </div>

    <!-- RIGHT CONTENT -->
    <div class="spec-box">

      <div class="spec-item">
        <span class="icon">📱</span>
        <p><strong>6.8"</strong><br>1440x3120 pixels</p>
      </div>

      <div class="spec-item">
        <span class="icon">📷</span>
        <p><strong>200MP</strong><br>4320p</p>
      </div>

      <div class="spec-item">
        <span class="icon">⚙️</span>
        <p><strong>12GB RAM</strong><br>Snapdragon 8 Gen 3</p>
      </div>

      <div class="spec-item">
        <span class="icon">🔋</span>
        <p><strong>5000mAh</strong><br>45W|15W</p>
      </div>

    </div>
  </div>

  <div class="bottom-row">

    <button class="review-btn">READ OUR REVIEW</button>

    <div class="stats">
      <div class="stat">
        <span>📉</span>  
        <p>24%<br><small>16,315,584 hits</small></p>
      </div>

      <div class="stat">
        <span>❤️</span>
        <p>1879<br><small>Become a fan</small></p>
      </div>
    </div>

  </div>
</div>

</body>
</html>