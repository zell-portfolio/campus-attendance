const video = document.getElementById("video");
const canvas = document.getElementById("canvas");
const status = document.getElementById("status");
const btnAbsen = document.getElementById("btnAbsen");

let faceDetected = false;

// Load models face-api
async function loadModels() {
  const MODEL_URL = "/kampus-absensi/public/assets/models";

  await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
  await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
  await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

  status.innerText = "✅ Model AI berhasil dimuat. Siap scan wajah!";
  status.className = "mt-2 text-success";
}

// Start camera
async function startCamera() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { width: 640, height: 480 },
    });
    video.srcObject = stream;
  } catch (err) {
    console.error(err);
    status.innerText =
      "❌ Error: Tidak bisa akses kamera. Pastikan izin kamera diberikan.";
    status.className = "mt-2 text-danger";
  }
}

// Detect face
video.addEventListener("play", async () => {
  const displaySize = {
    width: video.width || 640,
    height: video.height || 480,
  };
  faceapi.matchDimensions(canvas, displaySize);

  setInterval(async () => {
    const detections = await faceapi
      .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks();

    if (detections) {
      faceDetected = true;
      status.innerText =
        '✅ Wajah terdeteksi! Silakan klik tombol "Kirim Absensi"';
      status.className = "mt-2 text-success fw-bold";
      if (btnAbsen) btnAbsen.disabled = false;
    } else {
      faceDetected = false;
      status.innerText =
        "️ Wajah tidak terdeteksi. Pastikan wajah terlihat jelas di kamera.";
      status.className = "mt-2 text-warning";
      if (btnAbsen) btnAbsen.disabled = true;
    }
  }, 500);
});

// Initialize when page loads
window.addEventListener("load", () => {
  loadModels().then(() => {
    startCamera();
  });
});
