<?php



require_once 'php/includes/functions.php'; 


$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<div id="register-patient-modal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Register New Patient</h3>
        <p class="modal-subtitle">Enter patient information to create a new record</p>
      </div>
      <button class="modal-close-btn" data-modal-id="register-patient-modal"><i class="fas fa-times"></i></button>
    </div>
    
    <form id="register-patient-form" class="modal-form" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="action" value="create">
      <input type="hidden" id="face-descriptor-input" name="face_descriptor">
      <input type="hidden" id="face-image-base64" name="face_image_base64">

      <div class="form-row-2">
        <div class="form-group">
          <label for="reg-patient-name">Full Name *</label>
          <input type="text" id="reg-patient-name" name="name" placeholder="Enter patient name" required>
        </div>
        <div class="form-group">
          <label for="reg-age">Age *</label>
          <input type="number" id="reg-age" name="age" placeholder="Enter age" min="1" max="120" required>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label for="reg-gender">Gender *</label>
          <select id="reg-gender" name="gender" required>
            <option value="">Select gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label for="reg-contact">Contact Number</label>
          <input type="text" id="reg-contact" name="contact" placeholder="Enter phone number">
        </div>
      </div>

      <div class="form-group">
        <label for="reg-address">Address</label>
        <input type="text" id="reg-address" name="address" placeholder="Enter full address">
      </div>

      <div class="form-row-2 form-group">
        <div class="form-group">
          <label for="reg-blood-type">Blood Type</label>
          <input type="text" id="reg-blood-type" name="blood_type" placeholder="e.g., O+, A-">
        </div>
        <div class="form-group">
          <label for="reg-emergency-contact">Emergency Contact</label>
          <input type="text" id="reg-emergency-contact" name="emergency_contact" placeholder="Emergency phone number">
        </div>
      </div>

      <div class="form-section">
        <label>Biometric Data</label>
        <div class="biometric-buttons">
          <button type="button" class="btn-secondary" id="capture-fingerprint-btn">
            <i class="fas fa-fingerprint"></i> Capture Fingerprint (Simulated)
          </button>
          <button type="button" class="btn-secondary" id="open-face-capture-modal">
            <i class="fas fa-camera"></i> Capture Face <span id="face-captured-status" class="empty">(Not Captured)</span>
          </button>
        </div>
      </div>

                  <div class="form-group">
        <label for="reg-medical-history">Medical History</label>
        <textarea id="reg-medical-history" name="medical_history" rows="2" placeholder="Enter relevant past surgeries, hospitalizations, or general history..."></textarea>
      </div>
      
            <div class="form-row-2">
                <div class="form-group">
                    <label for="reg-allergies">Allergies</label>
                    <textarea id="reg-allergies" name="allergies" rows="2" placeholder="List drug, food, or environmental allergies..."></textarea>
                </div>
                <div class="form-group">
                    <label for="reg-chronic-condition">Chronic Conditions</label>
                    <textarea id="edit-chronic-conditions" name="chronic_conditions" rows="2" placeholder="List conditions like Diabetes, Hypertension, Asthma..."></textarea>

                </div>
            </div>
                  <div class="modal-footer">
        <button type="button" class="btn-cancel" data-modal-id="register-patient-modal">Cancel</button>
        <button type="submit" class="btn-primary-modal" id="register-submit-btn">Register Patient</button>
      </div>
    </form>
  </div>
</div>


<div id="face-capture-modal" class="modal-overlay">
  <div class="modal-content face-modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Capture Patient's Face</h3>
      <button class="modal-close-btn" id="close-face-capture-btn"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="face-capture-container">
      <video id="webcam-video" autoplay muted playsinline style="transform: scaleX(-1);"></video>
      
      <canvas id="face-capture-canvas" style="display: none;"></canvas>

      <img id="captured-image-preview" style="display: none;" alt="Captured Face Image">
      
      <div class="face-capture-buttons">
        <button type="button" id="start-camera-btn" class="btn-primary-modal"><i class="fas fa-video"></i> Start Camera</button>
        <button type="button" id="take-photo-btn" class="btn-secondary" style="display: none;"><i class="fas fa-camera"></i> Take Picture</button>
        <button type="button" id="retake-photo-btn" class="btn-secondary" style="display: none;"><i class="fas fa-redo"></i> Retake</button>
        <button type="button" id="confirm-capture-btn" class="btn-primary-modal" style="display: none;"><i class="fas fa-check"></i> Confirm & Save</button>
      </div>

      <p id="face-capture-status-text" class="muted">Click 'Start Camera' to begin.</p>
    </div>
  </div>
</div>

