<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>GSMArena Style Specs Table - 3 phones</title>
 
  <!-- Google fonts similar to GSMArena -->
  <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;700&family=Oswald:wght@400;600&display=swap"
    rel="stylesheet">

  <!-- Bootstrap (responsive) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

 <!-- Styles Css External  -->
  <link rel="stylesheet" href="style.css">

</head>




<style>


        :root {
      --specs-bg: #fafafa;
      --specs-border: #eee;
      --specs-left-border: #ddd;
      --specs-bottom-border: #f0f0f0;
      --title-color: #7d7464;
      --accent: #d50000;
    }


    body {
        background: #f8f9fa;
        /* padding: 24px 0; */
        /* color: #222; */
    }
    
    .container {
        font-family: "Arimo", Arial, sans-serif;
      max-width: 1048px;
    }

    #specs-list {
      width: 100%;
      margin-bottom: 10px;
    }

    /* reset-like small rules used in original snippet */
    tr,
    tt,
    ul,
    var,
    video {
      margin: 0;
      padding: 0;
      border: 0;
      font: inherit;
      vertical-align: baseline;
      box-sizing: border-box;
    }

    table {
      border-collapse: collapse;
      border-spacing: 0;
      width: 100%;
    }

    #specs-list table {
      border-top: 5px solid var(--specs-border);
      width: 100%;
      padding: 1px 0;
      background-color: var(--specs-bg);
      /* margin-bottom: 16px; */
      margin: 0;
    }

    #specs-list table tr th {
      border-right: none;
      font: 16px "Oswald", Arial, sans-serif;
      text-transform: uppercase;
      width: 86px;
      vertical-align: top;
      padding: 8px 10px;
      text-align: left;
      border: 1px solid #ddd;

    }

    /* title column in each row */
    #specs-list td.ttl {
      color: #555;
      font-weight: 700;
      font-size: 14px;
      width: 90px;
      padding: 2px 5px;
      vertical-align: top;
      border: none;
      word-break: normal;
    }

    /* info cells */
    #specs-list td.nfo {
      /* width: 266px; */
      border-left: 1px solid var(--specs-left-border);
      border-bottom: 1px solid var(--specs-bottom-border);
      word-break: break-word;
      line-height: 16px;
      font-size: 14px;
      padding: 6px 10px;
      vertical-align: top;
    }

    /* small link color & accented th links */
    #specs-list td.nfo a,
    #specs-list th {
      color: var(--accent);
      text-decoration: none;
    }

    /* small responsive tweaks */
    @media (max-width: 767px) {
          #specs-list table tr th{
            display: block;
            width: 100%;
          }

      #specs-list table tr th,
      #specs-list td.ttl,
      #specs-list td.nfo {
        /* display: block; */
        /* width: 100%; */
        border: #ddd;
        border-left: none;
        

      }

      /* stack rows visually similar to GSMArena mobile behavior */
      #specs-list table tr {
        display: block;
        margin-bottom: 8px;
      }

      #specs-list td.nfo[colspan] {
        padding-left: 10px;
      }
    }
</style>

