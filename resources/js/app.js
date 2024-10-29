import "./bootstrap";
import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";

document.addEventListener("DOMContentLoaded", function () {
    const calendarEl = document.getElementById("calendar");
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

    // Toggle Filter Row
    document.querySelector(".btn-filter").addEventListener("click", () => {
        document
            .querySelector(".calendar-additional-filters")
            .classList.toggle("hidden");
    });
});

flatpickr(".calendar-input", {
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "F j, Y",
    defaultDate: new Date(),
});

flatpickr("#calendar-input", {
    mode: "range",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "F j, Y",
    minDate: "2024-01-01",
    maxDate: "2030-12-31",
});
