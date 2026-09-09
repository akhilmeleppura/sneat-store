@extends('layouts/layoutFront')

@section('title', 'Store Locations & Pickup Points')

@section('content')
<!-- Leaflet Map CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<section class="section-py first-section-pt">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <span class="badge bg-label-primary px-3 py-1 mb-2">Omnichannel Retail</span>
        <h3 class="fw-bold mb-1">Find a Store / Pickup Location</h3>
        <p class="text-muted mb-0">Locate our fulfillment centers, retail showrooms, and branch pickup counters.</p>
      </div>
      <div>
        @if($activeBranch)
          <div class="p-2 px-3 bg-body-tertiary rounded border d-flex align-items-center gap-2">
            <i class="bx bx-check-circle text-success fs-4"></i>
            <div>
              <small class="text-muted d-block" style="font-size: 0.75rem;">YOUR ACTIVE BRANCH</small>
              <strong class="text-heading">{{ $activeBranch->name }}</strong>
            </div>
          </div>
        @endif
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="row g-4">
      <!-- Branches List (Left) -->
      <div class="col-lg-5 col-xl-4">
        <div class="card border-0 shadow-sm" style="max-height: 600px; overflow-y: auto;">
          <div class="card-header bg-transparent fw-bold border-bottom d-flex justify-content-between align-items-center">
            <span>Locations ({{ $branches->count() }})</span>
            <small class="text-muted">Click to focus</small>
          </div>
          <div class="list-group list-group-flush">
            @forelse($branches as $index => $branch)
              @php
                $isActive = $activeBranch && $activeBranch->id === $branch->id;
              @endphp
              <div class="list-group-item list-group-item-action p-3 {{ $isActive ? 'border-primary bg-label-primary-subtle' : '' }}" onclick="focusBranch({{ $branch->latitude }}, {{ $branch->longitude }}, '{{ addslashes($branch->name) }}')">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <h6 class="fw-bold mb-0 text-heading">{{ $branch->name }}</h6>
                  <span class="badge bg-label-secondary">{{ $branch->code ?: 'HUB' }}</span>
                </div>
                <p class="small text-muted mb-2"><i class="bx bx-map me-1 text-primary"></i>{{ $branch->full_address }}</p>
                <div class="d-flex justify-content-between align-items-center">
                  <small class="text-secondary"><i class="bx bx-phone me-1"></i>{{ $branch->phone ?? '+1 (800) 555-STORE' }}</small>
                  @if($isActive)
                    <span class="badge bg-success"><i class="bx bx-check me-1"></i>Selected</span>
                  @else
                    <a href="{{ route('branch.switch', $branch->id) }}" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                      Select
                    </a>
                  @endif
                </div>
              </div>
            @empty
              <div class="p-4 text-center text-muted">
                <i class="bx bx-map-pin fs-1 mb-2"></i>
                <p class="mb-0">No active branches configured.</p>
              </div>
            @endforelse
          </div>
        </div>
      </div>

      <!-- Interactive Map (Right) -->
      <div class="col-lg-7 col-xl-8">
        <div class="card border-0 shadow-sm overflow-hidden h-100">
          <div id="branchMap" style="width: 100%; height: 600px; min-height: 450px;"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Leaflet Map JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
  let map;
  let markers = [];

  document.addEventListener('DOMContentLoaded', function() {
    // Default center (New York or first branch)
    @php
      $firstBranch = $activeBranch ?: $branches->first();
      $centerLat = $firstBranch ? $firstBranch->latitude : 40.7128;
      $centerLng = $firstBranch ? $firstBranch->longitude : -74.0060;
    @endphp

    map = L.map('branchMap').setView([{{ $centerLat }}, {{ $centerLng }}], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Add branch pins
    @foreach($branches as $b)
      const marker_{{ $b->id }} = L.marker([{{ $b->latitude }}, {{ $b->longitude }}])
        .addTo(map)
        .bindPopup(`
          <div class="p-1" style="min-width: 180px;">
            <strong class="d-block text-primary fs-6 mb-1">{{ $b->name }}</strong>
            <small class="text-muted d-block mb-2">{{ $b->full_address }}</small>
            <div class="d-flex justify-content-between align-items-center">
              <small class="fw-semibold">{{ $b->code }}</small>
              <a href="{{ route('branch.switch', $b->id) }}" class="btn btn-xs btn-primary text-white" style="padding: 2px 8px; font-size: 11px;">
                Set Active
              </a>
            </div>
          </div>
        `);
      markers.push({ lat: {{ $b->latitude }}, lng: {{ $b->longitude }}, marker: marker_{{ $b->id }} });
    @endforeach
  });

  function focusBranch(lat, lng, name) {
    if (map) {
      map.flyTo([lat, lng], 14, { duration: 1.2 });
      const found = markers.find(m => Math.abs(m.lat - lat) < 0.0001 && Math.abs(m.lng - lng) < 0.0001);
      if (found) {
        found.marker.openPopup();
      }
    }
  }
</script>
@endsection
