document.addEventListener("DOMContentLoaded", () => {
  const counters = document.querySelectorAll(".metric-count");

  const animateCount = (el) => {
    const rawValue = el.textContent.trim(); // "50K"
    console.log("Raw value:", rawValue);

    const match = rawValue.match(/^([\d.]+)(.*)$/);

    if (!match) return;

    const targetNumber = parseFloat(match[1]);
    const suffix = match[2] || "";

    console.log("Number:", targetNumber, "Suffix:", suffix);

    let current = 0;
    const duration = 1200;
    const step = Math.max(1, targetNumber / (duration / 16));

    el.textContent = "0" + suffix;

    const update = () => {
      current += step;

      if (current >= targetNumber) {
        el.textContent = targetNumber + suffix;
        console.log("Final:", el.textContent);
        return;
      }

      el.textContent = Math.floor(current) + suffix;
      requestAnimationFrame(update);
    };

    update();
  };

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          observer.unobserve(entry.target); // run once
        }
      });
    },
    { threshold: 0.5 },
  );

  counters.forEach((counter) => observer.observe(counter));
});
