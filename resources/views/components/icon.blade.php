@props(['name', 'size' => 20])
<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
@switch($name)
@case('home')<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/>@break
@case('check-square')<rect x="4" y="3" width="16" height="18" rx="4"/><path d="m8 12 2.5 2.5L16 9"/>@break
@case('table')<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>@break
@case('folder')<path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v9A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5z"/>@break
@case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>@break
@case('chart')<path d="M4 20V10M9 20V4M14 20v-7M19 20V7M2 20h20"/>@break
@case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>@break
@case('tag')<path d="M20 13 13 20 3 10V3h7z"/><circle cx="7.5" cy="7.5" r="1"/>@break
@case('settings')<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1-.4H3v-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1V3h4v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.15.38.36.72.6 1 .29.25.64.39 1 .4h.09v4H21a1.7 1.7 0 0 0-1.6.6"/>@break
@case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>@break
@case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>@break
@case('message')<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 10h.01M12 10h.01M16 10h.01"/>@break
@case('menu')<path d="M4 7h16M4 12h16M4 17h16"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('chevron-down')<path d="m7 10 5 5 5-5"/>@break
@endswitch
</svg>
