@php
// Base URL untuk asset Livewire (ambil dari config/env jika ada)
$base = rtrim(config('livewire.asset_url') ?? url('/livewire'), '/');
// Hash versi agar cache busting tetap jalan
$hash = app(\Livewire\Mechanisms\FrontendAssets\FrontendAssets::class)->assetHash();
@endphp

<script
    src="{{ $base }}/livewire.min.js?id={{ $hash }}"
    data-update-uri="{{ url('/livewire/update') }}"
    data-navigate-once="true">
</script>