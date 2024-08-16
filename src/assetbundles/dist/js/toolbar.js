import { LibraryComponent } from './base/library-component.js';
import { bindCodeHighlight } from './base/code-highlight.js';
// Maybe use: https://github.com/wcoder/highlightjs-line-numbers.js

export class Toolbar extends LibraryComponent {
  constructor() {
    super();
    this.toolbar = document.querySelector('.toolbar');
    this.bindCodeHighlighting();
    this.bindCodeSwitches();
    this.bindNavigationEvents();
  }

  bindNavigationEvents() {
    this.app.router.addEventListener('component-swap', async (e) => {
      const url = e.detail.target.dataset.partialToolbarUrl;
      await this.swapComponentView(url);
      this.app.events.dispatchEvent('toolbar-visibility-changed', {
        detail: {
          visible: !!url
        }
      });
    })
  }

  async swapComponentView(url) {
    const response = await fetch(url);
    if (response.status !== 200)
      return console.error('Failed to fetch component view');
    const html = await response.text();
    this.toolbar.innerHTML = html;
    this.bindCodeHighlighting();
    this.bindCodeSwitches();
  }

  bindCodeHighlighting() {
    const codeElements = this.toolbar.querySelectorAll('pre code');
    bindCodeHighlight(codeElements);
  }

  bindCodeSwitches() {
    const switches = this.toolbar.querySelectorAll('.code-switch');
    switches.forEach(el => {
      this.updateCodeMode(el);
      el.addEventListener('change', (e) => {
        this.updateCodeMode(e.target.closest('.code-switch'))
      }, true)
    })
  }

  updateCodeMode(switchElement) {
    const checked = switchElement.querySelector('input').checked;
    const pane = switchElement.closest('.tabs__pane');
    const code = pane.querySelector('.toolbar__code');
    code.classList.toggle('toolbar__code--compile-enabled', checked);
  }


}
