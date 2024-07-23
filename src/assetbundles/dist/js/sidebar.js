import { LibraryComponent } from './base/library-component.js';
import { ExplorerTree } from './controls/explorer-tree.js';
import { SearchBar } from './controls/search-bar.js';

export class Sidebar extends LibraryComponent {
  constructor() {
    super();
    const treeElements = document.querySelectorAll('.explorer-tree.level-0');
    Array.from(treeElements).forEach(treeElements => new ExplorerTree(treeElements));
    const searchBarElements = document.querySelectorAll('.search-bar');
    Array.from(searchBarElements).forEach(searchBarElement => new SearchBar(searchBarElement));
  }
}
