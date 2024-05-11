import { ExplorerTree } from './explorer-tree.js';
import { Preview } from './preview.js';
import { Splitter } from './splitter.js';

const splitter = new Splitter();
const sidebar = document.querySelectorAll('nav > details');
Array.from(sidebar).forEach(sidebar => new ExplorerTree(sidebar));
const preview = new Preview();
