import "./bootstrap";
import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";

let calendar; // Global variable to store the calendar instance

// Function to initialize or update the calendar
function initCalendar(events) {
    const calendarEl = document.getElementById("calendar");
    if (!calendarEl) return;

    // If a calendar instance already exists, update its events
    if (calendar) {
        calendar.removeAllEvents(); // Remove all existing events
        calendar.addEventSource(events); // Add new events
    } else {
        // Create a new calendar instance if it doesn't exist
        calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: "dayGridMonth",
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay",
            },
            events: events, // Initial events
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
                    "Event: " +
                        event.title +
                        "\n" +
                        event.extendedProps.description
                );
            },
        });

        calendar.render(); // Render the calendar
    }
}

// Wait for Livewire to load
document.addEventListener("livewire:load", () => {
    console.log("Livewire load");
    // Initialize the calendar with an empty events array or data from Livewire
    initCalendar([]);
});

// Listener for eventsLoaded event dispatched by Livewire
window.addEventListener("eventsLoaded", (event) => {
    console.log("Events loaded:", event.detail); // Log the event data

    if (Array.isArray(event.detail)) {
        // Flatten the event array if it's wrapped in another array
        const events = event.detail.flat(); // Flatten the array

        // Initialize or update the calendar with the loaded events
        initCalendar(events);
    } else {
        console.error("Invalid event detail. Expected an array of events.");
    }
});
