/* prepare element showing/hiding events for special accommodations fields */
document.addEventListener('DigraphDOMReady', (e) => {
    const fields = e.target.getElementsByClassName('accommodations-field');
    for (const i in fields) {
        if (Object.hasOwnProperty.call(fields, i)) {
            const field = fields[i];
            field.addEventListener('change', e => update(field));
            update(field);
        }
    }
    function update(field) {
        const requested = field.getElementsByClassName('accommodations-field__requested')[0].getElementsByTagName('input')[0];
        const blurb = field.getElementsByClassName('accommodations-field__blurb')[0];
        const needs = field.getElementsByClassName('accommodations-field__needs')[0];
        const lastCB = Array.from(needs.getElementsByTagName('input')).pop()
        const text = field.getElementsByClassName('accommodations-field__extra-request')[0];
        const phone = Array.from(field.getElementsByClassName('accommodations-field__phone')).shift();
        if (!requested.checked) {
            needs.style.display = 'none';
            text.style.display = 'none';
            if (phone) phone.style.display = 'none';
            if (blurb) blurb.style.display = 'none';
        } else {

            needs.style.display = null;
            if (phone) phone.style.display = null;
            if (blurb) blurb.style.display = null;
            if (lastCB.checked) {
                text.style.display = null;
            } else {
                text.style.display = 'none';
            }
        }
    }
});