<?php

use App\Core\Middleware;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\MembersController;
use App\Controllers\Admin\SharesController;
use App\Controllers\Admin\ShareRequestsController as AdminShareRequestsController;
use App\Controllers\Admin\SubscriptionsController;
use App\Controllers\Admin\FoundingController as AdminFoundingController;
use App\Controllers\Admin\LoanRequestsController;
use App\Controllers\Admin\LoansController;
use App\Controllers\Admin\PaymentsController;
use App\Controllers\Admin\TransactionsController;
use App\Controllers\Admin\NotificationsController as AdminNotificationsController;
use App\Controllers\Admin\MessagesController;
use App\Controllers\Admin\ContentController;
use App\Controllers\Admin\SettingsController;

use App\Controllers\Site\AuthController as SiteAuthController;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\SharesController as SiteSharesController;
use App\Controllers\Site\ShareRequestsController as SiteShareRequestsController;
use App\Controllers\Site\FoundingController as SiteFoundingController;
use App\Controllers\Site\LoanController;
use App\Controllers\Site\NotificationsController as SiteNotificationsController;
use App\Controllers\Site\ProfileController;
use App\Controllers\Site\SettingsController as SiteSettingsController;
use App\Controllers\Site\PageController;

/** @var \App\Core\Router $router */

// =========================================================
// Language Switcher Routes (Independent Admin & Site)
// =========================================================
$router->get('/lang/{locale}', function ($locale) {
    \App\Core\Lang::setSiteLocale($locale);
    redirect_back('/');
});

$router->get('/admin', function () {
    redirect_to('admin/dashboard');
});

$router->get('/admin/lang/{locale}', function ($locale) {
    \App\Core\Lang::setAdminLocale($locale);
    redirect_back('admin/dashboard');
});

// =========================================================
// Public / customer website
// =========================================================
$router->get('/', [PageController::class, 'landing']);
$router->get('/page/{slug}', [PageController::class, 'show']);
$router->post('/page/{slug}/form', [PageController::class, 'submitForm']);
$router->post('/page/{slug}', [PageController::class, 'submitForm']);

$router->group('', [Middleware::memberGuest()], function ($router) {
    $router->get('/register', [SiteAuthController::class, 'showRegister']);
    $router->post('/register', [SiteAuthController::class, 'register']);
    $router->get('/register/verify', [SiteAuthController::class, 'showVerifyRegister']);
    $router->post('/register/verify', [SiteAuthController::class, 'verifyRegister']);
    $router->post('/register/resend', [SiteAuthController::class, 'resendRegisterOtp']);

    $router->get('/login', [SiteAuthController::class, 'showLogin']);
    $router->post('/login', [SiteAuthController::class, 'login']);

    $router->get('/forgot-password', [SiteAuthController::class, 'showForgot']);
    $router->post('/forgot-password', [SiteAuthController::class, 'sendOtp']);
    $router->get('/forgot-password/verify', [SiteAuthController::class, 'showVerify']);
    $router->post('/forgot-password/verify', [SiteAuthController::class, 'verifyOtp']);
    $router->get('/forgot-password/reset', [SiteAuthController::class, 'showReset']);
    $router->post('/forgot-password/reset', [SiteAuthController::class, 'reset']);
});

$router->post('/logout', [SiteAuthController::class, 'logout'], [Middleware::memberAuth()]);

$router->group('', [Middleware::memberAuth()], function ($router) {
    $router->get('/home', [HomeController::class, 'index']);

    $router->get('/shares', [SiteSharesController::class, 'index']);

    $router->get('/share-requests', [SiteShareRequestsController::class, 'index']);
    $router->post('/share-requests', [SiteShareRequestsController::class, 'store']);

    $router->get('/founding', [SiteFoundingController::class, 'index']);

    $router->get('/loans', [LoanController::class, 'index']);
    $router->get('/loans/request', [LoanController::class, 'createRequest']);
    $router->post('/loans/request', [LoanController::class, 'storeRequest']);
    $router->get('/loans/{id}', [LoanController::class, 'show']);

    $router->get('/notifications', [SiteNotificationsController::class, 'index']);

    $router->get('/profile', [ProfileController::class, 'index']);
    $router->post('/profile', [ProfileController::class, 'update']);
    $router->post('/profile/password', [ProfileController::class, 'updatePassword']);

    $router->get('/settings', [SiteSettingsController::class, 'index']);
    $router->post('/settings/support', [SiteSettingsController::class, 'sendSupportMessage']);
    $router->post('/settings/language', [SiteSettingsController::class, 'setLanguage']);
});

