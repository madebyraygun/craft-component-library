import Fuse from 'https://cdn.jsdelivr.net/npm/fuse.js@7.0.0/dist/fuse.mjs'
import { LibraryComponent } from '../base/library-component.js';

export class SearchBar extends LibraryComponent {
  constructor(rootElement) {
    super();
    this.root = rootElement;
    this.items = this.parseItems();
    this.fuse = this.initFuse();
    this.elements = new Map();
    this.bindListeners();
  }

  initFuse() {
    return new Fuse(this.items, {
      keys: ['name', 'path', 'type'],
      includeScore: true,
      includeMatches: true,
      threshold: 0.45,
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
    document.addEventListener('keydown', this.onKeyDown);
  }

  onSearchInput = (event) => {
    if (event.target.value === '') {
      this.toggleSearchResults(false, false);
      return;
    }
    const query = event.target.value
    const results = this.fuse.search(query);
    this.renderResults(results);
  }

  onItemClick = (event) => {
    const btn = event.target.closest('button');
    this.toggleSearchResults(false, false);
    this.app.events.dispatchEvent('explorer-tree:item-select', {
      detail: { handle: btn.getAttribute('data-handle') }
    });
  }

  onKeyDown = (event) => {
    if (event.key === 'Escape') {
      this.toggleSearchResults(false, false);
      this.root.querySelector('.search-bar__input').value = '';
    } else if (event.key === '/') {
      this.root.querySelector('.search-bar__input').focus();
      event.preventDefault();
    }
  }

  toggleSearchResults(resultsVisible, notFoundVisible) {
    this.root.classList.toggle('search-bar--results', resultsVisible);
    this.root.classList.toggle('search-bar--no-results', notFoundVisible);
    this.app.events.dispatchEvent('search-bar:results-toggle', {
      detail: { visible: resultsVisible || notFoundVisible }
    })
  }

  renderResults(results) {
    this.toggleSearchResults(results.length > 0, results.length === 0);
    const resultsElement = this.root.querySelector('.search-bar__results-list');
    resultsElement.innerHTML = '';
    results.forEach(result => {
      const itemElement = this.createItemElement(result.item);
      itemElement.removeEventListener('click', this.onItemClick);
      itemElement.addEventListener('click', this.onItemClick);
      this.updateElementHighlights(itemElement, result.matches);
      resultsElement.appendChild(itemElement);
    });
  }

  createElement(tag, classes, content) {
    const element = document.createElement(tag);
    element.classList.add(...classes);
    element.textContent = content;
    return element;
  }

  updateElementHighlights(element, matches) {
    const highlights = element.querySelectorAll('i');
    highlights.forEach(highlight => highlight.replaceWith(highlight.textContent));
    matches.forEach(match => {
      if (match.key === 'type') {
        return;
      }
      const node = element.querySelector(`.item__${match.key}`);
      const text = node.textContent;
      match.indices.forEach(([start, end]) => {
        const highlight = this.createElement('i', ['highlight'], text.substring(start, end + 1));
        node.textContent = text.substring(0, start);
        node.appendChild(highlight);
        node.appendChild(document.createTextNode(text.substring(end + 1)));
      });
    });
  }

  createItemElement(item) {
    const exists = this.elements.has(item.handle);
    if (!exists) {
      const el = this.createElement('li', ['search-bar__results-item']);
      const btn = this.createElement('button', ['item__button']);
      btn.setAttribute('data-handle', item.handle);
      el.appendChild(btn);
      btn.appendChild(this.createElement('span', ['item__icon', 'material-symbols-outlined', 'icon-file'], item.icon));
      btn.appendChild(this.createElement('div', ['item__name'], item.name));
      btn.appendChild(this.createElement('span', ['item__path'], item.path));
      this.elements.set(item.handle, el);
    }
    return this.elements.get(item.handle);
  }
}
