import { LibraryComponent } from './base/library-component.js';

export class Preview extends LibraryComponent {
  constructor() {
    super();
    this.preview = document.querySelector('.preview');
    this.bindNavigationEvents();
  }

  bindNavigationEvents() {
    this.app.router.addEventListener('component-swap', (e) => {
      this.swapComponentView(e.detail.target.dataset.partialPreviewUrl);
    })
  }

  async swapComponentView(url) {
    const response = await fetch(url);
    if (response.status !== 200)
      return console.error('Failed to fetch component view for:', url);
    const html = await response.text();
    this.preview.innerHTML = html;
  }
}
