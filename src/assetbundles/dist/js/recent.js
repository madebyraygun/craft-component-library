
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
    this.updateLists();
  }

  writeItems() {
    const lastItems = this.items.slice(0, 10);
    localStorage.setItem('recentItems', JSON.stringify(lastItems));
    this.updateLists();
  }

  updateLists() {
    const list = document.querySelector('.recent__list');
    if (!list) return;
    list.innerHTML = this.items.length > 0 ? '' : 'No recent items';
    this.items.forEach(item => {
      const li = document.createElement('li');
      li.classList.add('recent__item');
      const url = new URL(this.app.router.baseUrl);
      url.searchParams.set('name', item.handle);
      li.innerHTML = `
        <span class="material-symbols-outlined item__icon">${item.icon}</span>
        <a href="${url}">
          <span class="item__name">${item.name}</span>
          <span class="item__handle">${item.handle}</span>
        </a>
      `;
      list.appendChild(li);
    });
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
