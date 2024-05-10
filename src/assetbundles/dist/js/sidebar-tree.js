function collapseAllInside(detailsEl, expand) {
  const detailsElements = detailsEl.querySelectorAll('details');
  detailsElements.forEach(detailsElement => {
    detailsElement.open = expand;
  });
}

function isFullyExpanded(detailsEl) {
  const elements = detailsEl.querySelectorAll('details');
  return Array.from(elements).every(el =>el.open);
}

function updateExpandIcon(btnEl, isExpanded) {
  btnEl.classList.toggle('sidebar__details--expanded', isExpanded);
  btnEl.classList.toggle('sidebar__details--collapsed', !isExpanded);
}

function bindCollapseExpandButton(btnEl) {
  const detailsEl = btnEl.closest('details.sidebar__details');
  detailsEl.addEventListener('toggle', function() {
    const isExpanded = isFullyExpanded(detailsEl);
    updateExpandIcon(btnEl, isExpanded);
  }, { capture: true });

  btnEl.addEventListener('click', function(event) {
    const isExpanded = isFullyExpanded(detailsEl);
    collapseAllInside(detailsEl, !isExpanded);
  })
}

const buttons = document.querySelectorAll('.sidebar .colapse-expand-button');
buttons.forEach(btn => {
    bindCollapseExpandButton(btn);
});
