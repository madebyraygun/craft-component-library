import debounce from './base/debounce.js';
import { LibraryComponent } from './base/library-component.js';

export class Preview extends LibraryComponent {
  constructor() {
    super();
    this.preview = document.querySelector('.preview');
    this.bindSizeElements();
    this.bindNavigationEvents();
    this.bindButtonEvents();
    this.updatePreviewSize();
  }

  bindNavigationEvents() {
    this.app.router.addEventListener('component-swap', async (e) => {
      this.unbindSizeElements();
      await this.swapComponentView(e.detail.target.dataset.partialPreviewUrl);
      this.app.events.dispatchEvent('preview-component-swapped');
      this.bindButtonEvents();
      this.bindSizeElements();
      this.updatePreviewSize();
    })
  }

  bindSizeElements() {
    const { preview } = this;
    this.sizeElements = {
      iframe: preview.querySelector('#preview-iframe'),
      container: preview.querySelector('.header__size'),
      width: preview.querySelector('.size__width'),
      height: preview.querySelector('.size__height'),
      unit: preview.querySelector('.size__unit'),
    }
    const { iframe, container } = this.sizeElements;
    iframe.contentWindow.addEventListener('resize', this.updatePreviewSize)
    container.addEventListener('click', this.switchSizeUnit)
  }

  unbindSizeElements() {
    const { iframe, container } = this.sizeElements;
    iframe.removeEventListener('resize', this.updatePreviewSize)
    container.removeEventListener('click', this.switchSizeUnit)
  }

  switchSizeUnit = () => {
    const { unit } = this.sizeElements;
    unit.textContent = unit.textContent.toLowerCase() === 'rem' ? 'px' : 'rem';
    this.updatePreviewSize();
  }

  updatePreviewSize = debounce(() => {
    const { iframe, width, height, unit } = this.sizeElements;
    const { clientWidth, clientHeight } = iframe;
    const isRem = unit.textContent.toLowerCase() === 'rem';
    const unitScalar = isRem ? .0625 : 1;
    const fractionDigits = isRem ? 1 : 0;
    const wSize = clientWidth * unitScalar;
    const hSize = clientHeight * unitScalar;
    width.textContent = wSize.toFixed(fractionDigits);
    height.textContent = hSize.toFixed(fractionDigits);
  }, 10, true)

  async swapComponentView(url) {
    const response = await fetch(url);
    if (response.status !== 200)
      return console.error('Failed to fetch component view for:', url);
    const html = await response.text();
    this.preview.innerHTML = html;
  }

  bindButtonEvents() {
    const btnExitFs = this.preview.querySelector('.preview__exit-full-btn');
    const btnEnterFs = this.preview.querySelector('.preview__enter-full-btn');
    btnExitFs?.addEventListener('click', this.toggleFullScreen.bind(null, false));
    btnEnterFs?.addEventListener('click', this.toggleFullScreen.bind(null, true));
  }

  toggleFullScreen = (active, event) => {
    this.preview.classList.toggle('preview--fullscreen', active);
    event.preventDefault();
  }
}
