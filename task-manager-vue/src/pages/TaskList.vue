<script setup>
import { ref, onMounted, computed } from "vue";
import { createPinia, setActivePinia } from "pinia";
import { useTaskStore } from "../stores/taskStore";
import { useAuthStore } from "../stores/authStore";
import Swal from "sweetalert2";
import dayjs from "dayjs";
import weekday from "dayjs/plugin/weekday";
import localizedFormat from "dayjs/plugin/localizedFormat";
import utc from "dayjs/plugin/utc";

dayjs.extend(weekday);
dayjs.extend(localizedFormat);
dayjs.extend(utc);

setActivePinia(createPinia());
const taskStore = useTaskStore();
const authStore = useAuthStore();

const tasks = ref([]);
const showTrashed = ref(false);

const editingTaskId = ref(null);
const editedTask = ref({ title: "", description: "" });

const fetchTasks = async () => {
  if (showTrashed.value) {
    tasks.value = await taskStore.getTrashedTasks();
  } else {
    tasks.value = await taskStore.getTasks();
  }
};

const toggleView = async () => {
  showTrashed.value = !showTrashed.value;
  tasks.value = [];
  await fetchTasks();
};

// === Editing system ===
const startEditing = (task) => {
  editingTaskId.value = task.id;
  editedTask.value = { ...task };
};

const cancelEditing = () => {
  editingTaskId.value = null;
  editedTask.value = { title: "", description: "" };
};

const updateTask = async (taskId) => {
  await taskStore.updateTask(taskId, {
    title: editedTask.value.title,
    description: editedTask.value.description,
  });
  await fetchTasks();
  cancelEditing();
};

// === Delete / Restore system ===
const deleteTask = async (id) => {
  await taskStore.deleteTask(id);
  await fetchTasks();
};

const restoreTask = async (taskId) => {
  const result = await Swal.fire({
    title: "Are you sure?",
    text: "This task will be restored!",
    icon: "success",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, restore it!",
  });
  if (result.isConfirmed) {
    const success = await taskStore.restoreTask(taskId);
    if (success) await fetchTasks();
  }
};

