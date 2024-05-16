import { ExplorerTree } from './controls/explorer-tree.js';
export class Sidebar {
  constructor() {
    const treeElements = document.querySelectorAll('.explorer-tree.level-0');
    Array.from(treeElements).forEach(treeElements => new ExplorerTree(treeElements));
  }
}
