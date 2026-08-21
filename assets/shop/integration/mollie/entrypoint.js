let mollie = null;
let submitHandler = null;
let isMollieInitializing = false;

function getMollieContainer(opc) {
    return opc.querySelector('[data-profile_id]');
}

function updateMolliePanel(opc) {
    const container = getMollieContainer(opc);
    if (!container) return;
    const mollieRadio = opc.querySelector('input.mollie-payments');
    if (mollieRadio) {
        container.classList.toggle('d-none', !mollieRadio.checked);
    }
}

function loadMollieJs() {
    if (window.Mollie) return Promise.resolve(window.Mollie);

    return new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = 'https://js.mollie.com/v1/mollie.js';
        script.onload = () => resolve(window.Mollie ?? null);
        script.onerror = () => resolve(null);
        document.head.appendChild(script);
    });
}

async function initCreditCard(opc) {
    const container = getMollieContainer(opc);
    if (!container || container.dataset.mollieCcInitialized) return;

    const profileId = container.dataset.profile_id;
    if (!profileId) return;

    const MollieJs = await loadMollieJs();
    if (!MollieJs) return;

    isMollieInitializing = true;
    container.dataset.mollieCcInitialized = '1';
    mollie = MollieJs(profileId, {
        locale: container.dataset.locale || document.documentElement.lang || 'en_US',
        testmode: (container.dataset.environment || '') !== 'live',
    });

    const componentTargets = {
        cardHolder: '#card-holder',
        cardNumber: '#card-number',
        expiryDate: '#expiry-date',
        verificationCode: '#verification-code',
    };

    for (const [type, selector] of Object.entries(componentTargets)) {
        const target = opc.querySelector(selector);
        if (target) {
            target.innerHTML = '';
            mollie.createComponent(type).mount(target);
        }
    }
    setTimeout(() => { isMollieInitializing = false; }, 100);
}

function attachSubmitHandler(opc) {
    const form = opc.querySelector('form') || document.querySelector('form');
    if (!form) return;

    if (submitHandler) form.removeEventListener('submit', submitHandler);

    submitHandler = async (event) => {
        if (!opc.querySelector('input.online-payment__input[value="creditcard"]:checked')) return;
        if (!opc.querySelector('.online-payment__credit-card, #mollie_cart_data') || !mollie) return;

        event.preventDefault();

        const { token, error } = await mollie.createToken();
        const errorEl = opc.querySelector('#form-error');

        if (error) {
            if (errorEl) errorEl.textContent = error.message;
            return;
        }

        if (errorEl) errorEl.textContent = '';

        const tokenField = form.querySelector('[name$="[details][cartToken]"]');
        if (tokenField) tokenField.value = token;

        form.removeEventListener('submit', submitHandler);
        submitHandler = null;
        form.submit();
    };

    form.addEventListener('submit', submitHandler);
}

async function initOpcMollie() {
    const opc = document.querySelector('.sylius-one-page-checkout');
    if (!opc) return;

    updateMolliePanel(opc);

    opc.addEventListener('change', async (event) => {
        if (!event.target.matches('input[type="radio"]')) return;

        if (event.target.matches('input.online-payment__input') && event.target.checked) {
            const mollieRadio = opc.querySelector('input.mollie-payments');
            if (mollieRadio && !mollieRadio.checked) {
                mollieRadio.checked = true;
                mollieRadio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        updateMolliePanel(opc);

        if (event.target.matches('input.online-payment__input[value="creditcard"]') && event.target.checked) {
            await initCreditCard(opc);
            attachSubmitHandler(opc);
        }
    });

    if (opc.querySelector('input.online-payment__input[value="creditcard"]:checked')) {
        await initCreditCard(opc);
        attachSubmitHandler(opc);
    }
}

async function reinitOpcMollie() {
    mollie = null;
    submitHandler = null;
    const opc = document.querySelector('.sylius-one-page-checkout');
    if (opc) {
        opc.querySelectorAll('[data-mollie-cc-initialized]').forEach((el) => {
            delete el.dataset.mollieCcInitialized;
        });
    }
    await initOpcMollie();
}

document.addEventListener('DOMContentLoaded', () => {
    const opc = document.querySelector('.sylius-one-page-checkout');
    if (opc) {
        let debounceTimer = null;
        new MutationObserver(() => {
            if (isMollieInitializing) return;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(reinitOpcMollie, 0);
        }).observe(opc, { childList: true, subtree: true });
    }
    initOpcMollie();
});
