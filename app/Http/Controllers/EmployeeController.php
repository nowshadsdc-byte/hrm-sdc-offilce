<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (! $user || (! method_exists($user, 'hasPrivilege') && ! method_exists($user, 'hasAnyRole'))) {
            
            

                abort(403);
            }

            if (! $user->hasPrivilege('employees.access') && ! $user->hasAnyRole(['admin', 'super-admin','employee'])) {
                abort(403);
            }

            return $next($request);
        });
    }
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($search = $request->query('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nid', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateEmployee($request);
        $validated['clearance_completed'] = $request->boolean('clearance_completed');

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $this->validateEmployee($request);
        $validated['clearance_completed'] = $request->boolean('clearance_completed');

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    protected function validateEmployee(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nid' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'job_join_date' => ['nullable', 'date'],
            'job_description' => ['nullable', 'string'],
            'nid_file' => ['nullable', 'string', 'max:255'],
            'certificate_file' => ['nullable', 'string', 'max:255'],
            'contract_file' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive,separated'],
            'transfer_promotion_notes' => ['nullable', 'string'],
            'separation_type' => ['nullable', 'string', 'max:255'],
            'separation_date' => ['nullable', 'date'],
            'clearance_completed' => ['required', 'boolean'],
        ]);
    }
}
