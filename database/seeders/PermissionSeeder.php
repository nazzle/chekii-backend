<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            //MODULES PERMISSIONS
            'VIEW_POS_MODULE',
            'VIEW_SALES_MODULE',
            'VIEW_INVENTORY_MODULE',
            'VIEW_SUPPLIERS_MODULE',
            'VIEW_RESTOCKING_MODULE',
            'VIEW_EXPENSES_MODULE',
            'VIEW_GIFT_CARDS_MODULE',
            'VIEW_CASH_UP_MODULE',
            'VIEW_HR_MODULE',
            'VIEW_NOTIFICATIONS_MODULE',
            'VIEW_REPORTS_MODULE',
            'VIEW_CONFIGURATIONS_MODULE',

            // Products
            'VIEW_PRODUCTS',
            'CREATE_PRODUCTS',
            'UPDATE_PRODUCTS',
            'DELETE_PRODUCTS',

            // Categories
            'VIEW_CATEGORIES',
            'CREATE_CATEGORIES',
            'UPDATE_CATEGORIES',
            'DELETE_CATEGORIES',

            // Sales / Orders
            'CREATE_SALE',
            'VIEW_SALES',
            'CANCEL_SALE',
            'REFUND_SALE',

            // Customers
            'VIEW_CUSTOMERS',
            'CREATE_CUSTOMERS',
            'UPDATE_CUSTOMERS',
            'DELETE_CUSTOMERS',

            // Users and Roles
            'VIEW_USERS',
            'CREATE_USERS',
            'UPDATE_USERS',
            'DELETE_USERS',
            'ASSIGN_ROLES',
            'UPDATE_ROLES',

            // POS Terminal
            'OPEN_REGISTER',
            'CLOSE_REGISTER',
            'PRINT_RECEIPT',
            'APPLY_DISCOUNT',

            // Reports
            'VIEW_REPORTS',
            'EXPORT_REPORTS',

            // Inventory
            'VIEW_INVENTORY',
            'ADJUST_INVENTORY',
            'UPDATE_INVENTORY',
            'CREATE_RESTOCKING',

            // Suppliers
            'VIEW_SUPPLIERS',
            'CREATE_SUPPLIERS',
            'UPDATE_SUPPLIERS',
            'DELETE_SUPPLIERS',

            // Expenses
            'VIEW_EXPENSES',
            'CREATE_EXPENSES',
            'UPDATE_EXPENSES',
            'DELETE_EXPENSES',

            // Gift Cards
            'VIEW_GIFT_CARDS',
            'CREATE_GIFT_CARDS',
            'UPDATE_GIFT_CARDS',
            'DELETE_GIFT_CARDS',

            // Cash_ups
            'VIEW_CASH_UP',
            'CREATE_CASH_UP',
            'UPDATE_CASH_UP',
            'DELETE_CASH_UP',

            // Notifications
            'VIEW_NOTIFICATIONS',
            'CREATE_NOTIFICATIONS',
            'UPDATE_NOTIFICATIONS',
            'DELETE_NOTIFICATIONS',

            // Settings
            'MANAGE_SETTINGS',

            // Employees
            'VIEW_EMPLOYEES_LIST',
            'SAVE_EMPLOYEE_DETAILS',
            'VIEW_EMPLOYEE_DETAILS',
            'UPDATE_EMPLOYEE_DETAILS',
            'DELETE_EMPLOYEE_DETAILS',

            // Items
            'VIEW_ITEMS',
            'CREATE_ITEMS',
            'UPDATE_ITEMS',
            'DELETE_ITEMS',

            // Locations
            'VIEW_LOCATIONS',
            'CREATE_LOCATIONS',
            'UPDATE_LOCATIONS',
            'DELETE_LOCATIONS',

            // Movements
            'VIEW_MOVEMENTS',
            'CREATE_MOVEMENTS',
            'UPDATE_MOVEMENTS',
            'DELETE_MOVEMENTS',

            // Configurations
            'VIEW_CONFIGURATIONS',
            'CREATE_CONFIGURATIONS',
            'UPDATE_CONFIGURATIONS',
            'DELETE_CONFIGURATIONS',

            // Payment Options
            'VIEW_PAYMENT_OPTIONS',
            'CREATE_PAYMENT_OPTIONS',
            'UPDATE_PAYMENT_OPTIONS',
            'DELETE_PAYMENT_OPTIONS',

            // Reference Data
            'VIEW_REFERENCE_DATA',
            'CREATE_REFERENCE_DATA',
            'UPDATE_REFERENCE_DATA',
            'DELETE_REFERENCE_DATA',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create admin role (use App\Models\Role so we write to role_permissions table)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Assign all permissions to admin via role_permissions pivot (explicit array for sync)
        $permissionIds = Permission::pluck('id')->toArray();
        $adminRole->permissions()->sync($permissionIds);

        // Assign limited to cashier
//        $cashierRole->syncPermissions([
//            'VIEW_PRODUCTS',
//            'CREATE_SALE',
//            'VIEW_SALES',
//            'CANCEL_SALE',
//            'REFUND_SALE',
//            'VIEW_CUSTOMERS',
//            'CREATE_CUSTOMERS',
//            'PRINT_RECEIPT',
//            'OPEN_REGISTER',
//            'CLOSE_REGISTER',
//        ]);
    }
}
