import { LibraryComponent } from './base/library-component.js';
import { ExplorerTree } from './controls/explorer-tree.js';

export class Sidebar extends LibraryComponent {
  constructor() {
    super();
    const treeElements = document.querySelectorAll('.explorer-tree.level-0');
    Array.from(treeElements).forEach(treeElements => new ExplorerTree(treeElements));
  }
}
