let formEnhancement = function () {
    let confirmBeforeSubmit = function (e) {
        if (!e.target.matches('form[data-confirm-message]')) {
            return;
        }
        let message = e.target.getAttribute('data-confirm-message');
        if (confirm(message)) {
            return true;
            /*
                const ev2 = new MouseEvent("click", ev);
                const ev2 = new SubmitEvent("submit", { cancelable: true, bubbles: true });
                ev2.redispatched = true;
                mainLink.dispatchEvent(ev2);
             */
        } else {
            e.preventDefault();
            return false;
        }
    }

    document.addEventListener('submit', confirmBeforeSubmit);
}

formEnhancement();