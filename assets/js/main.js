/**
 * The Grand Palms Resort - Main JavaScript
 * Handles sticky nav, mobile menu, booking modal, room filtering, date constraints.
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Sticky Header Effect
  const header = document.querySelector('.site-header');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });

  // 2. Mobile Menu Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navLinks = document.getElementById('navLinks');
  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      navLinks.classList.toggle('open');
      const icon = mobileToggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-xmark');
      }
    });

    document.addEventListener('click', (e) => {
      if (!navLinks.contains(e.target) && !mobileToggle.contains(e.target)) {
        navLinks.classList.remove('open');
        const icon = mobileToggle.querySelector('i');
        if (icon) {
          icon.classList.remove('fa-xmark');
          icon.classList.add('fa-bars');
        }
      }
    });
  }

  // 3. Booking Modal Control
  const bookingModal = document.getElementById('bookingModal');
  const modalClose = document.getElementById('modalClose');
  const openModalBtns = document.querySelectorAll('.js-open-modal');
  const roomSelectInput = document.getElementById('modalRoomSelect');

  function openModal(roomTitle = '') {
    if (bookingModal) {
      bookingModal.classList.add('active');
      document.body.style.overflow = 'hidden';
      if (roomTitle && roomSelectInput) {
        roomSelectInput.value = roomTitle;
      }
    }
  }

  function closeModal() {
    if (bookingModal) {
      bookingModal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  openModalBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const room = btn.getAttribute('data-room') || '';
      openModal(room);
    });
  });

  if (modalClose) {
    modalClose.addEventListener('click', closeModal);
  }

  if (bookingModal) {
    bookingModal.addEventListener('click', (e) => {
      if (e.target === bookingModal) {
        closeModal();
      }
    });
  }

  // Escape key closes modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && bookingModal && bookingModal.classList.contains('active')) {
      closeModal();
    }
  });

  // 4. Date Inputs Logic (Minimum today, Check-out after Check-in)
  const today = new Date().toISOString().split('T')[0];
  const checkinInputs = document.querySelectorAll('input[type="date"][name="checkin"]');
  const checkoutInputs = document.querySelectorAll('input[type="date"][name="checkout"]');

  checkinInputs.forEach((cin, index) => {
    cin.min = today;
    cin.addEventListener('change', () => {
      const cout = checkoutInputs[index];
      if (cout) {
        const nextDay = new Date(cin.value);
        nextDay.setDate(nextDay.getDate() + 1);
        cout.min = nextDay.toISOString().split('T')[0];
        if (cout.value && cout.value <= cin.value) {
          cout.value = cout.min;
        }
      }
    });
  });

  // 5. Room Category Filter (for rooms.php)
  const filterBtns = document.querySelectorAll('.filter-btn');
  const roomCards = document.querySelectorAll('.room-filter-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filterValue = btn.getAttribute('data-filter');
      roomCards.forEach(card => {
        const category = card.getAttribute('data-category');
        if (filterValue === 'all' || category === filterValue) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  // 6. Modal Form Submit - Redirects to verified hotel booking flow
  const modalForm = document.getElementById('bookingModalForm');
  if (modalForm) {
    modalForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const room = modalForm.querySelector('select[name="room"]')?.value || '';
      const checkin = modalForm.querySelector('input[name="checkin"]')?.value || '';
      const checkout = modalForm.querySelector('input[name="checkout"]')?.value || '';
      window.location.href = `book.php?room=${encodeURIComponent(room)}&checkin=${encodeURIComponent(checkin)}&checkout=${encodeURIComponent(checkout)}`;
    });
  }
});
