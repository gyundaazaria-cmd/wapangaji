const body = document.body;
const csrfToken = body.dataset.csrf || '';
const currentPage = body.dataset.page || '';
const money = (value) => new Intl.NumberFormat('sw-TZ', {
    style: 'currency', currency: 'TZS', maximumFractionDigits: 0
}).format(Number(value || 0));
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
})[character]);

async function api(resource, method = 'GET', data = undefined) {
    const response = await fetch(`api.php?resource=${encodeURIComponent(resource)}`, {
        method,
        headers: {
            Accept: 'application/json',
            ...(data ? { 'Content-Type': 'application/json' } : {}),
            ...(method !== 'GET' ? { 'X-CSRF-Token': csrfToken } : {})
        },
        ...(data ? { body: JSON.stringify(data) } : {})
    });
    const result = await response.json().catch(() => ({}));
    if (response.status === 401) {
        window.location.assign('loginpage.php');
        throw new Error('Tafadhali ingia tena kwenye akaunti yako.');
    }
    if (!response.ok) throw new Error(result.error || 'Ombi halikukamilika. Jaribu tena.');
    return result.data;
}

let toastTimer;
function toast(message, isError = false) {
    const target = document.querySelector('#portal-toast');
    if (!target) return;
    target.textContent = message;
    target.classList.toggle('error', isError);
    target.classList.add('show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => target.classList.remove('show'), 3800);
}

function formatDate(value, options = { day: 'numeric', month: 'short', year: 'numeric' }) {
    if (!value) return '—';
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('sw-TZ', options).format(date);
}

function renderPayments(rows, target, limit = Infinity) {
    if (!target) return;
    if (!rows.length) {
        target.innerHTML = '<div class="empty-payments">Hakuna malipo yaliyorekodiwa bado.</div>';
        return;
    }
    const entries = rows.slice(0, limit);
    target.innerHTML = `<table class="portal-table"><thead><tr><th>TAREHE</th><th>MWEZI</th><th>KIASI (TZS)</th><th>HALI</th></tr></thead><tbody>${entries.map((row) => `<tr><td>${formatDate(row.payment_date)}</td><td>${formatDate(row.covered_month, { month: 'long', year: 'numeric' })}</td><td class="paid-amount"><strong>${money(row.amount)}</strong></td><td><span class="status-pill${row.status === 'Pending' ? ' status-pending' : ''}">${row.status === 'Pending' ? 'Inasubiri' : 'Imelipwa'}</span></td></tr>`).join('')}</tbody></table>`;
}

async function loadDashboard() {
    const [summary, payments] = await Promise.all([api('dashboard'), api('payments')]);
    const welcome = document.querySelector('#welcome-name');
    if (welcome) welcome.textContent = summary.full_name;
    document.querySelector('#stat-contract').textContent = summary.contract_status;
    document.querySelector('#stat-paid').textContent = money(summary.total_paid);
    document.querySelector('#stat-due').textContent = formatDate(summary.due_date);
    renderPayments(payments, document.querySelector('#recent-payments'), 4);
}

let pendingPhotoData = null;
async function loadProfile() {
    const profile = await api('profile');
    document.querySelector('#profile-name').value = profile.full_name;
    document.querySelector('#profile-phone').value = profile.phone;
    document.querySelector('#profile-email').value = profile.email;
    document.querySelector('#profile-room').textContent = profile.room_number || 'Haijawekwa';
    document.querySelector('#sidebar-username').textContent = profile.full_name;
    document.querySelector('#sidebar-avatar').textContent = profile.full_name.trim().charAt(0).toUpperCase();
    if (profile.photo_data) {
        const preview = document.querySelector('#profile-photo-preview');
        preview.src = profile.photo_data;
        preview.hidden = false;
        document.querySelector('#profile-photo-placeholder').hidden = true;
    }
    return profile;
}

function showMessage(element, text, isError = false) {
    if (!element) return;
    element.textContent = text;
    element.classList.toggle('error', isError);
}

function setupProfile() {
    const form = document.querySelector('#profile-form');
    if (!form) return;
    const message = document.querySelector('#profile-message');
    const photoInput = document.querySelector('#profile-photo');
    photoInput.addEventListener('change', () => {
        const file = photoInput.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 1048576) {
            photoInput.value = '';
            toast('Chagua picha ya JPG, PNG au WebP isiyozidi 1 MB.', true);
            return;
        }
        const reader = new FileReader();
        reader.addEventListener('load', () => {
            pendingPhotoData = String(reader.result);
            const preview = document.querySelector('#profile-photo-preview');
            preview.src = pendingPhotoData;
            preview.hidden = false;
            document.querySelector('#profile-photo-placeholder').hidden = true;
        });
        reader.addEventListener('error', () => toast('Picha haikuweza kufunguliwa.', true));
        reader.readAsDataURL(file);
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        showMessage(message, '');
        try {
            await api('profile', 'POST', {
                full_name: form.elements.full_name.value.trim(),
                phone: form.elements.phone.value.trim(),
                photo_data: pendingPhotoData
            });
            pendingPhotoData = null;
            await loadProfile();
            showMessage(message, 'Taarifa zako zimehifadhiwa.');
        } catch (error) {
            showMessage(message, error.message, true);
        } finally {
            submit.disabled = false;
        }
    });
}

