<?php namespace Responsiv\Pay;

use Backend;
use Flash;
use RainLab\User\Controllers\Users;
use RainLab\User\Models\User;
use Redirect;
use Responsiv\Pay\Models\UserProfile;
use System\Classes\PluginBase;
use Lang;

/**
 * Pay Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User',
        'RainLab.UserPlus',
        'RainLab.Location',
        'Responsiv.Currency'
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => Lang::get('responsiv.pay::lang.name'),
            'description' => Lang::get('responsiv.pay::lang.description'),
            'author'      => 'Responsiv Internet',
            'icon'        => 'icon-credit-card',
            'homepage'    => 'https://github.com/responsiv/pay-plugin'
        ];
    }

    public function boot()
    {
        User::extend(function($model) {
            $model->hasMany['payment_profiles'] = [UserProfile::class];
        });

        Users::extendFormFields(function($form, $model, $context)
        {
            if (!$model instanceof User || $context != 'preview') {
                return;
            }

            $form->addSecondaryTabFields([
                'payment_profiles' => [
                    'label'   => 'Payment profiles',
                    'type' => 'partial',
                    'path' => '$/responsiv/pay/partials/_payment_profiles.htm'
                ],
            ]);

        });

        Users::extend(function($controller) {
            $controller->addDynamicMethod('onLoadProfilePopup', function() use($controller) {
                $profile = UserProfile::find(post('profile_id'));
                $partialName = '$/responsiv/pay/paymenttypes/stripe/profile_form.htm';

                return $controller->makePartial('$/responsiv/pay/partials/_payment_profile_popup.htm', ['profile' => $profile, 'form' => $controller->makePartial($partialName)]);
            });

            $controller->addDynamicMethod('onUpdatePaymentProfile', function() use($controller) {
                $profile = UserProfile::find(post('profile_id'));
                $paymentMethod = $profile->payment_method;

                $result = $paymentMethod->updateUserProfile($profile->user, post());

                if ($result) {
                    Flash::success('Payment profile updated.');
                } else {
                    Flash::error('Something went wrong.');
                }

                return Redirect::refresh();
            });

            $controller->addDynamicMethod('onDeletePaymentProfile', function() use($controller) {
                $profile = UserProfile::find(post('profile_id'));
                $paymentMethod = $profile->payment_method;

                $result = $paymentMethod->deleteUserProfile($profile->user, post());
                Flash::success('Payment profile deleted.');

                return Redirect::refresh();
            });


        });
    }

    public function registerComponents()
    {
        return [
            \Responsiv\Pay\Components\Payment::class  => 'payment',
            \Responsiv\Pay\Components\Invoice::class  => 'invoice',
            \Responsiv\Pay\Components\Invoices::class => 'invoices',
            \Responsiv\Pay\Components\Profile::class  => 'payProfile',
            \Responsiv\Pay\Components\Profiles::class => 'payProfiles',
        ];
    }

    /**
     * Registers any payment gateways implemented in this plugin.
     * The gateways must be returned in the following format:
     * ['className1' => 'alias'],
     * ['className2' => 'anotherAlias']
     */
    public function registerPaymentGateways()
    {
        return [
            \Responsiv\Pay\PaymentTypes\PaypalStandard::class => 'paypal-standard',
            \Responsiv\Pay\PaymentTypes\PaypalAdaptive::class => 'paypal-adaptive',
            \Responsiv\Pay\PaymentTypes\PaypalPro::class      => 'paypal-pro',
            \Responsiv\Pay\PaymentTypes\Offline::class        => 'offline',
            \Responsiv\Pay\PaymentTypes\Skrill::class         => 'skrill',
            \Responsiv\Pay\PaymentTypes\Stripe::class         => 'stripe',
        ];
    }

    public function registerNavigation()
    {
        return [
            'pay' => [
                'label'       => Lang::get('responsiv.pay::lang.menu.payments'),
                'url'         => Backend::url('responsiv/pay/invoices'),
                'icon'        => 'icon-credit-card',
                'iconSvg'     => 'plugins/responsiv/pay/assets/images/pay-icon.svg',
                'permissions' => ['pay.*'],
                'order'       => 520,

                'sideMenu' => [
                    'invoices' => [
                        'label'       => Lang::get('responsiv.pay::lang.menu.invoices'),
                        'icon'        => 'icon-file-text-o',
                        'url'         => Backend::url('responsiv/pay/invoices'),
                        'permissions' => ['pay.access_invoices'],
                    ],
                    'taxes' => [
                        'label'       => Lang::get('responsiv.pay::lang.menu.tax'),
                        'icon'        => 'icon-table',
                        'url'         => Backend::url('responsiv/pay/taxes'),
                        'permissions' => ['pay.manage_taxes'],
                        'order'       => 500,
                    ],
                    'types' => [
                        'label'       => Lang::get('responsiv.pay::lang.menu.gateways'),
                        'icon'        => 'icon-money',
                        'url'         => Backend::url('responsiv/pay/paymentmethods'),
                        'permissions' => ['pay.manage_gateways'],
                        'order'       => 510,
                    ],
                ]
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => Lang::get('responsiv.pay::lang.settings.name'),
                'description' => Lang::get('responsiv.pay::lang.settings.description'),
                'icon'        => 'icon-credit-card',
                'class'       => 'Responsiv\Pay\Models\Settings',
                'category'    => Lang::get('responsiv.pay::lang.name'),
                'order'       => 520,
            ],
            'invoice_template' => [
                'label'       => Lang::get('responsiv.pay::lang.invoice_template.name'),
                'description' => Lang::get('responsiv.pay::lang.invoice_template.description'),
                'icon'        => 'icon-file-excel-o',
                'url'         => Backend::url('responsiv/pay/invoicetemplates'),
                'category'    => Lang::get('responsiv.pay::lang.name'),
                'order'       => 520,
            ]
        ];
    }
}
