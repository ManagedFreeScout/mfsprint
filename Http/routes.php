<?php

// Print options modal: logged-in users only; the controller also checks access to the conversation.
Route::group(['middleware' => ['web', 'auth'], 'namespace' => 'Modules\MFSPrint\Http\Controllers'], function () {
    Route::get('/mfsprint/modal/{id}', 'MFSPrintController@modal')->name('mfsprint.modal');
});

// Licence: admins only, same route shape as MFSEssentials and MFS Connect.
Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'namespace' => 'Modules\MFSPrint\Http\Controllers'], function () {
    Route::post('/admin/mfsprint/license/manage', 'MFSPrintController@manageLicense')->name('mfsprint.license.manage');
    Route::post('/admin/mfsprint/module-license-action', 'MFSPrintController@handleModuleLicenseAction')->name('mfsprint.module.license.action');
});
