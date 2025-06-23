const container = document.getElementById('boxContainer');

for (let i = 1; i <= 100; i++) {
  const box = document.createElement('div');
  box.classList.add('box');
  box.classList.add(i % 2 === 0 ? 'even' : 'odd');
  box.textContent = i;
  container.appendChild(box);
}
