document.querySelectorAll('.mobile-accordion-header').forEach(header => {
  header.addEventListener('click', () => {
    const content = header.nextElementSibling;
    header.classList.toggle('active');
    content.classList.toggle('active');
  });
});