const registerModal = document.getElementById("register-patient-modal");
const faceCaptureModal = document.getElementById("face-capture-modal");
const editModal = document.getElementById("edit-patient-modal");

const webcamVideo = document.getElementById("webcam-video");
const captureCanvas = document.getElementById("face-capture-canvas");
const capturedImagePreview = document.getElementById("captured-image-preview");
const faceDescriptorInput = document.getElementById("face-descriptor-input");
const faceImageBase64Input = document.getElementById("face-image-base64");
const faceCapturedStatus = document.getElementById("face-captured-status");
const faceCaptureStatusText = document.getElementById(
  "face-capture-status-text"
);

const startCameraBtn = document.getElementById("start-camera-btn");
const takePhotoBtn = document.getElementById("take-photo-btn");
const retakePhotoBtn = document.getElementById("retake-photo-btn");
const confirmCaptureBtn = document.getElementById("confirm-capture-btn");
const closeFaceCaptureBtn = document.getElementById("close-face-capture-btn");

const registerForm = document.getElementById("register-patient-form");
const editForm = document.getElementById("edit-patient-form");
const patientIdInput = document.getElementById("edit-patient-id-input");
const editFaceImagePathInput = document.getElementById("edit-face-image-path");
const editFaceEncodingInput = document.getElementById("edit-face-encoding");

let currentStream = null;
let faceDescriptor = null;
let faceImageBase64 = null;
const MODEL_URL = "./models";

async function loadFaceModels() {
  try {
    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
    console.log("Face-API Models Loaded.");
  } catch (error) {
    console.error("Failed to load face-api models:", error);
    showToast("Failed to load facial recognition models.", "error");
  }
}

async function startCamera() {
  faceCaptureStatusText.textContent = "Starting camera...";
  try {
    currentStream = await navigator.mediaDevices.getUserMedia({ video: true });
    webcamVideo.srcObject = currentStream;
    webcamVideo.style.display = "block";
    capturedImagePreview.style.display = "none";
    startCameraBtn.style.display = "none";
    takePhotoBtn.style.display = "block";
    retakePhotoBtn.style.display = "none";
    confirmCaptureBtn.style.display = "none";
    faceCaptureStatusText.textContent = 'Camera ready. Click "Take Picture".';
  } catch (err) {
    console.error("Camera access denied:", err);
    showToast("Camera access denied.", "error");
    faceCaptureStatusText.textContent = "Camera access failed.";
    startCameraBtn.style.display = "block";
    takePhotoBtn.style.display = "none";
  }
}

function stopCamera() {
  if (currentStream) {
    currentStream.getTracks().forEach((track) => track.stop());
    currentStream = null;
  }
  webcamVideo.style.display = "none";
  startCameraBtn.style.display = "block";
  takePhotoBtn.style.display = "none";
  retakePhotoBtn.style.display = "none";
  confirmCaptureBtn.style.display = "none";
  faceCaptureStatusText.textContent = 'Click "Start Camera" to begin.';
}

async function takePhoto() {
  if (!currentStream) return;
  faceCaptureStatusText.textContent = "Processing face...";
  takePhotoBtn.disabled = true;

  const context = captureCanvas.getContext("2d");
  captureCanvas.width = webcamVideo.videoWidth;
  captureCanvas.height = webcamVideo.videoHeight;
  context.save();
  context.scale(-1, 1);
  context.drawImage(
    webcamVideo,
    -captureCanvas.width,
    0,
    captureCanvas.width,
    captureCanvas.height
  );
  context.restore();

  try {
    const detection = await faceapi
      .detectSingleFace(captureCanvas, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!detection) {
      showToast("No face detected. Try again.", "warning");
      faceCaptureStatusText.textContent = "No face detected.";
      takePhotoBtn.disabled = false;
      return;
    }

    faceDescriptor = Array.from(detection.descriptor);
    faceImageBase64 = captureCanvas.toDataURL("image/jpeg");
    stopCamera();
    capturedImagePreview.src = faceImageBase64;
    capturedImagePreview.style.display = "block";
    takePhotoBtn.style.display = "none";
    retakePhotoBtn.style.display = "block";
    confirmCaptureBtn.style.display = "block";
    takePhotoBtn.disabled = false;
    faceCaptureStatusText.textContent = "Captured. Confirm or Retake.";
  } catch (error) {
    console.error("Face detection error:", error);
    showToast("Error during face processing.", "error");
    faceCaptureStatusText.textContent = "Error detected.";
    takePhotoBtn.disabled = false;
  }
}

