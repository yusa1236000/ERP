<!-- src/views/sales/OutstandingDeliveryForm.vue -->

<!-- ==================== TEMPLATE SECTION ==================== -->
<template>
    <div class="outstanding-delivery-form">
      <div class="page-header">
        <h1>Buat Pengiriman dari Outstanding Items</h1>
        <div class="page-actions">
          <button class="btn btn-secondary" @click="goBack">
            <i class="fas fa-arrow-left"></i> Kembali
          </button>
          <button class="btn btn-primary" @click="saveDelivery" :disabled="isSubmitting || selectedItems.length === 0">
            <i class="fas fa-save"></i> {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>

      <div v-if="error" class="alert alert-danger">
        {{ error }}
      </div>

      <div class="form-container">
        <!-- Delivery Information Card -->
        <div class="form-card">
          <div class="card-header">
            <h2>Informasi Pengiriman</h2>
          </div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group">
                <label for="delivery_number">Nomor Pengiriman*</label>
                <input
                  type="text"
                  id="delivery_number"
                  v-model="form.delivery_number"
                  required
                />
                <small class="text-muted">Nomor pengiriman akan otomatis diinkrementasi jika ada lebih dari satu SO</small>
              </div>

              <div class="form-group">
                <label for="delivery_date">Tanggal Pengiriman*</label>
                <input
                  type="date"
                  id="delivery_date"
                  v-model="form.delivery_date"
                  required
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="shipping_method">Metode Pengiriman</label>
                <select id="shipping_method" v-model="form.shipping_method">
                  <option value="">-- Pilih Metode --</option>
                  <option value="Road">Darat</option>
                  <option value="Sea">Laut</option>
                  <option value="Air">Udara</option>
                  <option value="Express">Express</option>
                </select>
              </div>

              <div class="form-group">
                <label for="tracking_number">Nomor Pelacakan</label>
                <input
                  type="text"
                  id="tracking_number"
                  v-model="form.tracking_number"
                  placeholder="Nomor pelacakan pengiriman"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Outstanding Sales Orders Card -->
        <div class="form-card">
          <div class="card-header">
            <h2>Outstanding Sales Orders</h2>
          </div>
          <div class="card-body">
            <div v-if="isLoadingSalesOrders" class="loading-indicator">
              <i class="fas fa-spinner fa-spin"></i> Memuat data sales orders...
            </div>

            <div v-else-if="outstandingSalesOrders.length === 0" class="empty-state">
              <div class="empty-icon">
                <i class="fas fa-file-invoice"></i>
              </div>
              <h3>Tidak ada sales order dengan item outstanding</h3>
              <p>Semua sales order sudah lengkap pengirimannya atau tidak ada sales order aktif.</p>
            </div>

            <div v-else class="outstanding-orders">
              <div class="filter-bar">
                <div class="search-box">
                  <i class="fas fa-search search-icon"></i>
                  <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Cari sales order atau customer..."
                    @input="filterSalesOrders"
                  />
                  <button v-if="searchQuery" @click="clearSearch" class="clear-search">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>

              <table class="orders-table">
                <thead>
                  <tr>
                    <th>Nomor SO</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Outstanding Qty</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="so in filteredSalesOrders" :key="so.so_id">
                    <td>{{ so.so_number }}</td>
                    <td>{{ formatDate(so.so_date) }}</td>
                    <td>{{ so.customer_name }}</td>
                    <td>{{ so.status }}</td>
                    <td>{{ so.outstanding_quantity }}</td>
                    <td>
                      <button
                        class="btn btn-sm btn-primary"
                        @click="viewOutstandingItems(so)"
                      >
                        Lihat Item
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Selected Items Card -->
        <div class="form-card" v-if="outstandingItems.length > 0">
          <div class="card-header">
            <h2>Item-item untuk Dikirim</h2>
            <div class="selected-count">
              {{ selectedItems.length }} item dipilih
            </div>
          </div>
          <div class="card-body">
            <div class="selected-so-info" v-if="selectedSalesOrder">
              <div class="info-item">
                <label>Sales Order:</label>
                <span>{{ selectedSalesOrder.so_number }}</span>
              </div>
              <div class="info-item">
                <label>Customer:</label>
                <span>{{ selectedSalesOrder.customer_name }}</span>
              </div>
            </div>

            <div class="outstanding-items">
              <table class="items-table">
                <thead>
                  <tr>
                    <th>
                      <div class="checkbox-wrapper">
                        <input
                          type="checkbox"
                          id="select-all"
                          :checked="isAllSelected"
                          @change="toggleSelectAll"
                        />
                        <label for="select-all">Item</label>
                      </div>
                    </th>
                    <th>Outstanding Qty</th>
                    <th>Jumlah Dikirim</th>
                    <th>Gudang</th>
                    <th>Batch Number</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in outstandingItems" :key="index">
                    <td>
                      <div class="checkbox-wrapper">
                        <input
                          type="checkbox"
                          :id="`item-${index}`"
                          v-model="item.selected"
                          @change="updateSelectedItems"
                        />
                        <div class="item-info">
                          <div class="item-code">{{ item.item_code }}</div>
                          <div class="item-name">{{ item.item_name }}</div>
                        </div>
                      </div>
                    </td>
                    <td>{{ item.outstanding_quantity }} {{ item.uom_name }}</td>
                    <td>
                      <input
                        type="number"
                        v-model="item.delivered_quantity"
                        :disabled="!item.selected"
                        min="0.01"
                        :max="item.outstanding_quantity"
                        step="0.01"
                        @input="validateQuantity(item)"
                      />
                    </td>
                    <td>
                      <select
                        v-model="item.warehouse_id"
                        :disabled="!item.selected"
                        required
                      >
                        <option value="">-- Pilih Gudang --</option>
                        <option
                          v-for="stock in item.warehouse_stocks"
                          :key="stock.warehouse_id"
                          :value="stock.warehouse_id"
                          :disabled="stock.available_quantity < item.delivered_quantity"
                        >
                          {{ stock.warehouse_name }} ({{ stock.available_quantity }} tersedia)
                        </option>
                      </select>
                    </td>
                    <td>
                      <input
                        type="text"
                        v-model="item.batch_number"
                        :disabled="!item.selected"
                        placeholder="Batch number (opsional)"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="goBack">
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="saveDelivery"
            :disabled="isSubmitting || selectedItems.length === 0"
          >
            {{ isSubmitting ? 'Menyimpan...' : 'Simpan Pengiriman' }}
          </button>
        </div>
      </div>
    </div>
  </template>

  <!-- ==================== SCRIPT SECTION ==================== -->
  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'OutstandingDeliveryForm',
    setup() {
      const router = useRouter();

      // Form data
      const form = ref({
        delivery_number: '',
        delivery_date: new Date().toISOString().substr(0, 10),
        shipping_method: '',
        tracking_number: '',
        items: []
      });

      // Data
      const outstandingSalesOrders = ref([]);
      const filteredSalesOrders = ref([]);
      const outstandingItems = ref([]);
      const selectedSalesOrder = ref(null);
      const searchQuery = ref('');

      // UI state
      const isLoadingSalesOrders = ref(true);
      const isLoadingItems = ref(false);
      const isSubmitting = ref(false);
      const error = ref('');

      // Generate a unique delivery number
      const generateDeliveryNumber = () => {
        const today = new Date();
        const year = today.getFullYear().toString().slice(-2);
        const month = (today.getMonth() + 1).toString().padStart(2, '0');
        const day = today.getDate().toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');

        return `DO${year}${month}${day}-${random}`;
      };

      // Computed properties
      const selectedItems = computed(() => {
        return outstandingItems.value.filter(item => item.selected);
      });

      const isAllSelected = computed(() => {
        return outstandingItems.value.length > 0 && outstandingItems.value.every(item => item.selected);
      });

      // Load outstanding sales orders
      const loadOutstandingSalesOrders = async () => {
        isLoadingSalesOrders.value = true;
        error.value = '';

        try {
          const response = await axios.get('/deliveries/outstanding-so');
          outstandingSalesOrders.value = response.data.data;
          filteredSalesOrders.value = [...outstandingSalesOrders.value];
        } catch (err) {
          console.error('Error loading outstanding sales orders:', err);
          error.value = 'Gagal memuat data sales order.';
        } finally {
          isLoadingSalesOrders.value = false;
        }
      };

      // Filter sales orders based on search query
      const filterSalesOrders = () => {
        if (!searchQuery.value) {
          filteredSalesOrders.value = [...outstandingSalesOrders.value];
          return;
        }

        const query = searchQuery.value.toLowerCase();
        filteredSalesOrders.value = outstandingSalesOrders.value.filter(so =>
          so.so_number.toLowerCase().includes(query) ||
          so.customer_name.toLowerCase().includes(query)
        );
      };

      // Clear search
      const clearSearch = () => {
        searchQuery.value = '';
        filterSalesOrders();
      };

      // View outstanding items for a specific sales order
      const viewOutstandingItems = async (salesOrder) => {
        isLoadingItems.value = true;
        error.value = '';
        selectedSalesOrder.value = salesOrder;

        try {
          const response = await axios.get(`/deliveries/outstanding-items/${salesOrder.so_id}`);
          const data = response.data.data;

          // Transform the data to include selection and delivery info
          outstandingItems.value = data.outstanding_items.map(item => ({
            ...item,
            selected: false,
            delivered_quantity: item.outstanding_quantity,
            batch_number: ''
          }));
        } catch (err) {
          console.error('Error loading outstanding items:', err);
          error.value = 'Gagal memuat data item.';
        } finally {
          isLoadingItems.value = false;
        }
      };

      // Toggle select all items
      const toggleSelectAll = () => {
        const newState = !isAllSelected.value;
        outstandingItems.value.forEach(item => {
          item.selected = newState;
        });
        updateSelectedItems();
      };

      // Update selected items
      const updateSelectedItems = () => {
        // Automatically set delivered_quantity to max if selected
        outstandingItems.value.forEach(item => {
          if (item.selected && (!item.delivered_quantity || item.delivered_quantity <= 0)) {
            item.delivered_quantity = item.outstanding_quantity;
          }
        });
      };

      // Validate quantity input
      const validateQuantity = (item) => {
        if (item.delivered_quantity > item.outstanding_quantity) {
          item.delivered_quantity = item.outstanding_quantity;
        } else if (item.delivered_quantity < 0.01) {
          item.delivered_quantity = 0.01;
        }
      };

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: '2-digit',
          month: 'short',
          year: 'numeric'
        });
      };

      // Navigate back
      const goBack = () => {
        router.push('/sales/deliveries');
      };

      // Save delivery
      const saveDelivery = async () => {
        // Validate form
        if (!form.value.delivery_number || !form.value.delivery_date) {
          error.value = 'Harap isi semua field yang wajib diisi.';
          return;
        }

        // Validate selected items
        if (selectedItems.value.length === 0) {
          error.value = 'Pilih setidaknya satu item untuk dikirim.';
          return;
        }

        // Prepare items data
        const items = selectedItems.value.map(item => ({
          so_line_id: item.so_line_id,
          delivered_quantity: parseFloat(item.delivered_quantity),
          warehouse_id: item.warehouse_id,
          batch_number: item.batch_number || null
        }));

        // Validate each item
        for (let i = 0; i < items.length; i++) {
          const item = items[i];
          if (!item.warehouse_id) {
            error.value = `Harap pilih gudang untuk item "${outstandingItems.value.find(oi => oi.so_line_id === item.so_line_id).item_name}".`;
            return;
          }
        }

        isSubmitting.value = true;
        error.value = '';

        try {
          // Create payload
          const payload = {
            delivery_number: form.value.delivery_number,
            delivery_date: form.value.delivery_date,
            shipping_method: form.value.shipping_method,
            tracking_number: form.value.tracking_number,
            items: items
          };

          // Submit to API
          await axios.post('/deliveries/from-outstanding', payload);

          alert('Pengiriman berhasil dibuat!');
          router.push('/sales/deliveries');
        } catch (err) {
          console.error('Error creating delivery:', err);

          if (err.response && err.response.data && err.response.data.message) {
            error.value = err.response.data.message;
          } else {
            error.value = 'Terjadi kesalahan saat membuat pengiriman.';
          }
        } finally {
          isSubmitting.value = false;
        }
      };

      // Initialize
      onMounted(() => {
        form.value.delivery_number = generateDeliveryNumber();
        loadOutstandingSalesOrders();
      });

      return {
        form,
        outstandingSalesOrders,
        filteredSalesOrders,
        outstandingItems,
        selectedSalesOrder,
        searchQuery,
        selectedItems,
        isAllSelected,
        isLoadingSalesOrders,
        isLoadingItems,
        isSubmitting,
        error,
        formatDate,
        filterSalesOrders,
        clearSearch,
        viewOutstandingItems,
        toggleSelectAll,
        updateSelectedItems,
        validateQuantity,
        goBack,
        saveDelivery
      };
    }
  };
  </script>

  <!-- ==================== STYLE SECTION ==================== -->
  <style scoped>
  .outstanding-delivery-form {
    padding: 1rem 0;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
  }

  .page-actions {
    display: flex;
    gap: 0.75rem;
  }

  .alert {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
  }

  .alert-danger {
    background-color: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
  }

  .form-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .form-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .card-header {
    background-color: #f8fafc;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
  }

  .card-body {
    padding: 1.5rem;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .form-row:last-child {
    margin-bottom: 0;
  }

  .form-group {
    margin-bottom: 0;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #334155;
  }

  .form-group input,
  .form-group select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }

  .form-group input:focus,
  .form-group select:focus {
    outline: 2px solid #2563eb;
    outline-offset: 1px;
  }

  .text-muted {
    color: #64748b;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: block;
  }

  .filter-bar {
    display: flex;
    margin-bottom: 1rem;
  }

  .search-box {
    position: relative;
    width: 100%;
    max-width: 320px;
  }

  .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
  }

  .search-box input {
    width: 100%;
    padding: 0.625rem 0.625rem 0.625rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }

  .search-box input:focus {
    outline: 2px solid #2563eb;
    outline-offset: 1px;
  }

  .clear-search {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
  }

  .orders-table,
  .items-table {
    width: 100%;
    border-collapse: collapse;
  }

  .orders-table th,
  .orders-table td,
  .items-table th,
  .items-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.875rem;
  }

  .orders-table th,
  .items-table th {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 500;
    color: #64748b;
  }

  .orders-table td,
  .items-table td {
    border-bottom: 1px solid #f1f5f9;
  }

  .orders-table tr:last-child td,
  .items-table tr:last-child td {
    border-bottom: none;
  }

  .orders-table tr:hover td {
    background-color: #f8fafc;
  }

  .items-table tr:hover td {
    background-color: #f8fafc;
  }

  .btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
  }

  .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
  }

  .btn-primary {
    background-color: #2563eb;
    color: white;
  }

  .btn-primary:hover:not(:disabled) {
    background-color: #1d4ed8;
  }

  .btn-primary:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
  }

  .btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
  }

  .btn-secondary:hover {
    background-color: #cbd5e1;
  }

  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem 0;
    color: #64748b;
  }

  .loading-indicator i {
    margin-right: 0.5rem;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 0;
    text-align: center;
    color: #64748b;
  }

  .empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #cbd5e1;
  }

  .empty-state h3 {
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .empty-state p {
    margin: 0;
    max-width: 24rem;
  }

  .selected-so-info {
    display: flex;
    gap: 2rem;
    padding: 0.5rem 0 1rem;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 1rem;
  }

  .info-item {
    display: flex;
    gap: 0.5rem;
  }

  .info-item label {
    font-weight: 500;
    color: #64748b;
  }

  .checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .checkbox-wrapper input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    cursor: pointer;
  }

  .item-info {
    display: flex;
    flex-direction: column;
  }

  .item-code {
    font-size: 0.75rem;
    color: #64748b;
  }

  .item-name {
    font-weight: 500;
  }

  .selected-count {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    background-color: #e2e8f0;
    border-radius: 1rem;
    color: #475569;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
  }

  @media (max-width: 1024px) {
    .form-row {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .selected-so-info {
      flex-direction: column;
      gap: 0.5rem;
    }
  }

  @media (max-width: 768px) {
    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .orders-table,
    .items-table {
      display: block;
      overflow-x: auto;
      white-space: nowrap;
    }
  }
  </style>
