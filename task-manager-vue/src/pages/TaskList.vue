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
import { useTaskAssignStore } from "../stores/taskAssignStore";

dayjs.extend(weekday);
dayjs.extend(localizedFormat);
dayjs.extend(utc);

setActivePinia(createPinia());
const taskStore = useTaskStore();
const authStore = useAuthStore();
const taskAssignStore = useTaskAssignStore();

const tasks = ref([]);
const showTrashed = ref(false);

const showGroupModal = ref(false);
const groupInfo = ref({ name: "", members: [] });

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

const formatStatus = (status) => {
  if (!status) return "Unknown";
  return status.charAt(0).toUpperCase() + status.slice(1);
};

const getVisibleStatus = (task) => {
  const userId = Number(authStore.user?.id);

  if (!task.assignments || !Array.isArray(task.assignments)) {
    return task.status || "created";
  }

  const userAssignment = task.assignments.find(
    (a) => a.assignee_user && Number(a.assignee_user.id) === userId
  );

  const groupAssignment = task.assignments.find((a) =>
    a.assignee_group?.users?.some((u) => Number(u.id) === userId)
  );

  if (authStore.isAdmin) {
    const hasCompleted = task.assignments.some((a) => a.status === "completed");
    const hasAssigned = task.assignments.some(
      (a) =>
        a.status === "assigned" ||
        a.status === "progress" ||
        a.status === "hold"
    );
    if (hasCompleted) return "completed";
    if (hasAssigned) return "assigned";
    return task.status || "created";
  }

  // Normal user
  if (userAssignment) return userAssignment.status;
  if (groupAssignment) return groupAssignment.status;

  return task.status || "created";
};

// Admin completed assignments list
const getAdminCompletedAssignments = (task) => {
  if (!authStore.isAdmin) return [];
  if (!task.assignments || !Array.isArray(task.assignments)) return [];
  const result = [];

  task.assignments.forEach((a) => {
    if (a.status === "completed") {
      if (a.assignee_user) result.push(`Completed by ${a.assignee_user.name}`);
      else if (a.assignee_group)
        result.push(`Completed by ${a.assignee_group.name}`);
    } else if (["assigned", "progress", "hold"].includes(a.status)) {
      if (a.assignee_user) result.push(` Assigned to ${a.assignee_user.name}`);
      else if (a.assignee_group)
        result.push(` Assigned to ${a.assignee_group.name}`);
    }
  });

  return result;
};

const getUserAssignment = (task) => {
  const userId = Number(authStore.user?.id);
  if (!task.assignments?.length) return null;

  const userAssignment = task.assignments.find(
    (a) => a.assignee_user && Number(a.assignee_user.id) === userId
  );
  if (userAssignment) {
    return {
      updated_at: userAssignment.updated_at,
      status: userAssignment.status,
    };
  }

  const groupAssignment = task.assignments.find((a) =>
    a.assignee_group?.users?.some((u) => Number(u.id) === userId)
  );
  if (groupAssignment) {
    return {
      updated_at: groupAssignment.updated_at,
      status: groupAssignment.status,
    };
  }

  return null;
};

const updateUserStatus = async (task, newStatus) => {
  const userId = Number(authStore.user?.id);

  const assignment =
    task.assignments.find(
      (a) => a.assignee_user && Number(a.assignee_user.id) === userId
    ) ||
    task.assignments.find((a) =>
      a.assignee_group?.users?.some((u) => Number(u.id) === userId)
    );

  if (!assignment) return;

  if (newStatus === "created") return;

  const updated = await taskAssignStore.updateAssignmentStatus(
    task.id,
    assignment.id,
    newStatus
  );

  if (updated) {
    assignment.status = updated.status;
    assignment.updated_at = new Date().toISOString();

    localStorage.setItem(
      "task_status_sync",
      JSON.stringify({
        taskId: task.id,
        status: updated.status,
        updated_at: assignment.updated_at,
        updatedBy: "user",
      })
    );
  }
};

const viewGroupInfo = (task) => {
  const userId = Number(authStore.user?.id);

  const groupAssignment = task.assignments.find((a) =>
    a.assignee_group?.users?.some((u) => Number(u.id) === userId)
  );

  if (!groupAssignment) {
    Swal.fire({
      icon: "info",
      title: "No Group Found",
      text: "You are not assigned to any group for this task.",
    });
    return;
  }

  groupInfo.value = {
    name: groupAssignment.assignee_group?.name || "Unnamed Group",
    members: groupAssignment.assignee_group?.users || [],
  };

  showGroupModal.value = true;
};

// Format date
const formatDate = (dateString) => {
  if (!dateString) return "";
  return dayjs.utc(dateString).local().format("dddd, DD MMMM, hh:mm A");
};

