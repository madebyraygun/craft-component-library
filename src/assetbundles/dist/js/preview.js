import { LibraryComponent } from './base/library-component.js';

export class Preview extends LibraryComponent {
  constructor() {
    super();
    this.iframe = document.querySelector('#preview-iframe');
    this.bind();
  }

  bind() {
    // document.addEventListener('nodenavigation', (e) => {
    //   this.iframe.src = e.detail.url;
    // }, true)
    this.app.router.addEventListener('component-swap', (e) => {
      console.log('Sidebar component-swap', );
      const url = e.detail.target.dataset.previewUrl;
      this.iframe.src = url;
    })
  }
}
