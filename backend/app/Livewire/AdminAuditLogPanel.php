<?php

namespace App\Livewire;

use App\Models\AdminAuditLog;
use App\Support\AdminPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Audit Logs - Admin')]
class AdminAuditLogPanel extends Component
{
    use WithPagination;

    public string $search = '';

    public string $actionFilter = '';

    public string $targetTypeFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $adminFilter = '';

    #[Locked]
    public bool $exporting = false;

    #[Locked]
    public int $localRefreshVersion = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => ''],
        'targetTypeFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'adminFilter' => ['except' => ''],
    ];

    #[Computed]
    public function logs()
    {
        return AdminAuditLog::with('admin:id,name')
            ->when($this->search, function ($q) {
                $s = strtolower(trim($this->search));
                $q->where(function ($q) use ($s) {
                    $q->whereRaw('LOWER(action) like ?', ["%{$s}%"])
                        ->orWhereRaw('LOWER(target_type) like ?', ["%{$s}%"])
                        ->orWhereRaw('LOWER(reason) like ?', ["%{$s}%"])
                        ->orWhereHas('admin', fn ($q) => $q->whereRaw('LOWER(name) like ?', ["%{$s}%"]));
                });
            })
            ->when($this->actionFilter, fn ($q) => $q->where('action', $this->actionFilter))
            ->when($this->targetTypeFilter, fn ($q) => $q->where('target_type', $this->targetTypeFilter))
            ->when($this->adminFilter, fn ($q) => $q->whereHas('admin', fn ($q) => $q->where('name', 'like', "%{$this->adminFilter}%")))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(50);
    }

    #[Computed]
    public function uniqueActions(): array
    {
        return AdminAuditLog::select('action')->distinct()->orderBy('action')->pluck('action')->toArray();
    }

    #[Computed]
    public function uniqueTargetTypes(): array
    {
        return AdminAuditLog::select('target_type')->distinct()->whereNotNull('target_type')->orderBy('target_type')->pluck('target_type')->toArray();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'actionFilter', 'targetTypeFilter', 'dateFrom', 'dateTo', 'adminFilter']);
    }

    public function refresh(): void
    {
        $this->localRefreshVersion++;
    }

    public function exportCsv(): void
    {
        $this->exporting = true;

        $logs = AdminAuditLog::with('admin:id,name')
            ->when($this->search, function ($q) {
                $s = strtolower(trim($this->search));
                $q->where(function ($q) use ($s) {
                    $q->whereRaw('LOWER(action) like ?', ["%{$s}%"])
                        ->orWhereRaw('LOWER(target_type) like ?', ["%{$s}%"])
                        ->orWhereHas('admin', fn ($q) => $q->whereRaw('LOWER(name) like ?', ["%{$s}%"]));
                });
            })
            ->when($this->actionFilter, fn ($q) => $q->where('action', $this->actionFilter))
            ->when($this->targetTypeFilter, fn ($q) => $q->where('target_type', $this->targetTypeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->get();

        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.csv';
        $path = 'exports/'.$filename;

        $handle = fopen('php://temp', 'w+');

        fputcsv($handle, [
            'ID', 'Admin', 'Action', 'Target Type', 'Target ID',
            'IP Address', 'User Agent', 'Device Name',
            'Reason', 'Previous Values', 'New Values', 'Created At',
        ]);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->admin?->name ?? 'System',
                $log->action,
                $log->target_type ?? '',
                $log->target_id ?? '',
                $log->ip_address ?? '',
                $log->user_agent ?? '',
                $log->device_name ?? '',
                $log->reason ?? '',
                $log->previous_values ? json_encode($log->previous_values) : '',
                $log->new_values ? json_encode($log->new_values) : '',
                $log->created_at->toISOString(),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('local')->put($path, $content);

        $this->exporting = false;

        $this->dispatch('csvReady', filename: $filename, path: $path);
    }

    public function render()
    {
        return view('livewire.admin-audit-log-panel', [
            'logs' => $this->logs,
            'uniqueActions' => $this->uniqueActions,
            'uniqueTargetTypes' => $this->uniqueTargetTypes,
            'canViewAudit' => Auth::user() && AdminPermissions::can(Auth::user(), AdminPermissions::AUDIT_VIEW),
        ]);
    }
}
