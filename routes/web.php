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
use App\Controllers\Admin\HomePageController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\LiveTranslateController;
use App\Controllers\Admin\RolesController;

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

// Live Translate JSON endpoints. They are called from the site as well as from the dashboard and answer JSON
// errors (403 / 419) themselves, so they stay out of the 'adminAuth' group whose middleware redirects to the login.
$router->post('/admin/live-translate/resolve', [LiveTranslateController::class, 'resolve']);
$router->post('/admin/live-translate/save', [LiveTranslateController::class, 'save']);

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
    $router->post('/founding/plan', [SiteFoundingController::class, 'setPlan']);

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
    // Dashboard Overview
    $router->get('/dashboard', [DashboardController::class, 'index'], [Middleware::permission('dashboard.view')]);
    $router->get('/dashboard/export', [DashboardController::class, 'export'], [Middleware::permission('dashboard.export')]);
    $router->get('/dashboard/print', [DashboardController::class, 'printReport'], [Middleware::permission('dashboard.export')]);

    // Members Management
    $router->get('/members', [MembersController::class, 'index'], [Middleware::permission('members.view')]);
    $router->get('/members/create', [MembersController::class, 'create'], [Middleware::permission('members.create')]);
    $router->post('/members', [MembersController::class, 'store'], [Middleware::permission('members.create')]);
    $router->get('/members/{id}', [MembersController::class, 'show'], [Middleware::permission('members.view')]);
    $router->get('/members/{id}/edit', [MembersController::class, 'edit'], [Middleware::permission('members.edit')]);
    $router->post('/members/{id}', [MembersController::class, 'update'], [Middleware::permission('members.edit')]);
    $router->post('/members/{id}/toggle-status', [MembersController::class, 'toggleStatus'], [Middleware::permission('members.edit')]);
    $router->post('/members/{id}/delete', [MembersController::class, 'destroy'], [Middleware::permission('members.delete')]);

    // Shares & Capital
    $router->get('/shares', [SharesController::class, 'index'], [Middleware::permission('shares.view')]);
    $router->post('/shares/value', [SharesController::class, 'updateShareValue'], [Middleware::permission('shares.manage')]);
    $router->post('/shares/{id}', [SharesController::class, 'updateMemberShares'], [Middleware::permission('shares.manage')]);

    $router->get('/share-requests', [AdminShareRequestsController::class, 'index'], [Middleware::permission('share_requests.view')]);
    $router->get('/share-requests/{id}', [AdminShareRequestsController::class, 'show'], [Middleware::permission('share_requests.view')]);
    $router->post('/share-requests/{id}/approve', [AdminShareRequestsController::class, 'approve'], [Middleware::permission('share_requests.manage')]);
    $router->post('/share-requests/{id}/reject', [AdminShareRequestsController::class, 'reject'], [Middleware::permission('share_requests.manage')]);

    // Subscriptions
    $router->get('/subscriptions', [SubscriptionsController::class, 'index'], [Middleware::permission('subscriptions.view')]);
    $router->get('/subscriptions/{memberId}', [SubscriptionsController::class, 'show'], [Middleware::permission('subscriptions.view')]);
    $router->post('/subscriptions/{memberId}/pay', [SubscriptionsController::class, 'recordPayment'], [Middleware::permission('subscriptions.pay')]);

    // Founding Amounts
    $router->get('/founding', [AdminFoundingController::class, 'index'], [Middleware::permission('founding.view')]);
    $router->get('/founding/{memberId}', [AdminFoundingController::class, 'show'], [Middleware::permission('founding.view')]);
    $router->post('/founding/{memberId}/pay', [AdminFoundingController::class, 'recordPayment'], [Middleware::permission('founding.pay')]);

    // Loan Requests
    $router->get('/loan-requests', [LoanRequestsController::class, 'index'], [Middleware::permission('loan_requests.view')]);
    $router->get('/loan-requests/{id}', [LoanRequestsController::class, 'show'], [Middleware::permission('loan_requests.view')]);
    $router->post('/loan-requests/{id}/approve', [LoanRequestsController::class, 'approve'], [Middleware::permission('loan_requests.manage')]);
    $router->post('/loan-requests/{id}/reject', [LoanRequestsController::class, 'reject'], [Middleware::permission('loan_requests.manage')]);

    // Loans Portfolio
    $router->get('/loans', [LoansController::class, 'index'], [Middleware::permission('loans.view')]);
    $router->get('/loans/create', [LoansController::class, 'create'], [Middleware::permission('loans.create')]);
    $router->post('/loans', [LoansController::class, 'store'], [Middleware::permission('loans.create')]);
    $router->get('/loans/{id}', [LoansController::class, 'show'], [Middleware::permission('loans.view')]);
    $router->get('/loans/{id}/edit', [LoansController::class, 'edit'], [Middleware::permission('loans.edit')]);
    $router->post('/loans/{id}', [LoansController::class, 'update'], [Middleware::permission('loans.edit')]);
    $router->post('/loans/{id}/pay', [LoansController::class, 'recordPayment'], [Middleware::permission('loans.edit')]);
    $router->post('/loans/{id}/close', [LoansController::class, 'close'], [Middleware::permission('loans.edit')]);
    $router->post('/loans/{id}/delete', [LoansController::class, 'destroy'], [Middleware::permission('loans.delete')]);

    // Financial Ledger & Payments
    $router->get('/payments', [PaymentsController::class, 'index'], [Middleware::permission('payments.view')]);
    $router->get('/payments/export', [PaymentsController::class, 'export'], [Middleware::permission('payments.view')]);
    $router->get('/payments/print', [PaymentsController::class, 'printReport'], [Middleware::permission('payments.view')]);

    $router->get('/transactions', [TransactionsController::class, 'index'], [Middleware::permission('transactions.view')]);
    $router->get('/transactions/export', [TransactionsController::class, 'export'], [Middleware::permission('transactions.view')]);
    $router->get('/transactions/print', [TransactionsController::class, 'printReport'], [Middleware::permission('transactions.view')]);

    // Notifications
    $router->get('/notifications', [AdminNotificationsController::class, 'index'], [Middleware::permission('notifications.view')]);
    $router->post('/notifications', [AdminNotificationsController::class, 'store'], [Middleware::permission('notifications.send')]);
    $router->post('/notifications/{id}/delete', [AdminNotificationsController::class, 'destroy'], [Middleware::permission('notifications.send')]);

    // Contact Messages
    $router->get('/messages', [MessagesController::class, 'index'], [Middleware::permission('messages.view')]);
    $router->get('/messages/{id}', [MessagesController::class, 'show'], [Middleware::permission('messages.view')]);
    $router->post('/messages/{id}/reply', [MessagesController::class, 'reply'], [Middleware::permission('messages.reply')]);

    // Content Management
    $router->get('/content', [ContentController::class, 'index'], [Middleware::permission('content.manage')]);
    $router->get('/content/home', [HomePageController::class, 'edit'], [Middleware::permission('content.manage')]);
    $router->post('/content/home', [HomePageController::class, 'update'], [Middleware::permission('content.manage')]);
    $router->post('/content/home/upload', [HomePageController::class, 'upload'], [Middleware::permission('content.manage')]);
    $router->post('/content/home/reset', [HomePageController::class, 'reset'], [Middleware::permission('content.manage')]);
    $router->get('/content/{slug}/edit', [ContentController::class, 'edit'], [Middleware::permission('content.manage')]);
    $router->post('/content/{slug}', [ContentController::class, 'update'], [Middleware::permission('content.manage')]);

    // Live Translate
    $router->get('/live-translate', [LiveTranslateController::class, 'index'], [Middleware::permission('live_translate.manage')]);
    $router->post('/live-translate/toggle', [LiveTranslateController::class, 'toggle'], [Middleware::permission('live_translate.manage')]);
    $router->get('/live-translate/export', [LiveTranslateController::class, 'export'], [Middleware::permission('live_translate.manage')]);
    $router->post('/live-translate/import', [LiveTranslateController::class, 'import'], [Middleware::permission('live_translate.manage')]);
    $router->post('/live-translate/{id}/delete', [LiveTranslateController::class, 'deleteEntry'], [Middleware::permission('live_translate.manage')]);

    // Roles & Permissions Management Package
    $router->get('/roles', [RolesController::class, 'index'], [Middleware::permission('roles.manage')]);
    $router->get('/roles/create', [RolesController::class, 'create'], [Middleware::permission('roles.manage')]);
    $router->post('/roles', [RolesController::class, 'store'], [Middleware::permission('roles.manage')]);
    $router->get('/roles/assign', [RolesController::class, 'showAssignMember'], [Middleware::permission('roles.manage')]);
    $router->post('/roles/assign', [RolesController::class, 'saveMemberAssignment'], [Middleware::permission('roles.manage')]);
    $router->post('/roles/revoke/{id}', [RolesController::class, 'revokeMember'], [Middleware::permission('roles.manage')]);
    $router->get('/roles/{id}/edit', [RolesController::class, 'edit'], [Middleware::permission('roles.manage')]);
    $router->post('/roles/{id}', [RolesController::class, 'update'], [Middleware::permission('roles.manage')]);
    $router->post('/roles/{id}/delete', [RolesController::class, 'destroy'], [Middleware::permission('roles.manage')]);

    // System Settings
    $router->get('/settings', [SettingsController::class, 'index'], [Middleware::permission('settings.manage')]);
    $router->post('/settings', [SettingsController::class, 'update'], [Middleware::permission('settings.manage')]);
    $router->post('/settings/test-sms', [SettingsController::class, 'testSms'], [Middleware::permission('settings.manage')]);
    $router->post('/settings/clear-rate-limits', [SettingsController::class, 'clearRateLimits'], [Middleware::permission('settings.manage')]);
});
