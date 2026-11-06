const giftForm = document.querySelector('[data-donate-form]');

if (giftForm) {
  const customAmount = giftForm.querySelector('[data-custom-amount]');
  const customInput = customAmount.querySelector('input');
  const presets = [...giftForm.querySelectorAll('input[name="preset"]')];
  const note = giftForm.querySelector('[data-donate-note]');
  const submit = giftForm.querySelector('button[type="submit"]');
  const amounts = { once: [50, 100, 250], monthly: [5, 10, 25] };
  const currency = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
  });

  function formatAmount(amount) {
    return currency.format(amount).replace(/\.00$/, '');
  }

  function updateGift() {
    const monthly = giftForm.elements.recurring.value === 'monthly';
    const suggestions = amounts[giftForm.elements.recurring.value];

    presets.slice(0, 3).forEach((preset, index) => {
      preset.value = suggestions[index];
      preset.nextElementSibling.textContent = formatAmount(suggestions[index]);
    });

    const selected = giftForm.elements.preset.value;
    const isCustom = selected === 'other';
    customAmount.hidden = !isCustom;
    customInput.disabled = !isCustom;
    customInput.required = isCustom;

    const amount = isCustom ? customInput.value : selected;
    const validAmount = amount && (!isCustom || customInput.validity.valid);
    giftForm.elements.amount.value = validAmount ? amount : '';
    const label = validAmount ? formatAmount(Number(amount)) : '';
    note.textContent = monthly
      ? 'Monthly supporters give fellows steady support they can plan around.'
      : 'Your gift supports all of Wikitongues’ work, including the fellowship.';
    if (monthly && validAmount) {
      note.textContent = `${label} a month is ${formatAmount(Number(amount) * 12)} a year. ${note.textContent}`;
    }
    const defaultLabel = monthly ? 'Give monthly' : 'Donate';
    const amountLabel = monthly ? `Give ${label} a month` : `Donate ${label}`;
    submit.textContent = validAmount ? amountLabel : defaultLabel;
    submit.dataset.ctaLocation = monthly ? 'donate-page-top-recurring' : 'donate-page-top-single';
  }

  giftForm.addEventListener('change', updateGift);
  customInput.addEventListener('input', updateGift);
  giftForm.addEventListener('formdata', (event) => event.formData.delete('preset'));
  updateGift();
  giftForm.hidden = false;
  document.querySelector('[data-donate-fallback]').hidden = true;
}

const share = document.querySelector('[data-donate-share]');
if (share && (navigator.share || navigator.clipboard)) {
  share.hidden = false;
  share.addEventListener('click', async () => {
    const status = document.querySelector('[data-donate-share-status]');
    const url = new URL(window.location.href);
    url.hash = '';
    url.search = '';
    status.textContent = '';

    try {
      if (navigator.share) {
        await navigator.share({ title: 'Donate to Wikitongues', url: url.href });
      } else {
        await navigator.clipboard.writeText(url.href);
        status.textContent = 'Link copied.';
      }
    } catch (error) {
      if (error.name !== 'AbortError') {
        status.textContent = 'You can share this page by copying the address from your browser.';
      }
    }
  });
}