let signatureStrokes = [];
let signatureCanvas;
let signatureContext;
let activeStroke = null;
function drawSignature() {
    if (!signatureCanvas || !signatureContext) return;
    const rect = signatureCanvas.getBoundingClientRect();
    signatureContext.clearRect(0, 0, rect.width, rect.height);
    signatureContext.strokeStyle = getComputedStyle(document.body).getPropertyValue('--text').trim() || '#212a22';
    signatureContext.lineWidth = Math.max(2, rect.width / 270);
    signatureContext.lineCap = 'round';
    signatureContext.lineJoin = 'round';
    for (const stroke of signatureStrokes) {
        signatureContext.beginPath();
        stroke.forEach((point, index) => {
            const x = point[0] * rect.width;
            const y = point[1] * rect.height;
            if (index === 0) signatureContext.moveTo(x, y);
            else signatureContext.lineTo(x, y);
        });
        signatureContext.stroke();
    }
}

function sizeSignatureCanvas() {
    if (!signatureCanvas) return;
    const rect = signatureCanvas.getBoundingClientRect();
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    signatureCanvas.width = Math.round(rect.width * ratio);
    signatureCanvas.height = Math.round(rect.height * ratio);
    signatureContext = signatureCanvas.getContext('2d');
    signatureContext.setTransform(ratio, 0, 0, ratio, 0, 0);
    drawSignature();
}

function setupSignaturePad() {
    signatureCanvas = document.querySelector('#signature-pad');
    if (!signatureCanvas) return;
    sizeSignatureCanvas();
    window.addEventListener('resize', sizeSignatureCanvas);
    signatureCanvas.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        signatureCanvas.setPointerCapture(event.pointerId);
        const rect = signatureCanvas.getBoundingClientRect();
        activeStroke = [[
            Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width)),
            Math.max(0, Math.min(1, (event.clientY - rect.top) / rect.height))
        ]];
        signatureStrokes.push(activeStroke);
        drawSignature();
    });
    signatureCanvas.addEventListener('pointermove', (event) => {
        if (!activeStroke) return;
        const rect = signatureCanvas.getBoundingClientRect();
        activeStroke.push([
            Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width)),
            Math.max(0, Math.min(1, (event.clientY - rect.top) / rect.height))
        ]);
        drawSignature();
    });
    const finishStroke = () => {
        if (activeStroke?.length === 1) activeStroke.push([...activeStroke[0]]);
        activeStroke = null;
    };
    signatureCanvas.addEventListener('pointerup', finishStroke);
    signatureCanvas.addEventListener('pointercancel', finishStroke);
    document.querySelector('#clear-signature').addEventListener('click', () => {
        signatureStrokes = [];
        drawSignature();
    });
}

