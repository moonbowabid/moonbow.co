// GLOBAL VARIABLES
let jobTitle = "";
let jobLocation = "";
let jobTiming = "";

jQuery(document).ready(function ($) {
  const urlParams = new URLSearchParams(window.location.search);
  let success = urlParams.get("q");

  const urlTitle = urlParams.get("title");
  const urlLocation = urlParams.get("location");
  const urlTiming = urlParams.get("timing");
  $(".job-apply-conformation").hide();
  // FUNCTION: OPEN MODAL
  function openModal(title = "", location = "", timing = "") {
    $(".Career-form").css("display", "flex");
    $("body").addClass("menu-open");

    if (success === "success") {
      // SUCCESS STATE → ONLY HIDE FORM
      // $(".gravity-form-container").hide();

      $(".gravity-form-container").hide();
      // APPLY DATA FROM URL
      $(".job-apply-conformation").css("display", "flex");
      if (urlTitle) {
        $(".confirmation-job-title h2").text("Applying for - " + urlTitle);
      }

      if (urlLocation) {
        $(".Career-form .job-location").text(urlLocation);
      }

      if (urlTiming) {
        $(".Career-form .job-timing").text(urlTiming);
      }
    } else {
      // NORMAL STATE
      $(".gravity-form-container").show();

      if (title) {
        $("#input_5_5").val(title);
        $(".Career-form .job-title h2").text("Applying for - " + title);
        $(".confirmation-job-title h2").text("Applying for - " + title);
        $(".Career-form .job-location").text(location);
        $(".Career-form .job-timing").text(timing);
      }
    }
  }

  // AUTO OPEN IF SUCCESS (after manual refresh case)
  if (success === "success") {
    openModal();
  }

  // APPLY BUTTON CLICK
  $(".career-jobs__apply-btn").on("click", function (e) {
    e.preventDefault();

    jobTitle = $(this).data("job-title");
    jobLocation = $(this).data("job-location");
    jobTiming = $(this).data("job-timing");

    openModal(jobTitle, jobLocation, jobTiming);
  });

  // CLOSE MODAL
  function closeModal() {
    $(".Career-form").hide();
    $("body").removeClass("menu-open");

    if (success === "success") {
      // reset state BEFORE reload
      success = null;

      window.location.href = window.location.origin + window.location.pathname;
      console.log("window.location.href", window.location.href);
    }
  }

  // CLOSE ON OUTSIDE CLICK
  $(document).mouseup(function (e) {
    let formInner = $(".Career-form.e-con.e-flex > .e-con-inner");
    if (!formInner.is(e.target) && formInner.has(e.target).length === 0) {
      closeModal();
    }
  });

  // CLOSE ON ESC
  $(document).on("keydown", function (e) {
    if (e.key === "Escape") {
      closeModal();
      // window.location.reload();
    }
  });
});

// AFTER SUBMIT → ADD PARAMS WITHOUT RELOAD
jQuery(document).on("gform_confirmation_loaded", function (event, formId) {
  if (formId === 5) {
    success = "success"; // ADD THIS LINE
    // Hide Gravity Form completely
    jQuery(".gravity-form-container").hide();

    //  Show your custom confirmation
    jQuery(".job-apply-conformation").css("display", "flex");
    const url = new URL(window.location);

    url.searchParams.set("q", "success");
    url.searchParams.set("title", jobTitle);
    url.searchParams.set("location", jobLocation);
    url.searchParams.set("timing", jobTiming);

    // UPDATE URL (NO RELOAD)
    window.history.pushState({}, "", url);
    window.location.reload();

    // OPEN MODAL IN SUCCESS STATE
    jQuery(".Career-form").css("display", "flex");
    jQuery("body").addClass("menu-open");
    // jQuery(".gravity-form-container").hide();

    // UPDATE UI
    if (jobTitle) {
      jQuery(".confirmation-job-title h2").text("Applying for - " + jobTitle);
    }
    if (jobLocation) {
      jQuery(".Career-form .job-location").text(jobLocation);
    }
    if (jobTiming) {
      jQuery(".Career-form .job-timing").text(jobTiming);
    }
  }
});
