<script setup>
import { ref, onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { createPinia, setActivePinia } from "pinia";
import { useTaskAssignStore } from "../stores/taskAssignStore";
import apiClient from "../services/axiosClient";
import cogoToast from "cogo-toast";
import dayjs from "dayjs";
import weekday from "dayjs/plugin/weekday";
import localizedFormat from "dayjs/plugin/localizedFormat";
import utc from "dayjs/plugin/utc";
import Swal from "sweetalert2";

dayjs.extend(weekday);
dayjs.extend(localizedFormat);
dayjs.extend(utc);

setActivePinia(createPinia());
const store = useTaskAssignStore();
const route = useRoute();

const tasks = ref([]);
const groups = ref([]);
const users = ref([]);
const assignments = ref([]);

const selectedTask = ref("");
const taskName = ref("");
const selectedGroup = ref("");
const selectedUser = ref("");
const selectedAvailableUser = ref("");

const availableUsers = ref([]);

onMounted(async () => {
  const taskId = route.params.id;
  selectedTask.value = taskId;
  const res = await apiClient.get(`/tasks/${taskId}`);
  taskName.value = res.data.data.title;
  groups.value = await store.getGroups();
  await loadAssignments();
  await fetchAvailableUsers();
});

const loadGroupUsers = async () => {
  if (selectedGroup.value) {
    users.value = await store.getGroupUsers(selectedGroup.value);
  } else {
    users.value = [];
  }
};

// Custom format function
const formatDate = (dateString) => {
  return dayjs.utc(dateString).local().format("dddd, DD MMMM, hh:mm A");
};

const loadAssignments = async () => {
  if (selectedTask.value) {
    assignments.value = await store.getAssignments(selectedTask.value);
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

    const groupUsers = groups.value.flatMap((g) => g.users || []);
    const groupUserIds = groupUsers.map((u) => u.id);

    availableUsers.value = all.filter(
      (user) => !user.group_id && !groupUserIds.includes(user.id)
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
    const success = await store.deleteAssignment(selectedTask.value, assignmentId);
    if (success) {
      await loadAssignments();
    }
  }
};

watch(selectedGroup, () => {
  selectedUser.value = "";
  users.value = [];
  loadGroupUsers();
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

          <!-- Select User -->
          <div class="col-md-4">
            <label class="form-label">Select User (From Group)</label>
            <select
              v-model="selectedUser"
              class="form-select"
              :disabled="!users.length"
            >
              <option value="">-- Entire Group --</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>

          <!-- Available Users (No Group) -->
          <div class="col-md-4">
            <label class="form-label">Available User (No Group)</label>
            <select v-model="selectedAvailableUser" class="form-select">
              <option value="">-- Select Available User --</option>
              <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
          </div>
        </div>

        <div class="mt-3 text-end">
          <button
            @click="assignTask"
            class="btn btn-primary"
            :disabled="!selectedGroup && !selectedAvailableUser"
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
              <th>Assigned Time</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in assignments" :key="a.id">
              <td>{{ a.assignee_type }}</td>
              <td>{{ a.assignee_name }}</td>
              <td>{{ a.assigned_by?.name }}</td>
              <td>
                {{ formatDate(a.assigned_at) }}
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