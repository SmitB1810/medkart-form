<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Forms\FormStatsController;
use App\Http\Controllers\Forms\PublicFormController;
use App\Http\Controllers\Forms\FormSubmissionController;
use App\Http\Controllers\Forms\FormController;
use App\Http\Controllers\Forms\RecordController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Forms\Integration\FormZapierWebhookController;
use App\Http\Middleware\Form\ResolveFormMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/users', function () {
    return "Hello, World";
});

// Login and Register route
Route::group(['middleware' => 'guest:api'], function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('register', [RegisterController::class, 'register']);

    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [ResetPasswordController::class, 'reset']);

    Route::post('email/verify/{user}', [VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('email/resend', [VerificationController::class, 'resend']);

    Route::post('oauth/{driver}', [OAuthController::class, 'redirect']);
    Route::get('oauth/{driver}/callback', [OAuthController::class, 'handleCallback'])->name('oauth.callback');
});


Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', [LoginController::class, 'logout']);

    Route::get('user', [UserController::class, 'current'])->name('user.current');
    Route::delete('user', [UserController::class, 'deleteAccount']);

    Route::patch('settings/profile', [ProfileController::class, 'update']);
    Route::patch('settings/password', [PasswordController::class, 'update']);

    // Pro features
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::put('/update-customer-details', [SubscriptionController::class, 'updateStripeDetails'])->name('update-stripe-details');
        Route::get('/new/{subscription}/{plan}/checkout/{trial?}', [SubscriptionController::class, 'checkout'])
            ->name('checkout')
            ->where('subscription', '(' . implode('|', SubscriptionController::SUBSCRIPTION_NAMES) . ')')
            ->where('plan', '(' . implode('|', SubscriptionController::SUBSCRIPTION_PLANS) . ')');
        Route::get('/billing-portal', [SubscriptionController::class, 'billingPortal'])->name('billing-portal');
    });

    Route::prefix('open')->name('open.')->group(function () {

        // For getting all the forms
        Route::get('/forms', [FormController::class, 'indexAll'])->name('forms.index-all');

        // Creating workspace and their info.
        Route::prefix('workspaces')->name('workspaces.')->group(function () {
            Route::get('/', [WorkspaceController::class, 'index'])->name('index');
            Route::post('/create', [WorkspaceController::class, 'create'])->name('create');

            // Function not defined listUsers()
            Route::prefix('/{workspaceId}')->group(function () {
                Route::get('/users', [WorkspaceController::class, 'listUsers'])->name('users.index');

                Route::prefix('/databases')->name('databases.')->group(function () {
                    Route::get('/search/{search?}', [WorkspaceController::class, 'searchDatabases'])->name('search');
                    Route::get('/{database_id}', [WorkspaceController::class, 'getDatabase'])->name('show');
                });

                // For getting forms present in workspace
                Route::get('/forms', [FormController::class, 'index'])->name('forms.index');
                Route::put('/custom-domains', [WorkspaceController::class, 'saveCustomDomain'])->name('save-custom-domains');
                Route::delete('/', [WorkspaceController::class, 'delete'])->name('delete');

                // Views and submissions data of perticular form
                Route::get('form-stats/{formId}', [FormStatsController::class, 'getFormStats'])->name('form.stats');
            });
        });

        // CRUD on form
        Route::prefix('forms')->name('forms.')->group(function () {
            Route::post('/', [FormController::class, 'store'])->name('store');
            Route::put('/{id}', [FormController::class, 'update'])->name('update');
            Route::delete('/{id}', [FormController::class, 'destroy'])->name('destroy');

            // For getting submitted data of forms
            Route::get('/{id}/submissions', [FormSubmissionController::class, 'submissions'])->name('submissions');
            // For getting submitted data of forms by Id
            Route::put('/{id}/submissions/{submission_id}', [FormSubmissionController::class, 'update'])->name('submissions.update')->middleware([ResolveFormMiddleware::class]);
            Route::get('/{id}/submissions/export', [FormSubmissionController::class, 'export'])->name('submissions.export');
            Route::get('/{id}/submissions/file/{filename}', [FormSubmissionController::class, 'submissionFile'])
                ->middleware('signed')
                ->withoutMiddleware(['auth:api'])
                ->name('submissions.file');

            Route::delete('/{id}/records/{recordid}/delete', [RecordController::class, 'delete'])->name('records.delete');

            // Form Admin tool
            Route::put('/{id}/regenerate-link/{option}', [FormController::class, 'regenerateLink'])
                ->where('option', '(uuid|slug)')
                ->name('regenerate-link');
            Route::post('/{id}/duplicate', [FormController::class, 'duplicate'])->name('duplicate');

            // Assets & uploaded files
            Route::post('/assets/upload', [FormController::class, 'uploadAsset'])->name('assets.upload');
            Route::get('/{id}/uploaded-file/{filename}', [FormController::class, 'viewFile'])->name('uploaded_file');

            // Integrations
            Route::post('/webhooks/zapier', [FormZapierWebhookController::class, 'store'])->name('integrations.zapier-hooks.store');
            Route::delete('/webhooks/zapier/{id}', [FormZapierWebhookController::class, 'delete'])->name('integrations.zapier-hooks.delete');
        });
    });

    Route::group(['middleware' => 'moderator', 'prefix' => 'admin'], function () {
        Route::get('impersonate/{identifier}', [\App\Http\Controllers\Admin\ImpersonationController::class, 'impersonate']);
    });
});


