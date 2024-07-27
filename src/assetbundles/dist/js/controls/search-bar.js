import Fuse from 'https://cdn.jsdelivr.net/npm/fuse.js@7.0.0/dist/fuse.mjs'
import { LibraryComponent } from '../base/library-component.js';

export class SearchBar extends LibraryComponent {
  constructor(rootElement) {
    super();
    this.root = rootElement;
    this.items = this.parseItems();
    this.elements = new Map();
    this.fuse = this.initFuse();
    this.bindListeners();
  }

  initFuse() {
    return new Fuse(this.items, {
      keys: ['name', 'path', 'type'],
      includeScore: true,
      // isCaseSensitive: false,
      includeMatches: true,
      // threshold: 0.3,
    });
  }

  parseItems() {
    const indexElelement = this.root.querySelector('#search-index');
    if (indexElelement) {
      const jsonData = JSON.parse(indexElelement.textContent);
      return jsonData.map(item => {
        let path = item.includeName.split('/');
        return {
          name: item.name,
          path: path.join(' / '),
          handle: item.includeName,
          icon: item.icon,
          type: item.type,
        }
      });
    }
    return [];
  }

  bindListeners() {
    const inputElement = this.root.querySelector('.search-bar__input');
    inputElement.addEventListener('input', this.onSearchInput);
  }

  onSearchInput = (event) => {
    if (event.target.value === '') {
      this.hideSearchResults();
      return;
    }
    const query = event.target.value
    const results = this.fuse.search(query);
    this.renderResults(results);
  }

  hideSearchResults() {
    this.root.classList.remove('search-bar--results');
    this.root.classList.remove('search-bar--no-results');
  }

  renderResults(results) {
    this.root.classList.toggle('search-bar--results', results.length > 0);
    this.root.classList.toggle('search-bar--no-results', results.length === 0);
    const resultsElement = this.root.querySelector('.search-bar__results-list');
    resultsElement.innerHTML = '';
    results.forEach(result => {
      const itemElement = this.createItemElement(result.item);
      resultsElement.appendChild(itemElement);
    });
  }

  createElement(tag, classes, content) {
    const element = document.createElement(tag);
    element.classList.add(...classes);
    element.textContent = content;
    return element;
  }

  createItemElement(item) {
    const exists = this.elements.has(item.handle);
    if (!exists) {
      const el = this.createElement('li', ['search-bar__results-item']);
      el.appendChild(this.createElement('span', ['item__icon', 'material-symbols-outlined', 'icon-file'], item.icon));
      el.appendChild(this.createElement('div', ['item__name'], item.name));
      el.appendChild(this.createElement('span', ['item__path'], item.path));
      this.elements.set(item.handle, el);
    }
    return this.elements.get(item.handle);
  }
}
