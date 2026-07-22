document.addEventListener("DOMContentLoaded", function () {
  const accordion = document.querySelector(".faq-block__accordion");
  if (!accordion) return;

  const buttons = accordion.querySelectorAll(".faq-block__question");

  function closeItem(button) {
    const content = document.getElementById(
      button.getAttribute("aria-controls"),
    );
    if (!content) return;

    content.style.height = content.scrollHeight + "px";

    requestAnimationFrame(() => {
      content.style.height = "0px";
    });

    button.setAttribute("aria-expanded", "false");
  }

  function openItem(button) {
    const content = document.getElementById(
      button.getAttribute("aria-controls"),
    );
    if (!content) return;

    content.style.height = "0px";

    requestAnimationFrame(() => {
      content.style.height = content.scrollHeight + "px";
    });

    button.setAttribute("aria-expanded", "true");
  }

  buttons.forEach((button) => {
    button.addEventListener("click", () => {
      const isOpen = button.getAttribute("aria-expanded") === "true";

      if (isOpen) {
        closeItem(button);
      } else {
        openItem(button);
      }
    });
  });

  // open first item correctly
  if (buttons.length > 0) {
    openItem(buttons[0]);
  }
});
