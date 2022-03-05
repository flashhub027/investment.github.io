<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="js">

<head>
    <meta charset="utf-8">
    <meta name="author" content="{{ site_info('author') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="site-token" content="{{ site_token() }}">
    <title>@yield('title') | {{ site_info('name') }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @php $style = (lang_dir() == 'rtl') ? 'apps-admin.rtl' : 'apps-admin'; @endphp
    <link rel="stylesheet" href="{{ asset('assets/css/'.$style.'.css?ver=113') }}">
@if(sys_settings('ui_theme_skin_admin', 'default') != 'default')
    <link rel="stylesheet" href="{{ asset('assets/css/skins/theme-'.sys_settings('ui_theme_skin_admin').'.css?ver=113') }}">
@endif
</head>

<body class="nk-body npc-cryptlite npc-admin has-sidebar"{!! lang_dir() == 'rtl' ? ' dir="rtl"' : '' !!}>
<div class="nk-app-root">

    <div class="nk-main ">

        @include('admin.layouts.sidebar')

        <div class="nk-wrap @yield('has-content-sidebar')">

            @include('admin.layouts.header')

            @yield('content-sidebar')

            <div class="nk-content ">
                <div class="container-fluid ">

                    @include('misc.message-admin')
                    @include('misc.notices')

                    @yield('content')

                </div>
            </div>

            @include('admin.layouts.footer')

        </div>
    </div>
</div>

@stack('modal')

<script type="text/javascript">
    const msgwng = "{{ __("Sorry, something went wrong!") }}", msgunp = "{{ __("Unable to process your request.") }}";
</script>
<script src="{{ asset('assets/js/bundle.js?ver=113') }}"></script>
<script src="{{ asset('assets/js/app.js?ver=113') }}"></script>
<script src="{{ asset('assets/js/app.admin.js?ver=113') }}"></script>
@stack('scripts')

</body>
</html>
