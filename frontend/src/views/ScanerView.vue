<template>
<!--  <button v-if="!startedApp" class="start-app" @click="initApp">Run Scaner</button>-->
  <div class="compass-app">
    <h2>1. Навигатор на точку (WebSocket + NMEA)</h2>
    <div>
      <b>Положение (ESP32):</b> {{ decimalLat ?? '-' }}, {{ decimalLon ?? '-' }}<br />
      <b>Цель:</b> {{ targetLat }}, {{ targetLon }} <br />
      <b>Дистанция:</b> {{ distance || '-' }} м<br />
      <b>Heading:</b> {{ heading || '-' }}<br />
      <b>Azimuth:</b> {{ azimuth || '-' }}
    </div>

    <div style="margin-bottom:16px;">
      <button @click="prevPoint" :disabled="currentIdx === 0">Дальше</button>
      <button @click="nextPoint" :disabled="currentIdx === testPoints.length-1">Ближе</button>
      <br>
      <div style="margin-top:12px;">
        <b>current index:</b> {{ currentIdx }}<br>
        <b>tLat:</b> {{ tLat }}<br>
        <b>tLon:</b> {{ tLon }}
      </div>
    </div>

    <CircleNavigation
      :myPoint="[tLat, tLon]"
      :targetPoint="[51.8248574467772, 55.17238619257802]"
      :heading="heading"
    />

    <div class="arrow-area">
      <div class="arrow" :style="{ transform: `rotate(${arrowRotation}deg)`, color: redFlag ? `green` : `#1976d2` }">▲</div>
    </div>

    <div>{{ compass }}</div>
    <div>{{ redFlag ? 'green' : 'blue' }}</div>

    <button @click="enableCompass" v-if="!compassPermissionGranted">
      Разрешить доступ к компасу
    </button>
    <div v-if="compassNotSupported" style="color: red">
      Доступ к компасу невозможен на этом устройстве или браузере.<br />
      Стрелка будет показывать только азимут до цели.
    </div>
    <div v-if="error" style="color: red">
      <button @click="startWs">Получить данные по WS</button>
    </div>
    <div v-if="lastRawNmea" style="font-size: 0.9em; color: #888">
      Последнее NMEA: <code class="nmea-log">{{ lastRawNmea }}</code>
    </div>

    <l-map style="height: 400px" :zoom="17" :center="[decimalLat, decimalLon]">
      <l-tile-layer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
      />
      <l-marker :lat-lng="[targetLat, targetLon]"></l-marker>
      <l-marker :lat-lng="[decimalLat, decimalLon]"></l-marker>
    </l-map>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { LMap, LTileLayer, LMarker } from '@vue-leaflet/vue-leaflet'
import CircleNavigation from '@/components/navigate/CircleNavigation.vue'

const startedApp = ref(false)
function initApp() {
  enableCompass();
  startWs();
  startedApp.value = true;
}

// ======= Конфиг: целевая точка ввиде ДД =======
// const targetLat = ref(51.82611073873647) // Пример: Дальняя ёлка
// const targetLon = ref(55.17117162603348)

const targetLat = ref(51.8248574467772) // Пример: Ближняя ёлка
const targetLon = ref(55.17238619257802)

// ======= Данные =======
const decimalLat = ref(51.82463)
const decimalLon = ref(55.17293)
const redFlag = ref(false)
const azimuth = ref(0)
const distance = ref(0)
const heading = ref(0)
const lastRawNmea = ref('')
const error = ref('')
const compassPermissionGranted = ref(false)
const compassNotSupported = ref(false)
const compass = ref('')

// ======= Геодезия =======
function toRad(deg) {
  return (deg * Math.PI) / 180
}

function getDistance(lat1, lon1, lat2, lon2) {
  const R = 6371000
  const dLat = toRad(lat2 - lat1)
  const dLon = toRad(lon2 - lon1)
  lat1 = toRad(lat1)
  lat2 = toRad(lat2)
  const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  return R * c
}

function getAzimuth(lat1, lon1, lat2, lon2) {
  lat1 = toRad(lat1)
  lat2 = toRad(lat2)
  const dLon = toRad(lon2 - lon1)
  const x = Math.sin(dLon) * Math.cos(lat2)
  const y = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon)
  let azimut = (Math.atan2(x, y) * 180) / Math.PI
  return (azimut + 360) % 360
}

