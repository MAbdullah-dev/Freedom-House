import './bootstrap';

flatpickr(".calendar-input", {
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "F j, Y",
    defaultDate: new Date(),
});
