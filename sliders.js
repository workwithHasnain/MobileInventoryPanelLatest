// ── Auto-Sliders Logic ──
document.querySelectorAll('.da-slider-wrap').forEach(wrap => {
  const scrollEl = wrap.querySelector('.da-auto-slider');
  const prevBtn = wrap.querySelector('.prev');
  const nextBtn = wrap.querySelector('.next');
  let autoScrollTimer;
  const pauseTime = 3000; // 3 seconds pause between slides

  if (!scrollEl) return;

  function doScroll() {
    if (scrollEl.matches(':hover') || window.innerWidth < 1024) return; // Pause if hovered or if on mobile

    // Calculate scroll amount dynamically (width of first child + gap)
    const firstChild = scrollEl.firstElementChild;
    const scrollAmount = firstChild ? firstChild.clientWidth + 16 : 250;

    const maxScrollLeft = scrollEl.scrollWidth - scrollEl.clientWidth;
    if (scrollEl.scrollLeft >= maxScrollLeft - 10) {
      scrollEl.scrollTo({
        left: 0,
        behavior: 'smooth'
      }); // Loop back
    } else {
      scrollEl.scrollBy({
        left: scrollAmount,
        behavior: 'smooth'
      });
    }
  }

  function startAuto() {
    autoScrollTimer = setInterval(doScroll, pauseTime);
  }

  function stopAuto() {
    clearInterval(autoScrollTimer);
  }

  // Handle interactions
  wrap.addEventListener('mouseenter', stopAuto);
  wrap.addEventListener('mouseleave', startAuto);

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      stopAuto();
      const firstChild = scrollEl.firstElementChild;
      const scrollAmount = firstChild ? firstChild.clientWidth + 16 : 250;
      scrollEl.scrollBy({
        left: -scrollAmount,
        behavior: 'smooth'
      });
      startAuto();
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      stopAuto();
      const firstChild = scrollEl.firstElementChild;
      const scrollAmount = firstChild ? firstChild.clientWidth + 16 : 250;
      scrollEl.scrollBy({
        left: scrollAmount,
        behavior: 'smooth'
      });
      startAuto();
    });
  }

  if (window.innerWidth >= 1024) startAuto();
});