onMounted(async () => {
  await fetchTasks();
});

window.addEventListener("storage", async (event) => {
  if (event.key === "task_status_sync") {
    try {
      const data = JSON.parse(event.newValue);

      if (data?.updatedBy === "user" && data?.taskId && data?.status) {
        const taskIndex = tasks.value.findIndex(
          (t) => Number(t.id) === Number(data.taskId)
        );
        if (taskIndex !== -1) {
          tasks.value[taskIndex].status = data.status;
        }
      }
    } catch (e) {
      console.error("Task status sync error:", e);
    }
  }
});
</script>

<template>
  <div class="content">
    <div class="container">
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4>{{ showTrashed ? "Trashed Tasks" : "Task List" }}</h4>

          <!-- Only Admin can see Add / Toggle Trash buttons -->
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
              <th v-if="!authStore.isAdmin">Status Updated Time</th>
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
                  v-if="
                    !authStore.isAdmin ||
                    getAdminCompletedAssignments(task).length === 0
                  "
                  class="badge"
                  :class="{
                    'bg-secondary': getVisibleStatus(task) === 'created',
                    'bg-info': getVisibleStatus(task) === 'assigned',
                    'bg-primary': getVisibleStatus(task) === 'progress',
                    'bg-warning text-dark': getVisibleStatus(task) === 'hold',
                    'bg-success':
                      getVisibleStatus(task) === 'completed' ||
                      (authStore.isAdmin &&
                        getAdminCompletedAssignments(task).length),
                    'bg-danger': getVisibleStatus(task) === 'cancelled',
                  }"
                >
                  {{
                    !authStore.isAdmin
                      ? formatStatus(getVisibleStatus(task))
                      : getAdminCompletedAssignments(task).length
                      ? "Completed"
                      : formatStatus(getVisibleStatus(task))
                  }}
                </span>

                <template
                  v-if="
                    authStore.isAdmin &&
                    getAdminCompletedAssignments(task).length
                  "
                >
                  <div class="d-flex flex-column gap-1 mt-2">
                    <span
                      v-for="(text, index) in getAdminCompletedAssignments(
                        task
                      )"
                      :key="index"
                      class="badge"
                      :class="{
                        'bg-success': text.includes('Completed by'),
                        'bg-info': text.includes('Assigned to'),
                      }"
                    >
                      {{ text }}
                    </span>
                  </div>
                </template>

                <div v-if="!authStore.isAdmin">
                  <select
                    v-if="
                      getVisibleStatus(task) !== 'completed' &&
                      getVisibleStatus(task) !== 'created' &&
                      getVisibleStatus(task) !== 'cancelled'
                    "
                    class="form-select form-select-sm mt-2"
                    :value="getVisibleStatus(task)"
                    @change="updateUserStatus(task, $event.target.value)"
                  >
                    <option value="progress">In Progress</option>
                    <option value="hold">On Hold</option>
                    <option value="completed">Completed</option>
                  </select>
                </div>
              </td>

              <td v-if="!authStore.isAdmin">
                <span
                  v-if="
                    getUserAssignment(task) &&
                    getVisibleStatus(task) !== 'created'
                  "
                >
                  <small class="text-muted">
                    {{ formatDate(getUserAssignment(task).updated_at) }}
                  </small>
                </span>
              </td>

              <td class="text-center align-middle">
                <!-- Active view -->
                <template v-if="!showTrashed">
                  <!-- Normal user: Only see Assign button -->
                  <div v-if="!authStore.isAdmin">
                    <button
                      class="btn btn-sm btn-info"
                      @click="viewGroupInfo(task)"
                    >
                      Group Info
                    </button>
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
        <!-- 🔹 Group Info Modal -->
        <div
          class="modal fade show"
          v-if="showGroupModal"
          tabindex="-1"
          style="
            align-items: center;
            justify-content: center;
            display: block;
            background: rgba(0, 0, 0, 0.5);
          "
        >
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Group Information</h5>
                <button
                  type="button"
                  class="btn-close"
                  @click="showGroupModal = false"
                ></button>
              </div>

              <div class="modal-body">
                <p><strong>Group Name:</strong> {{ groupInfo.name }}</p>
                <hr />
                <p><strong>Members:</strong></p>
                <ul class="list-group">
                  <li
                    v-for="(member, index) in groupInfo.members"
                    :key="index"
                    class="list-group-item"
                  >
                    {{ member.name }}
                  </li>
                </ul>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="showGroupModal = false"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
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
.modal {
  z-index: 9999;
}
.modal-content {
  border-radius: 12px;
}
.list-group-item {
  font-size: 0.95rem;
}
</style>