<body>
  <div class="container">
    <div id="specs-list" class="compare-specs-list">

      <!-- ===== NETWORK ===== -->
      <table class="table">
        <tr>
          <th rowspan="5">Network</th>
          <td class="ttl">Technology</td>
          <td class="nfo">GSM / HSPA / LTE</td>
         
        </tr>
        <tr>
          <td class="ttl">2G bands</td>
          <td class="nfo">GSM 850 / 900 / 1800 / 1900</td>
          
        </tr>
        <tr>
          <td class="ttl">3G bands</td>
          <td class="nfo">HSDPA 850 / 900 / 2100</td>
              </tr>
        <tr>
          <td class="ttl">4G bands</td>
          <td class="nfo">1, 3, 5, 7, 8, 20, 28, 38, 40, 41</td>
          </tr>
        <tr>
          <td class="ttl">5G bands</td>
          <td class="nfo">—</td>
           </tr>
      </table>

      <!-- ===== LAUNCH ===== -->
      <table class="table">
        <tr>
          <th rowspan="2">Launch</th>
          <td class="ttl">Announced</td>
          <td class="nfo">2023, November 10</td>
       
        </tr>
        <tr>
          <td class="ttl">Status</td>
          <td class="nfo">Available. Released 2023, November 10</td>
         
        </tr>
      </table>

      <!-- ===== BODY ===== -->
      <table class="table">
        <tr>
          <th rowspan="7">Body</th>
          <td class="ttl">Dimensions</td>
          <td class="nfo">168 × 78 × 8.1 mm (6.61 × 3.07 × 0.32 in)</td>
          
        </tr>
        <tr>
          <td class="ttl">3D size compare</td>
          <td class="nfo" colspan="3">Size up (link)</td>
        </tr>
        <tr>
          <td class="ttl">Weight</td>
          <td class="nfo">192 g (6.77 oz)</td>
          
        </tr>
        <tr>
          <td class="ttl">Build</td>
          <td class="nfo">—</td>
          </tr>
        <tr>
          <td class="ttl">SIM</td>
          <td class="nfo">Nano-SIM + Nano-SIM</td>
               </tr>
        <tr>
          <td class="ttl">Other</td>
          <td class="nfo">—</td>
                 </tr>
      </table>

      <!-- ===== DISPLAY ===== -->
      <table class="table">
        <tr>
          <th rowspan="5">Display</th>
          <td class="ttl">Type</td>
          <td class="nfo">IPS LCD, 90Hz, 450 nits (typ), 600 nits (HBM)</td>
             </tr>
        <tr>
          <td class="ttl">Size</td>
          <td class="nfo">6.74 inches, 109.7 cm² (~83.7% screen-to-body ratio)</td>
            </tr>
        <tr>
          <td class="ttl">Resolution</td>
          <td class="nfo">720 × 1600 pixels, 20:9 ratio (~260 ppi)</td>
            </tr>
        <tr>
          <td class="ttl">Protection</td>
          <td class="nfo">Corning Gorilla Glass</td>
           </tr>
        <tr>
          <td class="ttl">Other</td>
          <td class="nfo">—</td>
             </tr>
      </table>

      <!-- ===== PLATFORM ===== -->
      <table class="table">
        <tr>
          <th rowspan="4">Platform</th>
          <td class="ttl">OS</td>
          <td class="nfo">Android 13, MIUI 14</td>
            </tr>
        <tr>
          <td class="ttl">Chipset</td>
          <td class="nfo">Mediatek MT6769Z Helio G85 (12 nm)</td>
            </tr>
        <tr>
          <td class="ttl">CPU</td>
          <td class="nfo">Octa-core (2x2.0 GHz Cortex-A75 &amp; 6x1.8 GHz Cortex-A55)</td>
              </tr>
        <tr>
          <td class="ttl">GPU</td>
          <td class="nfo">Mali-G52 MC2</td>
                </tr>
      </table>

      <!-- ===== MEMORY ===== -->
      <table class="table">
        <tr>
          <th rowspan="3">Memory</th>
          <td class="ttl">Card slot</td>
          <td class="nfo">microSDXC (dedicated slot)</td>
        </tr>
        <tr>
          <td class="ttl">Internal</td>
          <td class="nfo">128GB 4GB RAM, 128GB 6GB RAM, 128GB 8GB RAM, 256GB 4GB RAM, 256GB 8GB RAM</td>
        </tr>
        <tr>
          <td class="ttl">Other</td>
          <td class="nfo">eMMC 5.1</td>
        </tr>
      </table>

      <!-- ===== CAMERA ===== -->
      <table class="table">
        <tr>
          <th rowspan="3">Main Camera</th>
          <td class="ttl">Modules</td>
          <td class="nfo">50 MP, f/1.8, 28 mm (wide), PDAF<br>2 MP, f/2.4, (macro)<br>0.08 MP (auxiliary lens)</td>
        </tr>
        <tr>
          <td class="ttl">Features</td>
          <td class="nfo">LED flash, HDR</td>
        </tr>
        <tr>
          <td class="ttl">Video</td>
          <td class="nfo">1080p@30fps</td>
        </tr>
      </table>

      <table class="table">
        <tr>
          <th rowspan="3">Selfie Camera</th>
          <td class="ttl">Modules</td>
          <td class="nfo">8 MP, f/2.0</td>
        </tr>
        <tr>
          <td class="ttl">Features</td>
          <td class="nfo">HDR</td>
        </tr>
        <tr>
          <td class="ttl">Video</td>
          <td class="nfo">1080p@30fps</td>
        </tr>
      </table>

      <!-- ===== SOUND ===== -->
      <table class="table">
        <tr>
          <th rowspan="2">Sound</th>
          <td class="ttl">Loudspeaker</td>
          <td class="nfo">Yes, with stereo speakers</td>
        </tr>
        <tr>
          <td class="ttl">3.5mm jack</td>
          <td class="nfo">Yes</td>
        </tr>
      </table>

      <!-- ===== COMMS ===== -->
      <table class="table">
        <tr>
          <th rowspan="7">Comms</th>
          <td class="ttl">WLAN</td>
          <td class="nfo">Wi-Fi 802.11 a/b/g/n/ac/6/7, dual-band, Wi-Fi Direct</td>
        </tr>
        <tr>
          <td class="ttl">Bluetooth</td>
          <td class="nfo">5.3, A2DP, LE</td>
        </tr>
        <tr>
          <td class="ttl">Positioning</td>
          <td class="nfo">GPS (L1+L5), BDS (B1I+B1c+B2a+B2b), GALILEO (E1+E5a+E5b), QZSS (L1+L5), GLONASS, NavIC (L5)
          </td>
        </tr>
        <tr>
          <td class="ttl">NFC</td>
          <td class="nfo">Yes (market/region dependent)</td>
        </tr>
        <tr>
          <td class="ttl">Infrared port</td>
          <td class="nfo">No</td>
        </tr>
        <tr>
          <td class="ttl">Radio</td>
          <td class="nfo">FM radio</td>
        </tr>
        <tr>
          <td class="ttl">USB</td>
          <td class="nfo">USB Type-C, OTG</td>
        </tr>
      </table>

      <!-- ===== FEATURES ===== -->
      <table class="table">
        <tr>
          <th rowspan="2">Features</th>
          <td class="ttl">Sensors</td>
          <td class="nfo">Fingerprint (side-mounted), accelerometer, compass</td>
        </tr>
        <tr>
          <td class="ttl">Other</td>
          <td class="nfo">Virtual proximity sensing</td>
        </tr>
      </table>

      <!-- ===== BATTERY ===== -->
      <table class="table">
        <tr>
          <th rowspan="2">Battery</th>
          <td class="ttl">Type</td>
          <td class="nfo">Li-Po 5000 mAh</td>
        </tr>
        <tr>
          <td class="ttl">Charging</td>
          <td class="nfo">18W wired, PD</td>
        </tr>
      </table>

      <!-- ===== MISC ===== -->
      <table class="table">
        <tr>
          <th rowspan="4">Misc</th>
          <td class="ttl">Colors</td>
          <td class="nfo">Midnight Black, Navy Blue, Glacier White, Clover Green</td>
        </tr>
        <tr>
          <td class="ttl">SAR EU</td>
          <td class="nfo">0.98 W/kg (head) &nbsp; 0.99 W/kg (body)</td>
        </tr>
        <tr>
          <td class="ttl">Models</td>
          <td class="nfo">CPH2797, PLJ110</td>
        </tr>
        <tr>
          <td class="ttl">Price</td>
          <td class="nfo">—</td>
        </tr>
      </table>

    </div>
  </div>
</body>

</html>