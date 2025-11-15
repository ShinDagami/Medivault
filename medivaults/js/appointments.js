document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("scheduleModal");
  const btn = document.getElementById("scheduleAppointmentBtn");
  const span = modal.querySelector(".close-btn");
  const cancelBtn = document.getElementById("cancelSchedule");
  const form = document.getElementById("scheduleAppointmentForm");
  const patientSelect = document.getElementById("patient_id");
  const doctorSelect = document.getElementById("doctor_id");
  const appointmentList = document.getElementById("appointment-list");
  const appointmentsDateDisplay = document.getElementById("appointments-date");
  const queueContainer = document.getElementById("current-queue-container");
  const today = new Date();

  let selectedDate = today;

  const formatDateForAPI = (date) => {
    if (isNaN(date.getTime())) return "";
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  };

  const formatDateForDisplay = (date) => {
    if (isNaN(date.getTime())) return "Selected Date";
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  };

  const toggleModal = (show) => {
    modal.style.display = show ? "block" : "none";
    if (show) {
      form.reset();

      document.getElementById("appointment_date").value =
        formatDateForAPI(selectedDate);
      document.getElementById("appointment-message").textContent = "";
    }
  };

  const showModalMessage = (message, isError = false) => {
    const msgElement = document.getElementById("appointment-message");
    msgElement.textContent = message;
    msgElement.style.color = isError
      ? "var(--color-danger)"
      : "var(--color-success)";
  };

  const getStatusClass = (status) => {
    switch (status) {
      case "Pending":
        return "status-pending";
      case "Ongoing":
        return "status-ongoing";
      case "Completed":
        return "status-completed";
      case "Cancelled":
        return "status-cancelled";
      default:
        return "status-default";
    }
  };

  let fetchAppointments;
  let fetchCurrentQueue;

  const updateStatus = async (id, status, current_date_str) => {
    try {
      const formData = new URLSearchParams();
      formData.append("action", "update_appointment_status");
      formData.append("appointment_id", id);
      formData.append("status", status);

      const response = await fetch("php/appointments_handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        await fetchAppointments(current_date_str);

        await fetchCurrentQueue();
      } else {
        alert(`Error updating status: ${result.message}`);

        await fetchAppointments(current_date_str);
        await fetchCurrentQueue();
      }
    } catch (error) {
      console.error("Network Error during status update:", error);
      alert("Failed to connect to the server for status update.");
      fetchAppointments(current_date_str);
      fetchCurrentQueue();
    }
  };

  const deleteAppointment = async (id, current_date_str) => {
    if (
      !confirm(
        `Are you sure you want to permanently delete appointment ID ${id}? This cannot be undone.`
      )
    ) {
      return;
    }

    try {
      const formData = new URLSearchParams();
      formData.append("action", "delete_appointment");
      formData.append("appointment_id", id);

      const response = await fetch("php/appointments_handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        await fetchAppointments(current_date_str);
        await fetchCurrentQueue();
      } else {
        alert(`Error deleting appointment: ${result.message}`);
      }
    } catch (error) {
      console.error("Network Error during deletion:", error);
      alert("Failed to connect to the server for deletion.");
    }
  };

  btn.onclick = () => {
    toggleModal(true);
    loadDropdowns();
  };

  span.onclick = () => toggleModal(false);
  cancelBtn.onclick = () => toggleModal(false);
  window.onclick = (event) => {
    if (event.target == modal) {
      toggleModal(false);
    }
  };

  const loadDropdowns = async () => {
    fetch("php/appointments_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "action=fetch_patients",
    })
      .then((res) => res.json())
      .then((data) => {
        patientSelect.innerHTML = '<option value="">Select patient</option>';
        if (data.success && data.data) {
          data.data.forEach((p) => {
            patientSelect.innerHTML += `<option value="${p.patient_id}">${p.name} (ID: ${p.patient_id})</option>`;
          });
        }
      });

    fetch("php/appointments_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "action=fetch_doctors",
    })
      .then((res) => res.json())
      .then((data) => {
        doctorSelect.innerHTML = '<option value="">Select doctor</option>';
        if (data.success && data.data) {
          data.data.forEach((d) => {
            doctorSelect.innerHTML += `<option value="${d.id}">Dr. ${d.name}</option>`;
          });
        }
      });
  };

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    showModalMessage("Scheduling...", false);

    const formData = new FormData(form);
    formData.append("action", "schedule_appointment");

    try {
      const response = await fetch("php/appointments_handler.php", {
        method: "POST",
        body: new URLSearchParams(formData),
      });

      const result = await response.json();

      if (result.success) {
        showModalMessage(result.message, false);

        fetchAppointments(formatDateForAPI(selectedDate));
        fetchCurrentQueue();
        setTimeout(() => toggleModal(false), 1500);
      } else {
        showModalMessage(result.message, true);
      }
    } catch (error) {
      showModalMessage("An unexpected error occurred.", true);
    }
  });

  const calendarContainer = document.getElementById("calendar-container");

  const updateSelectedDateOnNav = (newCalendarMonthDate) => {
    const currentDay = selectedDate.getDate();
    const newYear = newCalendarMonthDate.getFullYear();
    const newMonth = newCalendarMonthDate.getMonth();
    const attemptedDate = new Date(newYear, newMonth, currentDay);

    if (attemptedDate.getMonth() === newMonth) {
      selectedDate = attemptedDate;
    } else {
      selectedDate = new Date(newYear, newMonth + 1, 0);
    }

    fetchAppointments(formatDateForAPI(selectedDate));
  };

  const generateCalendar = (date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDayOfMonth = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let html = `
      <div class="calendar-header">
        <button class="prev-month"><i class="fas fa-chevron-left"></i></button>
        <div class="month-year">${date.toLocaleString("en-US", {
          month: "long",
        })} ${year}</div>
        <button class="next-month"><i class="fas fa-chevron-right"></i></button>
      </div>
      <div class="calendar-days-of-week">
        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
      </div>
      <div class="calendar-dates">
    `;

    for (let i = 0; i < firstDayOfMonth; i++) {
      html += `<span class="empty-day"></span>`;
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const currentDate = new Date(year, month, day);
      let classes = "day-cell";

      if (currentDate.toDateString() === today.toDateString()) {
        classes += " today";
      }

      if (formatDateForAPI(currentDate) === formatDateForAPI(selectedDate)) {
        classes += " selected";
      }

      html += `<span class="${classes}" data-date="${formatDateForAPI(
        currentDate
      )}">${day}</span>`;
    }

    html += `</div>`;
    calendarContainer.innerHTML = html;

    calendarContainer
      .querySelector(".prev-month")
      .addEventListener("click", () => {
        const newDate = new Date(date);
        newDate.setMonth(newDate.getMonth() - 1);
        updateSelectedDateOnNav(newDate);
        generateCalendar(newDate);
      });

    calendarContainer
      .querySelector(".next-month")
      .addEventListener("click", () => {
        const newDate = new Date(date);
        newDate.setMonth(newDate.getMonth() + 1);
        updateSelectedDateOnNav(newDate);
        generateCalendar(newDate);
      });

    calendarContainer.querySelectorAll(".day-cell").forEach((cell) => {
      cell.addEventListener("click", (e) => {
        selectedDate = new Date(e.target.dataset.date);
        generateCalendar(date);
        fetchAppointments(e.target.dataset.date);
      });
    });
  };

  fetchAppointments = async (date_str) => {
    appointmentsDateDisplay.textContent = `Scheduled appointments for ${formatDateForDisplay(
      new Date(date_str)
    )}`;
    appointmentList.innerHTML =
      '<div class="loading-message">Loading appointments...</div>';

    try {
      const response = await fetch(
        `php/appointments_handler.php?action=fetch_appointments_by_date&date=${date_str}`
      );
      const result = await response.json();

      if (result.success && result.data && result.data.length > 0) {
        renderAppointments(result.data);
      } else {
        appointmentList.innerHTML = `<div class="empty-message">${
          result.message || "No appointments scheduled for this date."
        }</div>`;
      }
    } catch (error) {
      appointmentList.innerHTML = `<div class="error-message">Failed to load appointments.</div>`;
    }
  };

  const renderAppointments = (appointments) => {
    appointmentList.innerHTML = "";

    appointments.forEach((app) => {
      const statusClass = getStatusClass(app.status);

      const appointmentItem = document.createElement("div");
      appointmentItem.className = "appointment-item";
      appointmentItem.dataset.id = app.appointment_id;

      appointmentItem.innerHTML = `
        <div class="appointment-time">${app.time_only}</div>
        <div class="appointment-details">
          <div class="patient-name"><strong>${app.patient_name}</strong></div>
          <div class="doctor-info">Dr. ${app.doctor_name} - ${app.type}</div>
        </div>
        <div class="appointment-actions">
          <span class="status ${statusClass}">${app.status}</span>
          <select class="status-changer" data-id="${app.appointment_id}">
            <option value="Pending" ${
              app.status === "Pending" ? "selected" : ""
            }>Pending</option>
            <option value="Ongoing" ${
              app.status === "Ongoing" ? "selected" : ""
            }>Ongoing</option>
            <option value="Completed" ${
              app.status === "Completed" ? "selected" : ""
            }>Completed</option>
            <option value="Cancelled" ${
              app.status === "Cancelled" ? "selected" : ""
            }>Cancelled</option>
          </select>
          <button class="delete-appointment-btn" data-id="${
            app.appointment_id
          }" title="Delete Appointment">
            <i class="fas fa-trash-alt"></i> 
          </button>
        </div>
      `;
      appointmentList.appendChild(appointmentItem);
    });

    appointmentList.querySelectorAll(".status-changer").forEach((select) => {
      select.addEventListener("change", (e) => {
        const id = e.target.dataset.id;
        const newStatus = e.target.value;

        updateStatus(id, newStatus, formatDateForAPI(selectedDate));
      });
    });

    appointmentList
      .querySelectorAll(".delete-appointment-btn")
      .forEach((button) => {
        button.addEventListener("click", (e) => {
          const id = e.currentTarget.dataset.id;

          deleteAppointment(id, formatDateForAPI(selectedDate));
        });
      });
  };

  fetchCurrentQueue = async () => {
    try {
      const response = await fetch(
        `php/appointments_handler.php?action=fetch_current_queue`
      );
      const result = await response.json();

      const queueData = result.success && result.data ? result.data : [];
      renderQueue(queueData);
    } catch (error) {
      console.error("Failed to load queue:", error);

      renderQueue([]);
    }
  };

  const renderQueue = (queue) => {
    queueContainer.innerHTML = "";

    const queueBoxesContainer = document.createElement("div");
    queueBoxesContainer.className = "queue-boxes";

    for (let i = 0; i < 3; i++) {
      const app = queue[i];
      let queueBox;

      if (app) {
        const displayStatus = app.display_status || "WAITING";
        const statusClass =
          displayStatus === "Being Attended"
            ? "status-attending"
            : "status-waiting";

        queueBox = document.createElement("div");

        queueBox.className = `queue-box card ${statusClass}`;
        queueBox.dataset.id = app.appointment_id;

        queueBox.innerHTML = `
                <div class="queue-number">#${i + 1}</div>
                <div class="patient-name-q">${app.patient_name}</div>
                <div class="doctor-info-q">Dr. ${app.doctor_name}</div>
                <span class="status ${statusClass}">${displayStatus}</span>
            `;
      } else {
        queueBox = document.createElement("div");
        queueBox.className = "queue-box card empty-queue-box";
        queueBox.innerHTML = `
                <div class="queue-number">#${i + 1}</div>
                <div class="patient-name-q text-gray-400">-- Empty Slot --</div>
                <div class="doctor-info-q text-gray-300"></div>
                <span class="status text-gray-200"></span>
            `;
      }

      queueBoxesContainer.appendChild(queueBox);
    }

    queueContainer.appendChild(queueBoxesContainer);

    if (queue.length === 0) {
      const noQueue = document.createElement("div");
      noQueue.className = "no-queue-overlay";

      queueContainer.appendChild(noQueue);
    }
  };

  generateCalendar(selectedDate);
  fetchAppointments(formatDateForAPI(selectedDate));
  fetchCurrentQueue();
});