async function setupContract() {
    const form = document.querySelector('#contract-form');
    if (!form) return;
    const message = document.querySelector('#contract-message');
    try {
        const [contract, profile] = await Promise.all([api('contract'), api('profile')]);
        if (contract) {
            document.querySelector('#contract-form-panel').hidden = true;
            const signed = document.querySelector('#contract-signed');
            signed.hidden = false;
            document.querySelector('#signed-summary').textContent =
                `${contract.full_name} · ${formatDate(contract.lease_start)} · ${contract.lease_duration_months} miezi · Kodi ${money(contract.monthly_rent)} kwa mwezi. Imesainiwa ${formatDate(contract.signed_at.slice(0, 10))}.`;
            document.querySelector('#download-contract').addEventListener('click', () => downloadContractPdf(contract));
            return;
        }
        form.elements.full_name.value = profile.full_name;
        form.elements.phone.value = profile.phone;
        sizeSignatureCanvas();
    } catch (error) {
        showMessage(message, error.message, true);
    }
    setupSignaturePad();
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        if (!signatureStrokes.length) {
            showMessage(message, 'Tafadhali weka sahihi yako kabla ya kuendelea.', true);
            return;
        }
        submit.disabled = true;
        showMessage(message, 'Inahifadhi mkataba...');
        try {
            await api('contract', 'POST', {
                full_name: form.elements.full_name.value.trim(),
                phone: form.elements.phone.value.trim(),
                nida_number: form.elements.nida_number.value.trim(),
                lease_start: form.elements.lease_start.value,
                lease_duration_months: form.elements.lease_duration_months.value,
                next_of_kin_name: form.elements.next_of_kin_name.value.trim(),
                next_of_kin_phone: form.elements.next_of_kin_phone.value.trim(),
                signature_data: JSON.stringify(signatureStrokes)
            });
            toast('Mkataba wako umesainiwa na kuhifadhiwa.');
            const contract = await api('contract');
            document.querySelector('#contract-form-panel').hidden = true;
            const signed = document.querySelector('#contract-signed');
            signed.hidden = false;
            document.querySelector('#signed-summary').textContent =
                `${contract.full_name} · ${formatDate(contract.lease_start)} · ${contract.lease_duration_months} miezi · Kodi ${money(contract.monthly_rent)} kwa mwezi. Imesainiwa ${formatDate(contract.signed_at.slice(0, 10))}.`;
            document.querySelector('#download-contract').addEventListener('click', () => downloadContractPdf(contract));
        } catch (error) {
            showMessage(message, error.message, true);
        } finally {
            submit.disabled = false;
        }
    });
}

const ruleTranslations = {
    rent: ['Pay rent on time', 'Pay your rent on or before the agreed date. If you expect a delay, let the landlord know early.'],
    care: ['Take care of the house', 'Keep your room and shared areas clean. Report damage or a maintenance issue when you notice it.'],
    noise: ['Respect your neighbours', 'Keep noise down, especially at night, and respect the privacy of other tenants.'],
    safety: ['Everyone’s safety', 'Lock doors, do not share your keys, and do not let unknown visitors into shared areas.'],
    changes: ['Changes and guests', 'Ask for permission before making permanent changes or allowing another person to move in.'],
    contact: ['Get in touch', 'For help or an urgent property issue, contact the landlord using the number below.']
};

function setupRules() {
    const buttons = document.querySelectorAll('[data-language]');
    if (!buttons.length) return;
    const setLanguage = (language) => {
        buttons.forEach((button) => button.classList.toggle('active', button.dataset.language === language));
        document.querySelector('#rules-language-label').textContent = language === 'sw' ? 'Kiswahili' : 'English';
        document.querySelector('#rules-list').dataset.language = language;
        document.querySelectorAll('[data-rule]').forEach((card) => {
            const swTitle = card.querySelector('h2').dataset.sw || card.querySelector('h2').textContent;
            const swBody = card.querySelector('p').dataset.sw || card.querySelector('p').textContent;
            card.querySelector('h2').dataset.sw = swTitle;
            card.querySelector('p').dataset.sw = swBody;
            card.querySelector('h2').textContent = language === 'sw' ? swTitle : ruleTranslations[card.dataset.rule][0];
            card.querySelector('p').textContent = language === 'sw' ? swBody : ruleTranslations[card.dataset.rule][1];
        });
    };
    buttons.forEach((button) => button.addEventListener('click', () => setLanguage(button.dataset.language)));
}

