<?php

namespace App\Http\Controllers\Api\V100;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryWithoutChildResource;
use App\Http\Resources\BlogResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\CategoryProductResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SellerResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\SiteResource\BannerResource;
use App\Http\Resources\SiteResource\PageGdprResource;
use App\Http\Resources\SiteResource\VideoResource;
use App\Http\Resources\SliderResource;
use App\Http\Resources\TopSellerResource;
use App\Repositories\Interfaces\Admin\Addon\VideoShoppingInterface;
use App\Repositories\Interfaces\Admin\AddonInterface;
use App\Repositories\Interfaces\Admin\Blog\BlogInterface;
use App\Repositories\Interfaces\Admin\CurrencyInterface;
use App\Repositories\Interfaces\Admin\LanguageInterface;
use App\Repositories\Interfaces\Admin\Marketing\CampaignInterface;
use App\Repositories\Interfaces\Admin\MediaInterface;
use App\Repositories\Interfaces\Admin\Page\PageInterface;
use App\Repositories\Interfaces\Admin\Product\BrandInterface;
use App\Repositories\Interfaces\Admin\Product\CategoryInterface;
use App\Repositories\Interfaces\Admin\Product\ProductInterface;
use App\Repositories\Interfaces\Admin\SellerInterface;
use App\Repositories\Interfaces\Admin\SellerProfileInterface;
use App\Repositories\Interfaces\Admin\Service\ServiceInterface;
use App\Repositories\Interfaces\Admin\Slider\BannerInterface;
use App\Repositories\Interfaces\Admin\Slider\SliderInterface;
use App\Repositories\Interfaces\Site\CartInterface;
use App\Repositories\Interfaces\Site\WishlistInterface;
use App\Traits\ApiReturnFormatTrait;
use App\Traits\HomePage;
use App\Traits\MetaGeneratorTrait;
use App\Utility\AppSettingUtility;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use ApiReturnFormatTrait,MetaGeneratorTrait,HomePage;


    public function parseMobileSettingsData($media, $category, $seller, $brand, $campaign, $product, $blog, $slider, $service, $shopping, $request)
    {

        $settings = settingHelper('mobile_home_page_contents');

        $results[] = [
            'section_type' => 'categories',
            'categories' => CategoryWithoutChildResource::collection($category->mobileCategory(get_pagination('api_paginate'))),
        ];
        $results[] = [
            'section_type' => 'slider',
            'slider' => SliderResource::collection($slider->homeScreenSliders()),
        ];

        if (settingHelper('mobile_service_info_section') == 1) {
            $results[] = [
                'section_type' => 'benefits',
                'benefits' => ServiceResource::collection($service->frontendService()),
            ];
        }

        if ($settings) {
            foreach ($settings as $key => $setting) {
                $key = $key + 2;
                foreach ($setting as $set_key => $item) {
                    if ($set_key == 'banner') {
                        $data['section_title'] = 'banner';
                        $banners = [];
                        if (array_key_exists('action_type',$item))
                        {
                            $action_types = $item['action_type'];
                            foreach ($item['thumbnail'] as $banner_key => $thumbnail) {
                                $image = $media->get($thumbnail);
                                $action_title = '';
                                $action_id = '';
                                if ($action_types[$banner_key] == 'product' && is_array($item['action_to'][$banner_key]) && array_key_exists($banner_key,$item['action_to'][$banner_key])) {
                                    $action_id = $item['action_to'][$banner_key][$banner_key];
                                    $banner_product = $product->get($action_id);
                                    $action_title =$banner_product ? nullCheck($banner_product->getTranslation('name', apiLanguage($request->lang))) : '';
                                } else if ($action_types[$banner_key] == 'category' && is_array($item['action_to'][$banner_key]) && array_key_exists($banner_key,$item['action_to'][$banner_key])) {
                                    $action_id = $item['action_to'][$banner_key][$banner_key];
                                    $banner_category = $category->get($action_id);
                                    $action_title = $banner_category ? nullCheck($banner_category->getTranslation('title', apiLanguage($request->lang))) : '';
                                } else if ($action_types[$banner_key] == 'brand' && is_array($item['action_to'][$banner_key]) && array_key_exists($banner_key,$item['action_to'][$banner_key])) {
                                    $action_id = $item['action_to'][$banner_key][$banner_key];
                                    $banner_brand = $brand->get($action_id);
                                    $action_title = $banner_brand ? nullCheck($banner_brand->getTranslation('title', apiLanguage($request->lang))) : '';

                                } else if ($action_types[$banner_key] == 'seller' && is_array($item['action_to'][$banner_key]) && array_key_exists($banner_key,$item['action_to'][$banner_key])) {
                                    $action_id = $item['action_to'][$banner_key][$banner_key];
                                    $banner_seller = $seller->getSeller($action_id);
                                    $action_title = $banner_seller ? nullCheck($banner_seller->shop_name) : '';
                                } else if ($action_types[$banner_key] == 'blog' && is_array($item['action_to'][$banner_key]) && array_key_exists($banner_key,$item['action_to'][$banner_key])) {
                                    $action_id = $item['action_to'][$banner_key][$banner_key];
                                    $banner_blog = $blog->get($action_id);
                                    $action_title = $banner_blog ? nullCheck($banner_blog->getTranslation('title', apiLanguage($request->lang))) : '';
                                } else if ($action_types[$banner_key] == 'url') {
                                    $action_title = nullCheck($item['action_to'][$banner_key][$banner_key]);
                                }

                                $banners[] = [
                                    'thumbnail' => @is_file_exists($image->image_variants['image_300x170'], $image->image_variants['storage']) ? @get_media($image->image_variants['image_300x170'], $image->image_variants['storage']) : static_asset('images/default/default-image-300x170.png'),
                                    'action_type' => $action_types[$banner_key],
                                    'action_to' => $action_title,
                                    'action_id' => $action_id,
                                ];
                            }
                            $results = $this->keyDefine('banners', $key, $banners, $results);
                        }
                    }
                    if ($set_key == 'campaign') {
                        $campaign_data = $campaign->campaignByIds($item);
                        if (count($campaign_data) > 0) {
                            $data['section_title'] = 'campaign';
                            $results = $this->keyDefine('campaigns', $key, CampaignResource::collection($campaign_data), $results);
                        }
                    }
                    if ($set_key == 'popular_category') {
                        $categories = $category->categoryByIds($item);
                        if (count($categories) > 0) {
                            $data['section_title'] = 'popular_categories';
                            $popular_category = CategoryWithoutChildResource::collection($categories);
                            $results = $this->keyDefine('popular_categories', $key, $popular_category, $results);
                        }
                    }
                    if ($set_key == 'top_category') {
                        $categories = $category->categoryByIds($item, 8);
                        if (count($categories) > 0) {
                            $data['section_title'] = 'top_categories';
                            $top_categories = CategoryWithoutChildResource::collection($categories);
                            $results = $this->keyDefine('top_categories', $key, $top_categories, $results);
                        }
                    }
                    if ($set_key == 'todays_deal') {
                        $products = $product->todayDeals();
                        if (count($products) > 0)
                        {
                            $todays_deal = ProductResource::collection($products);
                            $results = $this->keyDefine('today_deals', $key, $todays_deal, $results);
                        }
                    }
                    if ($set_key == 'custom_products') {
                        $products = $product->productByIds($item);
                        if (count($products) > 0)
                        {
                            $todays_deal = ProductResource::collection($products);
                            $results = $this->keyDefine('custom_products', $key, $todays_deal, $results);
                        }
                    }
                    if ($set_key == 'flash_deal') {
                        $products = $product->campaignProducts();
                        if (count($products) > 0)
                        {
                            $flash_products = ProductResource::collection($products);
                            $results = $this->keyDefine('flash_deals', $key, $flash_products, $results);
                        }
                    }
                    if ($set_key == 'latest_product') {
                        $products = $product->latestProducts();
                        if (count($products) > 0)
                        {
                            $latest_products = ProductResource::collection($products);
                            $results = $this->keyDefine('latest_products', $key, $latest_products, $results);
                        }
                    }
                    if ($set_key == 'category_section') {
                        $category_data = $category->categoryProducts($item['category']);

                        if ($category_data && count($category_data->products) > 0)
                        {
                            $category_products = new CategoryProductResource($category_data);
                            $results = $this->keyDefine('category_section', $key, $category_products, $results);
                        }
                    }
                    if ($set_key == 'best_selling_products') {
                        $products = $product->bestSelling();
                        if (count($products) > 0)
                        {
                            $best_selling_products = ProductResource::collection($products);
                            $results = $this->keyDefine('best_selling_products', $key, $best_selling_products, $results);
                        }
                    }
                    if ($set_key == 'offer_ending_soon') {
                        $products = $product->offerEndingSoon();
                        if (count($products) > 0)
                        {
                            $offer_end = ProductResource::collection($products);
                            $results = $this->keyDefine('offer_ending', $key, $offer_end, $results);
                        }
                    }
                    if ($set_key == 'latest_news') {
                        $blogs = $blog->homePageBlogs();
                        if (count($blogs) > 0)
                        {
                            $latest_news = BlogResource::collection($blogs);
                            $results = $this->keyDefine('latest_news', $key, $latest_news, $results);
                        }
                    }
                    if ($set_key == 'popular_brands') {
                        $brands = $brand->homePageBrands();
                        if (count($brands) > 0)
                        {
                            $brands = BrandResource::collection($brands);
                            $results = $this->keyDefine('popular_brands', $key, $brands, $results);
                        }
                    }
                    if ($set_key == 'top_sellers') {
                        $sellers = $seller->homePageSellers();
                        if (count($sellers) > 0)
                        {
                            $sellers = settingHelper('seller_system') == 1 ? TopSellerResource::collection($sellers) : [];
                            $results = $this->keyDefine('top_shops', $key, $sellers, $results);
                        }
                    }
                    if ($set_key == 'best_sellers') {
                        $sellers = $seller->homePageBestSellers();
                        if(count($sellers) > 0)
                        {
                            $best_sellers = settingHelper('seller_system') == 1 ? SellerResource::collection($sellers) : [];
                            $results = $this->keyDefine('best_shops', $key, $best_sellers, $results);
                        }
                    }
                    if ($set_key == 'featured_sellers') {
                        $sellers = $seller->homePageFeaturedSellers($item);
                        if (count($sellers) > 0)
                        {
                            $featured_sellers = settingHelper('seller_system') == 1 ? SellerResource::collection($sellers) : [];
                            $results = $this->keyDefine('featured_shops', $key, $featured_sellers, $results);
                        }
                    }
                    if ($set_key == 'express_sellers') {
                        $sellers = $seller->homePageExpressSellers($item);
                        if(count($sellers) > 0)
                        {
                            $express_sellers = settingHelper('seller_system') == 1 ? TopSellerResource::collection($sellers) : [];
                            $results = $this->keyDefine('express_shops', $key, $express_sellers, $results);
                        }
                    }
                    if ($set_key == 'video_shopping' && addon_is_activated('video_shopping')) {
                        $videos = $shopping->all()->active()->SellerCheck()->take(4)->get();
                        if (count($videos) > 0)
                        {
                            $videos = VideoResource::collection($videos);

                            $results = $this->keyDefine('video_shopping', $key, $videos, $results);
                        }
                    }
                }
            }
        }

        if (settingHelper('mobile_recent_viewed_products') == 1) {
            $products = $product->viewedProduct();
            if (count($products) > 0)
            {
                $results[] = [
                    'section_type' => 'recent_viewed_product',
                    'recent_viewed_product' => ProductResource::collection($products),
                ];
            }

        }

        if (settingHelper('mobile_subscription_section') == 1) {
            $results[] = [
                'section_type' => 'subscription_section',
                'subscription_section' => true,
            ];
        }

        return $results;
    }

    protected function keyDefine($key, $index, $data, $results): array
    {
        $results[$index] = [
            'section_type'  => $key,
            $key            => $data,
        ];

        return array_values($results);
    }

    public function homePageData(MediaInterface  $media, CategoryInterface $category, SellerInterface $seller, BrandInterface $brand, CampaignInterface $campaign, ProductInterface $product, BlogInterface $blog,
                                 SliderInterface $slider, ServiceInterface $service, VideoShoppingInterface $shopping, Request $request)
    {
        try {
            $data = $this->parseMobileSettingsData($media, $category, $seller, $brand, $campaign, $product, $blog, $slider, $service, $shopping, $request);

            return $this->responseWithSuccess(__('Date Fetched Successfully'), $data, 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }

    public function settingsData($page): array
    {

        $lang = languageCheck();

        $popup_modal = [];

        $stripe                         = settingData(get_yrsetting('is_stripe_activated'));
        $social_links                   = settingData(['facebook_link', 'twitter_link', 'instagram_link', 'youtube_link', 'linkedin_link']);
        $footer_data                    = settingData(['footer_contact_phone', 'footer_contact_email', 'footer_contact_address']);
        $currency_setting               = settingData(['decimal_separator', 'currency_symbol_format']);
        $header_data                    = settingData(['default_language', 'system_name', 'default_currency', 'header_contact_phone', 'header_contact_email', 'language_switcher', 'currency_switcher','seller_system','topbar_play_store_link','topbar_app_store_link','header_contact_number']);
        $store_links                    = settingData(['play_store_link', 'apple_store_link']);
        $other_data                     = settingData(['is_google_login_activated', 'is_facebook_login_activated', 'is_twitter_login_activated']);
        $recaptcha                      = settingData(['is_recaptcha_activated', 'recaptcha_Site_key']);
        $modules                        = settingData(['seller_system', 'color','pickup_point','wallet_system','coupon_system','pay_later_system']);
        $agreements                     = [
            'seller_agreement'          => PageGdprResource::collection($page->pageByLink(settingHelper('seller_agreement') && is_array(settingHelper('seller_agreement')) ? settingHelper('seller_agreement') : [])),
            'customer_agreement'        => PageGdprResource::collection($page->pageByLink(settingHelper('customer_agreement') && is_array(settingHelper('customer_agreement')) ? settingHelper('customer_agreement') : [])),
            'privacy_agreement'         => PageGdprResource::collection($page->pageByLink(settingHelper('privacy_agreement') && is_array(settingHelper('privacy_agreement')) ? settingHelper('privacy_agreement') : [])),
            'refund_policy_agreement'   => settingHelper('refund_policy_agreement'),
            'payment_agreement'         => PageGdprResource::collection($page->pageByLink(settingHelper('payment_agreement') && is_array(settingHelper('payment_agreement')) ? settingHelper('payment_agreement') : []))
        ];
        $map                            = settingData(['map_api_key', 'zoom_level','latitude','longitude']);

        $menu = [
            'footer_menu'                       => headerFooterMenu('footer_menu',$lang) ? headerFooterMenu('footer_menu',$lang) : headerFooterMenu('footer_menu'),
            'header_menu'                       => headerFooterMenu('header_menu',$lang) ? headerFooterMenu('header_menu',$lang) : headerFooterMenu('header_menu'),
            'useful_links'                      => settingHelper('useful_links')
        ];

        $popup_array = ['popup_title', 'popup_description', 'popup_image','site_popup_status','popup_show_in'];
        foreach ($popup_array as $key => $pop_data):
            $popup_modal[$pop_data] = settingHelper($pop_data, $lang);
        endforeach;

        if (array_key_exists('popup_image',$popup_modal))
        {
            $popup_modal['popup_image'] = getFileLink('270x260',settingHelper('popup_image'));
        }

        $ngn_exchange_rate      = 1;
        $is_paystack_activated  = settingHelper('is_paystack_activated') == 1;
        $is_flutterwave_activated  = settingHelper('is_flutterwave_activated') == 1;
        $is_mollie_activated       = settingHelper('is_mollie_activated') == 1;

        $euro = AppSettingUtility::currencies()->where('code','EUR')->first();
        if(!$euro):
            $is_mollie_activated    = 0;
        endif;

        $settings = [
            'light_logo'                        => settingHelper('light_logo') != [] && @is_file_exists(settingHelper('light_logo')['image_138x52']) ?  get_media(@settingHelper('light_logo')['image_138x52'], @settingHelper('light_logo')['storage']) : static_asset('images/default/logo.png'),
            'dark_logo'                         => settingHelper('dark_logo') != [] && @is_file_exists(settingHelper('dark_logo')['image_138x52']) ?  get_media(@settingHelper('dark_logo')['image_138x52'], @settingHelper('dark_logo')['storage']) : static_asset('images/default/dark-logo.png'),
            'subscription_section'              => settingHelper('show_subscription_section'),
            'copyright'                         => settingHelper('copyright',languageCheck()),
            'about_description'                 => settingHelper('about_description',languageCheck()),
            'article_section'                   => settingHelper('show_blog_section'),
            'recent_viewed'                     => settingHelper('show_recent_viewed_products'),
            'category_Section'                  => settingHelper('show_categories_section'),
            'article'                           => settingHelper('home_page_article'),
            'show_social_links'                 => settingHelper('social_link_status'),
            'show_service_info_section'         => settingHelper('show_service_info_section'),
            'payment_method_banner'             => @get_media(@settingHelper('payment_method_banner')['image_48x25'], @settingHelper('payment_method_banner')['storage']),
            'login_banner'                      => @getFileLink('320x520',settingHelper('login_banner')['images']),
            'top_bar_banner'                    => settingHelper('top_bar_banner') != null && @is_file_exists(settingHelper('top_bar_banner')['images']['original_image'],settingHelper('top_bar_banner')['images']['storage']) ? @get_media(settingHelper('top_bar_banner')['images']['original_image'],settingHelper('top_bar_banner')['images']['storage']) : '',
            'sign_up_banner'                    => @getFileLink('320x520',settingHelper('sing_up_banner')['images']),
            'affiliate_sing_up_banner'          => @getFileLink('320x520',settingHelper('affiliate_sing_up_banner')['images']),
            'seller_sing_up_banner'             => @getFileLink('320x852',settingHelper('seller_sing_up_banner')['images']),
            'forgot_password_banner'            => @getFileLink('320x520',settingHelper('forgot_password_banner')['images']),
            'user_dashboard_banner'             => @getFileLink('940x110',settingHelper('user_dashboard_banner')['images']),
            'affiliate_program_banner'          => @getFileLink('1920x412',settingHelper('affiliate_program_banner')['images']),
            'product_details_site_banner'       => @get_media(@settingHelper('product_details_site_banner')['images']['image_263x263'], @settingHelper('product_details_site_banner')['images']['storage']),
            'category_default_banner'           => @getFileLink('835x200',settingHelper('category_default_banner')['images']),
            'visa_pay_banner'                   => settingHelper('visa_pay_banner') == 1,
            'master_card_pay_banner'            => settingHelper('master_card_pay_banner') == 1,
            'american_express_pay_banner'       => settingHelper('american_express_pay_banner') == 1,
            'paypal_payment_banner'             => settingHelper('paypal_payment_banner') == 1,
            'apple_pay_banner'                  => settingHelper('apple_pay_banner') == 1,
            'affiliate_terms_condition'         => settingHelper('affiliate_terms_condition'),
            'lang_file'                         => file_exists(base_path('resources/lang/' . $lang . '.json')) ?  url("resources/lang/$lang.json")  : url('resources/lang/en.json'),
            'after_pay_banner'                  => settingHelper('after_pay_banner') == 1,
            'amazon_pay_banner'                 => settingHelper('amazon_pay_banner') == 1,
            'is_recaptcha_activated'            => settingHelper('is_recaptcha_activated'),
            'shipping_fee_type'                 => settingHelper('shipping_fee_type'),
            'header_theme'                      => settingHelper('header_theme'),
            'full_width_menu_background'        => settingHelper('full_width_menu_background'),
            'is_paypal_activated'               => settingHelper('is_paypal_activated'),
            'is_stripe_activated'               => settingHelper('is_stripe_activated'),
            'is_razorpay_activated'             => settingHelper('is_razorpay_activated'),
            'is_sslcommerz_activated'           => settingHelper('is_sslcommerz_activated'),
            'is_paytm_activated'                => settingHelper('is_paytm_activated'),
            'is_jazz_cash_activated'            => settingHelper('is_jazz_cash_activated'),
            'is_paystack_activated'             => $is_paystack_activated,
            'is_flutterwave_activated'          => $is_flutterwave_activated,
            'ngn_exchange_rate'                 => $ngn_exchange_rate,
            'is_mollie_activated'               => $is_mollie_activated,
            'reward_convert_rate'               => settingHelper('reward_convert_rate'),
            'refund_with_shipping_cost'         => settingHelper('refund_with_shipping_cost'),
            'refund_request_time'               => settingHelper('refund_request_time'),
            'wholesale_price_variations_show'   => settingHelper('wholesale_price_variations_show'),
            'gdpr'                              => settingHelper('cookies_agreement', $lang),
            'gdpr_enable'                       => settingHelper('cookies_status'),
            'footer_logo'                       => settingHelper('footer_logo') != [] && @is_file_exists(settingHelper('footer_logo')['image_89x33']) ? get_media(settingHelper('footer_logo')['image_89x33'],settingHelper('footer_logo')['storage']) : static_asset('images/default/logo-89x33.png'),
            'text_direction'                    => session()->has('text_direction') ? session()->get('text_direction') : 'ltl',
            'demo_mode'                         => isDemoServer(),
            'ssl_sandbox'                       => settingHelper('is_sslcommerz_sandbox_mode_activated'),
            'razor_key'                         => settingHelper('razorpay_key'),
            'paypal_key'                        => settingHelper('paypal_client_id'),
            'current_version'                   => settingHelper('current_version'),
            'shipping_cost'                     => settingHelper('shipping_fee_type'),
            'system_name'                       => settingHelper('system_name'),
            'default_country'                   => settingHelper('default_country') ? (int)settingHelper('default_country') : 19,
            'menu_background_color'             => settingHelper('menu_background_color'),
            'pushar_activated'                  => settingHelper('is_pusher_notification_active') == 1,
            'flw_public_key'                    => settingHelper('flutterwave_public_key'),
            'paystack_pk'                       => settingHelper('paystack_public_key'),
            'refund_sticker'                    => settingHelper('refund_sticker') != [] && @is_file_exists(settingHelper('refund_sticker')['image_45x45'] , settingHelper('refund_sticker')['storage'])  ?  get_media(@settingHelper('refund_sticker')['image_45x45'] , settingHelper('refund_sticker')['storage']) : static_asset('images/others/policy-icon.svg'),
            'refund_protection_title'           => settingHelper('refund_protection_title', $lang),
            'refund_protection_sub_title'       => settingHelper('refund_protection_sub_title', $lang),
            'tax_type'                          => settingHelper('vat_type') && settingHelper('vat_type') == 'after_tax' ? 'after_tax' : 'before_tax',
            'vat_and_tax_type'                  => settingHelper('vat_and_tax_type'),
            'is_mercado_pago_activated'         => settingHelper('is_mercado_pago_activated'),
            'is_mid_trans_activated'            => (bool)settingHelper('is_mid_trans_activated'),
            'mid_trans_client_id'               => settingHelper('mid_trans_client_id'),
            'is_telr_activated'                 => (bool)settingHelper('is_telr_activated'),
            'is_google_pay_activated'           => (bool)settingHelper('is_google_pay_activated'),
            'google_pay_merchant_name'          => settingHelper('google_pay_merchant_name') ? : 'Example Merchant',
            'google_pay_merchant_id'            => settingHelper('google_pay_merchant_id') ? : '0123456789',
            'google_pay_gateway'                => settingHelper('google_pay_gateway') ? : 'example',
            'google_pay_gateway_merchant_id'    => settingHelper('google_pay_gateway_merchant_id') ? : 'exampleGatewayMerchantId',
            'is_amarpay_activated'              => (bool)settingHelper('is_amarpay_activated'),
            'is_bkash_activated'                => (bool)settingHelper('is_bkash_activated'),
            'is_nagad_activated'                => (bool)settingHelper('is_nagad_activated'),
            'is_skrill_activated'               => (bool)settingHelper('is_skrill_activated'),
            'is_iyzico_activated'               => (bool)settingHelper('is_iyzico_activated'),
            'is_kkiapay_activated'              => (bool)settingHelper('is_kkiapay_activated'),
            'is_kkiapay_sandboxed'              => (bool)settingHelper('is_kkiapay_sandbox_enabled'),
            'kkiapay_public_key'                => settingHelper('kkiapay_public_api_key'),
            'no_of_decimals'                    => (int)settingHelper('no_of_decimals'),
            'disable_otp'                       => (bool)settingHelper('disable_otp_verification'),
            'disable_guest'                     => (bool)settingHelper('disable_guest_checkout'),
        ];
        return array_merge($settings,$other_data,$store_links,$header_data,$menu,$footer_data,$social_links,$stripe,$currency_setting,$popup_modal,$recaptcha,$modules,$agreements,$map);
    }
    public function defaultAssets(): array
    {
        return [
            'static_asset'                  => static_asset(),
        ];
    }


    public function settingApi(LanguageInterface $language,CurrencyInterface $currency, WishlistInterface $wishlist, CartInterface $cart, CategoryInterface $category, SliderInterface $slider,BannerInterface $banner, ServiceInterface $service, ProductInterface $product,SellerProfileInterface $seller,BlogInterface $blog,BrandInterface $brand,AddonInterface $addon,PageInterface $page,$email=null,$resetCode=null)
    {
        if (isAppMode())
        {
            if (authUser())
            {
                return redirect()->route('dashboard');
            }
            return redirect()->route('admin.login.form');
        }

        if (!authUser() && settingHelper('disable_guest_checkout') == 1 && (url()->current() == url('checkout') || url()->current() == url('payment'))) {
            Toastr::error(__('login_first'),__('Error'));
            return redirect('login');
        }

        try {
            if(request()->route()->getName() == 'seller.register'):
                if (settingHelper('seller_system') != 1):
                    return redirect('/');
                endif;
            endif;

            if(request()->route()->parameter('email'))
            {
                $this->resetPassword($email,$resetCode);
            }

            $meta = $this->generateMeta($product,$blog,$category,$brand,$seller);

            if (array_key_exists('url_exception',$meta) && $meta['url_exception'] == 1)
            {
                return redirect('page-not-found');
            }

            $components = [];
            $home_page_contents = settingHelper('home_page_contents') ? settingHelper('home_page_contents') : [];
            foreach ($home_page_contents as $key => $item) {
                foreach ($item as $k=> $component) {
                    $components[] = $k;
                }
            }

            $lang           = languageCheck();
            $user_currency  = currencyCheck();

            if (settingHelper('default_currency'))
            {
                $default_currency           = settingHelper('default_currency');
            }
            else{
                $default_currency = 1;
            }

            $active_currency = $currency->get($user_currency) ? : ($default_currency ? : [
                'exchange_rate' => 1,
                'name'          => 'USD',
                'symbol'        => '$',
            ]);

            if (addon_is_activated('ishopet'))
            {
                $active_currency = geoLocale()['currency'];
            }

            $default_currency  = $currency->get($default_currency);

            $data = [
                'settings'                          => $this->settingsData($page),
                'languages'                         => settingHelper('language_switcher') == 1 ? $language->activeLanguages() : [],
                'currencies'                        => $currency->activeCurrency(),
                'user'                              => authUser() ? authUser()->makeHidden(['is_user_banned','permissions', 'newsletter_enable', 'otp', 'firebase_auth_id', 'created_at', 'updated_at', 'images', 'image_id']) : [],
                'active_language'                   => $language->getByLocale($lang),
                'active_currency'                   => $active_currency,
                'default_currency'                  => $default_currency ? : [
                    'exchange_rate'                 => 1,
                    'name'                          => 'USD',
                    'symbol'                        => '$'
                ],
                'wishlists'                         => $wishlist->getHeaderWishlist(),
                'user_wishlists'                    => $wishlist->getUserWishlist(),
                'shop_follower'                     => [],
                'carts'                             => $this->cartList($cart->all()->where('is_buy_now',0)),
                'categories'                        => [],
                'sliders'                           => \App\Http\Resources\SiteResource\SliderResource::collection($slider->frontendSliders()),
                'banners'                           => BannerResource::collection($banner->frontendBanners()),
                'services'                          => settingHelper('show_service_info_section') == 1 ? \App\Http\Resources\SiteResource\ServiceResource::collection($service->frontendService()) : [],
                'viewed_products'                   => [],
                'pages'                             => [],
                'compare_list'                      => $product->compareList(),
                'home_components'                   => $components,
                'meta'                              => $meta,
                'addons'                            => $addon->activePlugin(),
                'favicon'                           => [
                    'image_16x16'                   => @is_file_exists(@settingHelper('favicon')['image_16x16_url']) ? get_media(settingHelper('favicon')['image_16x16_url']) : static_asset('images/ico/favicon.ico'),
                    'image_144x144'                 => @is_file_exists(@settingHelper('favicon')['image_144x144_url']) ? get_media(settingHelper('favicon')['image_144x144_url']) : static_asset('images/ico/apple-touch-icon-precomposed.png'),
                    'image_114x114'                 => @is_file_exists(@settingHelper('favicon')['image_144x144_url']) ? get_media(settingHelper('favicon')['image_114x114_url']) : static_asset('images/ico/apple-touch-icon-114-precomposed.png'),
                    'image_72x72'                   => @is_file_exists(@settingHelper('favicon')['image_72x72_url']) ? get_media(settingHelper('favicon')['image_72x72_url']) : static_asset('images/ico/apple-touch-icon-72-precomposed.png'),
                    'image_57x57'                   => @is_file_exists(@settingHelper('favicon')['image_57x57_url']) ? get_media(settingHelper('favicon')['image_57x57_url']) : static_asset('images/ico/apple-touch-icon-57-precomposed.png'),
                ],
                'default_assets' => $this->defaultAssets(),
            ];
            return $this->responseWithSuccess(__('Data Successfully Found'), $data, 200);

        } catch (\Exception $e) {
            dd($e);
            return $e;
        }
    }

}