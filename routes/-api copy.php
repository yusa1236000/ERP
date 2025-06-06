<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// routes/api.php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemCategoryController;
use App\Http\Controllers\Api\UnitOfMeasureController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\StockTransactionController;
use App\Http\Controllers\Api\StockAdjustmentController;
// purchase order
use App\Http\Controllers\API\VendorController;
use App\Http\Controllers\API\PurchaseRequisitionController;
use App\Http\Controllers\API\RequestForQuotationController;
use App\Http\Controllers\API\VendorQuotationController;
use App\Http\Controllers\API\PurchaseOrderController;
use App\Http\Controllers\API\GoodsReceiptController;
use App\Http\Controllers\API\VendorInvoiceController;
use App\Http\Controllers\API\VendorContractController;
use App\Http\Controllers\API\VendorEvaluationController;
// Sales Order
use App\Http\Controllers\Api\Sales\CustomerController;
use App\Http\Controllers\Api\Sales\SalesQuotationController;
use App\Http\Controllers\Api\Sales\SalesOrderController;
use App\Http\Controllers\Api\Sales\DeliveryController;
use App\Http\Controllers\Api\Sales\SalesInvoiceController;
use App\Http\Controllers\Api\Sales\SalesReturnController;
use App\Http\Controllers\Api\Sales\CustomerInteractionController;
use App\Http\Controllers\Api\Sales\SalesCommissionController;
use App\Http\Controllers\Api\Sales\SalesForecastController;
// Manufacturing
use App\Http\Controllers\Api\Manufacturing\ProductController;
use App\Http\Controllers\Api\Manufacturing\BOMController;
use App\Http\Controllers\Api\Manufacturing\BOMLineController;
use App\Http\Controllers\Api\Manufacturing\RoutingController;
use App\Http\Controllers\Api\Manufacturing\WorkCenterController;
use App\Http\Controllers\Api\Manufacturing\RoutingOperationController;
use App\Http\Controllers\Api\Manufacturing\WorkOrderController;
use App\Http\Controllers\Api\Manufacturing\WorkOrderOperationController;
use App\Http\Controllers\Api\Manufacturing\ProductionOrderController;
use App\Http\Controllers\Api\Manufacturing\ProductionConsumptionController;
use App\Http\Controllers\Api\Manufacturing\QualityInspectionController;
use App\Http\Controllers\Api\Manufacturing\QualityParameterController;
use App\Http\Controllers\Api\Manufacturing\MaintenanceScheduleController;
//Accounting
use App\Http\Controllers\Api\Accounting\ChartOfAccountController;
use App\Http\Controllers\Api\Accounting\AccountingPeriodController;
use App\Http\Controllers\Api\Accounting\JournalEntryController;
use App\Http\Controllers\Api\Accounting\CustomerReceivableController;
use App\Http\Controllers\Api\Accounting\ReceivablePaymentController;
use App\Http\Controllers\Api\Accounting\VendorPayableController;
use App\Http\Controllers\Api\Accounting\PayablePaymentController;
use App\Http\Controllers\Api\Accounting\TaxTransactionController;
use App\Http\Controllers\Api\Accounting\FixedAssetController;
use App\Http\Controllers\Api\Accounting\AssetDepreciationController;
use App\Http\Controllers\Api\Accounting\BudgetController;
use App\Http\Controllers\Api\Accounting\BankAccountController;
use App\Http\Controllers\Api\Accounting\BankReconciliationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Item Routes
    Route::apiResource('items', ItemController::class);
    Route::get('/items/stock-status', [ItemController::class, 'stockStatus']);
    
    // Category Routes
    Route::apiResource('item-categories', CategoryController::class);
    
    // UOM Routes
    Route::apiResource('unit-of-measures', UnitOfMeasureController::class);
    
    // Warehouse Routes
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('warehouses.zones', WarehouseZoneController::class);
    Route::apiResource('warehouses.zones.locations', WarehouseLocationController::class);
    
    // Transaction Routes
    Route::apiResource('stock-transactions', StockTransactionController::class);
    
    // Adjustment Routes
    Route::apiResource('stock-adjustments', StockAdjustmentController::class);
    Route::patch('/stock-adjustments/{stock_adjustment}/approve', [StockAdjustmentController::class, 'approve']);
    Route::patch('/stock-adjustments/{stock_adjustment}/cancel', [StockAdjustmentController::class, 'cancel']);
    
    // Reports
    Route::get('/reports/stock', [ReportController::class, 'stockReport']);
    Route::get('/reports/movement', [ReportController::class, 'movementReport']);
    Route::get('/reports/adjustment', [ReportController::class, 'adjustmentReport']);
    Route::get('/reports/valuation', [ReportController::class, 'valuationReport']);

    // Vendors
    Route::apiResource('vendors', VendorController::class);
    Route::get('vendors/{vendor}/evaluations', [VendorController::class, 'evaluations']);
    Route::get('vendors/{vendor}/purchase-orders', [VendorController::class, 'purchaseOrders']);
    
    // Purchase Requisitions
    Route::apiResource('purchase-requisitions', PurchaseRequisitionController::class);
    Route::patch('purchase-requisitions/{purchaseRequisition}/status', [PurchaseRequisitionController::class, 'updateStatus']);
    
    // Request For Quotations
    Route::apiResource('request-for-quotations', RequestForQuotationController::class);
    Route::patch('request-for-quotations/{requestForQuotation}/status', [RequestForQuotationController::class, 'updateStatus']);
    
    // Vendor Quotations
    Route::apiResource('vendor-quotations', VendorQuotationController::class);
    Route::patch('vendor-quotations/{vendorQuotation}/status', [VendorQuotationController::class, 'updateStatus']);
    
    // Purchase Orders
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::patch('purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus']);
    Route::post('purchase-orders/create-from-quotation', [PurchaseOrderController::class, 'createFromQuotation']);
    
    // Goods Receipts
    Route::apiResource('goods-receipts', GoodsReceiptController::class);
    Route::post('goods-receipts/{goodsReceipt}/confirm', [GoodsReceiptController::class, 'confirm']);
    
    // Vendor Invoices
    Route::apiResource('vendor-invoices', VendorInvoiceController::class);
    Route::post('vendor-invoices/{vendorInvoice}/approve', [VendorInvoiceController::class, 'approve']);
    Route::post('vendor-invoices/{vendorInvoice}/pay', [VendorInvoiceController::class, 'pay']);
    
    // Vendor Contracts
    Route::apiResource('vendor-contracts', VendorContractController::class);
    Route::post('vendor-contracts/{vendorContract}/activate', [VendorContractController::class, 'activate']);
    Route::post('vendor-contracts/{vendorContract}/terminate', [VendorContractController::class, 'terminate']);
    
    // Vendor Evaluations
    Route::apiResource('vendor-evaluations', VendorEvaluationController::class);
    Route::get('vendor-performance', [VendorEvaluationController::class, 'vendorPerformance']);
    
    // Customer routes
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::put('/{id}', [CustomerController::class, 'update']);
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
        Route::get('/{id}/quotations', [CustomerController::class, 'quotations']);
        Route::get('/{id}/orders', [CustomerController::class, 'orders']);
        Route::get('/{id}/invoices', [CustomerController::class, 'invoices']);
    });

    // Sales Quotation routes
    Route::prefix('quotations')->group(function () {
        Route::get('/', [SalesQuotationController::class, 'index']);
        Route::post('/', [SalesQuotationController::class, 'store']);
        Route::get('/{id}', [SalesQuotationController::class, 'show']);
        Route::put('/{id}', [SalesQuotationController::class, 'update']);
        Route::delete('/{id}', [SalesQuotationController::class, 'destroy']);
        Route::post('/{id}/lines', [SalesQuotationController::class, 'addLine']);
        Route::put('/{id}/lines/{lineId}', [SalesQuotationController::class, 'updateLine']);
        Route::delete('/{id}/lines/{lineId}', [SalesQuotationController::class, 'removeLine']);
    });

    // Sales Order routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [SalesOrderController::class, 'index']);
        Route::post('/', [SalesOrderController::class, 'store']);
        Route::post('/from-quotation', [SalesOrderController::class, 'createFromQuotation']);
        Route::get('/{id}', [SalesOrderController::class, 'show']);
        Route::put('/{id}', [SalesOrderController::class, 'update']);
        Route::delete('/{id}', [SalesOrderController::class, 'destroy']);
        Route::post('/{id}/lines', [SalesOrderController::class, 'addLine']);
        Route::put('/{id}/lines/{lineId}', [SalesOrderController::class, 'updateLine']);
        Route::delete('/{id}/lines/{lineId}', [SalesOrderController::class, 'removeLine']);
    });

    // Delivery routes
    Route::prefix('deliveries')->group(function () {
        Route::get('/', [DeliveryController::class, 'index']);
        Route::post('/', [DeliveryController::class, 'store']);
        Route::get('/{id}', [DeliveryController::class, 'show']);
        Route::put('/{id}', [DeliveryController::class, 'update']);
        Route::delete('/{id}', [DeliveryController::class, 'destroy']);
        Route::post('/{id}/complete', [DeliveryController::class, 'complete']);
    });

    // Sales Invoice routes
    Route::prefix('invoices')->group(function () {
        Route::get('/', [SalesInvoiceController::class, 'index']);
        Route::post('/', [SalesInvoiceController::class, 'store']);
        Route::post('/from-order', [SalesInvoiceController::class, 'createFromOrder']);
        Route::get('/{id}', [SalesInvoiceController::class, 'show']);
        Route::put('/{id}', [SalesInvoiceController::class, 'update']);
        Route::delete('/{id}', [SalesInvoiceController::class, 'destroy']);
        Route::get('/{id}/payment-info', [SalesInvoiceController::class, 'paymentInfo']);
    });

    // Sales Return routes
    Route::prefix('returns')->group(function () {
        Route::get('/', [SalesReturnController::class, 'index']);
        Route::post('/', [SalesReturnController::class, 'store']);
        Route::get('/{id}', [SalesReturnController::class, 'show']);
        Route::put('/{id}', [SalesReturnController::class, 'update']);
        Route::delete('/{id}', [SalesReturnController::class, 'destroy']);
        Route::post('/{id}/process', [SalesReturnController::class, 'process']);
    });

    // Customer Interaction routes
    Route::prefix('interactions')->group(function () {
        Route::get('/', [CustomerInteractionController::class, 'index']);
        Route::post('/', [CustomerInteractionController::class, 'store']);
        Route::get('/{id}', [CustomerInteractionController::class, 'show']);
        Route::put('/{id}', [CustomerInteractionController::class, 'update']);
        Route::delete('/{id}', [CustomerInteractionController::class, 'destroy']);
        Route::get('/customer/{customerId}', [CustomerInteractionController::class, 'getCustomerInteractions']);
    });

    // Sales Commission routes
    Route::prefix('commissions')->group(function () {
        Route::get('/', [SalesCommissionController::class, 'index']);
        Route::post('/', [SalesCommissionController::class, 'store']);
        Route::get('/{id}', [SalesCommissionController::class, 'show']);
        Route::put('/{id}', [SalesCommissionController::class, 'update']);
        Route::delete('/{id}', [SalesCommissionController::class, 'destroy']);
        Route::post('/calculate', [SalesCommissionController::class, 'calculateCommissions']);
        Route::get('/sales-person/{salesPersonId}', [SalesCommissionController::class, 'getSalesPersonCommissions']);
        Route::post('/mark-as-paid', [SalesCommissionController::class, 'markAsPaid']);
    });

    // Sales Forecast routes
    Route::prefix('forecasts')->group(function () {
        Route::get('/', [SalesForecastController::class, 'index']);
        Route::post('/', [SalesForecastController::class, 'store']);
        Route::get('/{id}', [SalesForecastController::class, 'show']);
        Route::put('/{id}', [SalesForecastController::class, 'update']);
        Route::delete('/{id}', [SalesForecastController::class, 'destroy']);
        Route::post('/generate', [SalesForecastController::class, 'generateForecasts']);
        Route::post('/update-actuals', [SalesForecastController::class, 'updateActuals']);
        Route::get('/accuracy', [SalesForecastController::class, 'getForecastAccuracy']);
    });
});

