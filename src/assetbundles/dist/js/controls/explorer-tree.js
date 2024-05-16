import { EventDispatcher } from '../base/event-dispatcher.js';

export class ExplorerTree extends EventDispatcher {
  constructor(rootElement) {
    super(rootElement);
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
    this.directories.forEach(directory => {
      directory.addEventListener('click', (e) => {
        e.stopPropagation();
        // this.selectNode(null, 'file');
        this.selectNode(directory, 'directory');
      });
    });

    this.files.forEach(file => {
      file.addEventListener('click', (e) => {
        e.stopPropagation();
        e.preventDefault();
        this.dispatchEvent('nodenavigation', {
          bubbles: true,
          detail: {
            url: file.querySelector('a').href
          }
        });
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
