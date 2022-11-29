(() => {
  document.addEventListener('click', (e) => {
    var target = e.target;
    if (target.classList.contains('loboalert__expand')) {
      target.parentNode.classList.add('loboalert--expanded');
      e.preventDefault();
    } else if (target.classList.contains('loboalert__collapse')) {
      target.parentNode.classList.remove('loboalert--expanded');
      e.preventDefault();
    }
  });
})();