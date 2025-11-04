<script setup>
import { ref, onMounted, watch, computed, nextTick } from "vue";
import { useRoute } from "vue-router";
import { createPinia, setActivePinia } from "pinia";
import { useTaskAssignStore } from "../stores/taskAssignStore";
import { useAuthStore } from "../stores/authStore";
import apiClient from "../services/axiosClient";
import cogoToast from "cogo-toast";
import Swal from "sweetalert2";

setActivePinia(createPinia());
const store = useTaskAssignStore();
const auth = useAuthStore();
const route = useRoute();

const isAdmin = computed(() => auth.user && auth.user.type === "Admin");

const tasks = ref([]);
const groups = ref([]);
const users = ref([]);
const assignments = ref([]);

const selectedTask = ref("");
const taskName = ref("");
const selectedGroup = ref("");
const selectedUser = ref("");
const selectedAvailableUser = ref("");

const isAssignDisabled = ref(true);

const availableUsers = ref([]);

onMounted(async () => {
  const taskId = route.params.id || route.params.taskId;
  if (!taskId) {
    cogoToast.error("Invalid Task ID!", { position: "top-right" });
    return;
  }
  selectedTask.value = taskId;
  const res = await apiClient.get(`/tasks/${taskId}`);
  taskName.value = res.data.data.title;
  groups.value = await store.getGroups();
  await loadAssignments();
  await fetchAvailableUsers();
});

window.addEventListener("storage", async (e) => {
  if (e.key === "task_status_updated") {
    await loadAssignments();
  }
});

const loadGroupUsers = async () => {
  if (selectedGroup.value) {
    users.value = await store.getGroupUsers(selectedGroup.value);
  } else {
    users.value = [];
  }
};

const loadAssignments = async () => {
  if (selectedTask.value) {
    assignments.value = await store.getAssignments(selectedTask.value);
  } else {
    console.warn("No task ID found when loading assignments!");
  }
};

const assignTask = async () => {
  if (!selectedTask.value) {
    cogoToast.warn("Task not found!", { position: "top-right" });
    return;
  }

  const chosenUser = selectedAvailableUser.value || selectedUser.value;
  const chosenGroup = selectedGroup.value;

  if (!chosenGroup && !chosenUser) {
    cogoToast.warn("Please select a group or a user!", {
      position: "top-right",
    });
    return;
  }

  if (chosenGroup && users.value.length === 0 && !chosenUser) {
    cogoToast.error("This group has no users. Cannot assign task!", {
      position: "top-right",
    });
    return;
  }

  const payload = {
    assignee_type: chosenUser ? "user" : "group",
    assignee_id: chosenUser || chosenGroup,
    status: "assigned",
  };

  const success = await store.assignTask(selectedTask.value, payload);
  if (success) {
    await loadAssignments();
    await fetchAvailableUsers();
    selectedUser.value = "";
    selectedGroup.value = "";
    selectedAvailableUser.value = "";
    users.value = [];
  }
};

const fetchAvailableUsers = async () => {
  try {
    const all = await store.getAllUsers();

    const authUserId = Number(auth.user?.id);

    const groupUsers = groups.value.flatMap((g) => g.users || []);
    const groupUserIds = groupUsers.map((u) => u.id);

    availableUsers.value = all.filter(
      (user) =>
        !user.group_id &&
        !groupUserIds.includes(user.id) &&
        user.id !== authUserId
    );
  } catch (error) {
    console.error("Failed to fetch available users:", error);
    availableUsers.value = [];
  }
};

