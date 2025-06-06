<!-- src/views/manufacturing/WorkOrderForm.vue -->
<template>
    <div class="work-order-form-page">
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">{{ isEditMode ? 'Edit Work Order' : 'Create Work Order' }}</h2>
        </div>
        <div class="card-body">
          <form @submit.prevent="submitForm">
            <div class="alert alert-info" v-if="isEditMode && workOrder.status !== 'Draft'">
              <i class="fas fa-info-circle mr-2"></i>
              This work order is already in {{ workOrder.status }} status. Some fields cannot be edited.
            </div>
            
            <!-- Form Errors -->
            <div v-if="formErrors.length > 0" class="alert alert-danger">
              <h5><i class="fas fa-exclamation-triangle mr-2"></i> Please fix the following errors:</h5>
              <ul>
                <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
              </ul>
            </div>
            
            <!-- Basic Information Section -->
            <div class="section-title">
              <h3>Basic Information</h3>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Work Order Number <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="workOrder.wo_number" 
                    :disabled="isEditMode" 
                    placeholder="WO-00001"
                    required
                  />
                  <small class="form-text text-muted">
                    Unique identifier for this work order
                  </small>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label>Work Order Date <span class="text-danger">*</span></label>
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="workOrder.wo_date" 
                    :disabled="isEditMode && workOrder.status !== 'Draft'"
                    required
                  />
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Product <span class="text-danger">*</span></label>
                  <select 
                    class="form-control" 
                    v-model="workOrder.item_id" 
                    @change="loadBOMsAndRoutings"
                    :disabled="isEditMode && workOrder.status !== 'Draft'"
                    required
                  >
                    <option value="">Select a product</option>
                    <option v-for="product in products" :key="product.item_id" :value="product.item_id">
                      {{ product.name }} ({{ product.item_code }})
                    </option>
                  </select>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label>Planned Quantity <span class="text-danger">*</span></label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model="workOrder.planned_quantity" 
                    min="1" 
                    step="1" 
                    :disabled="isEditMode && workOrder.status !== 'Draft'"
                    required
                  />
                </div>
              </div>
            </div>
            
            <!-- BOM and Routing Selection -->
            <div class="section-title">
              <h3>BOM and Routing Information</h3>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Bill of Materials <span class="text-danger">*</span></label>
                  <select 
                    class="form-control" 
                    v-model="workOrder.bom_id" 
                    :disabled="isEditMode && workOrder.status !== 'Draft'"
                    required
                  >
                    <option value="">Select a BOM</option>
                    <option v-for="bom in boms" :key="bom.bom_id" :value="bom.bom_id">
                      {{ bom.bom_code }} ({{ bom.revision }})
                    </option>
                  </select>
                  <small v-if="boms.length === 0 && workOrder.item_id" class="text-danger">
                    No BOMs found for this product. Please create a BOM first.
                  </small>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label>Routing <span class="text-danger">*</span></label>
                  <select 
                    class="form-control" 
                    v-model="workOrder.routing_id" 
                    :disabled="isEditMode && workOrder.status !== 'Draft'"
                    required
                  >
                    <option value="">Select a routing</option>
                    <option v-for="routing in routings" :key="routing.routing_id" :value="routing.routing_id">
                      {{ routing.routing_code }} ({{ routing.revision }})
                    </option>
                  </select>
                  <small v-if="routings.length === 0 && workOrder.item_id" class="text-danger">
                    No routings found for this product. Please create a routing first.
                  </small>
                </div>
              </div>
            </div>
            
            <!-- Scheduling Section -->
            <div class="section-title">
              <h3>Scheduling Information</h3>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Planned Start Date <span class="text-danger">*</span></label>
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="workOrder.planned_start_date" 
                    required
                  />
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label>Planned End Date <span class="text-danger">*</span></label>
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="workOrder.planned_end_date" 
                    required
                  />
                  <small v-if="endDateError" class="text-danger">
                    End date must be after start date
                  </small>
                </div>
              </div>
            </div>
            
            <!-- Additional Information Section -->
            <div class="section-title">
              <h3>Additional Information</h3>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Status</label>
                  <select 
                    class="form-control" 
                    v-model="workOrder.status" 
                    :disabled="isEditMode && workOrder.status !== 'Draft'"
                  >
                    <option value="Draft">Draft</option>
                    <option value="Planned" v-if="isEditMode">Planned</option>
                  </select>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label>Priority</label>
                  <select class="form-control" v-model="workOrder.priority">
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label>Notes</label>
              <textarea 
                class="form-control" 
                v-model="workOrder.notes" 
                rows="3" 
                placeholder="Enter any additional notes or instructions..."
              ></textarea>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
              <button type="button" class="btn btn-secondary mr-2" @click="cancelForm">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                {{ isEditMode ? 'Update Work Order' : 'Create Work Order' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRouter, useRoute } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'WorkOrderForm',
    setup() {
      const router = useRouter();
      const route = useRoute();
      
      // Determine if we're in edit or create mode
      const isEditMode = computed(() => !!route.params.id);
      
      // Form data
      const workOrder = ref({
        wo_number: '',
        wo_date: new Date().toISOString().split('T')[0], // Default to current date
        item_id: '',
        bom_id: '',
        routing_id: '',
        planned_quantity: 1,
        planned_start_date: new Date().toISOString().split('T')[0],
        planned_end_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // Default to 1 week ahead
        status: 'Draft',
        priority: 'Medium',
        notes: ''
      });
      
      // Form state
      const isSubmitting = ref(false);
      const formErrors = ref([]);
      const endDateError = ref(false);
      const products = ref([]);
      const boms = ref([]);
      const routings = ref([]);
      
      // Methods
      const loadProducts = async () => {
        try {
          const response = await axios.get('/api/items', { params: { type: 'manufactureable' } });
          products.value = response.data.data;
        } catch (error) {
          console.error('Error loading products:', error);
        }
      };
      
      const loadBOMsAndRoutings = async () => {
        if (!workOrder.value.item_id) {
          boms.value = [];
          routings.value = [];
          return;
        }
        
        try {
          // Load BOMs for the selected product
          const bomsResponse = await axios.get('/api/boms', { 
            params: { item_id: workOrder.value.item_id, status: 'Active' } 
          });
          boms.value = bomsResponse.data.data;
          
          // Load routings for the selected product
          const routingsResponse = await axios.get('/api/routings', { 
            params: { item_id: workOrder.value.item_id, status: 'Active' } 
          });
          routings.value = routingsResponse.data.data;
          
          // Auto-select if only one BOM or routing is available
          if (boms.value.length === 1) {
            workOrder.value.bom_id = boms.value[0].bom_id;
          }
          
          if (routings.value.length === 1) {
            workOrder.value.routing_id = routings.value[0].routing_id;
          }
        } catch (error) {
          console.error('Error loading BOMs and routings:', error);
        }
      };
      
      const loadWorkOrder = async () => {
        if (!isEditMode.value) return;
        
        try {
          const response = await axios.get(`/api/work-orders/${route.params.id}`);
          const data = response.data.data;
          
          // Update the work order form with the retrieved data
          workOrder.value = {
            wo_id: data.wo_id,
            wo_number: data.wo_number,
            wo_date: data.wo_date,
            item_id: data.item_id,
            bom_id: data.bom_id,
            routing_id: data.routing_id,
            planned_quantity: data.planned_quantity,
            planned_start_date: data.planned_start_date,
            planned_end_date: data.planned_end_date,
            status: data.status,
            priority: data.priority || 'Medium',
            notes: data.notes || ''
          };
          
          // Load BOMs and routings after setting the item_id
          await loadBOMsAndRoutings();
        } catch (error) {
          console.error('Error loading work order:', error);
          formErrors.value.push('Failed to load work order details.');
        }
      };
      
      const validateForm = () => {
        formErrors.value = [];
        endDateError.value = false;
        
        // Check if end date is after start date
        if (new Date(workOrder.value.planned_end_date) <= new Date(workOrder.value.planned_start_date)) {
          formErrors.value.push('Planned end date must be after the planned start date.');
          endDateError.value = true;
        }
        
        // Check if BOM is selected
        if (!workOrder.value.bom_id) {
          formErrors.value.push('Please select a Bill of Materials (BOM).');
        }
        
        // Check if routing is selected
        if (!workOrder.value.routing_id) {
          formErrors.value.push('Please select a Routing.');
        }
        
        return formErrors.value.length === 0;
      };
      
      const submitForm = async () => {
        if (!validateForm()) return;
        
        isSubmitting.value = true;
        
        try {
          if (isEditMode.value) {
            // Update existing work order
            await axios.put(`/api/work-orders/${workOrder.value.wo_id}`, workOrder.value);
            router.push(`/manufacturing/work-orders/${workOrder.value.wo_id}`);
          } else {
            // Create new work order
            const response = await axios.post('/api/work-orders', workOrder.value);
            router.push(`/manufacturing/work-orders/${response.data.data.wo_id}`);
          }
        } catch (error) {
          console.error('Error saving work order:', error);
          if (error.response && error.response.data && error.response.data.errors) {
            // Extract validation errors from the response
            const serverErrors = error.response.data.errors;
            formErrors.value = Object.values(serverErrors).flat();
          } else {
            formErrors.value.push('An error occurred while saving the work order.');
          }
        } finally {
          isSubmitting.value = false;
        }
      };
      
      const cancelForm = () => {
        if (isEditMode.value) {
          router.push(`/manufacturing/work-orders/${route.params.id}`);
        } else {
          router.push('/manufacturing/work-orders');
        }
      };
      
      // Lifecycle hooks
      onMounted(async () => {
        await loadProducts();
        
        if (isEditMode.value) {
          await loadWorkOrder();
        }
      });
      
      return {
        workOrder,
        isEditMode,
        isSubmitting,
        formErrors,
        endDateError,
        products,
        boms,
        routings,
        loadBOMsAndRoutings,
        submitForm,
        cancelForm
      };
    }
  };
  </script>
  
  <style scoped>
  .work-order-form-page {
    padding: 1rem;
  }
  
  .section-title {
    margin: 1.5rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
  }
  
  .section-title h3 {
    font-size: 1.25rem;
    margin: 0;
    color: var(--gray-800);
  }
  
  .form-actions {
    margin-top: 2rem;
    display: flex;
    justify-content: flex-end;
  }
  
  @media (max-width: 768px) {
    .row {
      flex-direction: column;
    }
    
    .col-md-6 {
      width: 100%;
    }
  }
  </style>