const forceDeleteTask = async (taskId) => {
  const result = await Swal.fire({
    title: "Are you sure?",
    text: "This task will be permanently deleted!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  });

  if (result.isConfirmed) {
    const success = await taskStore.forceDeleteTask(taskId);
    if (success) {
      await fetchTasks();
    }
  }
};

const updateStatus = async (taskId, newStatus, task) => {
  const updatedTask = await taskStore.updateTaskStatus(taskId, newStatus, task);
  if (updatedTask) {
    const index = tasks.value.findIndex((t) => t.id === taskId);
    if (index !== -1) tasks.value[index].status = updatedTask.status;
  }
};

const formatStatus = (status) => {
  if (!status) return "Unknown";
  return status.charAt(0).toUpperCase() + status.slice(1);
};

// --- Helpers ---
const isUserAssigned = (task) => {
  const userId = authStore.user?.id;
  return task.assigned_users?.some((u) => Number(u.id) === Number(userId));
};

const canChangeStatus = (task) => {
  if (authStore.isAdmin) return true;
  if (task.status === "completed") return false;
  return isUserAssigned(task);
};

const formatDate = (dateString) => {
  return dayjs.utc(dateString).local().format("dddd, DD MMMM, hh:mm A");
};

const getUserAssignment = (task) => {
  const userId = authStore.user?.id;
  if (!task.assigned_users) return null;
  return task.assigned_users.find(
    (a) => a.assignee_user && Number(a.assignee_user.id) === Number(userId)
  );
};

onMounted(fetchTasks);
</script>

<template>
  <div class="content">
    <div class="container">
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4>{{ showTrashed ? "Trashed Tasks" : "Task List" }}</h4>

          <!-- 🔹 Only Admin can see Add / Toggle Trash buttons -->
          <div v-if="authStore.isAdmin">
            <button @click="toggleView" class="btn btn-sm btn-secondary me-2">
              {{ showTrashed ? "Show Active" : "Show Trash" }}
            </button>
            <RouterLink
              v-if="!showTrashed"
              :to="{ name: 'task-create' }"
              class="btn btn-sm btn-primary"
            >
              Add New
            </RouterLink>
          </div>
        </div>

        <table class="table table-striped">
          <thead>
            <tr>
              <th>Title</th>
              <th>Description</th>
              <th>Created By</th>
              <th>Status</th>
              <th v-if="!authStore.isAdmin">Assigned At</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="task in tasks" :key="task.id">
              <td>
                <div v-if="editingTaskId === task.id">
                  <input v-model="editedTask.title" class="form-control" />
                </div>
                <div v-else>{{ task.title }}</div>
              </td>

              <td>
                <div v-if="editingTaskId === task.id">
                  <textarea
                    v-model="editedTask.description"
                    class="form-control"
                  ></textarea>
                </div>
                <div v-else>{{ task.description }}</div>
              </td>

              <td>{{ task.created_by?.name || "—" }}</td>

              <td class="text-center align-middle">
                <!-- Status badge -->
                <span
                  class="badge"
                  :class="{
                    'bg-secondary': task.status === 'created',
                    'bg-info': task.status === 'assigned',
                    'bg-primary': task.status === 'progress',
                    'bg-warning text-dark': task.status === 'hold',
                    'bg-success': task.status === 'completed',
                    'bg-danger': task.status === 'cancelled',
                  }"
                >
                  {{ formatStatus(task.status) }}
                </span>

                <!-- All users can change status -->
                <select
                  v-if="
                    (canChangeStatus(task) &&
                      task.status !== 'completed' &&
                      task.status !== 'created') ||
                    authStore.isAdmin
                  "
                  v-model="task.status"
                  @change="updateStatus(task.id, task.status, task)"
                  class="form-select form-select-sm mt-1"
                  style="max-width: 150px"
                >
                  <template v-if="authStore.isAdmin">
                    <option value="created">Created</option>
                    <option value="assigned">Assigned</option>
                    <option value="progress">In Progress</option>
                    <option value="hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </template>
                  <template v-else>
                    <option value="progress">In Progress</option>
                    <option value="hold">On Hold</option>
                    <option value="completed">Completed</option>
                  </template>
                </select>
              </td>

              <td v-if="!authStore.isAdmin">
                <div v-if="task.assigned_users?.length">
                  <div v-for="user in task.assigned_users" :key="user.id">
                    {{ user.assignee_user?.name || user.name }} -
                    {{ formatDate(user.assigned_at) }}
                  </div>
                </div>
                <div v-else>Not assigned</div>
              </td>

              <td class="text-center align-middle">
                <!-- Active view -->
                <template v-if="!showTrashed">
                  <!-- Normal user: Only see Assign button -->
                  <div v-if="!authStore.isAdmin">
                    <RouterLink
                      :to="{ name: 'task-assign', params: { id: task.id } }"
                      class="btn btn-sm btn-info"
                    >
                      View / Assign
                    </RouterLink>
                  </div>

                  <!-- Admin: Full control -->
                  <div v-else>
                    <div v-if="editingTaskId === task.id">
                      <button
                        @click="updateTask(task.id)"
                        class="btn btn-sm btn-success me-2"
                      >
                        Save
                      </button>
                      <button
                        @click="cancelEditing"
                        class="btn btn-sm btn-secondary"
                      >
                        Cancel
                      </button>
                    </div>
                    <div v-else>
                      <button
                        v-if="
                          task.status !== 'completed' &&
                          task.status !== 'cancelled'
                        "
                        @click="startEditing(task)"
                        class="btn btn-sm btn-warning me-2"
                      >
                        Edit
                      </button>
                      <button
                        @click="deleteTask(task.id)"
                        class="btn btn-sm btn-danger me-2"
                      >
                        Trash
                      </button>
                      <RouterLink
                        v-if="
                          task.status !== 'completed' &&
                          task.status !== 'cancelled'
                        "
                        :to="{ name: 'task-assign', params: { id: task.id } }"
                        class="btn btn-sm btn-info me-2"
                      >
                        Assign Task
                      </RouterLink>
                    </div>
                  </div>
                </template>

                <!-- Trashed view (Admin only) -->
                <template v-else-if="authStore.isAdmin">
                  <button
                    @click="restoreTask(task.id)"
                    class="btn btn-sm btn-success me-2"
                  >
                    Restore
                  </button>
                  <button
                    @click="forceDeleteTask(task.id)"
                    class="btn btn-sm btn-danger"
                  >
                    Delete Permanently
                  </button>
                </template>
              </td>
            </tr>

            <tr v-if="tasks.length === 0">
              <td colspan="5" class="text-center text-muted">No tasks found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
<style scoped>
.table td {
  max-width: 318px;
  white-space: normal;
  word-wrap: break-word;
  vertical-align: middle;
}
select:disabled {
  background-color: #eee;
  cursor: not-allowed;
  opacity: 0.7;
}
</style>
