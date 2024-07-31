import { LibraryComponent } from '../base/library-component.js';

export class ExplorerTree extends LibraryComponent {
  constructor(rootElement) {
    super();
    this.root = rootElement;
    this.directories = this.root.querySelectorAll('.list__item--directory');
    this.items = this.root.querySelectorAll('.list__item--file, .list__item--component, .list__item--document');
    this.bind();
  }

  bind() {
    this.bindNodeEvents();
    this.bindCollapseExpandButton();
  }

  bindNodeEvents() {

    this.app.events.addEventListener('explorer-tree:item-click', () => {
      this.selectNode(null, 'item');
    });

    this.app.events.addEventListener('explorer-tree:item-select', (event) => {
      this.selectNodeByHandle(event.detail.handle);
    });

    this.directories.forEach(directory => {
      directory.addEventListener('click', (e) => {
        e.stopPropagation();
        this.selectNode(directory, 'directory');
      });
    });

    this.items.forEach(item => {
      item.addEventListener('click', (e) => {
        this.app.events.dispatchEvent('explorer-tree:item-click')
        this.selectNode(item, 'item');
        this.selectNode(null, 'directory');
      }, { capture: true });
    });
  }

  bindCollapseExpandButton() {
    this.root.addEventListener('toggle', () => {
        const isExpanded = this.isFullyExpanded();
        this.updateExpandCollapseIcon(isExpanded);
    }, { capture: true });

    const btn = this.root.querySelector('button.collapse-expand-button');
    btn.addEventListener('click', () => {
        const isExpanded = this.isFullyExpanded();
        this.toggleExpand(!isExpanded);
    });
  }

  toggleExpand(expand) {
    const detailsElements = this.root.querySelectorAll('details.explorer-tree');
    detailsElements.forEach(detailsElement => {
        detailsElement.open = expand;
    });
  }

  selectNode(itemElement, type = 'directory') {
    const nodes = type === 'directory' ? this.directories : this.items;
    nodes.forEach(dir => dir.classList.remove('list__item--selected'));
    if (itemElement) {
      itemElement.classList.add('list__item--selected');
    }
  }

  expandNodeDetails(itemElement) {
    const details = itemElement.parentElement.closest('details.explorer-tree');
    if (details) {
      details.open = true;
      this.expandNodeDetails(details);
    }
  }

  selectNodeByHandle(handle) {
    const itemElement = this.root.querySelector(`[data-handle="${handle}"]`);
    if (itemElement) {
      itemElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
      this.expandNodeDetails(itemElement);
      itemElement.click();
    }
  }

  isFullyExpanded() {
    const elements = this.root.querySelectorAll('details.explorer-tree');
    return Array.from(elements).every(el => el.open);
  }

  updateExpandCollapseIcon(isExpanded) {
    this.root.classList.toggle('explorer-tree--expanded', isExpanded);
    this.root.classList.toggle('explorer-tree--collapsed', !isExpanded);
  }
}
