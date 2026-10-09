// MFS Print: close the print options window once Print is clicked. The printout opens in a new
// browser tab (in Teams via MFS Connect, msteamsfs card #308); the conversation stays as it was.
$(document).on('submit', '.mfsprint-form', function () {
    var modal = $(this).closest('.modal');
    // After the submit has been handled (native, or by MFS Connect in Teams).
    setTimeout(function () { modal.modal('hide'); }, 0);
});
