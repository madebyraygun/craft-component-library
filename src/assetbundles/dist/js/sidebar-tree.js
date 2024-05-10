class SidebarTree {
  constructor(rootElement) {
      this.root = rootElement;
      this.bindAllButtons();
  }

  bindAllButtons() {
      const buttons = this.root.querySelectorAll('.colapse-expand-button');
      buttons.forEach(btn => this.bindCollapseExpandButton(btn));
  }

  bindCollapseExpandButton(btn) {
      const detailsEl = btn.closest('details.sidebar__details');
      detailsEl.addEventListener('toggle', () => {
          const isExpanded = this.isFullyExpanded(detailsEl);
          this.updateExpandCollapseIcon(detailsEl, isExpanded);
      }, { capture: true });

      btn.addEventListener('click', () => {
          const isExpanded = this.isFullyExpanded(detailsEl);
          this.collapseAllInside(detailsEl, !isExpanded);
      });
  }

  collapseAllInside(detailsEl, expand) {
      const detailsElements = detailsEl.querySelectorAll('details');
      detailsElements.forEach(detailsElement => {
          detailsElement.open = expand;
      });
  }

  isFullyExpanded(detailsEl) {
      const elements = detailsEl.querySelectorAll('details');
      return Array.from(elements).every(el => el.open);
  }

  updateExpandCollapseIcon(btnEl, isExpanded) {
      btnEl.classList.toggle('sidebar__details--expanded', isExpanded);
      btnEl.classList.toggle('sidebar__details--collapsed', !isExpanded);
  }
}

(function() {
  const sidebar = document.querySelectorAll('.sidebar');
  Array.from(sidebar).forEach(sidebar => new SidebarTree(sidebar));
})();
