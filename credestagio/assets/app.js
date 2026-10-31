document.addEventListener('click', e => {
  if (e.target.closest('[data-print]')) window.print();
});
document.addEventListener('submit', e => {
  const m = e.target.dataset.confirm;
  if (m && !confirm(m)) e.preventDefault();
});
