<script setup>
import { ref, onMounted } from "vue";
import { createPinia, setActivePinia } from "pinia";
import { useGroupStore } from "../stores/groupStore";
import dayjs from "dayjs";
import weekday from "dayjs/plugin/weekday";
import localizedFormat from "dayjs/plugin/localizedFormat";
import utc from "dayjs/plugin/utc";

dayjs.extend(weekday);
dayjs.extend(localizedFormat);
dayjs.extend(utc);

setActivePinia(createPinia());
const groupStore = useGroupStore();

const groups = ref([]);
const editingGroupId = ref(null);
const editedGroup = ref({ name: "" });

const fetchGroups = async () => {
  groups.value = await groupStore.getGroups();
  localStorage.setItem("groups", JSON.stringify(groups.value));
};

// Custom format function
const formatDate = (dateString) => {
  return dayjs.utc(dateString).local().format("dddd, DD MMMM, hh:mm A");
};

// === Edit system ===
const startEditing = (group) => {
  editingGroupId.value = group.id;
  editedGroup.value = { name: group.name };
};

const cancelEditing = () => {
  editingGroupId.value = null;
  editedGroup.value = { name: "" };
};

const updateGroup = async (groupId) => {
  await groupStore.updateGroup(groupId, { name: editedGroup.value.name });
  await fetchGroups();
  cancelEditing();
};

// === Delete system ===
const deleteGroup = async (groupId) => {
  if (confirm("Are you sure you want to delete this group?")) {
    await groupStore.deleteGroup(groupId);
    await fetchGroups();
  }
};

onMounted(async () => {
  const cached = localStorage.getItem("groups");
  if (cached) {
    groups.value = JSON.parse(cached);
  }

  await fetchGroups();
});
</script>

<template>
  <div class="content">
    <div class="container">
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4>Group List</h4>
          <RouterLink
            :to="{ name: 'group-create' }"
            class="btn btn-sm btn-primary"
          >
            Add New Group
          </RouterLink>
        </div>

        <table class="table table-striped">
          <thead>
            <tr>
              <th>Name</th>
              <th>Created Time</th>
              <th>Updated Time</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="group in groups" :key="group.id">
              <td>
                <div v-if="editingGroupId === group.id">
                  <input v-model="editedGroup.name" class="form-control" />
                </div>
                <div v-else>{{ group.name }}</div>
              </td>

              <td>{{ formatDate(group.created_at) }}</td>
              <td>{{ formatDate(group.updated_at) }}</td>

              <td>
                <div v-if="editingGroupId === group.id">
                  <button
                    @click="updateGroup(group.id)"
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
                    @click="startEditing(group)"
                    class="btn btn-sm btn-warning me-2"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteGroup(group.id)"
                    class="btn btn-sm btn-danger me-2"
                  >
                    Delete
                  </button>

                  <RouterLink
                    :to="{ name: 'group-users', params: { id: group.id } }"
                    class="btn btn-sm btn-info me-2"
                  >
                   Manage Users
                  </RouterLink>
                </div>
              </td>
            </tr>

            <tr v-if="groups.length === 0">
              <td colspan="4" class="text-center text-muted">
                No groups found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.table td {
  white-space: normal;
  word-wrap: break-word;
  vertical-align: middle;
}
</style>
