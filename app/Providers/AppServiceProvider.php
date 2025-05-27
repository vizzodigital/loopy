<?php

declare(strict_types = 1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->setupLogViewer();
        $this->configModels();
        $this->configCommands();
        $this->configUrls();
        $this->configDate();
        $this->configGates();
        // $this->configMonitor();
    }

    /**
     * Configures and registers health checks for application monitoring.
     * This includes checks for optimized app settings, debug mode,
     * environment configuration, database connectivity, scheduled tasks,
     * and security advisories.
     */
    // private function configMonitor(): void
    // {
    //     Health::checks([
    //         OptimizedAppCheck::new(),
    //         DebugModeCheck::new(),
    //         EnvironmentCheck::new(),
    //         DatabaseCheck::new(),
    //         ScheduleCheck::new(),
    //         SecurityAdvisoriesCheck::new(),
    //     ]);
    // }

    /**
     * Configures the application models to operate in strict mode,
     * which will throw exceptions on undefined attributes.
     */
    private function configModels(): void
    {
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();
    }

    /**
     * Configures database commands to prohibit execution of destructive statements
     * when the application is running in a production environment.
     */
    private function configCommands(): void
    {
        DB::prohibitDestructiveCommands(
            app()->isProduction()
        );
    }

    /**
     * Configures the application to always use HTTPS URLs in production environments.
     */
    private function configUrls(): void
    {
        URL::forceHttps(app()->isProduction());
    }

    /**
     * Configures the application to use CarbonImmutable for date and time handling.
     */
    private function configDate(): void
    {
        Date::use(CarbonImmutable::class);
    }

    /**
     * Configure the authorization gates for the application, which define the abilities
     * users can perform on resources.
     *
     * In this case, the user with the role of 'Super Admin' can perform any ability.
     */
    private function configGates(): void
    {
        Gate::before(fn ($user, $ability): ?true => $user->hasRole('Admin') ? true : null);
    }

    /**
     * Setup the log viewer, which is accessible only to the user with email wagnerbugs@gmail.com.
     */
    private function setupLogViewer(): void
    {
        LogViewer::auth(fn ($request): bool => $request->user()?->email === 'wagnerbugs@gmail.com');
    }
}