Route::middleware('api')->group(function () {
    // Item Category Routes
    Route::prefix('item-categories')->group(function () {
        Route::get('/', [ItemCategoryController::class, 'index']);
        Route::post('/', [ItemCategoryController::class, 'store']);
        Route::get('/{id}', [ItemCategoryController::class, 'show']);
        Route::put('/{id}', [ItemCategoryController::class, 'update']);
        Route::delete('/{id}', [ItemCategoryController::class, 'destroy']);
    });

    // Unit of Measure Routes
    Route::prefix('unit-of-measures')->group(function () {
        Route::get('/', [UnitOfMeasureController::class, 'index']);
        Route::post('/', [UnitOfMeasureController::class, 'store']);
        Route::get('/{id}', [UnitOfMeasureController::class, 'show']);
        Route::put('/{id}', [UnitOfMeasureController::class, 'update']);
        Route::delete('/{id}', [UnitOfMeasureController::class, 'destroy']);
    });

    // Item Routes
    Route::prefix('items')->group(function () {
        Route::get('/', [ItemController::class, 'index']);
        Route::post('/', [ItemController::class, 'store']);
        Route::get('/stock-status', [ItemController::class, 'getStockStatus']);
        Route::get('/{id}', [ItemController::class, 'show']);
        Route::put('/{id}', [ItemController::class, 'update']);
        Route::delete('/{id}', [ItemController::class, 'destroy']);
    });

    // Warehouse Routes
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [WarehouseController::class, 'index']);
        Route::post('/', [WarehouseController::class, 'store']);
        Route::get('/{id}', [WarehouseController::class, 'show']);
        Route::put('/{id}', [WarehouseController::class, 'update']);
        Route::delete('/{id}', [WarehouseController::class, 'destroy']);
    });

    // Stock Transaction Routes
    Route::prefix('stock-transactions')->group(function () {
        Route::get('/', [StockTransactionController::class, 'index']);
        Route::post('/', [StockTransactionController::class, 'store']);
        Route::get('/{id}', [StockTransactionController::class, 'show']);
        Route::get('/item/{itemId}', [StockTransactionController::class, 'getItemTransactions']);
        Route::get('/warehouse/{warehouseId}', [StockTransactionController::class, 'getWarehouseTransactions']);
    });

    // Stock Adjustment Routes
    Route::prefix('stock-adjustments')->group(function () {
        Route::get('/', [StockAdjustmentController::class, 'index']);
        Route::post('/', [StockAdjustmentController::class, 'store']);
        Route::get('/{id}', [StockAdjustmentController::class, 'show']);
        Route::patch('/{id}/approve', [StockAdjustmentController::class, 'approve']);
        Route::patch('/{id}/cancel', [StockAdjustmentController::class, 'cancel']);
    });