// ======= NMEA PARSER =======
function nmeaCoordToDecimal(value, direction, type) {
  // value в формате GGM.MMMMM или GGG.MMMMM
  if (!value || !direction) return null
  let deg, min
  if (type === 'lat') {
    deg = Number(value.slice(0, 2))
    min = Number(value.slice(2))
  } else if (type === 'lon') {
    deg = Number(value.slice(0, 3))
    min = Number(value.slice(3))
  } else return null
  let dec = deg + min / 60
  if (direction === 'S' || direction === 'W') dec = -dec
  return dec
}

function parseGNRMC(fields) {
  // $GNRMC,183640.000,V,5149.46942,N,05510.35937,E,0.205,173.40,270106,,,N*5A
  if (fields.length < 6) return null
  const lat = nmeaCoordToDecimal(fields[3], fields[4], 'lat')
  const lon = nmeaCoordToDecimal(fields[5], fields[6], 'lon')
  const red = fields[2] == 'A' ? true : false
  return { lat, lon, red }
}

function parseGNGGA(fields) {
  // $GNGGA,183640.000,5149.46942,N,05510.35937,E,0,3,,163.288,M,-13.288,M,,*73
  if (fields.length < 7) return null
  const lat = nmeaCoordToDecimal(fields[2], fields[3], 'lat')
  const lon = nmeaCoordToDecimal(fields[4], fields[5], 'lon')
  const red = fields[6] == '4' ? true : false
  return { lat, lon, red }
}

// ======= Компас =======
let orientationHandler = null

function handler(e) {
  compass.value = e.webkitCompassHeading || Math.abs(e.alpha - 360)
}
function enableCompass() {
  DeviceOrientationEvent.requestPermission()
    .then((response) => {
      if (response === 'granted') {
        window.addEventListener('deviceorientation', handler, true)
      } else {
        alert('has to be allowed!')
      }
    })
    .catch(() => alert('not supported'))

  if (
    typeof DeviceOrientationEvent !== 'undefined' &&
    typeof DeviceOrientationEvent.requestPermission === 'function'
  ) {
    DeviceOrientationEvent.requestPermission()
      .then((state) => {
        if (state === 'granted') {
          compassPermissionGranted.value = true
          startOrientationListener()
        } else {
          compassNotSupported.value = true
        }
      })
      .catch(() => {
        compassNotSupported.value = true
      })
  } else if (typeof DeviceOrientationEvent !== 'undefined') {
    compassPermissionGranted.value = true
    startOrientationListener()
  } else {
    compassNotSupported.value = true
  }
}

function startOrientationListener() {
  if (!orientationHandler) {
    orientationHandler = (event) => {
      if (typeof event.alpha === 'number')
        heading.value = event.webkitCompassHeading || Math.abs(event.alpha - 360)
    }
    window.addEventListener('deviceorientationabsolute', orientationHandler)
    window.addEventListener('deviceorientation', orientationHandler)
  }
}

// ======= WebSocket и расчёты =======
onMounted(() => {
  startWs();
})

function startWs() {
  let ws
  try {
    ws = new WebSocket('wss://ws.skiwatch.ru/frontend')
    ws.onmessage = (msg) => {
      let data = msg.data;

      // Если это Blob (Chrome при binary, ws сервер отсылает blob)
      if (data instanceof Blob) {
        data.text().then(function (str) {
          parseReceivedLine(str);
        });
        return;
      }
      // Если это ArrayBuffer (обычно не бывает, но для совместимости)
      if (data instanceof ArrayBuffer) {
        data = new TextDecoder('utf-8').decode(data);
      }

      // Если это строка, продолжаем как раньше!
      parseReceivedLine(data);
    }
    ws.onerror = (e) => {
      error.value = 'Ошибка WebSocket: ' + (e.message || 'Нет связи')
    }
    ws.onclose = () => {
      error.value = 'WebSocket закрыт.'
    }
  } catch (e) {
    error.value = 'Ошибка открытия WebSocket: ' + e.message
  }
}

