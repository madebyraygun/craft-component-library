import { LibraryComponent } from '../base/library-component.js';

export class ExplorerTree extends LibraryComponent {
  constructor(rootElement) {
    super();
    this.root = rootElement;
    this.directories = this.root.querySelectorAll('.list__item--directory');
    this.files = this.root.querySelectorAll('.list__item--file');
    this.bind();
  }

  bind() {
    this.bindNodeEvents();
    this.bindCollapseExpandButton();
  }

  bindNodeEvents() {

    this.app.events.addEventListener('explorer-tree:file-click', () => {
      this.selectNode(null, 'file');
    });

    this.directories.forEach(directory => {
      directory.addEventListener('click', (e) => {
        e.stopPropagation();
        // this.selectNode(null, 'file');
        this.selectNode(directory, 'directory');
      });
    });

    this.files.forEach(file => {
      file.addEventListener('click', (e) => {
        this.app.events.dispatchEvent('explorer-tree:file-click')
        this.selectNode(file, 'file');
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
    const nodes = type === 'directory' ? this.directories : this.files;
    nodes.forEach(dir => dir.classList.remove('list__item--selected'));
    if (itemElement) {
      itemElement.classList.add('list__item--selected');
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
