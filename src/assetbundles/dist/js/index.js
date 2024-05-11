import { SidebarTree } from './sidebar-tree.js';

const sidebar = document.querySelectorAll('nav > details');
Array.from(sidebar).forEach(sidebar => new SidebarTree(sidebar));
