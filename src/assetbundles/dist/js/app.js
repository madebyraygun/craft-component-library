import { LibraryComponent } from './base/library-component.js';
import { Sidebar } from './sidebar.js';
import { Preview } from './preview.js';
import { Toolbar } from './toolbar.js';
import { Splitter } from './splitter.js';
import { Recent } from './recent.js';

export class App extends LibraryComponent {
  constructor() {
    super();
    this.container = document.querySelector('.container')
    this.splitter = new Splitter();
    this.recent = new Recent();
    this.sidebar = new Sidebar();
    this.preview = new Preview();
    this.toolbar = new Toolbar();
    this.bindEvents();
    this.container.classList.remove('js-loading');
  }

  bindEvents() {
    this.app.events.addEventListener('toolbar-visibility-changed', (e) => {
      this.container.classList.toggle('toolbar-hidden', !e.detail.visible);
    });
  }
}