function retakePhoto() {
  faceDescriptor = null;
  faceImageBase64 = null;
  capturedImagePreview.style.display = "none";
  startCamera();
}

function confirmCapture() {
  if (faceDescriptor && faceImageBase64) {
    faceDescriptorInput.value = JSON.stringify(faceDescriptor);
    faceImageBase64Input.value = faceImageBase64;
    faceCapturedStatus.textContent = "(Captured)";
    faceCapturedStatus.style.color = "#4CAF50";
    faceCaptureModal.classList.remove("active");
    stopCamera();
  } else {
    showToast("Capture a photo first.", "warning");
  }
}

async function refreshPatientTable() {
  try {
    const tableContainer = document.getElementById("patients-tables");
    const response = await fetch("php/api/patient.php?action=list");
    const result = await response.json();

    if (result.success && Array.isArray(result.data)) {
      const tbody = tableContainer.querySelector("tbody");
      tbody.innerHTML = "";

      result.data.forEach((p) => {
        tbody.insertAdjacentHTML(
          "beforeend",
          `
          <tr>
            <td>${p.patient_id || ""}</td>
            <td>${p.name || ""}</td>
            <td>${p.age || ""}</td>
            <td>${p.gender || ""}</td>
            <td>${p.contact || ""}</td>
            <td>${p.status || ""}</td>
            <td class="actions">
              <button class="btn-view" data-id="${
                p.id
              }"><i class="fa fa-eye"></i></button>
              <button class="btn-edit" data-id="${
                p.id
              }"><i class="fa fa-edit"></i></button>
              <button class="btn-delete" data-id="${
                p.id
              }"><i class="fa fa-trash"></i></button>
            </td>
          </tr>
        `
        );
      });

      if (typeof filterPatients === "function") {
        filterPatients();
      }
    } else {
      showToast("Error refreshing patient list.", "error");
    }
  } catch (error) {
    console.error("Error refreshing table:", error);
    showToast("Failed to refresh patient list.", "error");
  }
}

async function fetchPatientDetails(patientId) {
  try {
    const response = await fetch(
      `php/api/patient.php?action=read&id=${patientId}`
    );
    const result = await response.json();

    if (!result.success) {
      showToast(result.message || "Failed to fetch patient.", "error");
      return;
    }

    const p = result.data;
    document.getElementById("view-patient-name").textContent =
      p.name || "Unknown";
    document.getElementById("view-patient-id").textContent =
      p.patient_id || "N/A";
    document.getElementById("view-age").textContent = p.age || "N/A";
    document.getElementById("view-gender").textContent = p.gender || "N/A";
    document.getElementById("view-blood-type").textContent =
      p.blood_type || "N/A";
    document.getElementById("view-contact").textContent = p.contact || "N/A";
    document.getElementById("view-emergency-contact").textContent =
      p.emergency_contact || "N/A";
    document.getElementById("view-address").textContent = p.address || "N/A";
    document.getElementById("view-registration-date").textContent =
      p.registration_date
        ? new Date(p.registration_date).toLocaleDateString()
        : "N/A";
    document.getElementById("view-last-visit").textContent = p.last_visit
      ? new Date(p.last_visit).toLocaleDateString()
      : "N/A";
    document.getElementById("view-allergies").textContent =
      p.allergies || "None";
    document.getElementById("view-chronic-condition").textContent =
      p.chronic_condition || p.chronic_conditions || "None";
    document.getElementById("view-medical-history").textContent =
      p.medical_history || "None";
    const statusEl = document.getElementById("view-status");
    statusEl.textContent = p.status || "Unknown";
    statusEl.className =
      "status-badge " +
      (p.status ? p.status.toLowerCase().replace(" ", "-") : "");
    document.getElementById("view-biometric-status").textContent =
      p.biometric_enrolled ? "Enrolled" : "Not Enrolled";
    document.getElementById("view-face-image").src =
      p.face_image_path && p.face_image_path !== ""
        ? p.face_image_path
        : "assets/images/placeholder.png";
    document.getElementById("view-patient-modal").classList.add("active");
  } catch (error) {
    console.error("View patient error:", error);
    showToast("Error loading patient details.", "error");
  }
}

