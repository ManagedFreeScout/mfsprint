// MFS Print: close the print options window once Print is clicked. The printout opens in a new
// browser tab (in Teams via MFS Connect, msteamsfs card #308); the conversation stays as it was.
// The ticked boxes are remembered per agent in this browser (localStorage, card #452) and shown
// again the next time the window opens; without a remembered choice the settings defaults stay.
(function () {
    function storageKey(form) {
        var userId = $(form).attr('data-user-id');
        return userId ? 'mfsprint.choices.' + userId : null;
    }

    function rememberChoices(form) {
        var key = storageKey(form);
        if (!key) {
            return;
        }
        var choices = {};
        $(form).find('input[type="checkbox"]').each(function () {
            choices[this.name] = this.checked;
        });
        try {
            window.localStorage.setItem(key, JSON.stringify(choices));
        } catch (e) {
            // Storage blocked (private mode, policy): just keep the defaults next time.
        }
    }

    function restoreChoices(form) {
        var key = storageKey(form);
        var choices = null;
        if (!key) {
            return;
        }
        try {
            choices = JSON.parse(window.localStorage.getItem(key) || 'null');
        } catch (e) {
            choices = null;
        }
        if (!choices || typeof choices !== 'object') {
            return;
        }
        // Only boxes that are in this window and were remembered; others keep the default.
        $(form).find('input[type="checkbox"]').each(function () {
            if (Object.prototype.hasOwnProperty.call(choices, this.name)) {
                this.checked = !!choices[this.name];
            }
        });
    }

    // The window's content is loaded with ajax by FreeScout's modal; restore once it is in the page.
    $(document).ajaxComplete(function () {
        $('.mfsprint-form').not('[data-mfsprint-restored]').each(function () {
            $(this).attr('data-mfsprint-restored', '1');
            restoreChoices(this);
        });
    });

    $(document).on('submit', '.mfsprint-form', function () {
        rememberChoices(this);
        var modal = $(this).closest('.modal');
        // After the submit has been handled (native, or by MFS Connect in Teams).
        setTimeout(function () { modal.modal('hide'); }, 0);
    });
})();
