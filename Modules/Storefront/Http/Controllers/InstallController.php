<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallController extends Controller
{
    protected function authorize(): void
    {
        abort_unless(auth()->user()->can('superadmin') || auth()->user()->can('manage_modules'), 403);
    }

    public function index()
    {
        $this->authorize();
        return view('storefront::install');
    }

    public function install()
    {
        $this->authorize();
        try {
            app(\Modules\Storefront\Services\Installer::class)->install((int) (session('user.business_id') ?: session('business.id')));
        } catch (\Throwable $error) {
            report($error);
            return back()->withErrors(['install' => 'Storefront installation failed. '.$error->getMessage()]);
        }
        return redirect()->route('storefront.settings')->with('status', ['success' => 1, 'msg' => 'Storefront installed. Configure and enable your store below.']);
    }

    public function update() { return $this->index(); }
    public function uninstall()
    {
        $this->authorize();
        return view('storefront::uninstall');
    }

    public function runUninstall()
    {
        $this->authorize();
        $module = \Module::findOrFail('Storefront');
        try {
            DB::transaction(function () use ($module) {
                if (Schema::hasTable('storefront_settings')) {
                    \Modules\Storefront\Entities\StoreSetting::query()->update(['enabled' => false]);
                }
                \App\System::where('key', 'storefront_version')->delete();
                $module->disable();
            });
        } catch (\Throwable $error) {
            report($error);
            return back()->withErrors(['uninstall' => 'Storefront could not be uninstalled. Check that module activation storage is writable, then try again.']);
        }
        app('files')->delete(app()->getCachedRoutesPath());
        return redirect()->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', ['success' => 1, 'msg' => 'Storefront uninstalled. Customer ordering is disabled. Orders, payments and POS sales have been retained.']);
    }
}
