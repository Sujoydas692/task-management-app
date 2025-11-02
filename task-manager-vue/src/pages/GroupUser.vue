<script setup>
import { ref, onMounted, computed  } from "vue";
import { useRoute } from "vue-router";
import { createPinia, setActivePinia } from "pinia";
import { useGroupUserStore } from "../stores/groupUserStore";
import cogoToast from "cogo-toast";

setActivePinia(createPinia());
const route = useRoute();
const groupId = route.params.id;

const groupUserStore = useGroupUserStore();

const users = ref([]);
const newUserId = ref("");
const allUsers = ref([]);
const groupName = ref("");

const fetchGroupName = async () => {
  const group = await groupUserStore.getGroupDetails(groupId);
  groupName.value = group.name;
};

const fetchGroupUsers = async () => {
  users.value = await groupUserStore.getGroupUsers(groupId);
};

const addUserToGroup = async () => {
  if (!newUserId.value) {
    cogoToast.warn("Please select a user");
    return;
  }

  const success = await groupUserStore.addUserToGroup(groupId, newUserId.value);
  if (success) {
    newUserId.value = "";
    await fetchGroupUsers();
    await fetchAllUsers();
  }
};

const isDropdownDisabled = computed(() => allUsers.value.length === 0);

const removeUser = async (userId) => {
  if (confirm("Remove this user from group?")) {
    const success = await groupUserStore.removeUserFromGroup(groupId, userId);
    if (success){
      await fetchGroupUsers();
      await fetchAllUsers();
    }
  }
};

const fetchAllUsers = async () => {
  const all = await groupUserStore.getAllUsers();

  // filter out users who already belong to any group
  const assignedUserIds = users.value.map((u) => u.id);

  allUsers.value = all.filter(
    (user) => !user.group_id && !assignedUserIds.includes(user.id)
  );
};

onMounted(async () => {
  await fetchGroupName();
  await fetchGroupUsers();
  await fetchAllUsers();
});
</script>

<template>
  <div class="content">
    <div class="container py-4">
      <div class="card p-4">
        <h4 class="mb-3">Group Users</h4>

        <div class="d-flex mb-4">
          <input
            type="text"
            :value="groupName"
            class="form-control me-2"
            style="max-width: 250px"
            readonly
          />
          <select
            v-model="newUserId"
            class="form-select me-2"
            style="max-width: 250px"
            :disabled="isDropdownDisabled"
          >
            <option value="">Select a user</option>
            <option v-for="user in allUsers" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
          <button @click="addUserToGroup" class="btn btn-primary" :disabled="isDropdownDisabled">
            Add User
          </button>
        </div>

        <table class="table table-striped">
          <thead>
            <tr>
              <th>User Name</th>
              <th>Email</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <button
                  @click="removeUser(user.id)"
                  class="btn btn-sm btn-danger"
                >
                  Remove
                </button>
              </td>
            </tr>
            <tr v-if="users.length === 0">
              <td colspan="3" class="text-center text-muted">
                No users in this group
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
<style scoped></style>
