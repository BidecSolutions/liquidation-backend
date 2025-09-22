<?php

use Illuminate\Support\Facades\Route;
use \Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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
        // dd('here');
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

    Route::get('seed-categories', function () {
        // dd('here');
        try {
            Artisan::call('db:seed', [
                '--class' => 'CategorySeeder',
                '--force' => true,
            ]);

            $output = Artisan::output();

            return response()->json([
                'status' => true,
                'message' => 'Seeder executed successfully.',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Seeder failed to execute.',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('seed-username', function () {
        // dd('here');
        try {
            Artisan::call('db:seed', [
                '--class' => 'UpdateUsernamesSeeder',
                '--force' => true,
            ]);

            $output = Artisan::output();

            return response()->json([
                'status' => true,
                'message' => 'Seeder of user name executed successfully.',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Seeder of user name failed to execute.',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    Route::get('seed-admin', function () {
        // dd('here');
        try {
            Artisan::call('db:seed', [
                '--class' => 'AdminSeeder',
                '--force' => true,
            ]);

            $output = Artisan::output();

            return response()->json([
                'status' => true,
                'message' => 'Seeder of user name executed successfully.',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Seeder of user name failed to execute.',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    Route::get('seed-vehicle-makes', function () {
    try {
        Artisan::call('db:seed', [
            '--class' => 'VehicleMakeSeeder',
            '--force' => true,
        ]);

        $output = Artisan::output();
        $total = DB::table('vehicle_data')->count();

        return response()->json([
            'status'  => true,
            'message' => 'Vehicle makes seeded successfully.',
            'total_entries' => $total,
            'output'  => $output,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Seeder failed to execute.',
            'error'   => $e->getMessage()
        ], 500);
    }
});