<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppSettingsController;
use App\Http\Controllers\BrokerController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExplorerController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PhoneNumberController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ShippingRateController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/super/login')->name('home');

require __DIR__.'/super.php';

Route::prefix('{tenant}')->group(function () {
        Route::get('/q/{token}', [QuotationController::class, 'public'])->name('quotes.public');

        Route::middleware(['auth'])->group(function () {
            Route::get('dashboard', DashboardController::class)->name('dashboard');

            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('accounts/export', [AccountController::class, 'export'])->name('accounts.export');
            Route::get('accounts/{id}', [AccountController::class, 'show'])->whereNumber('id')->name('accounts.show');
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
            Route::get('invoices/{id}', [InvoiceController::class, 'show'])->whereNumber('id')->name('invoices.show');
            Route::get('invoices/{id}/pdf', [InvoiceController::class, 'pdf'])->whereNumber('id')->name('invoices.pdf');

            Route::prefix('explorer')->name('explorer.')->group(function () {
                Route::get('analytics', [ExplorerController::class, 'analytics'])->name('analytics');
                Route::get('activity', [ExplorerController::class, 'activity'])->name('activity');
                Route::get('activity/export', [ExplorerController::class, 'activityExport'])->name('activity.export');
                Route::get('active-bids', [ExplorerController::class, 'activeBids'])->name('active-bids');
                Route::get('active-bids/export', [ExplorerController::class, 'activeBidsExport'])->name('active-bids.export');
                Route::get('bids', [ExplorerController::class, 'bids'])->name('bids');
                Route::get('bids/export', [ExplorerController::class, 'bidsExport'])->name('bids.export');
                Route::get('watchlist', [ExplorerController::class, 'watchlist'])->name('watchlist');
                Route::get('watchlist/export', [ExplorerController::class, 'watchlistExport'])->name('watchlist.export');
                Route::delete('watchlist/{item}', [ExplorerController::class, 'watchlistDestroy'])->name('watchlist.destroy');
            });

            Route::middleware('admin')->group(function () {
                Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
                Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
                Route::put('accounts/{account}/settings', [AccountController::class, 'updateSettings'])->name('accounts.settings');
                Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
                Route::post('accounts/{id}/restore', [AccountController::class, 'restore'])->name('accounts.restore');
                Route::post('accounts/{account}/reveal-password', [AccountController::class, 'revealPassword'])->name('accounts.reveal');
                Route::put('accounts/{account}/customers', [AccountController::class, 'syncCustomers'])->name('accounts.customers');
                Route::put('accounts/{account}/brokers', [AccountController::class, 'syncBrokers'])->name('accounts.brokers');
                Route::post('accounts/{account}/documents', [AccountController::class, 'uploadDocument'])->name('accounts.documents.store');
                Route::delete('accounts/{account}/documents/{document}', [AccountController::class, 'deleteDocument'])->name('accounts.documents.destroy');

                Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
                Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
                Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
                Route::get('customers/{id}', [CustomerController::class, 'show'])->whereNumber('id')->name('customers.show');
                Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
                Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
                Route::post('customers/{id}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
                Route::put('customers/{customer}/accounts', [CustomerController::class, 'syncAccounts'])->name('customers.accounts');
                Route::put('customers/{customer}/credentials', [CustomerController::class, 'credentials'])->name('customers.credentials');
                Route::post('customers/{customer}/tokens', [CustomerController::class, 'createToken'])->name('customers.tokens.store');
                Route::delete('customers/{customer}/tokens/{token}', [CustomerController::class, 'revokeToken'])->name('customers.tokens.destroy');

                Route::get('brokers', [BrokerController::class, 'index'])->name('brokers.index');
                Route::get('brokers/export', [BrokerController::class, 'export'])->name('brokers.export');
                Route::post('brokers', [BrokerController::class, 'store'])->name('brokers.store');
                Route::put('brokers/{broker}', [BrokerController::class, 'update'])->name('brokers.update');
                Route::delete('brokers/{broker}', [BrokerController::class, 'destroy'])->name('brokers.destroy');
                Route::post('brokers/{id}/restore', [BrokerController::class, 'restore'])->name('brokers.restore');

                Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
                Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
                Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
                Route::post('invoices/{id}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');

                Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
                Route::get('finance/export', [FinanceController::class, 'export'])->name('finance.export');
                Route::post('finance', [FinanceController::class, 'store'])->name('finance.store');
                Route::put('finance/{transaction}', [FinanceController::class, 'update'])->name('finance.update');

                Route::get('shipping-rates', [ShippingRateController::class, 'index'])->name('shipping-rates.index');
                Route::post('shipping-rates', [ShippingRateController::class, 'store'])->name('shipping-rates.store');
                Route::put('shipping-rates/{rate}', [ShippingRateController::class, 'update'])->name('shipping-rates.update');
                Route::delete('shipping-rates/{rate}', [ShippingRateController::class, 'destroy'])->name('shipping-rates.destroy');

                Route::get('quotations', [QuotationController::class, 'index'])->name('quotations.index');
                Route::post('quotations', [QuotationController::class, 'store'])->name('quotations.store');
                Route::delete('quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy');

                Route::get('numbers', [PhoneNumberController::class, 'index'])->name('numbers.index');
                Route::post('numbers', [PhoneNumberController::class, 'store'])->name('numbers.store');
                Route::put('numbers/{number}', [PhoneNumberController::class, 'update'])->name('numbers.update');
                Route::delete('numbers/{number}', [PhoneNumberController::class, 'destroy'])->name('numbers.destroy');

                Route::get('settings', [AppSettingsController::class, 'index'])->name('settings.index');
                Route::put('settings', [AppSettingsController::class, 'update'])->name('settings.update');
                Route::post('settings/logo', [AppSettingsController::class, 'logo'])->name('settings.logo');
                Route::delete('settings/logo', [AppSettingsController::class, 'removeLogo'])->name('settings.logo.destroy');
                Route::post('settings/tokens', [AppSettingsController::class, 'createToken'])->name('settings.tokens.store');
                Route::delete('settings/tokens/{token}', [AppSettingsController::class, 'revokeToken'])->name('settings.tokens.destroy');
                Route::post('settings/team', [AppSettingsController::class, 'addMember'])->name('settings.team.store');
                Route::delete('settings/team/{user}', [AppSettingsController::class, 'removeMember'])->name('settings.team.destroy');
            });
        });

        require __DIR__.'/settings.php';
        require __DIR__.'/auth.php';
    });
