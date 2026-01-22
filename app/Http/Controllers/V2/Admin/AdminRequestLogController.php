<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRequestLog;
use Illuminate\Http\Request;

class AdminRequestLogController extends Controller
{
    public function fetch(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:200',
            'admin_id' => 'nullable|integer|min:0',
            'admin_email' => 'nullable|string|max:255',
            'method' => 'nullable|string|max:10',
            'path' => 'nullable|string|max:191',
            'start_at' => 'nullable|integer|min:0',
            'end_at' => 'nullable|integer|min:0',
        ]);

        $pageSize = (int) $request->input('pageSize', 20);

        $query = AdminRequestLog::query()->with([
            'admin:id,email',
        ]);

        if ($request->filled('admin_id')) {
            $query->where('admin_id', (int) $request->input('admin_id'));
        }

        if ($request->filled('admin_email')) {
            $email = (string) $request->input('admin_email');
            $query->whereHas('admin', function ($q) use ($email) {
                $q->where('email', 'like', '%' . $email . '%');
            });
        }

        if ($request->filled('method')) {
            $query->where('method', strtoupper((string) $request->input('method')));
        }

        if ($request->filled('path')) {
            $query->where('path', 'like', '%' . $request->input('path') . '%');
        }

        if ($request->filled('start_at')) {
            $query->where('created_at', '>=', (int) $request->input('start_at'));
        }

        if ($request->filled('end_at')) {
            $query->where('created_at', '<=', (int) $request->input('end_at'));
        }

        $records = $query->orderBy('id', 'DESC')->paginate($pageSize);

        $data = array_map(function ($row) {
            $item = $row->toArray();
            $item['admin_email'] = $row->admin?->email;
            unset($item['admin']);
            return $item;
        }, $records->items());

        return [
            'data' => $data,
            'total' => $records->total(),
        ];
    }
}

