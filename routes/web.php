<?php

use Illuminate\Support\Facades\Route;
use \Illuminate\Support\Facades\Artisan;

    Route::get('/', function () {
        return view('welcome');
    });


    Route::get('migrate', function () {
        // Run migrations
        Artisan::call('migrate');
        $migrateOutput = Artisan::output();

        // Get migration status
        Artisan::call('migrate:status');
        $statusOutput = Artisan::output();

        // Output both
        echo "<pre>";
        echo "Migration Output:\n" . $migrateOutput;
        echo "\n\nMigration Status:\n" . $statusOutput;
        echo "\n\nAll migrations ran successfully.";
        echo "</pre>";
    });

    Route::get('rollback', function () {
        // Run migrations
        Artisan::call('migrate:rollback');
        $migrateOutput = Artisan::output();

        // Get migration status
        Artisan::call('migrate:status');
        $statusOutput = Artisan::output();

        // Output both
        echo "<pre>";
        echo "Migration Rollback Output:\n" . $migrateOutput;
        echo "\n\nMigration Status:\n" . $statusOutput;
        echo "\n\nAll migrations ran successfully.";
        echo "</pre>";
    });

    
    Route::get('/run-app-schedule', function () {
        // Run first command and capture output
        Artisan::call('app:close-expired-listings');
        $output1 = Artisan::output();

        // Run second command and capture output
        Artisan::call('offers:expire');
        $output2 = Artisan::output();

        // Combine both outputs with headers and line breaks
        $finalOutput = "✅ app:close-expired-listings Output:\n" . $output1;
        $finalOutput .= "\n\n✅ offers:expire Output:\n" . $output2;

        return nl2br($finalOutput);
    });

    Route::get('clear', function(){
       Artisan::call('optimize:clear');
       Artisan::call('config:clear');
       Artisan::call('route:clear');
       Artisan::call('cache:clear');
       Artisan::call('view:clear');
        echo 'All optimizations clear successfully';
    });

    Route::get('seed-locations', function () {
    Artisan::call('db:seed', [
        // '--class' => 'PaymentMethodSeeder',
        '--force' => true // Allows running in production
    ]);

    $output = Artisan::output();

    echo "<pre>";
    echo "Seeding Output:\n" . $output;
    echo "\n\nLocations have been seeded successfully.";
    echo "</pre>";
});