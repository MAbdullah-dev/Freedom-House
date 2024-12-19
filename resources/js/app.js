import "./bootstrap";
import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";
let calendar;
function initCalendar(events, classes) {
    const calendarEl = document.getElementById("calendar");
    if (!calendarEl) return;
    if (calendar) {
        calendar.removeAllEvents();
        calendar.addEventSource(events);
        calendar.addEventSource(classes);
    } else {
        calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: "dayGridMonth",
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay",
            },
            events: events,
            eventContent: function (arg) {
                return {
                    html: `<div class="event-details">
                            <strong>${arg.event.title}</strong><br/>
                            <small>${arg.event.extendedProps.spotsLeft} spots left</small><br/>
                        </div>`,
                };
            },
            eventClick: function (info) {
                const event = info.event;
                alert(
                    "Event: " +
                        event.title +
                        "\n" +
                        event.extendedProps.description
                );
            },
        });
        calendar.render();
    }
}
document.addEventListener("livewire:load", () => {
    console.log("Livewire load");
    initCalendar([], []);
});
window.addEventListener("eventsLoaded", (event) => {
    console.log("Events loaded:", event.detail);
    if (Array.isArray(event.detail)) {
        const events = event.detail.flat();
        initCalendar(events, []);
    } else {
        console.error("Invalid event detail. Expected an array of events.");
    }
});
window.addEventListener("classesLoaded", (event) => {
    console.log("Classes loaded:", event.detail);
    if (Array.isArray(event.detail)) {
        const classes = event.detail.flat();
        initCalendar([], classes);
    } else {
        console.error("Invalid class detail. Expected an array of classes.");
    }
});
