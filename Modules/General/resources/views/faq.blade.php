@extends('layouts/layoutFront')

@section('title', 'Help Center & Frequently Asked Questions — AK-Mart')

@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-label-primary px-3 py-1 mb-2">Help Center &amp; Support</span>
      <h2 class="fw-bold mb-2">Frequently Asked Questions</h2>
      <p class="text-muted mx-auto" style="max-width: 600px;">
        Everything you need to know about shipping, payments, RMA returns, loyalty rewards, and privacy compliance.
      </p>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-10">
        @foreach($faqs as $category => $items)
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom">
              <h5 class="fw-bold mb-0 text-primary"><i class="bx bx-help-circle me-2"></i>{{ $category }}</h5>
            </div>
            <div class="card-body p-0">
              <div class="accordion accordion-flush" id="faqGroup_{{ Str::slug($category) }}">
                @foreach($items as $idx => $item)
                  @php $collapseId = 'collapse_' . Str::slug($category) . '_' . $idx; @endphp
                  <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="heading_{{ $collapseId }}">
                      <button class="accordion-button collapsed fw-semibold text-heading" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                        {{ $item['q'] }}
                      </button>
                    </h2>
                    <div id="{{ $collapseId }}" class="accordion-collapse collapse" aria-labelledby="heading_{{ $collapseId }}" data-bs-parent="#faqGroup_{{ Str::slug($category) }}">
                      <div class="accordion-body text-muted pt-0 pb-3">
                        {{ $item['a'] }}
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach

        <!-- Still Have Questions Banner -->
        <div class="card border-0 bg-primary text-white p-4 text-center mt-5 rounded-3 shadow">
          <h4 class="text-white fw-bold mb-2">Still have questions or need personalized assistance?</h4>
          <p class="text-white-50 mb-3">Our dedicated enterprise customer support team is available 24/7 to resolve your inquiries.</p>
          <div>
            <a href="mailto:support@sneat-store.com" class="btn btn-light btn-sm text-primary px-4 fw-semibold">
              <i class="bx bx-envelope me-1"></i> Contact Support Team
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