async function fetchPatientForEdit(id) {
  try {
    const response = await fetch(`php/api/patient.php?action=read&id=${id}`);
    const result = await response.json();

    if (result.success) {
      const p = result.data;
      patientIdInput.value = p.id;
      editForm.elements["full_name"].value = p.name || "";
      editForm.elements["age"].value = p.age || "";
      editForm.elements["gender"].value = p.gender || "";
      editForm.elements["contact_number"].value = p.contact || "";
      editForm.elements["address"].value = p.address || "";
      editForm.elements["blood_type"].value = p.blood_type || "";
      editForm.elements["emergency_contact"].value = p.emergency_contact || "";
      editForm.elements["medical_history"].value = p.medical_history || "";
      editForm.elements["status"].value = p.status || "Active";
      editForm.elements["allergies"].value = p.allergies || "";
      editForm.elements["chronic_conditions"].value =
        p.chronic_conditions || "";
      document.getElementById("edit-patient-display-id").textContent =
        p.patient_id;

      editFaceImagePathInput.value = p.face_image_path || "";
      editFaceEncodingInput.value = p.face_encoding || "";

      editModal.classList.add("active");
    } else {
      showToast(result.message, "error");
    }
  } catch (error) {
    console.error("Error fetching patient data for edit:", error);
    showToast("Error fetching patient data.", "error");
  }
}

document.addEventListener("DOMContentLoaded", () => {
  loadFaceModels();

  document
    .getElementById("open-register-modal")
    .addEventListener("click", () => {
      registerModal.classList.add("active");
      registerForm.reset();
      faceCapturedStatus.textContent = "(Not Captured)";
      faceCapturedStatus.style.color = "";
      faceDescriptorInput.value = "";
      faceImageBase64Input.value = "";
    });

  document.querySelectorAll("[data-modal-id]").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const modalId = e.target
        .closest("[data-modal-id]")
        .getAttribute("data-modal-id");
      document.getElementById(modalId).classList.remove("active");
      if (modalId === "face-capture-modal") stopCamera();
    });
  });

  document
    .getElementById("open-face-capture-modal")
    .addEventListener("click", () => {
      faceCaptureModal.classList.add("active");
      startCamera();
    });

  closeFaceCaptureBtn.addEventListener("click", () => {
    stopCamera();
    faceCaptureModal.classList.remove("active");
  });

  startCameraBtn.addEventListener("click", startCamera);
  takePhotoBtn.addEventListener("click", takePhoto);
  retakePhotoBtn.addEventListener("click", retakePhoto);
  confirmCaptureBtn.addEventListener("click", confirmCapture);

  registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const submitBtn = document.getElementById("register-submit-btn");
    submitBtn.disabled = true;
    submitBtn.textContent = "Registering...";

    try {
      const data = Object.fromEntries(new FormData(registerForm).entries());
      const response = await fetch("php/api/patient.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      const result = await response.json();
      if (result.success) {
        showToast(result.message, "success");
        registerModal.classList.remove("active");
        await refreshPatientTable();
      } else {
        showToast(result.message || "Registration failed.", "error");
      }
    } catch (error) {
      console.error("Registration error:", error);
      showToast("Network error during registration.", "error");
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = "Register Patient";
    }
  });

  editForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const submitBtn = document.getElementById("edit-submit-btn");
    submitBtn.disabled = true;
    submitBtn.textContent = "Saving...";

    try {
      const formData = new FormData(editForm);

      if (faceImageBase64 && faceDescriptor) {
        formData.set("face_image_base64", faceImageBase64);
        formData.set("face_encoding", JSON.stringify(faceDescriptor));
      } else {
        formData.set("face_image_path", editFaceImagePathInput.value);
        formData.set("face_encoding", editFaceEncodingInput.value);
      }

      const data = Object.fromEntries(formData.entries());
      data.name = data.full_name;
      delete data.full_name;
      data.contact = data.contact_number;
      delete data.contact_number;

      const response = await fetch("php/api/patient.php", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      const result = await response.json();

      if (result.success) {
        showToast(result.message, "success");
        editModal.classList.remove("active");
        await refreshPatientTable();
      } else {
        showToast(result.message || "Failed to update record.", "error");
      }
    } catch (error) {
      console.error("Update error:", error);
      showToast("Network error during update.", "error");
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = "Save Changes";
    }
  });

  document
    .getElementById("patients-tables")
    .addEventListener("click", async (e) => {
      const btn = e.target.closest("button");
      if (!btn) return;
      const id = btn.dataset.id;
      if (btn.classList.contains("btn-view")) await fetchPatientDetails(id);
      if (btn.classList.contains("btn-edit")) await fetchPatientForEdit(id);
    });
});

