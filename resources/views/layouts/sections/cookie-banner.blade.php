<div id="sneatCookieConsentBanner" class="position-fixed bottom-0 start-0 end-0 p-3 bg-dark text-white shadow-lg" style="z-index: 1090; display: none; background-color: rgba(35, 45, 60, 0.96) !important; backdrop-filter: blur(8px);">
  <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-2">
      <i class="bx bx-cookie text-warning fs-2"></i>
      <div>
        <strong class="d-block text-white">We Value Your Privacy &amp; Experience</strong>
        <small class="text-white-50">We use essential and analytics cookies to optimize performance, remember your preferences, and deliver personalized shopping recommendations.</small>
      </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <a href="{{ route('faq') }}" class="btn btn-sm btn-outline-light text-nowrap">Cookie Policy</a>
      <button type="button" class="btn btn-sm btn-primary text-nowrap" onclick="acceptAllCookies()">
        <i class="bx bx-check me-1"></i> Accept All
      </button>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (!localStorage.getItem('sneat_cookie_consent')) {
      const banner = document.getElementById('sneatCookieConsentBanner');
      if (banner) banner.style.display = 'block';
    }
  });

  function acceptAllCookies() {
    localStorage.setItem('sneat_cookie_consent', 'accepted');
    const banner = document.getElementById('sneatCookieConsentBanner');
    if (banner) banner.style.display = 'none';
  }
</script>