Route::group(['prefix' => 'appsumo'], function () {
    Route::get('oauth/callback', [\App\Http\Controllers\Auth\AppSumoAuthController::class, 'handleCallback'])->name('appsumo.callback');
    Route::post('webhook', [\App\Http\Controllers\Webhook\AppSumoController::class, 'handle'])->name('appsumo.webhook');
});

/*  
 * Public Forms related routes
 */
Route::prefix('forms')->name('forms.')->group(function () {
    Route::middleware('protected-form')->group(function () {

        // For submitting form
        Route::post('{slug}/answer', [PublicFormController::class, 'answer'])->name('answer');

        // For getting form user and their workspace Id (user lists, relation lists etc.)
        Route::get('{slug}/users', [PublicFormController::class, 'listUsers'])->name('users.index');
    });

    // For adding views
    Route::get('{slug}', [PublicFormController::class, 'show'])->name('show');

    //For getting response by Id
    Route::get('{slug}/submissions/{submission_id}', [PublicFormController::class, 'fetchSubmission'])->name('fetchSubmission');

    // For getting file uploads
    Route::get('assets/{assetFileName}', [PublicFormController::class, 'showAsset'])->name('assets.show');

    // AI
    Route::post('ai/generate', [\App\Http\Controllers\Forms\AiFormController::class, 'generateForm'])->name('ai.generate');
    Route::get('ai/{aiFormCompletion}', [\App\Http\Controllers\Forms\AiFormController::class, 'show'])->name('ai.show');
});

/**
 * Other public routes
 */
Route::prefix('content')->name('content.')->group(function () {
    Route::get('changelog/entries', [\App\Http\Controllers\Content\ChangelogController::class, 'index'])->name('changelog.entries');
});

Route::get('/sitemap-urls', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap.index');

// Templates
Route::prefix('templates')->group(function () {
    Route::get('/', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/', [TemplateController::class, 'create'])->name('templates.create');
    Route::get('/{slug}', [TemplateController::class, 'show'])->name('templates.show');
    Route::put('/{id}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('/{id}', [TemplateController::class, 'destroy'])->name('templates.destroy');
});

Route::post('/stripe/webhook', [\App\Http\Controllers\Webhook\StripeController::class, 'handleWebhook'])->name('cashier.webhook');

Route::post('/vapor/signed-storage-url', [\App\Http\Controllers\Content\SignedStorageUrlController::class, 'store'])->middleware([]);
Route::post('/upload-file', [\App\Http\Controllers\Content\FileUploadController::class, 'upload'])->middleware([]);

Route::get('local/temp/{path}', function (Request $request, string $path) {
    if (!$request->hasValidSignature()) {
        abort(401);
    }
    $response = Response::make(Storage::get($path), 200);
    $response->header("Content-Type", Storage::mimeType($path));
    return $response;
})->where('path', '(.*)')->name('local.temp');

Route::get('caddy/ask-certificate/{secret?}', [\App\Http\Controllers\CaddyController::class, 'ask'])
    ->name('caddy.ask')
    ->middleware(\App\Http\Middleware\CaddyRequestMiddleware::class);