// Delete assignment
const removeAssignment = async (assignmentId) => {
  const result = await Swal.fire({
    title: "Are you sure?",
    text: "You won't be able to revert this!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  });

  if (result.isConfirmed) {
    const success = await store.deleteAssignment(
      selectedTask.value,
      assignmentId
    );
    if (success) {
      await loadAssignments();
    }
  }
};

const changeTaskStatus = async (assignment) => {
  const response = await store.updateTaskStatusViaAssignment(
    assignment.task_id,
    assignment.id,
    assignment.status
  );

  if (response) {
    const index = assignments.value.findIndex((a) => a.id === assignment.id);
    if (index !== -1) {
      assignments.value[index].status = assignment.status;
    }
    if (!auth.isAdmin) {
      localStorage.setItem(
        "task_status_sync",
        JSON.stringify({
          taskId: assignment.task_id,
          status: assignment.status,
          updatedBy: "User",
          time: Date.now(),
        })
      );
    }
  }
};

const updateAssignButtonState = () => {
  if (selectedGroup.value && users.value.length === 0) {
    isAssignDisabled.value = true;
  } else {
    isAssignDisabled.value = !(selectedAvailableUser.value || selectedGroup.value);
  }
};

watch(selectedAvailableUser, async (newVal, oldVal) => {
  if (newVal) {
    selectedGroup.value = "";
  }
  await nextTick();
  updateAssignButtonState();
});

watch(selectedGroup, async (newVal) => {
  if (newVal) {
    selectedAvailableUser.value = "";
    selectedUser.value = "";
    await loadGroupUsers();
  } else {
    users.value = [];
  }
  await nextTick();
  updateAssignButtonState();
});
</script>

<template>
  <div class="content">
    <div class="container mt-4">
      <div class="card p-4 shadow-sm">
        <h4 class="mb-3 fw-bold">Assign Task to Group or User</h4>

        <div class="row g-3">
          <!-- Select Task -->
          <div class="col-md-4">
            <label class="form-label">Task</label>
            <input
              type="text"
              class="form-control bg-light"
              :value="taskName"
              readonly
            />
          </div>

          <!-- Select Group -->
          <div class="col-md-4">
            <label class="form-label">Select Group</label>
            <select v-model="selectedGroup" class="form-select">
              <option value="">-- Select Group --</option>
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.name }}
              </option>
            </select>
          </div>

          <!-- Select User (From Group) -->
          <div class="col-md-4" v-if="selectedGroup">
            <label class="form-label">Group Members</label>

            <div v-if="!users.length" class="text-muted small mt-1">
              No members found for this group
            </div>

            <div v-else class="d-flex flex-wrap gap-2 mt-2">
              <span
                v-for="user in users"
                :key="user.id"
                class="badge bg-primary"
              >
                {{ user.name }}
              </span>
            </div>
          </div>

          <!-- Available Users (No Group) -->
          <div class="col-md-4">
            <label class="form-label">Available User (No Group)</label>
            <select v-model="selectedAvailableUser" class="form-select">
              <option value="">-- Select Available User --</option>
              <option
                v-for="user in availableUsers"
                :key="user.id"
                :value="user.id"
              >
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
          </div>
        </div>

        <div class="mt-3 text-end">
          <button
            @click="assignTask"
            class="btn btn-primary"
            :disabled="isAssignDisabled"
          >
            Assign Task
          </button>
        </div>
      </div>

      <!-- Assignments Table -->
      <div v-if="assignments.length" class="card p-3 mt-4">
        <h5>Current Assignments</h5>
        <table class="table table-sm table-striped">
          <thead>
            <tr>
              <th>Assignee Type</th>
              <th>Assignee Name</th>
              <th>Assigned By</th>
              <th>Task Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in assignments" :key="a.id">
              <td>{{ a.assignee_type }}</td>
              <td>{{ a.assignee_name }}</td>
              <td>{{ a.assigned_by?.name }}</td>
              <td>
                <!-- Admin view -->
                <template v-if="isAdmin">
                  <select
                    v-model="a.status"
                    @change="changeTaskStatus(a)"
                    class="form-select form-select-sm"
                    :class="{
                      'border-success': a.status === 'completed',
                      'border-warning': a.status === 'hold',
                      'border-danger': a.status === 'cancelled',
                    }"
                  >
                    <option value="created">Created</option>
                    <option value="assigned">Assigned</option>
                    <option value="progress">In Progress</option>
                    <option value="hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </template>

                <!-- User view -->
                <template v-else>
                  <select
                    v-if="a.status !== 'completed'"
                    v-model="a.status"
                    @change="changeTaskStatus(a)"
                    class="form-select form-select-sm"
                  >
                    <option value="progress">In Progress</option>
                    <option value="hold">On Hold</option>
                    <option value="completed">Completed</option>
                  </select>

                  <span
                    v-else
                    class="text-green-600 font-semibold flex items-center gap-1"
                  >
                    ✅ Completed
                  </span>
                </template>
              </td>

              <td>
                <button
                  @click="removeAssignment(a.id)"
                  class="btn btn-sm btn-danger"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card {
  max-width: 950px;
  margin: auto;
}
.table td,
.table th {
  vertical-align: middle;
}
</style>
