document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.querySelector(".help-center-search__input");
  const categories = document.querySelectorAll(".help-center__category");
  const noResults = document.querySelector(".help-center__no-results");
  const input = document.querySelector(".single-help-page-search");
  const hint = document.querySelector(".help-search-hint");

  if (!searchInput) return;

  searchInput.addEventListener("input", function () {
    const value = this.value.toLowerCase().trim();
    let anyCategoryVisible = false;

    categories.forEach((category) => {
      const categoryTitle = category
        .querySelector(".help-center__category-title")
        .textContent.toLowerCase();

      const items = category.querySelectorAll(".help-center__category-item");
      let categoryHasMatch = false;

      // Reset when search is short
      if (value.length < 2) {
        category.style.display = "";
        items.forEach((item) => (item.style.display = ""));
        noResults.style.display = "none";
        return;
      }

      items.forEach((item) => {
        const titleText = item.textContent.toLowerCase();
        const contentText =
          item.getAttribute("data-content")?.toLowerCase() || "";

        // Match title OR content
        if (titleText.includes(value) || contentText.includes(value)) {
          item.style.display = "";
          categoryHasMatch = true;
        } else {
          item.style.display = "none";
        }
      });

      // Match category title
      if (categoryTitle.includes(value)) {
        categoryHasMatch = true;
        items.forEach((item) => (item.style.display = ""));
      }

      // Toggle category visibility
      if (categoryHasMatch) {
        category.style.display = "";
        anyCategoryVisible = true;
      } else {
        category.style.display = "none";
      }
    });

    // No results state
    if (value.length >= 2 && !anyCategoryVisible) {
      noResults.style.display = "block";
    } else {
      noResults.style.display = "none";
    }
  });
  const params = new URLSearchParams(window.location.search);
  const query = params.get("q");

  if (query) {
    searchInput.value = query;
    searchInput.dispatchEvent(new Event("input"));
  }

  if (!input || !hint) return;

  let timer = null;

  input.addEventListener("input", function () {
    hint.classList.remove("is-visible");
    clearTimeout(timer);

    if (this.value.trim().length < 2) return;

    timer = setTimeout(() => {
      hint.classList.add("is-visible");
    }, 1000);
  });

  input.addEventListener("keydown", function () {
    hint.classList.remove("is-visible");
    clearTimeout(timer);
  });
});
