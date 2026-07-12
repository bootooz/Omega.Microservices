<template>
  <div style="text-align:center;margin-top:20px;">
    <svg :width="size" :height="size" style="border:1px solid #ccc">
      <!-- Окружность по центру -->
      <circle :cx="center[0]" :cy="center[1]" :r="radius" fill="none" stroke="#aaa" stroke-width="2" />
      <!-- Линия от центра к цели -->
      <line
        :x1="center[0]" :y1="center[1]"
        :x2="targetXY[0]" :y2="targetXY[1]"
        stroke="blue" stroke-width="2"
      />
      <!-- Стрелка вверх (ваш heading) -->
      <polygon :points="arrowPoints" fill="orange" stroke="black" />
      <circle :cx="center[0]" :cy="center[1]" r="8" fill="green" />
      <circle :cx="targetXY[0]" :cy="targetXY[1]" r="8" fill="red" />
      <text :x="center[0] + 15" :y="center[1] - 10" fill="white" font-size="14">
        {{ "Дист.: " + (distanceToTarget/100).toFixed(2) }} м ({{distanceToTarget.toFixed(1)}}см)
      </text>
      <text :x="size-85" :y="30" fill="orange" font-size="14">
        Heading: {{ heading }}°
      </text>
      <text :x="center[0]-60" :y="20" fill="orange" font-size="13">
        Целевой азимут: {{ azimuthToTargetDeg.toFixed(1) }}°
      </text>
      <text :x="center[0]-60" :y="38" fill="orange" font-size="13">
        Δ от heading: {{ relativeAngleDeg.toFixed(1) }}°
      </text>
    </svg>
    <div style="margin-top:12px;">
      <strong>Ваша точка:</strong> {{ myPoint.join(', ') }}<br>
      <strong>Целевая точка:</strong> {{ targetPoint.join(', ') }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const size = 400
const radius = (size / 2) - 16
const minDistCm = 10

const props = defineProps({
  myPoint: { type: Array, required: true }, // [lat, lon] градусы
  targetPoint: { type: Array, required: true }, // [lat, lon] градусы
  heading: { type: Number, required: true } // градусы: 0 — север, по часовой
})

const center = [size / 2, size / 2]

// Геодезическое смещение (см)
function geoDeltaCm(latA, lonA, latB, lonB) {
  const R = 6371000
  const lat1 = latA * Math.PI / 180
  const lat2 = latB * Math.PI / 180
  const dLat = lat2 - lat1
  const dLon = (lonB - lonA) * Math.PI / 180
  const meanLat = (lat1 + lat2) / 2
  const dx = dLon * Math.cos(meanLat) * R * 100
  const dy = dLat * R * 100
  return [dx, dy]
}

// Азимут ОТ вашей точки К цели (градусы, из севера, по часовой)
function calcAzimuthDeg(latA, lonA, latB, lonB) {
  const phi1 = latA * Math.PI / 180, phi2 = latB * Math.PI / 180
  const lambda1 = lonA * Math.PI / 180, lambda2 = lonB * Math.PI / 180
  const y = Math.sin(lambda2 - lambda1) * Math.cos(phi2)
  const x = Math.cos(phi1) * Math.sin(phi2) - Math.sin(phi1) * Math.cos(phi2) * Math.cos(lambda2 - lambda1)
  let θ = Math.atan2(y, x) * 180 / Math.PI
  θ = (θ + 360) % 360 // в диапазоне [0..360)
  return θ
}

const relVec = computed(() => geoDeltaCm(props.myPoint[0], props.myPoint[1], props.targetPoint[0], props.targetPoint[1]))
const distanceToTarget = computed(() => {
  const [x, y] = relVec.value;
  return Math.sqrt(x * x + y * y)
})

const azimuthToTargetDeg = computed(() =>
  calcAzimuthDeg(props.myPoint[0], props.myPoint[1], props.targetPoint[0], props.targetPoint[1])
)

// ВАЖНО: В какую сторону от heading лежит цель? Это difference!
const relativeAngleDeg = computed(() => {
  let diff = azimuthToTargetDeg.value - props.heading
  if (diff > 180) diff -= 360
  if (diff < -180) diff += 360
  return diff
})
// Переводим в радианы для отрисовки (0 рад — верх, π/2 — вправо, π — вниз)
const relativeAngleRad = computed(() =>
  (-relativeAngleDeg.value + 90) * Math.PI / 180
)

// === Координаты целевой точки (на окружности/внутри) ===
const targetXY = computed(() => {
  const d = distanceToTarget.value
  const ang = relativeAngleRad.value
  // Только когда ближе minDistCm -- по прямой к центру, иначе на окружности
  const r = d > minDistCm ? radius : (radius * d / minDistCm)
  return [
    center[0] + r * Math.cos(ang),
    center[1] - r * Math.sin(ang) // ВНИМАНИЕ: SVG Y вниз!
  ]
})

// === Стрелка (heading/север всегда вверх) ===
const arrowLen = 36, arrowWidth = 16
const arrowHead = computed(() => {
  const cx = center[0], cy = center[1]
  return [
    [cx, cy - arrowLen], [cx - arrowWidth / 2, cy + 10], [cx + arrowWidth / 2, cy + 10]
  ]
})
const arrowPoints = computed(() =>
  arrowHead.value.map(pt => pt.join(',')).join(' ')
)
</script>
