import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import '../css/image-cropper.css';

/**
 * Parse a `data-cropper-aspect-ratio` value ("16/9", "1.5", ...) into a number.
 * Returns NaN for a free ratio.
 *
 * @param {string|undefined} value
 * @returns {number}
 */
function parseAspectRatio(value) {
  if (!value) {
    return NaN;
  }
  if (value.includes('/')) {
    const [w, h] = value.split('/').map((n) => parseFloat(n));
    return h ? w / h : NaN;
  }
  return parseFloat(value);
}

/**
 * Build the modal DOM used to crop a single file input.
 *
 * @param {string} title
 * @param {boolean} withPreview
 * @returns {{overlay: HTMLElement, image: HTMLImageElement, preview: HTMLElement|null, applyBtn: HTMLElement, cancelEls: HTMLElement[]}}
 */
function buildModal(title, withPreview) {
  const overlay = document.createElement('div');
  overlay.className = 'ic-modal';
  overlay.innerHTML = `
    <div class="ic-modal__dialog" role="dialog" aria-modal="true">
      <div class="ic-modal__header">
        <h2 class="ic-modal__title"></h2>
        <button type="button" class="ic-modal__close" aria-label="Close">&times;</button>
      </div>
      <div class="ic-modal__body">
        <div class="ic-modal__stage"><img class="ic-modal__image" alt="" /></div>
        ${withPreview ? '<div class="ic-modal__preview"></div>' : ''}
      </div>
      <div class="ic-modal__footer">
        <button type="button" class="ic-btn ic-btn--secondary" data-ic-cancel>Cancel</button>
        <button type="button" class="ic-btn ic-btn--primary" data-ic-apply>Apply crop</button>
      </div>
    </div>`;
  overlay.querySelector('.ic-modal__title').textContent = title;

  return {
    overlay,
    image: overlay.querySelector('.ic-modal__image'),
    preview: overlay.querySelector('.ic-modal__preview'),
    applyBtn: overlay.querySelector('[data-ic-apply]'),
    cancelEls: [
      overlay.querySelector('[data-ic-cancel]'),
      overlay.querySelector('.ic-modal__close'),
    ],
  };
}

/**
 * Write a crop rectangle into the hidden fields referenced by the input.
 *
 * @param {HTMLInputElement} input
 * @param {{x: number, y: number, width: number, height: number}} data
 */
function writeCropData(input, data) {
  const map = {
    x: input.dataset.cropperX,
    y: input.dataset.cropperY,
    width: input.dataset.cropperWidth,
    height: input.dataset.cropperHeight,
  };
  Object.entries(map).forEach(([key, id]) => {
    const field = id && document.getElementById(id);
    if (field) {
      field.value = String(data[key]);
    }
  });
}

/**
 * Open the cropping modal for a selected file.
 *
 * @param {HTMLInputElement} input
 * @param {File} file
 */
function openCropper(input, file) {
  const withPreview = input.dataset.cropperPreview !== '0';
  const title = input.dataset.cropperModalTitle || 'Crop image';
  const aspectRatio = parseAspectRatio(input.dataset.cropperAspectRatio);
  const objectUrl = URL.createObjectURL(file);
  const modal = buildModal(title, withPreview);
  document.body.appendChild(modal.overlay);

  const cropper = new Cropper(modal.image, {
    aspectRatio,
    viewMode: 1,
    autoCropArea: 1,
    background: false,
    preview: modal.preview || undefined,
  });

  const cleanup = () => {
    cropper.destroy();
    URL.revokeObjectURL(objectUrl);
    modal.overlay.remove();
  };

  modal.image.src = objectUrl;
  modal.applyBtn.addEventListener('click', () => {
    writeCropData(input, cropper.getData(true));
    cleanup();
  });
  modal.cancelEls.forEach((el) => el && el.addEventListener('click', cleanup));
  modal.overlay.addEventListener('click', (event) => {
    if (event.target === modal.overlay) {
      cleanup();
    }
  });
}

/**
 * Wire up every cropper-enabled file input found within `root`.
 *
 * @param {ParentNode} [root=document]
 */
export function init(root = document) {
  const inputs = root.querySelectorAll('input[type="file"][data-image-cropper]');
  inputs.forEach((input) => {
    if (input.dataset.imageCropperReady === '1') {
      return;
    }
    input.dataset.imageCropperReady = '1';
    input.addEventListener('change', (event) => {
      const file = event.target.files && event.target.files[0];
      if (file && file.type.startsWith('image/')) {
        openCropper(input, file);
      }
    });
  });
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
  } else {
    init();
  }
}
