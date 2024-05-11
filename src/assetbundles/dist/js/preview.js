export class Preview {
  constructor() {
    this.iframe = document.querySelector('#preview-iframe');
    this.bind();
  }

  bind() {
    document.addEventListener('nodenavigation', (e) => {
      this.iframe.src = e.detail.url;
    }, true)
  }
}