<div id="edit-patient-modal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Edit Patient Record</h3>
        <p class="modal-subtitle">Patient ID: <strong id="edit-patient-display-id">P00X</strong> - Modify patient details</p>
      </div>
      <button class="modal-close-btn" data-modal-id="edit-patient-modal"><i class="fas fa-times"></i></button>
    </div>
    
    <form id="edit-patient-form" class="modal-form" method="POST">
      <input type="hidden" id="edit-patient-id-input" name="id"> 
      <input type="hidden" name="action" value="update">
        <input type="hidden" id="edit-face-image-path" name="face_image_path">
     <input type="hidden" id="edit-face-encoding" name="face_encoding">

      <div class="form-row-2">
        <div class="form-group">
          <label for="edit-full-name">Full Name *</label>
          <input type="text" id="edit-full-name" name="full_name" required>
        </div>
        <div class="form-group">
          <label for="edit-age">Age *</label>
          <input type="number" id="edit-age" name="age" min="1" max="120" required>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label for="edit-gender">Gender *</label>
          <select id="edit-gender" name="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label for="edit-contact-number">Contact Number</label>
          <input type="text" id="edit-contact-number" name="contact_number">
        </div>
      </div>

      <div class="form-group">
        <label for="edit-address">Address</label>
        <input type="text" id="edit-address" name="address">
      </div>

      <div class="form-row-3 form-group">
        <div class="form-group">
          <label for="edit-blood-type">Blood Type</label>
          <input type="text" id="edit-blood-type" name="blood_type">
        </div>
        <div class="form-group">
          <label for="edit-emergency-contact">Emergency Contact</label>
          <input type="text" id="edit-emergency-contact" name="emergency_contact">
        </div>
        <div class="form-group">
          <label for="edit-status">Status</label>
          <select id="edit-status" name="status" required>
            <option value="Active">Active</option>
            <option value="Discharged">Discharged</option>
            <option value="On Hold">On Hold</option>
          </select>
        </div>
      </div>

      <div class="form-group form-section">
        <label for="edit-medical-history">Medical History</label>
        <textarea id="edit-medical-history" name="medical_history" rows="2" placeholder="Enter relevant past history..."></textarea>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label for="edit-allergies">Allergies</label>
          <textarea id="edit-allergies" name="allergies" rows="2" placeholder="List allergies..."></textarea>
        </div>
        <div class="form-group">
          <label for="edit-chronic-conditions">Chronic Conditions</label>
          <textarea id="edit-chronic-conditions" name="chronic_conditions" rows="2" placeholder="List conditions like Diabetes, Hypertension, Asthma..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" data-modal-id="edit-patient-modal">Cancel</button>
        <button type="submit" class="btn-primary-modal" id="edit-submit-btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>


<div id="view-patient-modal" class="modal-overlay">
  <div class="view-modal-content">
    <div class="view-modal-header">
      <div>
        <h3 class="modal-title" id="view-patient-name">Patient Name</h3>
        <span class="modal-subtitle">Patient ID: <strong id="view-patient-id">P00X</strong></span>
      </div>
      <button class="modal-close-btn" data-modal-id="view-patient-modal"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="view-modal-body">
      <div class="info-section">
        <h4><i class="fas fa-info-circle icon"></i> Personal Information</h4>
        <div class="info-row-pair">
          <div class="info-row">
            <label>Age</label><span id="view-age"></span>
          </div>
          <div class="info-row">
            <label>Gender</label><span id="view-gender"></span>
          </div>
          <div class="info-row">
            <label>Blood Type</label><span id="view-blood-type"></span>
          </div>
        </div>
        <div class="info-row-pair">
          <div class="info-row">
            <label>Contact</label><span id="view-contact"></span>
          </div>
          <div class="info-row">
            <label>Emergency Contact</label><span id="view-emergency-contact"></span>
          </div>
          <div class="info-row">
            <label>Status</label><span class="status-badge" id="view-status"></span>
          </div>
        </div>
        <div class="info-row-full">
          <label>Address</label><p id="view-address"></p>
        </div>
      </div>

      <div class="info-section">
        <h4><i class="fas fa-heartbeat icon"></i> Medical & Biometric Status</h4>
        <div class="info-row-pair">
          <div class="info-row">
            <label>Registration Date</label><span id="view-registration-date"></span>
          </div>
          <div class="info-row">
            <label>Biometric Enrolled</label><span id="view-biometric-status" class="enrolled"></span>
          </div>
          <div class="info-row">
            <label>Last Visit</label><span id="view-last-visit"></span>
          </div>
        </div>
                
                        <div class="info-row-full">
          <label>Allergies</label><p id="view-allergies"></p>
        </div>
                <div class="info-row-full">
          <label>Chronic Conditions</label><p id="view-chronic-condition"></p>         </div>
                        <div class="info-row-full">
          <label>Medical History</label><p id="view-medical-history"></p>
        </div>
        <div class="info-row-full">
          <label>Stored Face Image</label>
          <img id="view-face-image" src="assets/images/placeholder.png" alt="Patient Face" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 5px;">
        </div>
      </div>
      </div>

    <div class="view-modal-footer">
      <button type="button" class="btn-secondary" id="edit-patient-btn"><i class="fas fa-edit"></i> Edit Record</button>
      <button type="button" class="btn-cancel" data-modal-id="view-patient-modal">Close</button>
    </div>
  </div>
</div>