function pdfSafe(value) {
    return String(value ?? '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\x20-\x7E]/g, '?')
        .replace(/[\\()]/g, '\\$&');
}

function downloadContractPdf(contract) {
    const lines = [
        'GYUNDA HOUSE - MAKUBALIANO YA UPANGAJI',
        'MKATABA WA UPANGAJI WA NYUMBA',
        '',
        `Jina kamili la mpangaji: ${contract.full_name}`,
        `Namba ya simu: ${contract.phone}`,
        `Namba ya NIDA: ${contract.nida_number}`,
        `Tarehe ya kuanza: ${formatDate(contract.lease_start)}`,
        `Muda wa mkataba: ${contract.lease_duration_months} miezi`,
        `Kodi kwa mwezi: ${money(contract.monthly_rent)}`,
        `Ndugu wa karibu: ${contract.next_of_kin_name}`,
        `Simu ya ndugu wa karibu: ${contract.next_of_kin_phone}`,
        '',
        'Mpangaji anakubali kulipa kodi kwa wakati na kutunza nyumba.',
        'Mpangaji ataheshimu sheria za nyumba na majirani.',
        'Makubaliano haya yanatumika kwa muda uliotajwa kuanzia tarehe ya mwanzo.',
        '',
        'Sahihi ya mpangaji:'
    ];
    let stream = '0.18 0.38 0.28 rg\n';
    stream += 'BT /F1 17 Tf 52 744 Td (' + pdfSafe(lines[0]) + ') Tj ET\n';
    stream += '0.12 0.16 0.13 rg\n';
    let y = 710;
    for (let index = 1; index < lines.length; index += 1) {
        const isHeading = index === 1;
        const size = isHeading ? 14 : 10;
        if (lines[index] === '') {
            y -= 12;
            continue;
        }
        stream += `BT /F1 ${size} Tf 52 ${y} Td (${pdfSafe(lines[index])}) Tj ET\n`;
        y -= isHeading ? 27 : 22;
    }
    const signatureBaseline = Math.max(90, y - 13);
    stream += `0.1 0.1 0.1 RG 1.7 w 52 ${signatureBaseline} m 560 ${signatureBaseline} l S\n`;
    let strokes = [];
    try {
        strokes = JSON.parse(contract.signature_data);
    } catch {
        toast('Sahihi ya mkataba haikuweza kusomwa.', true);
        return;
    }
    stream += '0.13 0.29 0.23 RG 2.2 w 1 J 1 j\n';
    for (const stroke of strokes) {
        if (!Array.isArray(stroke) || !stroke.length) continue;
        const first = stroke[0];
        stream += `${(60 + first[0] * 480).toFixed(2)} ${(signatureBaseline + 8 + (1 - first[1]) * 42).toFixed(2)} m\n`;
        for (const point of stroke.slice(1)) {
            stream += `${(60 + point[0] * 480).toFixed(2)} ${(signatureBaseline + 8 + (1 - point[1]) * 42).toFixed(2)} l\n`;
        }
        stream += 'S\n';
    }
    stream += `BT /F1 9 Tf 52 ${(signatureBaseline - 18).toFixed(2)} Td (Imesainiwa tarehe: ${pdfSafe(formatDate(contract.signed_at.slice(0, 10)))}) Tj ET\n`;
    const objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        `<< /Length ${new TextEncoder().encode(stream).length} >>\nstream\n${stream}endstream`
    ];
    let pdf = '%PDF-1.4\n';
    const offsets = [0];
    let byteOffset = new TextEncoder().encode(pdf).length;
    objects.forEach((object, index) => {
        const serialized = `${index + 1} 0 obj\n${object}\nendobj\n`;
        offsets.push(byteOffset);
        pdf += serialized;
        byteOffset += new TextEncoder().encode(serialized).length;
    });
    const xrefOffset = byteOffset;
    pdf += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`;
    for (const offset of offsets.slice(1)) pdf += `${String(offset).padStart(10, '0')} 00000 n \n`;
    pdf += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xrefOffset}\n%%EOF`;
    const url = URL.createObjectURL(new Blob([pdf], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'gyunda-house-mkataba.pdf';
    document.body.append(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function setupTheme() {
    const button = document.querySelector('#theme-toggle');
    if (!button) return;
    try {
        if (localStorage.getItem('gyunda-tenant-theme') === 'dark') document.body.classList.add('theme-dark');
    } catch {
        // Theme can still be toggled for this page if browser storage is unavailable.
    }
    button.addEventListener('click', () => {
        document.body.classList.toggle('theme-dark');
        try {
            localStorage.setItem('gyunda-tenant-theme', document.body.classList.contains('theme-dark') ? 'dark' : 'light');
        } catch {
            toast('Mwonekano umebadilika, lakini kivinjari hakikuruhusu kuhifadhi chaguo hili.');
        }
        drawSignature();
    });
}

function setupMobileMenu() {
    const button = document.querySelector('#portal-menu');
    const sidebar = document.querySelector('#portal-sidebar');
    if (!button || !sidebar) return;
    button.addEventListener('click', () => sidebar.classList.toggle('sidebar-open'));
    document.addEventListener('click', (event) => {
        if (!sidebar.contains(event.target) && !button.contains(event.target)) sidebar.classList.remove('sidebar-open');
    });
}

async function initialize() {
    setupTheme();
    setupMobileMenu();
    setupProfile();
    setupRules();
    try {
        if (currentPage === 'dashboard') await loadDashboard();
        if (currentPage === 'profile') await loadProfile();
        if (currentPage === 'payments') renderPayments(await api('payments'), document.querySelector('#payment-history'));
        if (currentPage === 'contract') await setupContract();
    } catch (error) {
        toast(error.message, true);
        document.querySelectorAll('.portal-loading').forEach((element) => {
            element.textContent = error.message;
            element.classList.add('form-message', 'error');
        });
    }
}

initialize();
