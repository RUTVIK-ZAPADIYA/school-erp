$(document).ready(function () {
  function parseValidationRules(field) {
    var rawRules = field.data("validation");
    var rules = [];

    if (rawRules) {
      rules = String(rawRules)
        .split(",")
        .map(function (rule) {
          return rule.trim();
        })
        .filter(function (rule) {
          return rule !== "";
        });
    }

    if (field.prop("required") && rules.indexOf("required") === -1) {
      rules.push("required");
    }

    if (field.attr("minlength") !== undefined && rules.indexOf("min") === -1) {
      rules.push("min");
    }

    if (field.attr("maxlength") !== undefined && rules.indexOf("max") === -1) {
      rules.push("max");
    }

    if (
      field.attr("type") === "email" &&
      rules.indexOf("email") === -1
    ) {
      rules.push("email");
    }

    if (
      field.attr("type") === "number" &&
      rules.indexOf("number") === -1
    ) {
      rules.push("number");
    }

    if (
      field.attr("pattern") !== undefined &&
      rules.indexOf("pattern") === -1
    ) {
      rules.push("pattern");
    }

    if (
      field.is("select") &&
      field.prop("required") &&
      rules.indexOf("select") === -1
    ) {
      rules.push("select");
    }

    return rules;
  }

  function getErrorField(field) {
    var rawIdentifier = field.attr("name") || field.attr("id") || "field";
    var safeIdentifier = rawIdentifier.replace(/[^a-zA-Z0-9_-]/g, "_");
    var errorId = safeIdentifier + "_error";
    var errorfield = $("#" + errorId);

    if (errorfield.length) {
      return errorfield;
    }

    // Create an inline error container when one is not already present.
    errorfield = $('<div id="' + errorId + '" class="small text-danger validation-error" style="display:none;"></div>');

    if (field.parent().hasClass("input-group")) {
      field.parent().after(errorfield);
    } else {
      field.after(errorfield);
    }

    return errorfield;
  }

  function validateInput(input) {
    var field = $(input);
    var inputType = (field.attr("type") || "").toLowerCase();

    if (inputType === "hidden" || field.is(":disabled")) {
      return true;
    }

    var value = field.val() ? field.val().trim() : "";
    var validationRules = parseValidationRules(field);

    if (validationRules.length === 0) {
      field.removeClass("is-invalid is-valid");
      return true;
    }

    var errorfield = getErrorField(field);
    var minLength =
      field.data("min") ||
      (field.attr("minlength") !== undefined
        ? parseInt(field.attr("minlength"), 10)
        : 0);
    var maxLength =
      field.data("max") ||
      (field.attr("maxlength") !== undefined
        ? parseInt(field.attr("maxlength"), 10)
        : 9999);
    var minValue =
      field.data("minValue") !== undefined
        ? parseFloat(field.data("minValue"))
        : field.attr("min") !== undefined
        ? parseFloat(field.attr("min"))
        : null;
    var maxValue =
      field.data("maxValue") !== undefined
        ? parseFloat(field.data("maxValue"))
        : field.attr("max") !== undefined
        ? parseFloat(field.attr("max"))
        : null;
    var fileSize = field.data("filesize") || 0;
    var fileType = field.data("filetype") || "";
    var patternValue = field.attr("pattern") || "";
    let errorMessage = "";
    var isFileInput = inputType === "file";
    var isCheckbox = inputType === "checkbox";

    if (validationRules.length > 0) {
      // Required field validation (all types)
      if (validationRules.includes("required")) {
        if (isCheckbox) {
          if (!field.is(":checked")) {
            errorMessage = "You must accept the terms and conditions.";
          }
        } else if (isFileInput) {
          if (!field[0].files || field[0].files.length === 0) {
            errorMessage = "This field is required.";
          }
        } else if (value === "" || value === null) {
          errorMessage = "This field is required.";
        }
      }

      // Only continue with other validations if field has a value
      if (value !== "" && !errorMessage) {
        // Minimum length validation
        if (validationRules.includes("min") && value.length < minLength) {
          errorMessage = `This field must be at least ${minLength} characters long.`;
        }

        // Maximum length validation
        if (validationRules.includes("max") && value.length > maxLength) {
          errorMessage = `This field must be at most ${maxLength} characters long.`;
        }

        if (validationRules.includes("alphabetic")) {
          const alphabetRegex = /^[a-zA-Z\s]+$/;
          if (!alphabetRegex.test(value)) {
            errorMessage = "Please enter alphabetic characters only.";
          }
        }

        // Email format validation
        if (validationRules.includes("email")) {
          const emailRegex = /^[\w-\.]+@([\w-]+\.)+[\w]{2,4}$/;
          if (!emailRegex.test(value)) {
            errorMessage = "Please enter a valid email address.";
          }
        }

        // Pattern validation from native input pattern attribute.
        if (validationRules.includes("pattern") && patternValue && !errorMessage) {
          try {
            const patternRegex = new RegExp("^(" + patternValue + ")$");
            if (!patternRegex.test(value)) {
              errorMessage = "Please enter a valid value.";
            }
          } catch (err) {
            // Ignore malformed patterns and let server-side checks handle it.
          }
        }

        // Numeric value validation
        if (validationRules.includes("number")) {
          const numberRegex = /^-?\d+(\.\d+)?$/;
          if (!numberRegex.test(value)) {
            errorMessage = "Please enter a valid number.";
          } else {
            const numericValue = parseFloat(value);

            if (!isNaN(minValue) && numericValue < minValue) {
              errorMessage = `Value must be at least ${minValue}.`;
            } else if (!isNaN(maxValue) && numericValue > maxValue) {
              errorMessage = `Value must be at most ${maxValue}.`;
            }
          }
        }

        // Strong password validation (at least 8 chars, 1 upper, 1 lower, 1 number, 1 special)
        if (validationRules.includes("strongPassword")) {
          const passwordRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
          if (!passwordRegex.test(value)) {
            errorMessage =
              "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
          }
        }

        // Password confirmation validation
        if (validationRules.includes("confirmPassword")) {
          const confirmPassword = $("#" + field.attr("name") + "_confirm").val();
          if (value !== confirmPassword) {
            errorMessage = "Passwords do not match.";
          }
        }

        // Dropdown selection validation
        if (validationRules.includes("select") && (value === "" || value === "0" || value === null)) {
          errorMessage = "Please select an option.";
        }
      }

      // File validations (only if file is selected)
      if (isFileInput && field[0].files && field[0].files.length > 0) {
        const file = field[0].files[0];
        
        // File size validation
        if (validationRules.includes("fileSize")) {
          if (file.size > fileSize * 1024) {
            errorMessage = `File size must be less than ${fileSize}KB.`;
          }
        }

        // File type validation
        if (validationRules.includes("fileType") && !errorMessage) {
          const fileExtension = file.name.split(".").pop().toLowerCase();
          const allowedExtensions = fileType
            .split(",")
            .map((ext) => ext.trim().toLowerCase());
          if (!allowedExtensions.includes(fileExtension)) {
            errorMessage = `File type must be ${fileType}.`;
          }
        }
      }

      if (errorMessage) {
        errorfield.text(errorMessage).show();
        field.addClass("is-invalid").removeClass("is-valid");
        errorfield.addClass("small text-danger");
        return false;
      } else {
        errorfield.text("").hide();
        field.removeClass("is-invalid").addClass("is-valid");
        return true;
      }
    }
    return true;
  }

  // Disable native browser validation so all checks run through JS handlers.
  $("form").attr("novalidate", "novalidate");

  $("input, textarea, select").on("input change blur", function () {
    validateInput(this);
  });

  $("form").on("submit", function (e) {
    let isValid = true;
    $(this)
      .find("input, textarea, select")
      .each(function () {
        const fieldValid = validateInput(this);
        if (!fieldValid) {
          isValid = false;
        }
      });
    if (!isValid) {
      e.preventDefault();
      return false;
    }
  });
});
