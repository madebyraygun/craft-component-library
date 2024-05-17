import { LibraryComponent } from './base/library-component.js';

export class Preview extends LibraryComponent {
  constructor() {
    super();
    this.iframe = document.querySelector('#preview-iframe');
    this.bind();
  }

  bind() {
    this.app.router.addEventListener('component-swap', (e) => {
      const url = e.detail.target.dataset.previewUrl;
      this.iframe.src = url;
    })
  }
}
