/**
 * form-draft.js
 * Automatically saves and restores form data to/from localStorage.
 * Clears the draft upon successful form submission.
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    if (!form) return;

    // Unique key for the current page
    const pageKey = 'admission_draft_' + window.location.pathname.split('/').pop().replace('.php', '');

    // 1. Load data from localStorage
    const savedData = localStorage.getItem(pageKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(name => {
                const element = form.elements[name];
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = data[name] === true;
                        element.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (element instanceof RadioNodeList || element.type === 'radio') {
                        const radios = element instanceof RadioNodeList ? Array.from(element) : [element];
                        const radio = radios.find(r => r.value === data[name]);
                        if (radio) {
                            radio.checked = true;
                            // Trigger both click and change to handle inline onclick and event listeners
                            radio.click(); 
                            radio.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    } else if (element.tagName === 'SELECT') {
                        element.value = data[name];
                        element.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        element.value = data[name];
                        element.dispatchEvent(new Event('input', { bubbles: true }));
                        element.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
            console.log('Draft restored for ' + pageKey);
        } catch (e) {
            console.error('Failed to restore draft:', e);
        }
    }

    // 2. Save data to localStorage on input
    form.addEventListener('input', function (e) {
        saveDraft();
    });

    form.addEventListener('change', function (e) {
        saveDraft();
    });

    function saveDraft() {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            // Skip hidden fields like 'college' or 'app_id' as they are usually fixed per session
            if (form.elements[key] && form.elements[key].type === 'hidden') return;
            
            // For multi-select or multiple checkboxes with same name (if any)
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });

        // Also handle checkboxes that are NOT in FormData (because they are unchecked)
        Array.from(form.elements).forEach(element => {
            if (element.type === 'checkbox' && !element.checked && element.name) {
                if (!data[element.name]) {
                    data[element.name] = false;
                }
            }
            // Handle radios that are NOT in FormData (because none are checked)
            if (element.type === 'radio' && element.name && !data[element.name]) {
                data[element.name] = null;
            }
        });

        localStorage.setItem(pageKey, JSON.stringify(data));
    }

    // 3. Optional: Clear on submit (if you want fresh start for that step only)
    // Removed to keep draft if submission fails or if user goes back
    // form.addEventListener('submit', function () {
    //     localStorage.removeItem(pageKey);
    // });
});
