<?php

namespace Modules\Accounting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\app\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [

            /*
            |--------------------------------------------------------------------------
            | ASSETS
            |--------------------------------------------------------------------------
            */

            ['code' => '1-0000', 'name' => 'Assets', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => null],

            ['code' => '1-1000', 'name' => 'Fixed Assets (Tangible)', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '1-0000'],
            ['code' => '1-1003', 'name' => 'Musical Instruments', 'type' => 'Fixed Asset', 'division' => 'SOFP', 'parent_code' => '1-1000'],
            ['code' => '1-1004', 'name' => 'Office Equipment', 'type' => 'Fixed Asset', 'division' => 'SOFP', 'parent_code' => '1-1000'],
            ['code' => '1-1005', 'name' => 'Furniture', 'type' => 'Fixed Asset', 'division' => 'SOFP', 'parent_code' => '1-1000'],

            ['code' => '1-1200', 'name' => 'Accumulated Depreciation', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '1-0000'],
            ['code' => '1-1201', 'name' => 'Accn Depn - Leasehold Improvements', 'type' => 'Contra Asset', 'division' => 'SOFP', 'parent_code' => '1-1200'],
            ['code' => '1-1202', 'name' => 'Accn Depn - Equipment (PPE)', 'type' => 'Contra Asset', 'division' => 'SOFP', 'parent_code' => '1-1200'],
            ['code' => '1-1203', 'name' => 'Accn Depn - Musical Instruments', 'type' => 'Contra Asset', 'division' => 'SOFP', 'parent_code' => '1-1200'],
            ['code' => '1-1204', 'name' => 'Accn Depn - Equipment', 'type' => 'Contra Asset', 'division' => 'SOFP', 'parent_code' => '1-1200'],

            /*
            |--------------------------------------------------------------------------
            | CURRENT ASSETS
            |--------------------------------------------------------------------------
            */

            ['code' => '2-0000', 'name' => 'Current Assets', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => null],

            ['code' => '2-1000', 'name' => 'Cash & Bank', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '2-0000'],
            ['code' => '2-1001', 'name' => 'Office Cash', 'type' => 'Cash', 'division' => 'SOFP', 'parent_code' => '2-1000'],

            ['code' => '2-1020', 'name' => 'Schedule of Inventory Held', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '2-0000'],
            ['code' => '2-1021', 'name' => 'Inventory Food', 'type' => 'Inventory', 'division' => 'SOFP', 'parent_code' => '2-1020'],
            ['code' => '2-1022', 'name' => 'Inventory Beverage', 'type' => 'Inventory', 'division' => 'SOFP', 'parent_code' => '2-1020'],
            ['code' => '2-1023', 'name' => 'Inventory Tobacco', 'type' => 'Inventory', 'division' => 'SOFP', 'parent_code' => '2-1020'],
            ['code' => '2-1024', 'name' => 'Inventory General', 'type' => 'Inventory', 'division' => 'SOFP', 'parent_code' => '2-1020'],
            ['code' => '2-1025', 'name' => 'Inventory Stationery', 'type' => 'Inventory', 'division' => 'SOFP', 'parent_code' => '2-1020'],

            ['code' => '2-1050', 'name' => 'Other Receivable', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '2-0000'],
            ['code' => '2-1051', 'name' => 'Receivable Restaurant', 'type' => 'Receivable', 'division' => 'SOFP', 'parent_code' => '2-1050'],
            ['code' => '2-1052', 'name' => 'Receivable Staff Loan', 'type' => 'Receivable', 'division' => 'SOFP', 'parent_code' => '2-1050'],
            ['code' => '2-1053', 'name' => 'Receivable Staff Advance', 'type' => 'Receivable', 'division' => 'SOFP', 'parent_code' => '2-1050'],
            ['code' => '2-1054', 'name' => 'Receivable MD Family', 'type' => 'Receivable', 'division' => 'SOFP', 'parent_code' => '2-1050'],
            ['code' => '2-1055', 'name' => 'Receivable Default', 'type' => 'Receivable', 'division' => 'SOFP', 'parent_code' => '2-1050'],

            ['code' => '2-2000', 'name' => 'Receivable Debtor', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '2-0000'],
            ['code' => '2-2001', 'name' => 'RT- Door To Door', 'type' => 'Accounts Receivable', 'division' => 'SOFP', 'parent_code' => '2-2000'],
            ['code' => '2-2002', 'name' => 'RT- Tiger Company', 'type' => 'Accounts Receivable', 'division' => 'SOFP', 'parent_code' => '2-2000'],
            ['code' => '2-2003', 'name' => 'RT- Member', 'type' => 'Accounts Receivable', 'division' => 'SOFP', 'parent_code' => '2-2000'],
            ['code' => '2-2004', 'name' => 'Unknown', 'type' => 'Accounts Receivable', 'division' => 'SOFP', 'parent_code' => '2-2000'],
            ['code' => '2-2005', 'name' => 'KTV- Door To Door', 'type' => 'Accounts Receivable', 'division' => 'SOFP', 'parent_code' => '2-2000'],

            ['code' => '2-3000', 'name' => 'Prepaid', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '2-0000'],
            ['code' => '2-3001', 'name' => 'Prepaid - Rental Fee', 'type' => 'Prepaid Expense', 'division' => 'SOFP', 'parent_code' => '2-3000'],
            ['code' => '2-3002', 'name' => 'Prepaid - Professional Fee', 'type' => 'Prepaid Expense', 'division' => 'SOFP', 'parent_code' => '2-3000'],
            ['code' => '2-3003', 'name' => 'Prepaid - CCTV', 'type' => 'Prepaid Expense', 'division' => 'SOFP', 'parent_code' => '2-3000'],
            ['code' => '2-3004', 'name' => 'Prepaid - Wifi', 'type' => 'Prepaid Expense', 'division' => 'SOFP', 'parent_code' => '2-3000'],
            ['code' => '2-3005', 'name' => 'Prepaid - Fire Insurance', 'type' => 'Prepaid Expense', 'division' => 'SOFP', 'parent_code' => '2-3000'],

            ['code' => '2-4000', 'name' => 'Deposit Payment', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '2-0000'],
            ['code' => '2-4001', 'name' => 'Deposit Paid -', 'type' => 'Deposit', 'division' => 'SOFP', 'parent_code' => '2-4000'],
            ['code' => '2-4002', 'name' => 'Deposit Paid -', 'type' => 'Deposit', 'division' => 'SOFP', 'parent_code' => '2-4000'],

            /*
            |--------------------------------------------------------------------------
            | EQUITY
            |--------------------------------------------------------------------------
            */

            ['code' => '3-0000', 'name' => 'Financing (Equity Section)', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => null],

            ['code' => '3-1000', 'name' => 'Capital', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '3-0000'],
            ['code' => '3-1010', 'name' => 'Existing Capital', 'type' => 'Equity', 'division' => 'SOFP', 'parent_code' => '3-1000'],
            ['code' => '3-1020', 'name' => 'Retained Earnings', 'type' => 'Equity', 'division' => 'SOFP', 'parent_code' => '3-1000'],
            ['code' => '3-1030', 'name' => 'Net Profit', 'type' => 'Equity', 'division' => 'SOFP', 'parent_code' => '3-1000'],

            ['code' => '3-2000', 'name' => 'Drawings', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '3-0000'],
            ['code' => '3-2001', 'name' => 'Drawings - MD', 'type' => 'Equity', 'division' => 'SOFP', 'parent_code' => '3-2000'],
            ['code' => '3-2002', 'name' => 'Drawings -', 'type' => 'Equity', 'division' => 'SOFP', 'parent_code' => '3-2000'],

            ['code' => '3-3000', 'name' => 'Opening Balance Equity', 'type' => 'Equity', 'division' => 'SOFP', 'parent_code' => '3-0000'],

            /*
            |--------------------------------------------------------------------------
            | LIABILITIES
            |--------------------------------------------------------------------------
            */

            ['code' => '4-0000', 'name' => 'Liabilities', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => null],

            ['code' => '4-1000', 'name' => 'Longterm Liabilities', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '4-0000'],
            ['code' => '4-1001', 'name' => 'Loan', 'type' => 'Liability', 'division' => 'SOFP', 'parent_code' => '4-1000'],
            ['code' => '4-1002', 'name' => 'Loan- Yuan', 'type' => 'Liability', 'division' => 'SOFP', 'parent_code' => '4-1000'],
            ['code' => '4-1003', 'name' => 'Loan- Dollar', 'type' => 'Liability', 'division' => 'SOFP', 'parent_code' => '4-1000'],
            ['code' => '4-1004', 'name' => 'Loan- Interest', 'type' => 'Liability', 'division' => 'SOFP', 'parent_code' => '4-1000'],

            ['code' => '4-2000', 'name' => 'Current Liabilities', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '4-0000'],

            ['code' => '4-2100', 'name' => 'Creditor A/C', 'type' => 'Heading', 'division' => 'SOFP', 'parent_code' => '4-2000'],
            ['code' => '4-2101', 'name' => '360 Petro', 'type' => 'Accounts Payable', 'division' => 'SOFP', 'parent_code' => '4-2100'],

            ['code' => '4-3000', 'name' => 'Customer Deposit Received', 'type' => 'Heading', 'division' => 'Trading', 'parent_code' => '4-0000'],
            ['code' => '4-3001', 'name' => 'Customer - Default', 'type' => 'Customer Deposit', 'division' => 'Trading', 'parent_code' => '4-3000'],
            ['code' => '4-3002', 'name' => 'Customer -', 'type' => 'Customer Deposit', 'division' => 'Trading', 'parent_code' => '4-3000'],
            ['code' => '4-3003', 'name' => 'Customer -', 'type' => 'Customer Deposit', 'division' => 'Trading', 'parent_code' => '4-3000'],

            /*
            |--------------------------------------------------------------------------
            | INCOME
            |--------------------------------------------------------------------------
            */

            ['code' => '5-0000', 'name' => 'Cash Sales', 'type' => 'Income', 'division' => 'P&L', 'parent_code' => null],
            ['code' => '5-0001', 'name' => 'Income - Food (RT)', 'type' => 'Income', 'division' => 'P&L', 'parent_code' => '5-0000'],
            ['code' => '5-0002', 'name' => 'Income - Beverages (RT)', 'type' => 'Income', 'division' => 'P&L', 'parent_code' => '5-0000'],
            ['code' => '5-0003', 'name' => 'Income - Room Charges (RT)', 'type' => 'Income', 'division' => 'P&L', 'parent_code' => '5-0000'],

            /*
            |--------------------------------------------------------------------------
            | COST OF SALES
            |--------------------------------------------------------------------------
            */

            ['code' => '6-0000', 'name' => 'Cost of Sales', 'type' => 'COGS', 'division' => 'P&L', 'parent_code' => null],
            ['code' => '6-1001', 'name' => 'COS - Food', 'type' => 'COGS', 'division' => 'P&L', 'parent_code' => '6-0000'],
            ['code' => '6-1002', 'name' => 'COS - Beverage', 'type' => 'COGS', 'division' => 'P&L', 'parent_code' => '6-0000'],
            ['code' => '6-1003', 'name' => 'COS - Banquet/Function', 'type' => 'COGS', 'division' => 'P&L', 'parent_code' => '6-0000'],

            /*
            |--------------------------------------------------------------------------
            | SELLING EXPENSES
            |--------------------------------------------------------------------------
            */

            ['code' => '6-2000', 'name' => 'Selling Expenses', 'type' => 'Heading', 'division' => 'Trading', 'parent_code' => null],
            ['code' => '6-2001', 'name' => 'Advertising & Business Promotion', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],
            ['code' => '6-2002', 'name' => 'Carriage Outwards / Delivery', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],
            ['code' => '6-2003', 'name' => 'Discount Allowed', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],
            ['code' => '6-2004', 'name' => 'FOC & Entertainment-Customer', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],
            ['code' => '6-2005', 'name' => 'Entrance & Parking', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],
            ['code' => '6-2006', 'name' => 'Foc & Entertainment-Owner', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],
            ['code' => '6-2007', 'name' => 'Event & Function Supplies', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],
            ['code' => '6-2008', 'name' => 'Packing & Shop Supplies', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],
            ['code' => '6-2009', 'name' => 'Service Money', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-2000'],

            /*
            |--------------------------------------------------------------------------
            | OPERATION EXPENSE
            |--------------------------------------------------------------------------
            */

            ['code' => '6-3000', 'name' => 'Operation Expense', 'type' => 'Heading', 'division' => 'Trading', 'parent_code' => null],
            ['code' => '6-3001', 'name' => 'Garden & Landscaping', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-3000'],
            ['code' => '6-3002', 'name' => 'Health & Water Supplies', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-3000'],
            ['code' => '6-3003', 'name' => 'Hygience Service', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-3000'],
            ['code' => '6-3004', 'name' => 'Repair & Maintenance', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-3000'],
            ['code' => '6-3005', 'name' => 'Newspaper & Magazine', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-3000'],
            ['code' => '6-3006', 'name' => 'Pest Control', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-3000'],
            ['code' => '6-3007', 'name' => 'Phone/Internet/Wifi', 'type' => 'Expense', 'division' => 'Trading', 'parent_code' => '6-3000'],

            /*
            |--------------------------------------------------------------------------
            | ADMIN & GENERAL
            |--------------------------------------------------------------------------
            */

            ['code' => '6-4000', 'name' => 'Admin & General', 'type' => 'Heading', 'division' => 'P&L', 'parent_code' => null],
            ['code' => '6-4001', 'name' => 'Bank Charges', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-4000'],
            ['code' => '6-4002', 'name' => 'Fire Insurance Expense', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-4000'],
            ['code' => '6-4003', 'name' => 'Printing & Stationery, IT & Data Processing', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-4000'],
            ['code' => '6-4004', 'name' => 'Waste Removal', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-4000'],
            ['code' => '6-4005', 'name' => 'Official Entertainment', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-4000'],
            ['code' => '6-4006', 'name' => 'Professional Entertainment', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-4000'],
            ['code' => '6-4007', 'name' => 'Legal Expenses', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-4000'],

            /*
            |--------------------------------------------------------------------------
            | PAY & RELATED EXPENSE
            |--------------------------------------------------------------------------
            */

            ['code' => '6-5000', 'name' => 'Pay & Related Exps', 'type' => 'Heading', 'division' => 'P&L', 'parent_code' => null],
            ['code' => '6-5001', 'name' => 'Employee Benefit', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5002', 'name' => 'Employee Training & Relations', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5003', 'name' => 'Medical Benefit', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5004', 'name' => 'Professional Fee', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5005', 'name' => 'Canteen Cost', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5006', 'name' => 'Staff Recruitment', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5007', 'name' => 'Staff Refreshment & Vacation', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5008', 'name' => 'Staff Salary', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5009', 'name' => 'Staff Uniform & Accessories', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5010', 'name' => 'Wages', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5011', 'name' => 'DJ/Unplugged Fees', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5012', 'name' => 'KPI Money', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5013', 'name' => 'Htun Tauk Hlan Security', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5014', 'name' => 'Mass Security', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],
            ['code' => '6-5015', 'name' => 'Security Fee', 'type' => 'Expense', 'division' => 'P&L', 'parent_code' => '6-5000'],

        ];

        foreach ($accounts as $account) {

            $parentId = null;

            if (!empty($account['parent_code'])) {
                $parentId = Account::where('code', $account['parent_code'])->value('id');
            }

            Account::updateOrCreate(
                [
                    'code' => $account['code'],
                ],
                [
                    'parent_account_id' => $parentId,
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'division' => $account['division'],
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
