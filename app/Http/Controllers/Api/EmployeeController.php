<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    // Save new employee details
    public function saveEmployeeDetails(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('SAVE_EMPLOYEE_DETAILS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'gender' => 'required|in:male,female',
            'address' => 'required|string',
            //'active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::create($validator->validated());
        return response()->json([
            'employee' => $employee,
            'code' => config('httpStatus.OK'),
            'status' => true,
            'message' => 'Employee record saved successfully'
        ], 201);
    }

    // Get paginated list of employees
    public function getPaginatedListOfEmployees(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_EMPLOYEES_LIST')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        $employees_list = Employee::paginate($perPage);
        $employees = [
            'employeesResponse' => $employees_list,
            'status' => true,
            'code' => 200,
        ];
        return response()->json($employees);
    }

    // Get non-paginated list of employees
    public function getNonPaginatedListOfEmployees(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_EMPLOYEES_LIST')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $employees = Employee::all();
        return response()->json([
            'employeesResponse' => $employees,
            'status' => true,
            'code' => config('httpStatus.OK')
        ]);
    }

    // Get employee details by ID
    public function getEmployeeDetailsById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_EMPLOYEE_DETAILS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
        return response()->json(['employee' => $employee]);
    }

    // Update employee details
    public function updateEmployeeDetails(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('UPDATE_EMPLOYEE_DETAILS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'firstName' => 'sometimes|required|string',
            'lastName' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string',
            'email' => 'nullable|email',
            'gender' => 'sometimes|required|in:male,female',
            'address' => 'sometimes|required|string',
            'active' => 'sometimes|required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $employee->update($validator->validated());
        return response()->json([
            'employee' => $employee,
            'code' => config('httpStatus.OK'),
            'status' => true,
            'message' => 'Employee record updated successfully'
        ]);
    }

    // Change employee active status
    public function changeEmployeeStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('DELETE_EMPLOYEE_DETAILS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

//        $validator = Validator::make($request->all(), [
//            'active' => 'required|boolean',
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json(['errors' => $validator->errors()], 422);
//        }

        $employee->update(['active' => ! $employee->active]);

        $status = $employee->active ? 'activated' : 'deactivated';

        return response()->json([
            'employee' => $employee,
            'code' => config('httpStatus.OK'),
            'status' => true,
            'message' => "Employee record {$status} successfully"
        ]);
    }
}
