<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsAudit;

    public function index()
    {
        $employees = Employee::withCount('orders')
            ->orderBy('LNM')
            ->get();
        return view('pos.employees', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'role'       => 'required|in:cashier,waiter,chef,manager',
            'username'   => 'required|string|max:255|unique:employees,Username',
            'password'   => 'required|string|min:6',
        ]);

        Employee::create([
            'FNM'      => $validated['first_name'],
            'LNM'      => $validated['last_name'],
            'Role'     => $validated['role'],
            'Username' => $validated['username'],
            'Password' => Hash::make($validated['password']),
        ]);

        $this->audit('employee.created', 'Employee added: ' . $validated['first_name'] . ' ' . $validated['last_name'] . ' (' . $validated['role'] . ')', $validated['first_name'] . ' ' . $validated['last_name']);
        return back()->with('success', 'Employee added.');
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'role'       => 'required|in:cashier,waiter,chef,manager',
            'username'   => 'required|string|max:255|unique:employees,Username,' . $employee->Employee_ID . ',Employee_ID',
            'password'   => 'nullable|string|min:6',
        ]);

        $data = [
            'FNM'      => $validated['first_name'],
            'LNM'      => $validated['last_name'],
            'Role'     => $validated['role'],
            'Username' => $validated['username'],
        ];

        if (!empty($validated['password'])) {
            $data['Password'] = Hash::make($validated['password']);
        }

        $employee->update($data);
        $this->audit('employee.updated', 'Employee updated: ' . $employee->FNM . ' ' . $employee->LNM, $employee->FNM . ' ' . $employee->LNM);
        return back()->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $fullName = $employee->FNM . ' ' . $employee->LNM;
        $employee->delete();
        $this->audit('employee.deleted', 'Employee removed: ' . $fullName, $fullName);
        return back()->with('success', 'Employee removed.');
    }
}
