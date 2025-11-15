const scanBtn = document.getElementById("scanFaceBtn");
const modal = document.getElementById("faceModal");
const closeBtn = document.getElementById("closeFaceModal");
const captureBtn = document.getElementById("captureFace");
const video = document.getElementById("videoFeed");
const patientPlaceholder = document.querySelector(".patient-placeholder");
const successMessage = document.getElementById("face-success-message");
const scanAnotherBtn = document.getElementById("scan-another-btn");

let stream;
let modelsLoaded = false;

function showToast(message, type = "info", duration = 3000) {
  const container =
    document.getElementById("toast-container") || createToastContainer();
  const toast = document.createElement("div");

  const iconClass = {
    success: "fas fa-check-circle",
    error: "fas fa-times-circle",
    warning: "fas fa-exclamation-triangle",
    info: "fas fa-info-circle",
  };

  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="${iconClass[type] || iconClass.info}"></i>
                       <span class="toast-message">${message}</span>`;

  container.prepend(toast);

  setTimeout(() => toast.classList.add("show"), 10);
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 400);
  }, duration);
}

function createToastContainer() {
  const container = document.createElement("div");
  container.id = "toast-container";
  document.body.appendChild(container);
  return container;
}

async function loadModels() {
  if (modelsLoaded) return;
  try {
    await faceapi.nets.tinyFaceDetector.loadFromUri("models/");
    await faceapi.nets.faceLandmark68Net.loadFromUri("models/");
    await faceapi.nets.faceRecognitionNet.loadFromUri("models/");
    modelsLoaded = true;
  } catch (err) {
    console.error(err);
    showToast("Failed to load face models.", "error");
  }
}

scanBtn.onclick = async () => {
  modal.style.display = "flex";
  successMessage.style.display = "none";
  scanAnotherBtn.style.display = "none";
  patientPlaceholder.innerHTML = `<div class="empty-message">
        <p>No patient data available</p>
        <p>Scan a patient to view their details</p>
    </div>`;

  try {
    await loadModels();
    await startCamera();
  } catch (err) {
    console.error(err);
    showToast("Failed to access camera.", "error");
  }
};

closeBtn.onclick = () => {
  stopCamera();
  modal.style.display = "none";
};

function stopCamera() {
  if (stream) stream.getTracks().forEach((t) => t.stop());
}

async function startCamera() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
  } catch (err) {
    console.error(err);
    showToast("Failed to access camera.", "error");
  }
}

captureBtn.onclick = async () => {
  try {
    const detection = await faceapi
      .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!detection) {
      showToast("No face detected. Try again.", "warning");
      return;
    }

    const response = await fetch("php/fetch_faces.php");
    const knownFaces = await response.json();

    const labeledDescriptors = knownFaces
      .filter((f) => f.face_encoding)
      .map((f) => {
        let encodingData = f.face_encoding;
        try {
          if (typeof encodingData === "string")
            encodingData = JSON.parse(encodingData);
          if (Array.isArray(encodingData[0])) encodingData = encodingData[0];
        } catch (e) {
          console.error("Error parsing encoding for patient", f.patient_id, e);
          return null;
        }
        return new faceapi.LabeledFaceDescriptors(f.patient_id.toString(), [
          new Float32Array(encodingData),
        ]);
      })
      .filter(Boolean);

    const faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.32);
    const bestMatch = faceMatcher.findBestMatch(detection.descriptor);

    if (bestMatch.label !== "unknown" && bestMatch.distance < 0.35) {
      stopCamera();
      modal.style.display = "none";
      showPatientDetails(bestMatch.label);
    } else {
      showToast("No matching patient found.", "warning");
    }
  } catch (err) {
    console.error(err);
    showToast("Error during face detection.", "error");
  }
};

async function showPatientDetails(patient_id) {
  try {
    const res = await fetch(
      `/medivaults/php/fetch_patient.php?id=${patient_id}`
    );
    const data = await res.json();

    if (data.error) {
      showToast(data.error, "error");
      return;
    }

    patientPlaceholder.innerHTML = `
            <div class="patient-info">
                <h3>${data.full_name}</h3>
                <p>Patient ID: ${data.patient_id}</p>
                <h4>Personal Information</h4>
                <p><strong>Age:</strong> ${data.age || "N/A"}</p>
                <p><strong>Gender:</strong> ${data.gender || "N/A"}</p>
                <p><strong>Blood Type:</strong> ${data.blood_type || "N/A"}</p>
                <p><strong>Contact:</strong> ${data.contact || "N/A"}</p>
                <p><strong>Emergency Contact:</strong> ${
                  data.emergency_contact || "N/A"
                }</p>
                <p><strong>Status:</strong> ${data.status || "N/A"}</p>
                <p><strong>Address:</strong> ${data.address || "N/A"}</p>
                <h4>Medical & Biometric Status</h4>
                <p><strong>Registration Date:</strong> ${
                  data.registration_date
                    ? new Date(data.registration_date).toLocaleDateString()
                    : "N/A"
                }</p>
                <p><strong>Biometric Enrolled:</strong> ${
                  data.biometric_enrolled ? "Enrolled" : "Not Enrolled"
                }</p>
                <p><strong>Last Visit:</strong> ${
                  data.last_visit
                    ? new Date(data.last_visit).toLocaleDateString()
                    : "N/A"
                }</p>
                <p><strong>Allergies:</strong> ${data.allergies || "None"}</p>
                <p><strong>Chronic Conditions:</strong> ${
                  data.chronic_conditions || "None"
                }</p>
                <p><strong>Medical History:</strong> ${
                  data.medical_history || "None"
                }</p>
            </div>
        `;

    successMessage.style.display = "block";
    scanAnotherBtn.style.display = "inline-block";
  } catch (err) {
    console.error(err);
    showToast("Failed to fetch patient details.", "error");
  }
}

scanAnotherBtn.addEventListener("click", () => {
  successMessage.style.display = "none";
  scanAnotherBtn.style.display = "none";
  patientPlaceholder.innerHTML = `<div class="empty-message">
        <p>No patient data available</p>
        <p>Scan a patient to view their details</p>
    </div>`;
  modal.style.display = "flex";
  startCamera();
});
