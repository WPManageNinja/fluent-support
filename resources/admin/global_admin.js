jQuery(document).ready(function ($) {
    let $handHeld = $('.fframe_handheld');
    $handHeld.on('click', function () {
        $(this).parent().find('.fframe_menu').toggleClass('fframe_menu_open');
    });
    $('.fframe_menu_item a').on('click', function () {
        $handHeld.parent().find('.fframe_menu').removeClass('fframe_menu_open');

    });

    jQuery('.update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error').remove();


    jQuery('.toplevel_page_fluent-support a').on('click', function () {
        jQuery('.toplevel_page_fluent-support li').removeClass('current');
        jQuery(this).parent().addClass('current');
    });

    // Handle offcanvas menu label clicks for responsive drawer - same pattern as toplevel
    jQuery(document).off('click', '.fs_offcanvas_menu_label a').on('click', '.fs_offcanvas_menu_label a', function (e) {
        jQuery('.fs_offcanvas_menu_label').removeClass('current');
        jQuery(this).closest('.fs_offcanvas_menu_label').addClass('current');
    });

    // Mobile menu drawer functionality
    const menuToggle = document.querySelector('[data-fs-menu-toggle]');
    if (menuToggle) {
        const parent = menuToggle.parentNode;
        const menuWrapper = parent.querySelector('[data-fs-offcanvas-menu]');
        const menuCloseButton = parent.querySelector('[data-fs-offcanvas-menu-close]');
        const overlay = parent.querySelector('[data-fs-offcanvas-menu-overlay]');

        if (menuToggle && menuWrapper && overlay) {
            menuToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                overlay.classList.toggle('active');
                menuWrapper.classList.toggle('open');
                document.body.style.overflow = 'hidden';
            });

            if (menuCloseButton) {
                menuCloseButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    overlay.classList.remove('active');
                    menuWrapper.classList.remove('open');
                    document.body.style.overflow = '';
                });
            }

            overlay.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                overlay.classList.remove('active');
                menuWrapper.classList.remove('open');
                document.body.style.overflow = '';
            });
        }
    }

});
