/**
 * Tierphysio Manager - Main Application JavaScript
 * Handles sidebar, navigation, and UI interactions
 * @version 3.0.0
 */

$(function() {
    "use strict";

    // Initialize Perfect Scrollbar for containers
    if ($(".app-container").length) {
        new PerfectScrollbar(".app-container");
    }
    
    if ($(".header-message-list").length) {
        new PerfectScrollbar(".header-message-list");
    }
    
    if ($(".header-notifications-list").length) {
        new PerfectScrollbar(".header-notifications-list");
    }

    // Mobile search functionality
    $(".mobile-search-icon").on("click", function() {
        $(".search-bar").addClass("full-search-bar");
    });
    
    $(".search-close").on("click", function() {
        $(".search-bar").removeClass("full-search-bar");
    });

    // Sidebar toggle functionality
    $(".mobile-toggle-menu").on("click", function() {
        const isDesktop = window.matchMedia("(min-width: 992px)").matches;
        
        if (isDesktop) {
            // Desktop: Toggle wrapper class
            $(".wrapper").toggleClass("toggled");
        } else {
            // Mobile: Toggle sidebar and overlay
            $(".sidebar-wrapper").toggleClass("active");
            $(".sidebar-overlay").toggleClass("active");
        }
    });
    
    // Sidebar collapse button
    $(".toggle-icon").on("click", function() {
        $(".wrapper").toggleClass("toggled");
    });
    
    // Close sidebar on overlay click (mobile)
    $(".sidebar-overlay").on("click", function() {
        $(".sidebar-wrapper").removeClass("active");
        $(".sidebar-overlay").removeClass("active");
    });

    // Back to top button functionality
    $(window).on("scroll", function() {
        if ($(this).scrollTop() > 300) {
            $(".back-to-top").fadeIn();
        } else {
            $(".back-to-top").fadeOut();
        }
    });
    
    $(".back-to-top").on("click", function() {
        $("html, body").animate({
            scrollTop: 0
        }, 600);
        return false;
    });

    // Active menu highlighting with MetisMenu
    $(function() {
        // Initialize MetisMenu
        $("#menu").metisMenu();
        
        // Highlight active page in navigation
        const currentLocation = window.location.href;
        $(".metismenu li a").each(function() {
            if (this.href === currentLocation) {
                $(this).addClass("active");
                $(this).parent().addClass("mm-active");
                
                // Expand parent menus if any
                let parent = $(this).parent().parent();
                if (parent.is("ul")) {
                    parent.addClass("mm-show");
                    parent.parent().addClass("mm-active");
                }
            }
        });
    });

    // Chat wrapper toggle (if exists)
    $(".chat-toggle-btn").on("click", function() {
        $(".chat-wrapper").toggleClass("chat-toggled");
    });
    
    $(".chat-toggle-btn-mobile").on("click", function() {
        $(".chat-wrapper").removeClass("chat-toggled");
    });

    // Email wrapper toggle (if exists)
    $(".email-toggle-btn").on("click", function() {
        $(".email-wrapper").toggleClass("email-toggled");
    });
    
    $(".email-toggle-btn-mobile").on("click", function() {
        $(".email-wrapper").removeClass("email-toggled");
    });

    // Compose mail popup (if exists)
    $(".compose-mail-btn").on("click", function() {
        $(".compose-mail-popup").show();
    });
    
    $(".compose-mail-close").on("click", function() {
        $(".compose-mail-popup").hide();
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Handle window resize for responsive sidebar
    $(window).on("resize", function() {
        const isDesktop = window.matchMedia("(min-width: 992px)").matches;
        
        if (isDesktop) {
            // Remove mobile classes on desktop
            $(".sidebar-wrapper").removeClass("active");
            $(".sidebar-overlay").removeClass("active");
        }
    });

    // DataTable default configuration
    $.extend(true, $.fn.dataTable.defaults, {
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/de-DE.json"
        },
        "pageLength": 25,
        "responsive": true,
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });

    // Form validation styling
    $("form.needs-validation").on("submit", function(event) {
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass("was-validated");
    });

    // Smooth scroll for anchor links
    $('a[href^="#"]').on("click", function(event) {
        const target = $(this.getAttribute("href"));
        if (target.length) {
            event.preventDefault();
            $("html, body").animate({
                scrollTop: target.offset().top - 100
            }, 600);
        }
    });

    // Auto-hide alerts after 5 seconds
    $(".alert.auto-dismiss").delay(5000).fadeOut("slow");

    // File input preview
    $(".custom-file-input").on("change", function() {
        const fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });

    // Password strength indicator
    $("#password").on("keyup", function() {
        const strength = checkPasswordStrength($(this).val());
        const indicator = $("#password-strength");
        
        indicator.removeClass("weak medium strong");
        
        if (strength === "weak") {
            indicator.addClass("weak").text("Schwach");
        } else if (strength === "medium") {
            indicator.addClass("medium").text("Mittel");
        } else if (strength === "strong") {
            indicator.addClass("strong").text("Stark");
        }
    });

    function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!]+/)) strength++;
        
        if (strength < 3) return "weak";
        if (strength < 5) return "medium";
        return "strong";
    }

    // Print functionality
    $(".print-btn").on("click", function() {
        window.print();
    });

    // Confirmation dialogs
    $("[data-confirm]").on("click", function(e) {
        const message = $(this).data("confirm");
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });

    // Loading state for forms
    $("form").on("submit", function() {
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop("disabled", true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Wird verarbeitet...');
        
        // Re-enable after 10 seconds (fallback)
        setTimeout(function() {
            submitBtn.prop("disabled", false);
            submitBtn.html(originalText);
        }, 10000);
    });

    // Copy to clipboard functionality
    $(".copy-to-clipboard").on("click", function() {
        const text = $(this).data("clipboard-text");
        const temp = $("<input>");
        
        $("body").append(temp);
        temp.val(text).select();
        document.execCommand("copy");
        temp.remove();
        
        // Show success notification
        const originalText = $(this).html();
        $(this).html('<i class="bi bi-check"></i> Kopiert!');
        
        setTimeout(() => {
            $(this).html(originalText);
        }, 2000);
    });

    // Initialize any Chart.js charts
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Poppins', 'Nunito Sans', sans-serif";
        Chart.defaults.color = '#6b7280';
        Chart.defaults.borderColor = 'rgba(124, 77, 255, 0.1)';
    }

    // Console welcome message
    console.log("%cTierphysio Manager v3.0.0", "color: #7C4DFF; font-size: 20px; font-weight: bold;");
    console.log("%cDeveloped with ❤️ by Florian Engelhardt", "color: #9C27B0; font-size: 12px;");
});