function parseReceivedLine(lineRaw) {
  const line = lineRaw.trim();
  lastRawNmea.value = line;
  let parsed = null;
  if (line.startsWith('$GNRMC')) {
    parsed = parseGNRMC(line.split(','));
  } else if (line.startsWith('$GNGGA')) {
    parsed = parseGNGGA(line.split(','));
  } else if (line.startsWith('Hello from ESP32')) {
    console.log(line);
  }
  if (parsed && parsed.lat && parsed.lon) {
    decimalLat.value = parsed.lat;
    decimalLon.value = parsed.lon;
    if (parsed.red) redFlag.value = parsed.red;
    azimuth.value = getAzimuth(parsed.lat, parsed.lon, targetLat.value, targetLon.value);
    distance.value = Math.round(
      getDistance(parsed.lat, parsed.lon, targetLat.value, targetLon.value)
    );
    error.value = '';
  }
}

// ======= Вычисление угла стрелки =======
const arrowRotation = computed(() => {
  // Если компас не доступен, крутим просто по азимуту (на север экрана)
  if (!compassPermissionGranted.value || compassNotSupported.value) {
    return azimuth.value - heading.value
  } else {
    return azimuth.value - heading.value
  }
})


// ============================TEST=====================
function destPoint(lat1, lon1, azimuthDeg, distanceM) {
  const R = 6371000;
  const δ = distanceM / R;
  const φ1 = lat1 * Math.PI / 180;
  const λ1 = lon1 * Math.PI / 180;
  const θ = azimuthDeg * Math.PI / 180;
  const φ2 = Math.asin(Math.sin(φ1)*Math.cos(δ) + Math.cos(φ1)*Math.sin(δ)*Math.cos(θ));
  const λ2 = λ1 + Math.atan2(Math.sin(θ)*Math.sin(δ)*Math.cos(φ1),
    Math.cos(δ)-Math.sin(φ1)*Math.sin(φ2));
  return [φ2 * 180/Math.PI, λ2 * 180/Math.PI];
}

const testPoints = [
  [51.82459011565496, 55.17277563880518], // 40 метров на азимут 138
  [51.82479061411722, 55.17248355456815], // 10 метров на азимут 138
  [51.824850763514824, 55.17239592879003], // 1 метр на азимут 138
  [51.82485671161838, 55.17238726356148], // 11 сантиметра на азимут 138
  [51.82485677845099, 55.17238716619935], // 10 сантиметра на азимут 138
  [51.82485684528362, 55.17238706883722], // 9 сантиметра на азимут 138
  [51.82485691211624, 55.172386971475085], // 8 сантиметра на азимут 138
  [51.82485697894886, 55.17238687411296], // 7 сантиметра на азимут 138
  [51.824857045781485, 55.17238677675083], // 6 сантиметра на азимут 138
  [51.824857112614104, 55.17238667938868], // 5 сантиметра на азимут 138
  [51.824857179446724, 55.17238658202655], // 4 сантиметра на азимут 138
  [51.82485724627934, 55.17238648466442], // 3 сантиметра на азимут 138
  [51.82485731311197, 55.17238638730229], // 2 сантиметра на азимут 138
  [51.824857379944575, 55.17238628994015], // 1 сантиметра на азимут 138
];

const currentIdx = ref(0)
const tLat = ref(testPoints[currentIdx.value][0])
const tLon = ref(testPoints[currentIdx.value][1])

function nextPoint() {
  if (currentIdx.value < testPoints.length - 1) {
    currentIdx.value++
    tLat.value = testPoints[currentIdx.value][0]
    tLon.value = testPoints[currentIdx.value][1]
  }
}
function prevPoint() {
  if (currentIdx.value > 0) {
    currentIdx.value--
    tLat.value = testPoints[currentIdx.value][0]
    tLon.value = testPoints[currentIdx.value][1]
  }
}

</script>

<style scoped>
body {
  padding: 0;
  margin: 0;
}
html, body {
  height: 100%;
}

.start-app {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  padding: 0;
  margin: 0;
  border: none;
  text-decoration: none;
  background: var(--vt-c-black);
  color: var(--vt-c-white);
  font-weight: bold;
  font-size: 50px;
  z-index: 999999999;
}
.compass-app {
  max-width: 420px;
  margin: auto;
  text-align: center;
  //font-family: Arial;
}

.arrow-area {
  margin: 32px 0;
  font-size: 48px;
}

.arrow {
  display: inline-block;
  //transition: transform 0.3s;
  font-size: 72px;
  color: #1976d2;
}

button {
  margin: 8px 0;
  padding: 8px 16px;
  font-size: 1em;
}

.nmea-log {
  display: inline-block;
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  color: white;
}

#map { height: 600px; }
</style>
