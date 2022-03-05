@extends('admin.layouts.master')
@section('title', __('Miscellaneous Setting'))

@section('has-content-sidebar', 'has-content-sidebar')

@section('content-sidebar')
    @include('admin.settings.content-sidebar')
@endsection

@section('content')
<div class="nk-content-body">
        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">{{ __('Miscellaneous') }}</h3>
                    <p>{{ __('Misc options and settings of the application.') }}</p>
                </div>
                <div class="nk-block-head-content">
                    <ul class="nk-block-tools gx-1">
                        <li class="d-lg-none">
                            <a href="#" class="btn btn-icon btn-trigger toggle" data-target="pageSidebar"><em class="icon ni ni-menu-right"></em></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="nk-block card card-bordered nk-block-mh">
            <div class="card-inner">
                <form action="{{ route('admin.save.app.settings') }}" class="form-settings" method="POST">
                    <h5 class="title">{{ __('SEO on Public Pages') }}</h5>
                    <p>{{ __('The meta content will add into public pages like home/login/register for SEO purpose.') }}</p>
                    <div class="form-sets gy-3 wide-md">
                        <div class="row g-3 align-start">
                            <div class="col-lg-5">
                                <div class="form-group">
                                    <label class="form-label" for="seo-pg-description">{{ __('Global SEO Description') }}</label>
                                    <span class="form-note">
                                        {!! __('Add global meta description & keywords on public pages.') !!}
                                    </span>
                                </div>
                            </div> 
                            <div class="col-lg-7">
                                <div class="form-group">
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="seo-pg-description" name="seo_description" value="{{ sys_settings('seo_description') }}">
                                    </div>
                                    <div class="form-note">
                                        {{ __('Meta page description') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="seo-pg-keyword" name="seo_keyword" value="{{ sys_settings('seo_keyword') }}">
                                    </div>
                                    <div class="form-note">
                                        {{ __('Meta page keywords') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <div class="form-sets gy-3 wide-md">
                        <div class="row g-3 align-start">
                            <div class="col-lg-5">
                                <div class="form-group">
                                    <label class="form-label" for="seo-home-description">{{ __('Home SEO Description') }}</label>
                                    <span class="form-note">
                                        {!! __('Add custom meta description & keywords on home page.') !!}
                                    </span>
                                </div>
                            </div> 
                            <div class="col-lg-7">
                                <div class="form-group">
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="seo-home-description" name="seo_description_home" value="{{ sys_settings('seo_description_home') }}">
                                    </div>
                                    <div class="form-note">
                                        {{ __('Meta page description') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="seo-home-keyword" name="seo_keyword_home" value="{{ sys_settings('seo_keyword_home') }}">
                                    </div>
                                    <div class="form-note">
                                        {{ __('Meta page keywords') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divider"></div>

                    <h5 class="title">{{ __('Misc Options') }}</h5>
                    <div class="form-sets gy-3 wide-md">
                        <div class="row g-3 align-center">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Custom Stylesheet') }}</label>
                                    <span class="form-note">{{ __('Load custom css stylesheet for your own style.') }}</span>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input class="switch-option-value" type="hidden" name="custom_stylesheet" value="{{ sys_settings('custom_stylesheet') ?? 'off' }}">
                                        <input id="custom-css-enable" type="checkbox" class="custom-control-input switch-option" 
                                               data-switch="on"{{ (sys_settings('custom_stylesheet', 'off') == 'on') ? ' checked=""' : ''}}>
                                        <label for="custom-css-enable" class="custom-control-label">{{ __('Enable') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divider"></div>

                    <div class="form-sets gy-3 wide-md">
                        <div class="row g-3 align-start">
                            <div class="col-lg-5">
                                <div class="form-group">
                                    <label class="form-label" for="google-analytics">{{ __('Google Analytics') }}</label>
                                    <span class="form-note">
                                        {!! __('Add global site tag (gtag.js) tracking ID to your website.') !!}<br>
                                        {!! __('It will helps you to connect with Google Analytics.') !!}
                                    </span>
                                </div>
                            </div> 
                            <div class="col-lg-7">
                                <div class="form-group">
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="google-analytics" name="google_track_id" placeholder="UA-XXXXXXXX-X / G-XXXXXXXXXX" value="{{ sys_settings('google_track_id') }}">
                                    </div>
                                    <div class="form-note">
                                        {{ __('Tracking ID / Measurement ID') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divider"></div>
                    
                    <h5 class="title">{{ __('Header & Footer Code') }}</h5>
                    <div class="form-sets gy-3 wide-md">
                        <div class="row g-3 align-start">
                            <div class="col-lg-5">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Header Code') }}</label>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="form-group">
                                    <div class="form-control-wrap">
                                        <textarea class="form-control" name="header_code">{{ sys_settings('header_code') }}</textarea>
                                    </div>
                                    <div class="form-note">
                                    	{{ __('You can use this for analytics code. Please enter full code including <script> tag.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 align-start">
                            <div class="col-lg-5">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Footer Code') }}</label>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="form-group">
                                    <div class="form-control-wrap">
                                        <textarea class="form-control" name="footer_code">{{ sys_settings('footer_code') }}</textarea>
                                    </div>
                                    <div class="form-note">
                                    	{{ __('You can use this for chat or third-party tracker codes. Please enter full code including <script> tag.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-sets gy-3 wide-md">
                        <div class="row g-3">
                            <div class="col-lg-7 offset-lg-5">
                                <div class="form-group mt-2">
                                    @csrf
                                    <input type="hidden" name="form_type" value="misc-setting">
                                    <button type="button" class="btn btn-primary submit-settings" disabled="">
                                        <span class="spinner-border spinner-border-sm hide" role="status" aria-hidden="true"></span>
                                        <span>{{ __('Update') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
