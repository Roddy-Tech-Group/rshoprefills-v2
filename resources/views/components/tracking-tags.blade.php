@php
    // Marketing / analytics tags, driven by the admin SEO settings first
    // (System Settings -> SEO) and falling back to env. Each renders nothing
    // until its ID is set, and none load on the admin panel - internal staff
    // sessions must not pollute customer analytics.
    $isAdminArea = request()->is('admin*');

    $gaId = \App\Models\SiteSetting::get('seo.google_analytics_id') ?: config('services.google.analytics_id');
    $gtmId = \App\Models\SiteSetting::get('seo.google_tag_manager_id');
    $fbPixelId = \App\Models\SiteSetting::get('seo.facebook_pixel_id');
@endphp

@unless ($isAdminArea)
    @if ($gaId)
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($gaId));
        </script>
    @endif

    @if ($gtmId)
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($gtmId));</script>
    @endif

    @if ($fbPixelId)
        <!-- Meta Pixel -->
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
            document,'script','https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', @json($fbPixelId)); fbq('track', 'PageView');
        </script>
    @endif
@endunless
