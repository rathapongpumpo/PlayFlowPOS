<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('/login', 'Auth\LoginController@login')->name('login.attempt');
});

Route::post('/logout', 'Auth\LoginController@logout')
    ->name('logout')
    ->middleware('auth');

Route::middleware(['auth', 'shop.access'])->group(function (): void {
    Route::get('/', 'DashboardController@index')->name('dashboard');
    Route::get('/my-commission', 'MasseuseController@selfDashboard')
        ->name('masseuse.self')
        ->middleware('roles:masseuse');

    Route::middleware('roles:super_admin')->prefix('system')->group(function (): void {
        Route::get('/shops', 'ShopPortalController@index')->name('system.shops.index');
        Route::get('/shops/{shopId}/enter', 'ShopPortalController@enter')->name('system.shops.enter');
        Route::post('/shops', 'ShopPortalController@store')->name('system.shops.store');
        Route::put('/shops/{shopId}', 'ShopPortalController@update')->name('system.shops.update');
        Route::patch('/shops/{shopId}/toggle', 'ShopPortalController@toggle')->name('system.shops.toggle');
        Route::delete('/shops/{shopId}', 'ShopPortalController@destroy')->name('system.shops.destroy');
    });

    Route::get('/home', function () {
        return redirect()->route('dashboard');
    })->name('home');

    Route::get('/my-profile', 'StaffManagementController@profile')->name('profile.show');
    Route::get('/masseuse', 'MasseuseController@index')->name('masseuse');

    Route::middleware('roles:super_admin,shop_owner,branch_manager,cashier')->group(function (): void {
        Route::get('/pos', 'PosController@index')->name('pos');
        Route::post('/pos/checkout', 'PosController@checkout')->name('pos.checkout');
        Route::get('/booking', 'BookingController@index')->name('booking');
        Route::get('/booking/data', 'BookingController@data')->name('booking.data');
        Route::post('/booking', 'BookingController@store')->name('booking.store');
        Route::put('/booking/{bookingId}', 'BookingController@update')->name('booking.update');
        Route::delete('/booking/{bookingId}', 'BookingController@destroy')->name('booking.destroy');
        Route::post('/customers/quick-create', 'CustomerController@quickCreate')->name('customers.quick-create');
    });

    Route::middleware('roles:super_admin,shop_owner,branch_manager,cashier')->group(function (): void {
        Route::post('/masseuse/attendance', 'MasseuseController@updateAttendance')->name('masseuse.attendance');
    });

    Route::middleware('admin.only')->group(function (): void {
        Route::get('/receipts', 'ReceiptController@index')->name('receipts');
        Route::get('/receipts/{orderId}', 'ReceiptController@show')->name('receipts.show');
        Route::post('/receipts/{orderId}/void', 'ReceiptController@voidOrder')->name('receipts.void');
        Route::get('/customers', 'CustomerController@index')->name('customers');
        Route::post('/customers', 'CustomerController@store')->name('customers.store');
        Route::get('/customers/{customerId}/history', 'CustomerController@history')->name('customers.history');
        Route::put('/customers/{customerId}', 'CustomerController@update')->name('customers.update');
        Route::delete('/customers/{customerId}', 'CustomerController@destroy')->name('customers.destroy');
        Route::post('/customers/{customerId}/topup', 'CustomerController@topup')->name('customers.topup');
        Route::get('/crm', 'CrmController@index')->name('crm.index');
        Route::get('/membership-levels', 'MembershipLevelController@index')->name('membership-levels');
        Route::post('/membership-levels', 'MembershipLevelController@store')->name('membership-levels.store');
        Route::put('/membership-levels/{tierId}', 'MembershipLevelController@update')->name('membership-levels.update');

        Route::get('/massage-rooms', 'MassageRoomController@index')->name('massage-rooms');
        Route::get('/massage-rooms/rooms/{roomId}/edit', 'MassageRoomController@edit')->name('massage-rooms.rooms.edit');
        Route::post('/massage-rooms/rooms', 'MassageRoomController@storeRoom')->name('massage-rooms.rooms.store');
        Route::put('/massage-rooms/rooms/{roomId}', 'MassageRoomController@updateRoom')->name('massage-rooms.rooms.update');
        Route::delete('/massage-rooms/rooms/{roomId}', 'MassageRoomController@destroyRoom')->name('massage-rooms.rooms.destroy');
        Route::post('/massage-rooms/beds', 'MassageRoomController@storeBed')->name('massage-rooms.beds.store');
        Route::put('/massage-rooms/beds/{bedId}', 'MassageRoomController@updateBed')->name('massage-rooms.beds.update');
        Route::delete('/massage-rooms/beds/{bedId}', 'MassageRoomController@destroyBed')->name('massage-rooms.beds.destroy');

        Route::get('/masseuse/create', 'MasseuseController@create')->name('masseuse.create');
        Route::get('/masseuse/{staffId}/edit', 'MasseuseController@edit')->name('masseuse.edit');
        Route::post('/masseuse', 'MasseuseController@store')->name('masseuse.store');
        Route::put('/masseuse/{staffId}', 'MasseuseController@update')->name('masseuse.update');
        Route::delete('/masseuse/{staffId}', 'MasseuseController@destroy')->name('masseuse.destroy');
        Route::post('/masseuse/shifts', 'MasseuseShiftController@store')->name('masseuse.shifts.store');
        Route::put('/masseuse/shifts/{shiftId}', 'MasseuseShiftController@update')->name('masseuse.shifts.update');
        Route::delete('/masseuse/shifts/{shiftId}', 'MasseuseShiftController@destroy')->name('masseuse.shifts.destroy');

        // Store Operations (Open/Close)
        Route::get('/operations', 'StoreOperationsController@index')->name('operations.index');
        Route::post('/operations/open', 'StoreOperationsController@openStore')->name('operations.open');
        Route::post('/operations/close', 'StoreOperationsController@closeStore')->name('operations.close');
        Route::post('/operations/reopen', 'StoreOperationsController@reopenStore')->name('operations.reopen');

        // Store Assets (Internal stock)
        Route::get('/store-assets', 'StoreAssetController@index')->name('store-assets.index');
        Route::post('/store-assets', 'StoreAssetController@store')->name('store-assets.store');
        Route::post('/store-assets/{id}/adjust', 'StoreAssetController@adjustStock')->name('store-assets.adjust');
        Route::get('/packages', 'PackageController@index')->name('packages');
        Route::post('/packages', 'PackageController@store')->name('packages.store');
        Route::put('/packages/{packageId}', 'PackageController@update')->name('packages.update');
        Route::delete('/packages/{packageId}', 'PackageController@destroy')->name('packages.destroy');

        Route::get('/products', 'ProductController@index')->name('products');
        Route::post('/products', 'ProductController@store')->name('products.store');
        Route::put('/products/{productId}', 'ProductController@update')->name('products.update');
        Route::delete('/products/{productId}', 'ProductController@destroy')->name('products.destroy');
        Route::post('/products/categories', 'ProductController@storeCategory')->name('products.categories.store');
        Route::put('/products/categories/{categoryId}', 'ProductController@updateCategory')->name('products.categories.update');
        Route::delete('/products/categories/{categoryId}', 'ProductController@deleteCategory')->name('products.categories.destroy');
        Route::post('/products/{productId}/adjust-stock', 'ProductController@adjustStock')->name('products.adjust-stock');

        Route::get('/services', 'ServiceManagementController@index')->name('services.index');
        Route::post('/services', 'ServiceManagementController@store')->name('services.store');
        Route::put('/services/{serviceId}', 'ServiceManagementController@update')->name('services.update');
        Route::delete('/services/{serviceId}', 'ServiceManagementController@destroy')->name('services.destroy');
        Route::post('/services/categories', 'ServiceManagementController@storeCategory')->name('services.categories.store');
        Route::put('/services/categories/{categoryId}', 'ServiceManagementController@updateCategory')->name('services.categories.update');
        Route::delete('/services/categories/{categoryId}', 'ServiceManagementController@deleteCategory')->name('services.categories.destroy');

        // โซนจัดการการตั้งค่าระบบ (Admin Only)
        Route::get('/admin/commission', 'CommissionConfigController@index')->name('admin.commission.index');
        Route::post('/admin/commission', 'CommissionConfigController@store')->name('admin.commission.store');
        Route::put('/admin/commission/{id}', 'CommissionConfigController@update')->name('admin.commission.update');
        Route::delete('/admin/commission/{id}', 'CommissionConfigController@destroy')->name('admin.commission.destroy');

        // สาขา (Branch Management)
        Route::get('/branches', 'BranchController@index')->name('branches.index');
        Route::post('/branches', 'BranchController@store')->name('branches.store');
        Route::put('/branches/{branchId}', 'BranchController@update')->name('branches.update');
        Route::delete('/branches/{branchId}', 'BranchController@destroy')->name('branches.destroy');
    });

    Route::middleware('roles:super_admin,shop_owner,branch_manager')->group(function (): void {
        // สาขา (Branch Management)
        Route::get('/branches', 'BranchController@index')->name('branches.index');
        Route::post('/branches', 'BranchController@store')->name('branches.store');
        Route::put('/branches/{branchId}', 'BranchController@update')->name('branches.update');
        Route::delete('/branches/{branchId}', 'BranchController@destroy')->name('branches.destroy');

        // พนักงาน (Staff Management)
        Route::get('/staff', 'StaffManagementController@index')->name('staff.index');
        Route::post('/staff', 'StaffManagementController@store')->name('staff.store');
        Route::put('/staff/{staffId}', 'StaffManagementController@update')->name('staff.update');
        Route::delete('/staff/{staffId}', 'StaffManagementController@destroy')->name('staff.destroy');

        // หมอนวด (Masseuse Management)
        Route::get('/masseuse/create', 'MasseuseController@create')->name('masseuse.create');
        Route::get('/masseuse/{staffId}/edit', 'MasseuseController@edit')->name('masseuse.edit');
        Route::post('/masseuse', 'MasseuseController@store')->name('masseuse.store');
        Route::put('/masseuse/{staffId}', 'MasseuseController@update')->name('masseuse.update');
        Route::delete('/masseuse/{staffId}', 'MasseuseController@destroy')->name('masseuse.destroy');

        // ผู้ใช้งาน (User Accounts)
        Route::get('/users', 'UserAccountController@index')->name('users.index');
        Route::post('/users', 'UserAccountController@store')->name('users.store');
        Route::put('/users/{userId}', 'UserAccountController@update')->name('users.update');
        Route::post('/users/{userId}/reset-password', 'UserAccountController@resetPassword')->name('users.reset-password');
        Route::delete('/users/{userId}', 'UserAccountController@destroy')->name('users.destroy');
    });

    // Reports
    Route::middleware('roles:super_admin,shop_owner,branch_manager')->group(function (): void {
        Route::get('/reports', 'ReportController@index')->name('reports');
        Route::get('/reports/export-csv', 'ReportController@exportCsv')->name('reports.export-csv');

        $modules = [
            'promotions', 'financial',
        ];

        foreach ($modules as $module) {
            Route::get('/' . $module, 'ModuleController@comingSoon')->name($module);
        }
    });
});
