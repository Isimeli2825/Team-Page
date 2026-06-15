/**
 * @file
 * Team Page JavaScript behaviors.
 *
 * Features (progressive enhancement):
 * 1. Department filtering — filter team members by department
 * 2. Modal dialog — view team member details in an accessible modal
 *
 * Architecture:
 * - Uses Drupal's behavior system for proper integration
 * - Uses the `once` library to prevent double initialization
 * - All DOM queries use the `data-*` attributes from the Twig template
 * - Keyboard accessible (Escape to close modal, Tab trapping)
 * - ARIA attributes are managed dynamically for screen reader support
 *
 * @see team_page_preprocess_team_page()
 * @see team-page.html.twig
 */

(function (Drupal, once, document) {
  'use strict';

  /**
   * Team Page behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.teamPage = {
    attach: function (context, settings) {
      // Ensure we only initialize once per page load.
      const teamPage = once('team-page', '.team-page', context);

      if (teamPage.length === 0) {
        return;
      }

      // Cache DOM references.
      const page = teamPage[0];
      const filterButtons = page.querySelectorAll('.team-page__filter');
      const cards = page.querySelectorAll('.team-page__card');
      const sections = page.querySelectorAll('.team-page__section');
      const modalTriggers = page.querySelectorAll('.team-page__card-detail-toggle');
      const modals = page.querySelectorAll('.team-page__modal');

      /**
       * -----------------------------------------------------------------------
       * 1. Department Filtering
       * -----------------------------------------------------------------------
       * Filters team member cards by department. When a filter is active,
       * sections with no visible cards are hidden.
       * -----------------------------------------------------------------------
       */
      function initFilters() {
        if (filterButtons.length === 0) {
          return;
        }

        filterButtons.forEach(function (button) {
          button.addEventListener('click', function (e) {
            const department = button.getAttribute('data-department');

            // Update active state on filter buttons.
            filterButtons.forEach(function (btn) {
              btn.classList.remove('team-page__filter--active');
              btn.setAttribute('aria-selected', 'false');
            });
            button.classList.add('team-page__filter--active');
            button.setAttribute('aria-selected', 'true');

            // Show/hide cards based on department.
            filterCards(department);
          });
        });
      }

      /**
       * Filters cards by department.
       *
       * @param {string} department
       *   The department machine name, or 'all' to show all.
       */
      function filterCards(department) {
        var hasVisibleCards = {};

        cards.forEach(function (card) {
          var cardDept = card.getAttribute('data-team-department');

          if (department === 'all' || cardDept === department) {
            card.style.display = '';
            hasVisibleCards[cardDept] = true;
          }
          else {
            card.style.display = 'none';
          }
        });

        // Show/hide entire department sections.
        sections.forEach(function (section) {
          var sectionCards = section.querySelectorAll('.team-page__card');
          var anyVisible = false;
          sectionCards.forEach(function (card) {
            if (card.style.display !== 'none') {
              anyVisible = true;
            }
          });
          section.style.display = anyVisible ? '' : 'none';
        });

        // Update URL with department filter (without page reload).
        if (department && department !== 'all') {
          var url = new URL(window.location);
          url.searchParams.set('department', department);
          window.history.replaceState({}, '', url);
        }
        else {
          var cleanUrl = new URL(window.location);
          cleanUrl.searchParams.delete('department');
          window.history.replaceState({}, '', cleanUrl);
        }
      }

      /**
       * -----------------------------------------------------------------------
       * 2. Modal Dialog
       * -----------------------------------------------------------------------
       * Accessible modal dialog showing team member details.
       * Features:
       * - Open via info button on card
       * - Close via X button, overlay click, or Escape key
       * - Focus trapping inside modal
       * - Body scroll locking
       * - ARIA management
       * -----------------------------------------------------------------------
       */
      function initModals() {
        if (modalTriggers.length === 0) {
          return;
        }

        // Track currently open modal.
        var activeModal = null;
        var previousActiveElement = null;
        var focusableElements = null;
        var firstFocusable = null;
        var lastFocusable = null;

        /**
         * Opens a modal dialog.
         *
         * @param {HTMLElement} modal
         *   The modal element to open.
         */
        function openModal(modal) {
          if (!modal) {
            return;
          }

          // Store previously focused element for restoration.
          previousActiveElement = document.activeElement;
          activeModal = modal;

          // Show modal.
          modal.removeAttribute('hidden');
          modal.setAttribute('aria-hidden', 'false');

          // Lock body scroll.
          document.body.style.overflow = 'hidden';

          // Find focusable elements for focus trapping.
          focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
          );

          if (focusableElements.length > 0) {
            firstFocusable = focusableElements[0];
            lastFocusable = focusableElements[focusableElements.length - 1];
            // Focus the first focusable element (close button).
            firstFocusable.focus();
          }

          // Add escape key handler.
          document.addEventListener('keydown', handleKeydown);
        }

        /**
         * Closes the currently open modal.
         */
        function closeModal() {
          if (!activeModal) {
            return;
          }

          // Hide modal.
          activeModal.setAttribute('aria-hidden', 'true');
          activeModal.setAttribute('hidden', '');

          // Unlock body scroll.
          document.body.style.overflow = '';

          // Restore focus to trigger element.
          if (previousActiveElement) {
            previousActiveElement.focus();
          }

          // Clean up.
          activeModal = null;
          previousActiveElement = null;
          focusableElements = null;
          firstFocusable = null;
          lastFocusable = null;

          document.removeEventListener('keydown', handleKeydown);
        }

        /**
         * Handles keyboard events for modal.
         *
         * @param {KeyboardEvent} event
         *   The keyboard event.
         */
        function handleKeydown(event) {
          if (!activeModal) {
            return;
          }

          // Escape closes modal.
          if (event.key === 'Escape' || event.key === 'Esc') {
            event.preventDefault();
            closeModal();
            return;
          }

          // Tab trapping.
          if (event.key === 'Tab') {
            if (event.shiftKey) {
              // Shift+Tab: if focus is on first element, wrap to last.
              if (document.activeElement === firstFocusable) {
                event.preventDefault();
                if (lastFocusable) {
                  lastFocusable.focus();
                }
              }
            }
            else {
              // Tab: if focus is on last element, wrap to first.
              if (document.activeElement === lastFocusable) {
                event.preventDefault();
                if (firstFocusable) {
                  firstFocusable.focus();
                }
              }
            }
          }
        }

        // Open modal on trigger click.
        modalTriggers.forEach(function (trigger) {
          trigger.addEventListener('click', function (e) {
            e.preventDefault();
            var memberId = trigger.getAttribute('data-member-id');
            var modal = document.getElementById(memberId + '-modal');

            if (modal) {
              openModal(modal);
            }
          });
        });

        // Close modal on overlay click or close button click.
        modals.forEach(function (modal) {
          var closeElements = modal.querySelectorAll('[data-modal-close]');
          closeElements.forEach(function (element) {
            element.addEventListener('click', function () {
              closeModal();
            });
          });
        });
      }

      /**
       * -----------------------------------------------------------------------
       * 3. URL-based Initial State
       * -----------------------------------------------------------------------
       * If the URL contains a ?department= parameter, apply that filter
       * on page load.
       * -----------------------------------------------------------------------
       */
      function initFromUrl() {
        var urlParams = new URLSearchParams(window.location.search);
        var deptParam = urlParams.get('department');

        if (deptParam) {
          // Find matching filter button and activate it.
          filterButtons.forEach(function (button) {
            if (button.getAttribute('data-department') === deptParam) {
              button.classList.add('team-page__filter--active');
              button.setAttribute('aria-selected', 'true');
              filterCards(deptParam);
            }
          });
        }
      }

      // Initialize all features.
      initFilters();
      initModals();
      initFromUrl();
    }
  };

})(Drupal, once, document);