document.addEventListener("DOMContentLoaded", () => {
  const table = document.getElementById("patients-tables");

  table.addEventListener("click", async (e) => {
    if (e.target.closest(".btn-delete")) {
      const btn = e.target.closest(".btn-delete");
      const id = btn.getAttribute("data-id");

      if (!id) return alert("Invalid patient ID.");
      if (!confirm("Are you sure you want to delete this patient?")) return;

      try {
        const response = await fetch("php/api/patient.php", {
          method: "DELETE",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id }),
        });

        const result = await response.json();
        if (result.success) {
          alert(result.message);
          btn.closest("tr").remove();
        } else {
          alert(result.message || "Delete failed.");
        }
      } catch (err) {
        console.error("Delete error:", err);
        alert("Error deleting patient.");
      }
    }
  });

  const searchInput = document.getElementById("search-input");
  const genderToggle = document.getElementById("gender-filter-toggle");
  const genderMenu = document.getElementById("gender-dropdown-menu");
  const genderText = document.getElementById("gender-filter-text");

  function filterPatients() {
    const search = searchInput.value.trim().toLowerCase();
    const gender =
      genderText.textContent === "All Genders"
        ? ""
        : genderText.textContent.toLowerCase();

    const rows = document.querySelectorAll("#patients-tables tbody tr");
    rows.forEach((row) => {
      const id = row.querySelector("td:nth-child(1)").textContent.toLowerCase();
      const name = row
        .querySelector("td:nth-child(2)")
        .textContent.toLowerCase();
      const contact = row
        .querySelector("td:nth-child(5)")
        .textContent.toLowerCase();
      const rowGender = row
        .querySelector("td:nth-child(4)")
        .textContent.toLowerCase();

      const matchesSearch =
        id.includes(search) ||
        name.includes(search) ||
        contact.includes(search);
      const matchesGender = !gender || rowGender === gender;

      row.style.display = matchesSearch && matchesGender ? "" : "none";
    });
  }

  genderToggle.addEventListener("click", () => {
    genderMenu.classList.toggle("show");
  });

  document.addEventListener("click", (event) => {
    if (
      !genderMenu.contains(event.target) &&
      !genderToggle.contains(event.target)
    ) {
      genderMenu.classList.remove("show");
    }
  });

  genderMenu.querySelectorAll(".dropdown-item").forEach((item) => {
    item.addEventListener("click", () => {
      genderMenu
        .querySelectorAll(".dropdown-item")
        .forEach((i) => i.classList.remove("active"));
      item.classList.add("active");
      genderText.textContent = item.textContent;
      genderMenu.classList.remove("show");
      filterPatients();
    });
  });

  searchInput.addEventListener("input", filterPatients);
});
