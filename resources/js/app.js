import "./bootstrap";

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
