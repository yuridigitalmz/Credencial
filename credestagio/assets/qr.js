document.querySelectorAll('[data-qr]').forEach(el => {
  const q = qrcode(0, 'M');
  q.addData(el.dataset.qr);
  q.make();
  el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 4, scalable: true });
});
