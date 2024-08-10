
import { LibraryComponent } from './base/library-component.js';

export class Recent extends LibraryComponent {
  constructor() {
    super();
    this.bindNavigationEvents();
    this.updateCurrentComponent();
  }

  bindNavigationEvents() {
    this.app.events.addEventListener('preview-component-swapped', this.updateCurrentComponent);
  }

  updateCurrentComponent = () => {
    const current = document.querySelector('#preview-current');
    if (current) {
      const data = JSON.parse(current.textContent);
      console.log(data);
    }
  }
}