// =========================================================
// Admin panel
// =========================================================
$router->group('/admin', [Middleware::adminGuest()], function ($router) {
    $router->get('/login', [AdminAuthController::class, 'showLogin']);
    $router->post('/login', [AdminAuthController::class, 'login']);

    $router->get('/forgot-password', [AdminAuthController::class, 'showForgot']);
    $router->post('/forgot-password', [AdminAuthController::class, 'sendOtp']);
    $router->get('/forgot-password/verify', [AdminAuthController::class, 'showVerify']);
    $router->post('/forgot-password/verify', [AdminAuthController::class, 'verifyOtp']);
    $router->get('/forgot-password/reset', [AdminAuthController::class, 'showReset']);
    $router->post('/forgot-password/reset', [AdminAuthController::class, 'reset']);
});

$router->post('/admin/logout', [AdminAuthController::class, 'logout'], [Middleware::adminAuth()]);

$router->group('/admin', [Middleware::adminAuth()], function ($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/dashboard/export', [DashboardController::class, 'export']);
    $router->get('/dashboard/print', [DashboardController::class, 'printReport']);

    $router->get('/members', [MembersController::class, 'index']);
    $router->get('/members/create', [MembersController::class, 'create']);
    $router->post('/members', [MembersController::class, 'store']);
    $router->get('/members/{id}', [MembersController::class, 'show']);
    $router->get('/members/{id}/edit', [MembersController::class, 'edit']);
    $router->post('/members/{id}', [MembersController::class, 'update']);
    $router->post('/members/{id}/toggle-status', [MembersController::class, 'toggleStatus']);

    $router->get('/shares', [SharesController::class, 'index']);
    $router->post('/shares/value', [SharesController::class, 'updateShareValue']);
    $router->post('/shares/{id}', [SharesController::class, 'updateMemberShares']);

    $router->get('/share-requests', [AdminShareRequestsController::class, 'index']);
    $router->get('/share-requests/{id}', [AdminShareRequestsController::class, 'show']);
    $router->post('/share-requests/{id}/approve', [AdminShareRequestsController::class, 'approve']);
    $router->post('/share-requests/{id}/reject', [AdminShareRequestsController::class, 'reject']);

    $router->get('/subscriptions', [SubscriptionsController::class, 'index']);
    $router->get('/subscriptions/{memberId}', [SubscriptionsController::class, 'show']);
    $router->post('/subscriptions/{memberId}/pay', [SubscriptionsController::class, 'recordPayment']);

    $router->get('/founding', [AdminFoundingController::class, 'index']);
    $router->get('/founding/{memberId}', [AdminFoundingController::class, 'show']);
    $router->post('/founding/{memberId}/pay', [AdminFoundingController::class, 'recordPayment']);

    $router->get('/loan-requests', [LoanRequestsController::class, 'index']);
    $router->get('/loan-requests/{id}', [LoanRequestsController::class, 'show']);
    $router->post('/loan-requests/{id}/approve', [LoanRequestsController::class, 'approve']);
    $router->post('/loan-requests/{id}/reject', [LoanRequestsController::class, 'reject']);

    $router->get('/loans', [LoansController::class, 'index']);
    $router->get('/loans/create', [LoansController::class, 'create']);
    $router->post('/loans', [LoansController::class, 'store']);
    $router->get('/loans/{id}', [LoansController::class, 'show']);
    $router->get('/loans/{id}/edit', [LoansController::class, 'edit']);
    $router->post('/loans/{id}', [LoansController::class, 'update']);
    $router->post('/loans/{id}/pay', [LoansController::class, 'recordPayment']);
    $router->post('/loans/{id}/close', [LoansController::class, 'close']);
    $router->post('/loans/{id}/delete', [LoansController::class, 'destroy']);

    $router->get('/payments', [PaymentsController::class, 'index']);
    $router->get('/payments/export', [PaymentsController::class, 'export']);
    $router->get('/payments/print', [PaymentsController::class, 'printReport']);

    $router->get('/transactions', [TransactionsController::class, 'index']);
    $router->get('/transactions/export', [TransactionsController::class, 'export']);
    $router->get('/transactions/print', [TransactionsController::class, 'printReport']);

    $router->get('/notifications', [AdminNotificationsController::class, 'index']);
    $router->post('/notifications', [AdminNotificationsController::class, 'store']);
    $router->post('/notifications/{id}/delete', [AdminNotificationsController::class, 'destroy']);

    $router->get('/messages', [MessagesController::class, 'index']);
    $router->get('/messages/{id}', [MessagesController::class, 'show']);
    $router->post('/messages/{id}/reply', [MessagesController::class, 'reply']);

    $router->get('/content', [ContentController::class, 'index']);
    $router->get('/content/{slug}/edit', [ContentController::class, 'edit']);
    $router->post('/content/{slug}', [ContentController::class, 'update']);

    $router->get('/settings', [SettingsController::class, 'index']);
    $router->post('/settings', [SettingsController::class, 'update']);
    $router->post('/settings/test-sms', [SettingsController::class, 'testSms']);
});