// Manufacturing Module Routes

// Products
Route::apiResource('products', ProductController::class);

// BOM
Route::apiResource('boms', BOMController::class);
Route::apiResource('boms/{bomId}/lines', BOMLineController::class);

// Routing
Route::apiResource('routings', RoutingController::class);
Route::apiResource('routings/{routingId}/operations', RoutingOperationController::class);

// Work Centers
Route::apiResource('work-centers', WorkCenterController::class);
Route::get('work-centers/{workCenterId}/maintenance-schedules', [MaintenanceScheduleController::class, 'byWorkCenter']);

// Work Orders
Route::apiResource('work-orders', WorkOrderController::class);
Route::apiResource('work-orders/{workOrderId}/operations', WorkOrderOperationController::class)
    ->except(['store', 'destroy']);

// Production Orders
Route::apiResource('production-orders', ProductionOrderController::class);
Route::apiResource('production-orders/{productionId}/consumptions', ProductionConsumptionController::class);

// Maintenance Schedules
Route::apiResource('maintenance-schedules', MaintenanceScheduleController::class);

// Quality Control
Route::apiResource('quality-inspections', QualityInspectionController::class);
Route::apiResource('quality-inspections/{inspectionId}/parameters', QualityParameterController::class);
Route::get('quality-inspections/by-reference/{referenceType}/{referenceId}', [QualityInspectionController::class, 'byReference']);
// Accounting module routes
Route::prefix('accounting')->group(function () {
    // Chart of Accounts
    Route::get('chart-of-accounts/hierarchy', [ChartOfAccountController::class, 'hierarchy']);
    Route::apiResource('chart-of-accounts', ChartOfAccountController::class);
    
    // Accounting Periods
    Route::get('accounting-periods/current', [AccountingPeriodController::class, 'current']);
    Route::apiResource('accounting-periods', AccountingPeriodController::class);
    
    // Journal Entries
    Route::post('journal-entries/{id}/post', [JournalEntryController::class, 'post']);
    Route::apiResource('journal-entries', JournalEntryController::class);
    
    // Customer Receivables
    Route::get('customer-receivables/aging', [CustomerReceivableController::class, 'aging']);
    Route::apiResource('customer-receivables', CustomerReceivableController::class);
    
    // Receivable Payments
    Route::apiResource('receivable-payments', ReceivablePaymentController::class);
    
    // Vendor Payables
    Route::get('vendor-payables/aging', [VendorPayableController::class, 'aging']);
    Route::apiResource('vendor-payables', VendorPayableController::class);
    
    // Payable Payments
    Route::apiResource('payable-payments', PayablePaymentController::class);
    
    // Tax Transactions
    Route::get('tax-transactions/summary', [TaxTransactionController::class, 'summary']);
    Route::apiResource('tax-transactions', TaxTransactionController::class);
    
    // Fixed Assets
    Route::apiResource('fixed-assets', FixedAssetController::class);
    
    // Asset Depreciations
    Route::post('fixed-assets/{id}/calculate-depreciation', [AssetDepreciationController::class, 'calculateDepreciation']);
    Route::apiResource('asset-depreciations', AssetDepreciationController::class);
    
    // Budgets
    Route::get('budgets/variance-report', [BudgetController::class, 'varianceReport']);
    Route::apiResource('budgets', BudgetController::class);
    
    // Bank Accounts
    Route::apiResource('bank-accounts', BankAccountController::class);
    
    // Bank Reconciliations
    Route::post('bank-reconciliations/{id}/finalize', [BankReconciliationController::class, 'finalize']);
    Route::apiResource('bank-reconciliations', BankReconciliationController::class);
    Route::apiResource('bank-reconciliations.lines', BankReconciliationController::class);
    
    // Financial Reports
    Route::prefix('reports')->group(function () {
        Route::get('trial-balance', [FinancialReportController::class, 'trialBalance']);
        Route::get('income-statement', [FinancialReportController::class, 'incomeStatement']);
        Route::get('balance-sheet', [FinancialReportController::class, 'balanceSheet']);
        Route::get('cash-flow', [FinancialReportController::class, 'cashFlow']);
        Route::get('accounts-receivable', [FinancialReportController::class, 'accountsReceivable']);
        Route::get('accounts-payable', [FinancialReportController::class, 'accountsPayable']);
    });
});
});