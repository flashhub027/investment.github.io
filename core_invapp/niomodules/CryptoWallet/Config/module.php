<?php

use NioModules\CryptoWallet\CryptoWalletModule;

return [
    CryptoWalletModule::SLUG => [
        'name' => __('Crypto Wallet'),
        'slug' => CryptoWalletModule::SLUG,
        'method' => CryptoWalletModule::METHOD,
        'icon' => 'ni-wallet-fill',
        'full_icon' => 'ni-wallet-fill',
        'is_online' => false,
        'processor_type' => 'payment',
        'processor' => CryptoWalletModule::class,
        'supported_currency' => [
            'BTC', 'ETH', 'LTC', 'BCH', 'BNB', 'USDC', 'USDT', 'TRX'
        ],
        'system' => [
            'kind' => 'Payment',
            'info' => 'Manual / Offline',
            'type' => CryptoWalletModule::MOD_TYPES,
            'version' => CryptoWalletModule::VERSION,
            'update' => CryptoWalletModule::LAST_UPDATE,
            'description' => 'Accept crypto related payments manually from user.',
            'addons' => false,
        ]
    ],
];
