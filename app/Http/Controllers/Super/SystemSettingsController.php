<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Http\Requests\Super\LogFileRequest;
use App\Http\Requests\Super\RunMigrationRequest;
use App\Services\SystemOpsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemSettingsController extends Controller
{
    public function __construct(private SystemOpsService $ops) {}

    public function index(LogFileRequest $request): Response
    {
        $file = $request->validated('file') ?? 'laravel.log';
        $lines = (int) ($request->validated('lines') ?? 300);

        return Inertia::render('super/system-settings', [
            'migrationStatus' => $this->ops->migrationStatus(),
            'logFiles' => $this->ops->listLogFiles(),
            'log' => $this->ops->readLogTail($file, $lines),
            'lines' => $lines,
        ]);
    }

    public function migrate(RunMigrationRequest $request): RedirectResponse
    {
        $scope = $request->validated('scope');

        $result = $scope === 'central'
            ? $this->ops->runCentralMigrations()
            : $this->ops->runTenantMigrations();

        $message = $result['exit_code'] === 0
            ? ($scope === 'central' ? 'Central migrations finished.' : 'Tenant migrations finished.')
            : 'Migration command exited with errors.';

        return back()->with([
            $result['exit_code'] === 0 ? 'success' : 'error' => $message,
            'migrate_output' => $result['output'],
            'migrate_scope' => $scope,
        ]);
    }

    public function clearLog(LogFileRequest $request): RedirectResponse
    {
        $file = $request->validated('file') ?? 'laravel.log';
        $this->ops->clearLog($file);

        return back()->with('success', 'Log file cleared.');
    }

    public function downloadLog(LogFileRequest $request): StreamedResponse
    {
        $file = $request->validated('file') ?? 'laravel.log';

        return $this->ops->downloadLog($file);
    }
}
