import "./bootstrap";

import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";

function initCalendar() {
    const calendarEl = document.getElementById("calendar");
    if (!calendarEl) return;

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin],
        initialView: "dayGridMonth",
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
                html: `<div>${arg.event.title}<br/><small>${arg.event.extendedProps.description}</small></div>`,
            };
        },
    });
    calendar.render();
}

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

// Toggle Filter Row
document.querySelector(".btn-filter").addEventListener("click", () => {
    document
        .querySelector(".calendar-additional-filters")
        .classList.toggle("hidden");
});
