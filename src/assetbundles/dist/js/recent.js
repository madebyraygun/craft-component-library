
import { LibraryComponent } from './base/library-component.js';

export class Recent extends LibraryComponent {
  constructor() {
    super();
    this.items = [];
    this.readItems();
    this.bindNavigationEvents();
    this.updateCurrentComponent();
  }

  bindNavigationEvents() {
    this.app.events.addEventListener('preview-component-swapped', this.updateCurrentComponent);
  }

  readItems() {
    const items = localStorage.getItem('recentItems');
    if (items) {
      this.items = JSON.parse(items) || [];
    }
  }

  writeItems() {
    localStorage.setItem('recentItems', JSON.stringify(this.items));
  }

  updateCurrentComponent = () => {
    const current = document.querySelector('#preview-current');
    if (current) {
      const data = JSON.parse(current.textContent);
      if (data.exists) {
        this.items = this.items.filter(item => item.handle !== data.handle);
        this.items.unshift(data);
        this.writeItems();
      }
    }
  }
}
