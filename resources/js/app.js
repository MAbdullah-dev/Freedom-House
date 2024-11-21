import "./bootstrap";

import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";

// // Correctly import the FullCalendar CSS files
// import "@fullcalendar/core/";
// import "@fullcalendar/daygrid/main.css";
// import "@fullcalendar/timegrid/main.css";

function initCalendar() {
    const calendarEl = document.getElementById("calendar");
    if (!calendarEl) return;

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay",
        },
        events: [
            {
                title: "Mindset Full Training",
                start: "2024-10-30",
                description: "20 Spots",
            },
            {
                title: "Clinical Documentation Training",
                start: "2024-10-30",
                description: "25 Spots",
            },
            {
                title: "Mindset Full Training",
                start: "2024-10-10",
                description: "19 Spots",
            },
            {
                title: "QI Orientation",
                start: "2024-10-23",
                description: "10 Spots",
            },
        ],
        eventContent: function (arg) {
            return {
                html: `<div class="event-details">
                            <strong>${arg.event.title}</strong><br/>
                            <small>${arg.event.extendedProps.description}</small>
                        </div>`,
            };
        },
        eventClick: function (info) {
            const event = info.event;
            alert(
                "Event: " + event.title + "\n" + event.extendedProps.description
            );
        },
        editable: true, // Allow dragging and resizing events
        droppable: true, // Allow events to be dragged onto the calendar
    });

    calendar.render();
}

document.addEventListener("DOMContentLoaded", () => {
    initCalendar();
});

// Initialize calendar on Livewire load

document.addEventListener("livewire:load", () => {
    console.log("Livewire load");
    initCalendar();
});

// Listen for the calendarInitialized event to reinitialize the calendar if needed
window.addEventListener("calendarInitialized", () => {
    console.log("Re-initializing calendar on Livewire update");
    initCalendar();
});
